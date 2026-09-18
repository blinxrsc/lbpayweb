#include <WiFi.h>
#include <PubSubClient.h>
#include <ArduinoJson.h>
#include <Preferences.h> // Library for permanent storage
#include <esp_task_wdt.h> // ESP32 Watchdog library
#include <HTTPUpdate.h>
#include <BLEDevice.h>
#include <BLEServer.h>
#include <BLEUtils.h>
#include <BLE2902.h>

// =====================================================================
// v1.4 ADDITIONS: BLE WiFi provisioning.
//  - WiFi SSID/password are no longer compile-time constants. They're
//    loaded from Preferences at boot (falling back to the hardcoded
//    defaults below for a factory-fresh device), and can be rewritten at
//    any time — online or offline — by the Flutter technician app over
//    BLE, without re-flashing.
//  - The device always advertises over BLE as "LB-<serial>" so the app
//    can go straight from a QR scan (which gives it the serial) to the
//    right peripheral, without the technician manually picking from a
//    generic BLE device list.
//  - A single write characteristic accepts {"ssid":"...","password":"..."};
//    a notify characteristic reports back "connecting" / "connected" /
//    "failed:<reason>" so the app can show real progress instead of
//    guessing.
// =====================================================================

// BLE UUIDs — distinct, made up for this project. Keep these in sync
// with the Flutter app's ble_provisioning_service.dart.
#define BLE_SERVICE_UUID        "d804b643-6ce7-4e81-9e8e-1b25f0e12345"
#define BLE_WIFI_WRITE_UUID     "d804b643-6ce7-4e81-9e8e-1b25f0e12346"
#define BLE_WIFI_STATUS_UUID    "d804b643-6ce7-4e81-9e8e-1b25f0e12347"

BLECharacteristic* bleStatusCharacteristic = nullptr;
bool bleProvisioningRequested = false;
String pendingSsid, pendingPass;

// --- Configuration ---
// Fallback defaults, only used the very first time a device boots with
// nothing yet saved in Preferences (i.e. before it has ever been
// provisioned by the technician app).
const char* default_ssid = "paynwash";
const char* default_password = "p@ynw@sh9@1@";
// --EMQX Instance 2 (Use Public IP or Domain if outside AWS, Private IP if inside same VPC)
const char* mqtt_server = "56.68.70.106";
const int mqtt_port = 1883;
const char* mqtt_user = "NYJ312007A100216290"; // From EMQX Authentication
const char* mqtt_pass = "paynwash9010";
// --Device Identity (Match this with device_serial_number in your table)
const char* device_serial = "NYJ312007A100216290";

unsigned long lastMsg = 0;
volatile int pendingPulses = 0; // physical coin-acceptor pulses awaiting upload ('volatile' required for interrupt variable)
unsigned long lastInterruptTime = 0;
const int COIN_PIN = 14;
const int COIN_OUTPUT_PIN = 27; // GPIO that drives the machine's coin/pulse input

// Topics
String telemetry_topic = "machines/" + String(device_serial) + "/telemetry";
String cmd_topic       = "machines/" + String(device_serial) + "/cmd";
String status_topic    = "machines/" + String(device_serial) + "/status";

WiFiClient espClient;
PubSubClient client(espClient);
Preferences preferences;

// watchdog
esp_task_wdt_config_t wdt_config = {
    .timeout_ms = 5000,
    .idle_core_mask = (1 << 2) - 1,
    .trigger_panic = true
};

// ---------------------------------------------------------------------
// Per-device pulse/coin configuration — pushed from the backend via a
// "CONFIG" command and persisted to flash so it survives reboots.
// Defaults below match the values shown on the device config screen.
// ---------------------------------------------------------------------
struct PulseConfig {
  uint16_t pulseWidthMs      = 40;   // "Pulse Width (ms)"
  uint16_t pulseDelayMs      = 100;  // "Pulse Delay (ms)"
  bool     pulseActiveLow    = true; // output line idles HIGH, pulses LOW
  bool     coinPullUp        = true; // "Pulse Pull Down or Up" for COIN_PIN
  bool     coinIdleHigh      = true; // "Coin Signal Idle Level" = 1
  uint16_t coinSignalWidthMs = 40;   // "Coin Signal Width (ms)"
  uint16_t coinDebounceMs    = 200;  // "Coin Signal Sensitive" — debounce window;
                                      // lower = more sensitive/faster pulses accepted
};
PulseConfig cfg;

void loadConfigFromPrefs() {
  preferences.begin("pulse-cfg", true);
  cfg.pulseWidthMs      = preferences.getUShort("p_width", cfg.pulseWidthMs);
  cfg.pulseDelayMs      = preferences.getUShort("p_delay", cfg.pulseDelayMs);
  cfg.pulseActiveLow    = preferences.getBool("p_low", cfg.pulseActiveLow);
  cfg.coinPullUp        = preferences.getBool("c_pullup", cfg.coinPullUp);
  cfg.coinIdleHigh      = preferences.getBool("c_idle_hi", cfg.coinIdleHigh);
  cfg.coinSignalWidthMs = preferences.getUShort("c_width", cfg.coinSignalWidthMs);
  cfg.coinDebounceMs    = preferences.getUShort("c_debounce", cfg.coinDebounceMs);
  preferences.end();
}

void saveConfigToPrefs() {
  preferences.begin("pulse-cfg", false);
  preferences.putUShort("p_width", cfg.pulseWidthMs);
  preferences.putUShort("p_delay", cfg.pulseDelayMs);
  preferences.putBool("p_low", cfg.pulseActiveLow);
  preferences.putBool("c_pullup", cfg.coinPullUp);
  preferences.putBool("c_idle_hi", cfg.coinIdleHigh);
  preferences.putUShort("c_width", cfg.coinSignalWidthMs);
  preferences.putUShort("c_debounce", cfg.coinDebounceMs);
  preferences.end();
}

// ---------------------------------------------------------------------
// WiFi credentials — mutable at runtime, persisted so a re-provision
// over BLE survives a reboot/power cycle.
// ---------------------------------------------------------------------
String wifiSsid;
String wifiPass;

void loadWifiCredsFromPrefs() {
  preferences.begin("wifi-cfg", true);
  wifiSsid = preferences.getString("ssid", default_ssid);
  wifiPass = preferences.getString("pass", default_password);
  preferences.end();
}

void saveWifiCredsToPrefs(const String &newSsid, const String &newPass) {
  preferences.begin("wifi-cfg", false);
  preferences.putString("ssid", newSsid);
  preferences.putString("pass", newPass);
  preferences.end();
  wifiSsid = newSsid;
  wifiPass = newPass;
}

void bleNotifyStatus(const String &status) {
  if (!bleStatusCharacteristic) return;
  bleStatusCharacteristic->setValue(status.c_str());
  bleStatusCharacteristic->notify();
  Serial.println("[BLE] status -> " + status);
}

// Attempts to join the given network with a bounded wait (this blocks
// briefly, which is acceptable here — unlike the payment pulse path,
// nothing time-critical is running during a deliberate reprovisioning
// action). Saves the credentials regardless of outcome, since the
// technician typed them on purpose; a failed connect is reported back
// over BLE so they can retry with corrected details.
void applyNewWifiCredentials(const String &newSsid, const String &newPass) {
  Serial.println("[BLE] Applying new WiFi credentials for SSID: " + newSsid);
  bleNotifyStatus("connecting");

  saveWifiCredsToPrefs(newSsid, newPass);

  WiFi.disconnect(true);
  delay(100);
  WiFi.begin(wifiSsid.c_str(), wifiPass.c_str());

  unsigned long startAttemptTime = millis();
  while (WiFi.status() != WL_CONNECTED && millis() - startAttemptTime < 15000) {
    delay(200);
    esp_task_wdt_reset();
  }

  if (WiFi.status() == WL_CONNECTED) {
    Serial.println("[BLE] New WiFi credentials connected: " + WiFi.localIP().toString());
    bleNotifyStatus("connected");
  } else {
    Serial.println("[BLE] Failed to connect with new WiFi credentials.");
    bleNotifyStatus("failed:could_not_connect");
  }
}

class WifiWriteCallback: public BLECharacteristicCallbacks {
  void onWrite(BLECharacteristic *characteristic) override {
    std::string rawStd = characteristic->getValue();
    if (rawStd.length() == 0) return;
    String raw = String(rawStd.c_str());

    StaticJsonDocument<256> doc;
    if (deserializeJson(doc, raw)) {
      bleNotifyStatus("failed:bad_json");
      return;
    }

    const char* newSsid = doc["ssid"] | "";
    const char* newPass = doc["password"] | "";
    if (strlen(newSsid) == 0) {
      bleNotifyStatus("failed:missing_ssid");
      return;
    }

    // Defer the actual (blocking-ish) connect attempt to loop(), so we
    // return from this BLE callback immediately.
    pendingSsid = String(newSsid);
    pendingPass = String(newPass);
    bleProvisioningRequested = true;
  }
};

// Always-on BLE GATT server for WiFi (re)provisioning. Advertises as
// "LB-<serial>" so the Flutter app can connect directly to the device
// matching the serial it just read from the QR code, without the
// technician having to pick it out of a generic BLE scan list.
void setupBleProvisioning() {
  String bleName = "LB-" + String(device_serial);
  BLEDevice::init(bleName.c_str());

  BLEServer* server = BLEDevice::createServer();
  BLEService* service = server->createService(BLE_SERVICE_UUID);

  BLECharacteristic* wifiWriteChar = service->createCharacteristic(
    BLE_WIFI_WRITE_UUID,
    BLECharacteristic::PROPERTY_WRITE
  );
  wifiWriteChar->setCallbacks(new WifiWriteCallback());

  bleStatusCharacteristic = service->createCharacteristic(
    BLE_WIFI_STATUS_UUID,
    BLECharacteristic::PROPERTY_NOTIFY
  );
  bleStatusCharacteristic->addDescriptor(new BLE2902());

  service->start();

  BLEAdvertising* advertising = BLEDevice::getAdvertising();
  advertising->addServiceUUID(BLE_SERVICE_UUID);
  advertising->setScanResponse(true);
  BLEDevice::startAdvertising();

  Serial.println("[BLE] Advertising as " + bleName);
}

void applyPinModes() {
  pinMode(COIN_PIN, cfg.coinPullUp ? INPUT_PULLUP : INPUT_PULLDOWN);
  pinMode(COIN_OUTPUT_PIN, OUTPUT);
  // Idle state = "not active"
  digitalWrite(COIN_OUTPUT_PIN, cfg.pulseActiveLow ? HIGH : LOW);
}

// ---------------------------------------------------------------------
// Non-blocking pulse state machine
// ---------------------------------------------------------------------
enum PulsePhase { P_IDLE, P_ON, P_GAP };
PulsePhase pulsePhase = P_IDLE;
int pulsesRemaining = 0;
unsigned long pulseStepStart = 0;
String activeOpId = "";
String lastCompletedOpId = "";

void setPulseOutput(bool active) {
  bool level = cfg.pulseActiveLow ? (active ? LOW : HIGH) : (active ? HIGH : LOW);
  digitalWrite(COIN_OUTPUT_PIN, level);
}

void publishAck(const String &opId, const String &status, const String &error = "") {
  StaticJsonDocument<256> doc;
  doc["event"] = "start_ack";
  doc["op_id"] = opId;
  doc["status"] = status; // received | completed | busy | rejected | duplicate_ignored
  if (error.length()) doc["error"] = error;
  char buf[256];
  serializeJson(doc, buf);
  client.publish(status_topic.c_str(), buf);
}

// Called once from REMOTE_START handling to kick off a new pulse sequence.
void beginPulses(const String &opId, int pulses) {
  activeOpId = opId;
  pulsesRemaining = pulses;
  pulsePhase = P_ON;
  pulseStepStart = millis();
  setPulseOutput(true);
  publishAck(opId, "received");
}

// Must be called every loop() iteration. Advances the pulse sequence
// using millis() instead of delay(), so MQTT/watchdog keep servicing.
void servicePulseOutput() {
  if (pulsePhase == P_IDLE) return;

  unsigned long elapsed = millis() - pulseStepStart;

  if (pulsePhase == P_ON) {
    if (elapsed >= cfg.pulseWidthMs) {
      setPulseOutput(false);
      pulsePhase = P_GAP;
      pulseStepStart = millis();
    }
  } else if (pulsePhase == P_GAP) {
    if (elapsed >= cfg.pulseDelayMs) {
      pulsesRemaining--;
      if (pulsesRemaining > 0) {
        pulsePhase = P_ON;
        pulseStepStart = millis();
        setPulseOutput(true);
      } else {
        pulsePhase = P_IDLE;
        lastCompletedOpId = activeOpId;
        preferences.begin("op-log", false);
        preferences.putString("last_op", lastCompletedOpId);
        preferences.end();
        publishAck(activeOpId, "completed");
        Serial.println("Pulse sequence completed for op_id=" + activeOpId);
        activeOpId = "";
      }
    }
  }
}

// --- MQTT callback ---
void callback(char* topic, byte* payload, unsigned int length) {
  StaticJsonDocument<384> doc;
  DeserializationError err = deserializeJson(doc, payload, length);

  String raw;
  for (unsigned int i = 0; i < length; i++) raw += (char)payload[i];
  Serial.print("Message arrived ["); Serial.print(topic); Serial.print("] "); Serial.println(raw);

  // OTA still uses a plain "UPDATE:<url>" text payload rather than JSON.
  if (raw.startsWith("UPDATE:")) {
    if (pulsePhase != P_IDLE) {
      Serial.println("Ignoring OTA update while a start is in progress.");
      return;
    }
    String updateUrl = raw.substring(7);
    Serial.println("Starting OTA Update from: " + updateUrl);
    client.publish(status_topic.c_str(), "{\"ota_status\":\"downloading\"}");

    t_httpUpdate_return ret = httpUpdate.update(espClient, updateUrl);
    esp_task_wdt_reset();

    switch (ret) {
      case HTTP_UPDATE_FAILED: {
        String errorMsg = "{\"ota_status\":\"failed\", \"error\":\"" + httpUpdate.getLastErrorString() + "\"}";
        client.publish(status_topic.c_str(), errorMsg.c_str());
        break;
      }
      case HTTP_UPDATE_NO_UPDATES:
        Serial.println("No update needed.");
        break;
      case HTTP_UPDATE_OK:
        client.publish(status_topic.c_str(), "{\"ota_status\":\"success\"}");
        ESP.restart();
        break;
    }
    return;
  }

  if (err) {
    Serial.println("Bad JSON command payload, ignoring.");
    return;
  }

  const char* action = doc["action"] | "";

  if (strcmp(action, "REBOOT") == 0) {
    Serial.println("Remote Reset Triggered! Rebooting...");
    delay(500);
    ESP.restart();
    return;
  }

  if (strcmp(action, "CONFIG") == 0) {
    // Backend pushes machine-specific pulse/coin settings on this same
    // cmd topic, published with retain=true. A retained publish doesn't
    // affect non-retained messages already delivered on the topic, but it
    // does mean any device that (re)subscribes later immediately receives
    // the last CONFIG — including one that reconnects after being offline.
    cfg.pulseWidthMs      = doc["pulse_width_ms"]              | cfg.pulseWidthMs;
    cfg.pulseDelayMs      = doc["pulse_delay_ms"]              | cfg.pulseDelayMs;
    cfg.pulseActiveLow    = doc["pulse_active_low"]            | cfg.pulseActiveLow;
    cfg.coinPullUp        = doc["coin_pull_up"]                | cfg.coinPullUp;
    cfg.coinIdleHigh      = doc["coin_idle_high"]              | cfg.coinIdleHigh;
    cfg.coinSignalWidthMs = doc["coin_signal_width_ms"]        | cfg.coinSignalWidthMs;
    cfg.coinDebounceMs    = doc["coin_signal_sensitivity_ms"]  | cfg.coinDebounceMs;
    saveConfigToPrefs();
    applyPinModes();
    applyCoinInterrupt(); // re-attach with the correct trigger edge for the new idle level
    client.publish(status_topic.c_str(), "{\"event\":\"config_ack\",\"status\":\"applied\"}");
    return;
  }

  if (strcmp(action, "REMOTE_START") == 0) {
    String opId = doc["op_id"] | "";
    int pulses  = doc["pulses"] | 0; // Laravel now sends the final pulse count directly

    if (opId.length() == 0 || pulses <= 0) {
      publishAck(opId, "rejected", "missing_op_id_or_pulses");
      return;
    }
    if (opId == lastCompletedOpId) {
      publishAck(opId, "duplicate_ignored");
      return;
    }
    if (pulsePhase != P_IDLE) {
      publishAck(opId, "busy", "device_busy");
      return;
    }

    Serial.printf("Remote Start Triggered: op_id=%s pulses=%d\n", opId.c_str(), pulses);
    beginPulses(opId, pulses);
    return;
  }

  Serial.println("Unknown action, ignoring.");
}

void setup_wifi() {
  unsigned long startAttemptTime = millis();
  delay(10);
  Serial.print("Connecting to "); Serial.println(wifiSsid);
  WiFi.begin(wifiSsid.c_str(), wifiPass.c_str());
  while (WiFi.status() != WL_CONNECTED && millis() - startAttemptTime < 10000) {
    delay(100);
    esp_task_wdt_reset();
    Serial.print(".");
  }
  Serial.println("\nWiFi connected");
}

void reconnect() {
  while (!client.connected()) {
    Serial.print("Attempting MQTT connection...");
    String lwtMessage = "{\"status\":\"offline\"}";

    if (client.connect(device_serial, mqtt_user, mqtt_pass, telemetry_topic.c_str(), 1, true, lwtMessage.c_str())) {
      Serial.println("connected");
      client.subscribe(cmd_topic.c_str());
      sendHeartbeat();

      // If a pulse sequence was interrupted mid-run by a disconnect/reboot,
      // don't silently resume it blind — tell the backend so it can decide
      // whether to retry the start rather than assume it worked.
      if (pulsePhase != P_IDLE) {
        publishAck(activeOpId, "failed", "device_reconnected_mid_pulse");
        pulsePhase = P_IDLE;
      }
    } else {
      Serial.print("failed, rc="); Serial.print(client.state());
      delay(5000);
    }
  }
}

void sendHeartbeat() {
  StaticJsonDocument<200> doc;
  doc["status"] = "online";
  doc["signal"] = WiFi.RSSI();
  doc["coins"] = 0;
  char buffer[256];
  serializeJson(doc, buffer);
  client.publish(telemetry_topic.c_str(), buffer);
}

bool sendCoinPulse() {
  StaticJsonDocument<200> doc;
  doc["status"] = "online";
  doc["coins"] = 1;
  char buffer[256];
  serializeJson(doc, buffer);
  return client.publish(telemetry_topic.c_str(), buffer, false);
}

// Runs instantly when a physical coin pulse arrives on COIN_PIN.
void IRAM_ATTR handleCoinPulse() {
  unsigned long interruptTime = millis();
  if (interruptTime - lastInterruptTime > cfg.coinDebounceMs) { // "Coin Signal Sensitive"
    pendingPulses++;
  }
  lastInterruptTime = interruptTime;
}

// Attaches (or re-attaches, e.g. after a CONFIG change) the coin interrupt
// with the edge that actually matches the configured idle level. If the
// signal idles HIGH, "coin accepted" is a drop to LOW (FALLING). If it
// idles LOW, "coin accepted" is a rise to HIGH (RISING). Previously this
// was hardcoded to FALLING regardless of coinIdleHigh, so that setting had
// no real effect on pulse detection — only on the pin's pull mode.
void applyCoinInterrupt() {
  detachInterrupt(digitalPinToInterrupt(COIN_PIN));
  attachInterrupt(
    digitalPinToInterrupt(COIN_PIN),
    handleCoinPulse,
    cfg.coinIdleHigh ? FALLING : RISING
  );
}

void setup() {
  Serial.begin(115200);

  Serial.println("Configuring Watchdog...");
  esp_task_wdt_reconfigure(&wdt_config);
  esp_task_wdt_add(NULL);

  loadConfigFromPrefs();
  loadWifiCredsFromPrefs();
  setupBleProvisioning(); // always-on, so re-provisioning works even while online

  preferences.begin("coin-vault", false);
  pendingPulses = preferences.getInt("saved_pulses", 0);
  preferences.end();
  if (pendingPulses > 0) {
    Serial.printf("Recovered %d pulses from memory!\n", pendingPulses);
  }

  preferences.begin("op-log", true);
  lastCompletedOpId = preferences.getString("last_op", "");
  preferences.end();

  setup_wifi();
  client.setServer(mqtt_server, mqtt_port);
  client.setCallback(callback);

  applyPinModes();
  applyCoinInterrupt();

  String bootPayload = "{\"event\":\"boot\",\"status\":\"online\",\"version\":\"1.5.0\"}";
  client.publish(status_topic.c_str(), bootPayload.c_str());
}

void loop() {
  esp_task_wdt_reset();

  // Handle a WiFi reprovisioning request received over BLE. Done here
  // rather than inside the BLE write callback so the ~15s connect
  // attempt doesn't run inside BLE's own callback/stack context.
  if (bleProvisioningRequested) {
    bleProvisioningRequested = false;
    applyNewWifiCredentials(pendingSsid, pendingPass);
  }

  if (!client.connected()) {
    reconnect();
  }
  client.loop();

  servicePulseOutput(); // non-blocking — replaces the old delay() loop

  // Flash-protected upload of physical coin pulses
  if (pendingPulses > 0) {
    if (client.connected()) {
      if (sendCoinPulse()) {
        pendingPulses--;
        preferences.begin("coin-vault", false);
        preferences.putInt("saved_pulses", pendingPulses);
        preferences.end();
      }
    } else {
      static int lastSavedToFlash = -1;
      if (pendingPulses != lastSavedToFlash) {
        preferences.begin("coin-vault", false);
        preferences.putInt("saved_pulses", pendingPulses);
        preferences.end();
        lastSavedToFlash = pendingPulses;
        Serial.println("Internet down. Pulse protected in Flash.");
      }
    }
  }

  unsigned long now = millis();
  if (now - lastMsg > 60000) {
    lastMsg = now;
    sendHeartbeat();
  }
}
