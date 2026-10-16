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
// v1.5.2 — reliability / safety fixes on top of v1.5.1 (behaviour of the
// MQTT protocol, BLE provisioning and pin map is unchanged):
//  - MQTT reconnect is now NON-BLOCKING. Previously reconnect() looped
//    with delay(5000) inside loop(): the pulse state machine froze (relay
//    could stay energised for the whole outage) and the 5 s watchdog fired
//    on every failed attempt.
//  - An interrupted pulse sequence now always de-energises the output.
//  - "boot" event is published after MQTT actually connects (before, it
//    was published before any connection existed and silently dropped).
//  - A "completed" ack that couldn't be sent (MQTT down) is re-sent on
//    reconnect.
//  - OTA uses its own WiFiClient (before, it reused the MQTT socket),
//    doesn't auto-reboot before reporting success, feeds the watchdog
//    during download, and only accepts http:// URLs.
//  - Coin-pulse counter is protected against ISR/loop race conditions.
//  - PubSubClient buffer raised to 512 (a full CONFIG payload is close to
//    the 256-byte default limit and would be dropped silently).
//  - Remote values are range-checked (pulse count, widths, delays).
//  - CONFIG is only written to flash when something actually changed
//    (a retained CONFIG re-arrives on every reconnect -> flash wear).
//  - CONFIG no longer forces the output idle in the middle of a pulse.
// =====================================================================

// BLE UUIDs — distinct, made up for this project. Keep these in sync
// with the Flutter app's ble_provisioning_service.dart.
#define BLE_SERVICE_UUID        "d804b643-6ce7-4e81-9e8e-1b25f0e12345"
#define BLE_WIFI_WRITE_UUID     "d804b643-6ce7-4e81-9e8e-1b25f0e12346"
#define BLE_WIFI_STATUS_UUID    "d804b643-6ce7-4e81-9e8e-1b25f0e12347"

#define FW_VERSION "1.5.2"

BLECharacteristic* bleStatusCharacteristic = nullptr;
volatile bool bleProvisioningRequested = false;
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
const int COIN_PIN = 14; // matches the stock EQLink firmware (coin-drop input, pull-up, interrupt)

// Confirmed by GPIO sweep test (see Pinout Result spreadsheet): GPIO12 is
// the pin that actually drives this board's relay.
// NOTE: the stock EQLink firmware dump uses GPIO4 as its machine start
// pulse output ("LM_PULSE") and GPIO12 is an ESP32 strapping pin (MTDI).
// If GPIO4 also starts the machine on this board, GPIO4 is the safer choice
// because it is not a strapping pin. Test both before changing this.
const int COIN_OUTPUT_PIN = 12; // GPIO that drives the machine's coin/pulse input

// Safety limits for remote commands (adjust to your machines).
const int      MAX_PULSES_PER_START = 100;
const uint16_t MAX_PULSE_WIDTH_MS   = 2000;
const uint16_t MAX_PULSE_DELAY_MS   = 5000;
const uint16_t MAX_DEBOUNCE_MS      = 5000;

// Topics
String telemetry_topic = "machines/" + String(device_serial) + "/telemetry";
String cmd_topic       = "machines/" + String(device_serial) + "/cmd";
String status_topic    = "machines/" + String(device_serial) + "/status";

WiFiClient espClient;   // MQTT only
WiFiClient otaClient;   // OTA only — never share the MQTT socket
PubSubClient client(espClient);
Preferences preferences;

// watchdog (15 s: a worst-case MQTT connect attempt can take a few seconds)
esp_task_wdt_config_t wdt_config = {
    .timeout_ms = 15000,
    .idle_core_mask = (1 << 2) - 1,
    .trigger_panic = true
};

// Physical coin-acceptor pulses awaiting upload. Shared between the ISR and
// loop(), so every access outside the ISR goes through coinMux.
portMUX_TYPE coinMux = portMUX_INITIALIZER_UNLOCKED;
volatile int pendingPulses = 0;
volatile unsigned long lastInterruptTime = 0;

// ---------------------------------------------------------------------
// Per-device pulse/coin configuration — pushed from the backend via a
// "CONFIG" command and persisted to flash so it survives reboots.
// ---------------------------------------------------------------------
struct PulseConfig {
  uint16_t pulseWidthMs      = 40;   // "Pulse Width (ms)"
  uint16_t pulseDelayMs      = 100;  // "Pulse Delay (ms)"
  bool     pulseActiveLow    = true; // output line idles HIGH, pulses LOW
  bool     coinPullUp        = true; // "Pulse Pull Down or Up" for COIN_PIN
  bool     coinIdleHigh      = true; // "Coin Signal Idle Level" = 1
  uint16_t coinSignalWidthMs = 40;   // "Coin Signal Width (ms)" (stored, not used by firmware yet)
  uint16_t coinDebounceMs    = 200;  // "Coin Signal Sensitive" — debounce window;
                                      // lower = more sensitive/faster pulses accepted
};
PulseConfig cfg;

bool cfgEquals(const PulseConfig &a, const PulseConfig &b) {
  return a.pulseWidthMs == b.pulseWidthMs && a.pulseDelayMs == b.pulseDelayMs &&
         a.pulseActiveLow == b.pulseActiveLow && a.coinPullUp == b.coinPullUp &&
         a.coinIdleHigh == b.coinIdleHigh && a.coinSignalWidthMs == b.coinSignalWidthMs &&
         a.coinDebounceMs == b.coinDebounceMs;
}

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
// briefly, which is acceptable here — nothing time-critical should be
// running during a deliberate reprovisioning action). Saves the
// credentials regardless of outcome, since the technician typed them on
// purpose; a failed connect is reported back over BLE so they can retry.
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
// matching the serial it just read from the QR code.
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

// ---------------------------------------------------------------------
// Non-blocking pulse state machine
// ---------------------------------------------------------------------
enum PulsePhase { P_IDLE, P_ON, P_GAP };
PulsePhase pulsePhase = P_IDLE;
int pulsesRemaining = 0;
unsigned long pulseStepStart = 0;
String activeOpId = "";
String lastCompletedOpId = "";
String pendingCompletedAck = ""; // "completed" ack that could not be sent yet

void setPulseOutput(bool active) {
  bool level = cfg.pulseActiveLow ? (active ? LOW : HIGH) : (active ? HIGH : LOW);
  digitalWrite(COIN_OUTPUT_PIN, level);
}

void applyPinModes() {
  pinMode(COIN_PIN, cfg.coinPullUp ? INPUT_PULLUP : INPUT_PULLDOWN);
  // Preset the level BEFORE enabling the driver so the line never glitches,
  // and reflect the current phase so a CONFIG arriving mid-pulse doesn't
  // cut the pulse short.
  setPulseOutput(pulsePhase == P_ON);
  pinMode(COIN_OUTPUT_PIN, OUTPUT);
}

bool publishAck(const String &opId, const String &status, const String &error = "") {
  StaticJsonDocument<256> doc;
  doc["event"] = "start_ack";
  doc["op_id"] = opId;
  doc["status"] = status; // received | completed | busy | rejected | duplicate_ignored | failed
  if (error.length()) doc["error"] = error;
  char buf[256];
  serializeJson(doc, buf);
  return client.publish(status_topic.c_str(), buf);
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
        if (!publishAck(activeOpId, "completed")) {
          pendingCompletedAck = activeOpId; // re-sent when MQTT reconnects
        }
        Serial.println("Pulse sequence completed for op_id=" + activeOpId);
        activeOpId = "";
      }
    }
  }
}

// --- Coin pulse counter helpers (ISR-safe) ---
int getPendingPulses() {
  portENTER_CRITICAL(&coinMux);
  int n = pendingPulses;
  portEXIT_CRITICAL(&coinMux);
  return n;
}

void consumeOnePulse() {
  portENTER_CRITICAL(&coinMux);
  if (pendingPulses > 0) pendingPulses--;
  portEXIT_CRITICAL(&coinMux);
}

// Runs instantly when a physical coin pulse arrives on COIN_PIN.
void IRAM_ATTR handleCoinPulse() {
  unsigned long interruptTime = millis();
  if (interruptTime - lastInterruptTime > cfg.coinDebounceMs) { // "Coin Signal Sensitive"
    portENTER_CRITICAL_ISR(&coinMux);
    pendingPulses++;
    portEXIT_CRITICAL_ISR(&coinMux);
  }
  lastInterruptTime = interruptTime;
}

// Attaches (or re-attaches, e.g. after a CONFIG change) the coin interrupt
// with the edge that matches the configured idle level. Idle HIGH ->
// "coin accepted" is a drop to LOW (FALLING); idle LOW -> RISING.
void applyCoinInterrupt() {
  detachInterrupt(digitalPinToInterrupt(COIN_PIN));
  attachInterrupt(
    digitalPinToInterrupt(COIN_PIN),
    handleCoinPulse,
    cfg.coinIdleHigh ? FALLING : RISING
  );
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

// --- MQTT callback ---
void callback(char* topic, byte* payload, unsigned int length) {
  StaticJsonDocument<384> doc;
  DeserializationError err = deserializeJson(doc, payload, length);

  String raw;
  raw.reserve(length + 1);
  for (unsigned int i = 0; i < length; i++) raw += (char)payload[i];
  Serial.print("Message arrived ["); Serial.print(topic); Serial.print("] "); Serial.println(raw);

  // OTA still uses a plain "UPDATE:<url>" text payload rather than JSON.
  // SECURITY: anyone able to publish to this topic can flash arbitrary
  // firmware. Lock the topic down with broker ACLs and, ideally, verify a
  // signature / use HTTPS with a pinned certificate.
  if (raw.startsWith("UPDATE:")) {
    if (pulsePhase != P_IDLE) {
      Serial.println("Ignoring OTA update while a start is in progress.");
      return;
    }
    String updateUrl = raw.substring(7);
    updateUrl.trim();
    if (!updateUrl.startsWith("http://")) {
      client.publish(status_topic.c_str(), "{\"ota_status\":\"failed\",\"error\":\"only_http_urls_supported\"}");
      return;
    }
    Serial.println("Starting OTA Update from: " + updateUrl);
    client.publish(status_topic.c_str(), "{\"ota_status\":\"downloading\"}");

    httpUpdate.rebootOnUpdate(false); // report success first, then restart ourselves
    httpUpdate.onProgress([](int cur, int total) { esp_task_wdt_reset(); });
    t_httpUpdate_return ret = httpUpdate.update(otaClient, updateUrl);
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
        client.loop();
        delay(200);
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
    setPulseOutput(false); // never restart with the relay energised
    delay(500);
    ESP.restart();
    return;
  }

  if (strcmp(action, "CONFIG") == 0) {
    // Backend pushes machine-specific pulse/coin settings on this same
    // cmd topic, published with retain=true, so a device that (re)connects
    // immediately receives the last CONFIG.
    PulseConfig before = cfg;
    int v;
    v = doc["pulse_width_ms"]             | (int)cfg.pulseWidthMs;      cfg.pulseWidthMs      = constrain(v, 1, (int)MAX_PULSE_WIDTH_MS);
    v = doc["pulse_delay_ms"]             | (int)cfg.pulseDelayMs;      cfg.pulseDelayMs      = constrain(v, 1, (int)MAX_PULSE_DELAY_MS);
    cfg.pulseActiveLow                    = doc["pulse_active_low"]     | cfg.pulseActiveLow;
    cfg.coinPullUp                        = doc["coin_pull_up"]         | cfg.coinPullUp;
    cfg.coinIdleHigh                      = doc["coin_idle_high"]       | cfg.coinIdleHigh;
    v = doc["coin_signal_width_ms"]       | (int)cfg.coinSignalWidthMs; cfg.coinSignalWidthMs = constrain(v, 0, 5000);
    v = doc["coin_signal_sensitivity_ms"] | (int)cfg.coinDebounceMs;     cfg.coinDebounceMs    = constrain(v, 0, (int)MAX_DEBOUNCE_MS);

    if (!cfgEquals(before, cfg)) {
      saveConfigToPrefs();
      applyPinModes();
      applyCoinInterrupt(); // re-attach with the correct trigger edge for the new idle level
    }
    client.publish(status_topic.c_str(), "{\"event\":\"config_ack\",\"status\":\"applied\"}");
    return;
  }

  if (strcmp(action, "REMOTE_START") == 0) {
    String opId = doc["op_id"] | "";
    int pulses  = doc["pulses"] | 0; // backend sends the final pulse count directly

    if (opId.length() == 0 || pulses <= 0) {
      publishAck(opId, "rejected", "missing_op_id_or_pulses");
      return;
    }
    if (pulses > MAX_PULSES_PER_START) {
      publishAck(opId, "rejected", "too_many_pulses");
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
  WiFi.persistent(false);      // don't rewrite WiFi creds to flash on every begin()
  WiFi.setAutoReconnect(true);
  unsigned long startAttemptTime = millis();
  delay(10);
  Serial.print("Connecting to "); Serial.println(wifiSsid);
  WiFi.begin(wifiSsid.c_str(), wifiPass.c_str());
  while (WiFi.status() != WL_CONNECTED && millis() - startAttemptTime < 10000) {
    delay(100);
    esp_task_wdt_reset();
    Serial.print(".");
  }
  if (WiFi.status() == WL_CONNECTED) Serial.println("\nWiFi connected");
  else Serial.println("\nWiFi not connected yet — will keep retrying in the background");
}

// ---------------------------------------------------------------------
// MQTT — single, non-blocking connect attempt. Called from loop() at most
// once every 5 s, and never while a pulse sequence is running (a blocking
// connect would stretch the pulse).
// ---------------------------------------------------------------------
unsigned long lastMqttAttempt = 0;
bool bootAnnounced = false;

void tryMqttConnect() {
  if (WiFi.status() != WL_CONNECTED) {
    static unsigned long lastWifiKick = 0;
    if (millis() - lastWifiKick > 10000) {
      lastWifiKick = millis();
      Serial.println("WiFi down, reconnecting...");
      WiFi.reconnect();
    }
    return;
  }

  Serial.print("Attempting MQTT connection...");
  String lwtMessage = "{\"status\":\"offline\"}";

  if (client.connect(device_serial, mqtt_user, mqtt_pass, telemetry_topic.c_str(), 1, true, lwtMessage.c_str())) {
    Serial.println("connected");
    client.subscribe(cmd_topic.c_str());
    sendHeartbeat();

    if (!bootAnnounced) {
      String bootPayload = "{\"event\":\"boot\",\"status\":\"online\",\"version\":\"" FW_VERSION "\"}";
      if (client.publish(status_topic.c_str(), bootPayload.c_str())) bootAnnounced = true;
    }

    // A sequence that finished while offline: tell the backend now.
    if (pendingCompletedAck.length() && publishAck(pendingCompletedAck, "completed")) {
      pendingCompletedAck = "";
    }
  } else {
    Serial.print("failed, rc="); Serial.println(client.state());
  }
}

void setup() {
  Serial.begin(115200);

  Serial.println("Configuring Watchdog...");
  esp_task_wdt_reconfigure(&wdt_config);
  esp_task_wdt_add(NULL);

  loadConfigFromPrefs();
  loadWifiCredsFromPrefs();

  // Bring the output to its idle level as early as possible.
  applyPinModes();

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
  client.setBufferSize(512);   // default 256 is too small for a full CONFIG payload
  client.setSocketTimeout(3);  // keep any blocking connect/read short (default 15 s)

  applyCoinInterrupt();
}

void loop() {
  esp_task_wdt_reset();

  // Handle a WiFi reprovisioning request received over BLE. Done here
  // rather than inside the BLE write callback so the ~15s connect
  // attempt doesn't run inside BLE's own callback/stack context.
  if (bleProvisioningRequested && pulsePhase == P_IDLE) {
    bleProvisioningRequested = false;
    applyNewWifiCredentials(pendingSsid, pendingPass);
  }

  if (client.connected()) {
    client.loop();
  } else if (pulsePhase == P_IDLE && millis() - lastMqttAttempt > 5000) {
    lastMqttAttempt = millis();
    tryMqttConnect();
  }

  servicePulseOutput(); // non-blocking

  // Flash-protected upload of physical coin pulses
  int pending = getPendingPulses();
  if (pending > 0) {
    if (client.connected()) {
      if (sendCoinPulse()) {
        consumeOnePulse();
        preferences.begin("coin-vault", false);
        preferences.putInt("saved_pulses", getPendingPulses());
        preferences.end();
      }
    } else {
      static int lastSavedToFlash = -1;
      if (pending != lastSavedToFlash) {
        preferences.begin("coin-vault", false);
        preferences.putInt("saved_pulses", pending);
        preferences.end();
        lastSavedToFlash = pending;
        Serial.println("Internet down. Pulse protected in Flash.");
      }
    }
  }

  unsigned long now = millis();
  if (client.connected() && now - lastMsg > 60000) {
    lastMsg = now;
    sendHeartbeat();
  }
}
