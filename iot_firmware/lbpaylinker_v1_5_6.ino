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
// v1.5.6 — fleet-scale behaviour (review items F6/F7/F8, built on v1.5.5):
//   * ONE BINARY FOR THE WHOLE FLEET. Serial number and MQTT login are read
//     from flash (NVS namespace "ident") instead of being compiled in.
//       - Devices upgrading from <= v1.5.5: on first boot the compiled-in
//         values are copied to NVS once ("adopted"). So the upgrade build
//         for an existing device must still carry that device's serial
//         (see build_fleet.sh). Every later OTA can use one common binary.
//       - New devices: flash the common binary, then send over USB serial:
//           IDENT {"serial":"NYJ...","mqtt_user":"NYJ...","mqtt_pass":"...","mqtt_host":"..."}
//         The device saves it and reboots. "IDENT?" prints the identity
//         (without the password).
//     The compiled defaults can be overridden at build time with
//     -DLB_DEVICE_SERIAL=... -DLB_MQTT_PASS=... etc.
//   * MQTT reconnect uses exponential backoff with jitter (2 s -> 60 s,
//     +-30%) instead of a fixed 5 s. After a broker outage, 8,000 devices
//     no longer all reconnect in the same second.
//   * Presence topic machines/{sn}/presence: retained {"status":"online"}
//     on connect, retained LWT {"status":"offline"}. The backend always
//     knows the current state of every device, even after a restart. The
//     old retained LWT on the telemetry topic is deleted on connect.
//   * Heartbeat interval comes from CONFIG ("heartbeat_s", 30-600 s,
//     default 60) so it can be stretched for 8,000 devices without a
//     firmware change.
//   * Commands are subscribed with QoS 1.
//   * Remembers the last 8 completed op_ids (was only the last one), and a
//     repeat of the op currently pulsing is answered "received" instead of
//     "busy", so the backend's automatic re-send can never double-credit.
//
// v1.5.5 — REMOTE_START pulse-count fix:
//   * BUG: MAX_PULSES_PER_START was hard-coded to 100. With pulse_price
//     RM0.01 (RM1.00 = 100 pulses) any start above RM1.00 (e.g. RM2.00 =
//     200 pulses) was REJECTED with "too_many_pulses" — the machine got
//     nothing for it, while the backend had already taken the payment.
//     The limit is now a config value (cfg.maxPulses, default 1000,
//     hard ceiling 2000) that the backend pushes in CONFIG as
//     "max_pulses", so it always matches the backend's own limit.
//   * REMOTE_START now uses the "width" / "delay" values sent in the
//     command itself (range-checked), falling back to the CONFIG values.
//     Before, the firmware ignored them, so a device that never received
//     a CONFIG pulsed with the 40/100 ms defaults regardless of the
//     admin settings.
//   * Acks now carry numbers: "received" includes the requested pulse
//     count, "completed" includes how many pulses were actually sent, and
//     "rejected: too_many_pulses" includes the device's limit. This makes
//     "machine got fewer credits than expected" diagnosable from the
//     backend/app without a serial cable.
//
// v1.5.4 — BLE improvements for the admin app's "Config Network" flow:
//   * New BLE characteristic (BLE_WIFI_SCAN_UUID): the app writes to it to
//     make the DEVICE scan for WiFi networks, then reads back a compact
//     JSON list. This matters because the ESP32 is 2.4 GHz only — a phone
//     lists 5 GHz networks the device can never join, and its signal
//     strength readings are for the wrong location anyway. Scan is async
//     (non-blocking) so MQTT/pulse handling and the watchdog keep running.
//   * BLEDevice::setMTU(517) was never called, so the server negotiated
//     the default 23-byte MTU (20-byte payloads) — the WiFi credentials
//     JSON (up to ~120 bytes) could not be written in a single packet.
//
// v1.5.3 — pin correction, based on disassembling the original EQLink
// firmware image (ESP-IDF, not Arduino). COIN_OUTPUT_PIN changed from
// GPIO12 to GPIO4 ("LM_PULSE" in the stock firmware — the actual machine
// start-pulse output). GPIO12 was our own earlier empirical finding (a
// relay audibly clicked when driven); it was very likely the wrong relay,
// not connected to the coin-credit line at all — which would fully
// explain "REMOTE_START runs, but no deduction on the machine."
//
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
#define BLE_WIFI_SCAN_UUID      "d804b643-6ce7-4e81-9e8e-1b25f0e12348"

#define FW_VERSION "1.5.6"

BLECharacteristic* bleStatusCharacteristic = nullptr;
volatile bool bleProvisioningRequested = false;
BLECharacteristic* bleScanCharacteristic = nullptr;
volatile bool bleScanRequested = false;   // set by the BLE write callback
bool wifiScanRunning = false;             // async scan in flight
unsigned long wifiScanStartedAt = 0;
String pendingSsid, pendingPass;

// --- Configuration ---
// Fallback defaults, only used the very first time a device boots with
// nothing yet saved in Preferences (i.e. before it has ever been
// provisioned by the technician app).
const char* default_ssid = "paynwash";
const char* default_password = "p@ynw@sh9@1@";
// --Device identity / MQTT login. Compiled values are only DEFAULTS used
// the first time (then stored in NVS "ident"); override per build with
// -DLB_DEVICE_SERIAL="..." etc. (see build_fleet.sh).
#ifndef LB_DEVICE_SERIAL
#define LB_DEVICE_SERIAL "NYJ312007A100216290"
#endif
#ifndef LB_MQTT_HOST
#define LB_MQTT_HOST "56.68.70.106"   // EMQX (public IP/domain outside AWS)
#endif
#ifndef LB_MQTT_PORT
#define LB_MQTT_PORT 1883
#endif
#ifndef LB_MQTT_USER
#define LB_MQTT_USER LB_DEVICE_SERIAL  // EMQX username = serial
#endif
#ifndef LB_MQTT_PASS
#define LB_MQTT_PASS "paynwash9010"
#endif

String device_serial;
String mqtt_server;
int    mqtt_port = LB_MQTT_PORT;
String mqtt_user;
String mqtt_pass;

unsigned long lastMsg = 0;
const int COIN_PIN = 14; // matches the stock EQLink firmware (coin-drop input, pull-up, interrupt)

// Confirmed by disassembling the original EQLink firmware image (ESP-IDF):
// GPIO4 is "LM_PULSE", the machine start-pulse output, agreed on by four
// independent code paths (pulse-output init, lm_hal_pulse_init idle-level
// drive, coin-drop relay setup, and the LM_PULSE debug string). GPIO14 as
// the coin-drop input was independently cross-validated by our own GPIO
// sweep test on this exact board, which strongly supports trusting GPIO4
// too. GPIO12 (our earlier empirical finding — a relay did click when
// driven) is kept only as a comment: it's very likely we were driving an
// unrelated relay on the board, not the actual coin-credit line, and it
// carries strapping-pin risk that GPIO4 doesn't.
const int COIN_OUTPUT_PIN = 4; // GPIO that drives the machine's coin/pulse input (was 12)

// Safety limits for remote commands (adjust to your machines).
// MAX_PULSES_HARD_LIMIT is an absolute ceiling compiled into the firmware;
// the per-device limit actually enforced is cfg.maxPulses (pushed by the
// backend in CONFIG as "max_pulses"). Was a fixed 100, which silently
// rejected every start above 100 pulses (e.g. RM2.00 at RM0.01/pulse).
const int      MAX_PULSES_HARD_LIMIT = 2000;
const uint16_t MAX_PULSE_WIDTH_MS   = 2000;
const uint16_t MAX_PULSE_DELAY_MS   = 5000;
const uint16_t MAX_DEBOUNCE_MS      = 5000;

// Topics
// Built in buildTopics() once the identity is loaded.
String telemetry_topic;
String cmd_topic;
String status_topic;
String presence_topic;

void buildTopics() {
  String base = "machines/" + device_serial;
  telemetry_topic = base + "/telemetry";
  cmd_topic       = base + "/cmd";
  status_topic    = base + "/status";
  presence_topic  = base + "/presence";
}

// ---------------------------------------------------------------------
// Identity in NVS ("ident"). First boot after upgrading from <= v1.5.5:
// adopt the compiled-in values once, so the device keeps its serial.
// ---------------------------------------------------------------------
void loadIdentity() {
  Preferences id;
  id.begin("ident", false);
  if (!id.isKey("serial")) {
    id.putString("serial", LB_DEVICE_SERIAL);
    id.putString("mqtt_host", LB_MQTT_HOST);
    id.putInt("mqtt_port", LB_MQTT_PORT);
    id.putString("mqtt_user", LB_MQTT_USER);
    id.putString("mqtt_pass", LB_MQTT_PASS);
    Serial.println("[IDENT] Adopted compiled-in identity into NVS");
  }
  device_serial = id.getString("serial", LB_DEVICE_SERIAL);
  mqtt_server   = id.getString("mqtt_host", LB_MQTT_HOST);
  mqtt_port     = id.getInt("mqtt_port", LB_MQTT_PORT);
  mqtt_user     = id.getString("mqtt_user", device_serial);
  mqtt_pass     = id.getString("mqtt_pass", LB_MQTT_PASS);
  id.end();
  Serial.printf("[IDENT] serial=%s host=%s:%d user=%s\n",
                device_serial.c_str(), mqtt_server.c_str(), mqtt_port, mqtt_user.c_str());
}

// USB-serial provisioning (factory / bench only — needs physical access):
//   IDENT {"serial":"...","mqtt_user":"...","mqtt_pass":"...","mqtt_host":"...","mqtt_port":1883}
//   IDENT?   -> prints the current identity (no password)
void serviceSerialCommands() {
  static String line;
  while (Serial.available()) {
    char c = (char)Serial.read();
    if (c == '\r') continue;
    if (c != '\n') { if (line.length() < 400) line += c; continue; }

    String cmd = line; line = "";
    cmd.trim();
    if (cmd == "IDENT?") {
      Serial.printf("[IDENT] serial=%s host=%s:%d user=%s fw=%s\n", device_serial.c_str(),
                    mqtt_server.c_str(), mqtt_port, mqtt_user.c_str(), FW_VERSION);
    } else if (cmd.startsWith("IDENT ")) {
      StaticJsonDocument<384> doc;
      if (deserializeJson(doc, cmd.substring(6))) { Serial.println("[IDENT] bad JSON"); continue; }
      const char* sn = doc["serial"] | "";
      if (strlen(sn) < 4 || strlen(sn) > 40) { Serial.println("[IDENT] serial missing/invalid"); continue; }
      Preferences id;
      id.begin("ident", false);
      id.putString("serial", sn);
      id.putString("mqtt_user", doc["mqtt_user"] | sn);
      if (doc.containsKey("mqtt_pass")) id.putString("mqtt_pass", (const char*)(doc["mqtt_pass"] | ""));
      if (doc.containsKey("mqtt_host")) id.putString("mqtt_host", (const char*)(doc["mqtt_host"] | LB_MQTT_HOST));
      if (doc.containsKey("mqtt_port")) id.putInt("mqtt_port", (int)(doc["mqtt_port"] | LB_MQTT_PORT));
      id.end();
      Serial.println("[IDENT] saved, rebooting");
      delay(300);
      ESP.restart();
    }
  }
}

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
  uint16_t maxPulses         = 1000; // max pulses accepted in ONE REMOTE_START
  uint16_t heartbeatS        = 60;   // heartbeat interval (CONFIG "heartbeat_s")
};
PulseConfig cfg;

bool cfgEquals(const PulseConfig &a, const PulseConfig &b) {
  return a.pulseWidthMs == b.pulseWidthMs && a.pulseDelayMs == b.pulseDelayMs &&
         a.pulseActiveLow == b.pulseActiveLow && a.coinPullUp == b.coinPullUp &&
         a.coinIdleHigh == b.coinIdleHigh && a.coinSignalWidthMs == b.coinSignalWidthMs &&
         a.coinDebounceMs == b.coinDebounceMs && a.maxPulses == b.maxPulses &&
         a.heartbeatS == b.heartbeatS;
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
  cfg.maxPulses         = preferences.getUShort("max_pulses", cfg.maxPulses);
  cfg.heartbeatS        = preferences.getUShort("hb_s", cfg.heartbeatS);
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
  preferences.putUShort("max_pulses", cfg.maxPulses);
  preferences.putUShort("hb_s", cfg.heartbeatS);
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

// The app writes any byte to the scan characteristic to request a scan.
class WifiScanCallback: public BLECharacteristicCallbacks {
  void onWrite(BLECharacteristic *characteristic) override {
    bleScanRequested = true; // actual scan is started from loop()
  }
};

// Builds a compact JSON list of the strongest unique SSIDs the device can
// see: [{"s":"name","r":-55,"e":1}, ...]  (e = 1 if the network is secured).
// Kept under ~480 bytes so it fits in one BLE read at MTU 517.
void publishWifiScanResults(int n) {
  struct Net { String ssid; int rssi; bool secure; };
  Net nets[16];
  int count = 0;

  for (int i = 0; i < n; i++) {
    String s = WiFi.SSID(i);
    if (s.length() == 0) continue; // hidden network
    int rssi = WiFi.RSSI(i);
    bool secure = WiFi.encryptionType(i) != WIFI_AUTH_OPEN;

    int existing = -1;
    for (int j = 0; j < count; j++) {
      if (nets[j].ssid == s) { existing = j; break; }
    }
    if (existing >= 0) {
      if (rssi > nets[existing].rssi) { nets[existing].rssi = rssi; nets[existing].secure = secure; }
      continue;
    }
    if (count < 16) nets[count++] = Net{s, rssi, secure};
  }

  // strongest first (insertion sort — at most 16 items)
  for (int i = 1; i < count; i++) {
    Net key = nets[i];
    int j = i - 1;
    while (j >= 0 && nets[j].rssi < key.rssi) { nets[j + 1] = nets[j]; j--; }
    nets[j + 1] = key;
  }

  StaticJsonDocument<1024> doc;
  JsonArray arr = doc.to<JsonArray>();
  for (int i = 0; i < count; i++) {
    JsonObject o = arr.createNestedObject();
    o["s"] = nets[i].ssid;
    o["r"] = nets[i].rssi;
    o["e"] = nets[i].secure ? 1 : 0;
    if (measureJson(doc) > 480) { arr.remove(arr.size() - 1); break; }
  }

  String out;
  serializeJson(doc, out);
  if (bleScanCharacteristic) bleScanCharacteristic->setValue(std::string(out.c_str()));
  WiFi.scanDelete();
  Serial.println("[BLE] WiFi scan done: " + String(arr.size()) + " networks");
  bleNotifyStatus("scan_done");
}

// Always-on BLE GATT server for WiFi (re)provisioning. Advertises as
// "LB-<serial>" so the Flutter app can connect directly to the device
// matching the serial it just read from the QR code.
void setupBleProvisioning() {
  String bleName = "LB-" + device_serial;
  BLEDevice::init(bleName.c_str());
  BLEDevice::setMTU(517); // must follow init(); default 23 bytes can't carry the credentials JSON

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

  bleScanCharacteristic = service->createCharacteristic(
    BLE_WIFI_SCAN_UUID,
    BLECharacteristic::PROPERTY_WRITE | BLECharacteristic::PROPERTY_READ
  );
  bleScanCharacteristic->setCallbacks(new WifiScanCallback());
  bleScanCharacteristic->setValue(std::string("[]"));

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
int pulsesRequested = 0;   // for the "completed" ack
int pulsesSent = 0;        // pulses actually driven so far in this op
uint16_t opWidthMs = 0;    // timing for the op in progress (from the
uint16_t opDelayMs = 0;    // command, else from CONFIG)
unsigned long pulseStepStart = 0;
String activeOpId = "";
String lastCompletedOpId = "";

// Last 8 completed op_ids (RAM + NVS), so a re-sent start that already ran
// is answered "duplicate_ignored" even if other starts happened since.
const int OP_RING = 8;
String recentOps[OP_RING];
int recentOpsNext = 0;

bool isRecentOp(const String &op) {
  for (int i = 0; i < OP_RING; i++) if (recentOps[i].length() && recentOps[i] == op) return true;
  return false;
}

void rememberOp(const String &op) {
  recentOps[recentOpsNext] = op;
  recentOpsNext = (recentOpsNext + 1) % OP_RING;
  String joined;
  for (int i = 0; i < OP_RING; i++) { if (i) joined += ","; joined += recentOps[i]; }
  preferences.begin("op-log", false);
  preferences.putString("ops", joined);
  preferences.putInt("ops_next", recentOpsNext);
  preferences.end();
}

void loadRecentOps() {
  preferences.begin("op-log", true);
  String joined = preferences.getString("ops", "");
  recentOpsNext = preferences.getInt("ops_next", 0) % OP_RING;
  String legacy = preferences.getString("last_op", "");
  preferences.end();
  int idx = 0, start = 0;
  while (idx < OP_RING && start <= (int)joined.length()) {
    int comma = joined.indexOf(',', start);
    if (comma < 0) comma = joined.length();
    recentOps[idx++] = joined.substring(start, comma);
    start = comma + 1;
  }
  if (legacy.length() && !isRecentOp(legacy)) rememberOp(legacy); // from <= v1.5.5
}
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

bool publishAck(const String &opId, const String &status, const String &error = "", int pulses = -1, int sent = -1) {
  StaticJsonDocument<256> doc;
  doc["event"] = "start_ack";
  doc["op_id"] = opId;
  doc["status"] = status; // received | completed | busy | rejected | duplicate_ignored | failed
  if (error.length()) doc["error"] = error;
  if (pulses >= 0) doc["pulses"] = pulses; // requested
  if (sent >= 0)   doc["sent"] = sent;     // actually driven
  char buf[256];
  serializeJson(doc, buf);
  return client.publish(status_topic.c_str(), buf);
}

// Called once from REMOTE_START handling to kick off a new pulse sequence.
void beginPulses(const String &opId, int pulses, uint16_t widthMs, uint16_t delayMs) {
  activeOpId = opId;
  pulsesRemaining = pulses;
  pulsesRequested = pulses;
  pulsesSent = 0;
  opWidthMs = widthMs;
  opDelayMs = delayMs;
  pulsePhase = P_ON;
  pulseStepStart = millis();
  setPulseOutput(true);
  publishAck(opId, "received", "", pulses);
}

// Must be called every loop() iteration. Advances the pulse sequence
// using millis() instead of delay(), so MQTT/watchdog keep servicing.
void servicePulseOutput() {
  if (pulsePhase == P_IDLE) return;

  unsigned long elapsed = millis() - pulseStepStart;

  if (pulsePhase == P_ON) {
    if (elapsed >= opWidthMs) {
      setPulseOutput(false);
      pulsesSent++;
      pulsePhase = P_GAP;
      pulseStepStart = millis();
    }
  } else if (pulsePhase == P_GAP) {
    if (elapsed >= opDelayMs) {
      pulsesRemaining--;
      if (pulsesRemaining > 0) {
        pulsePhase = P_ON;
        pulseStepStart = millis();
        setPulseOutput(true);
      } else {
        pulsePhase = P_IDLE;
        lastCompletedOpId = activeOpId;
        rememberOp(activeOpId);
        if (!publishAck(activeOpId, "completed", "", pulsesRequested, pulsesSent)) {
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
    v = doc["max_pulses"]                 | (int)cfg.maxPulses;         cfg.maxPulses         = constrain(v, 1, MAX_PULSES_HARD_LIMIT);
    v = doc["heartbeat_s"]                | (int)cfg.heartbeatS;        cfg.heartbeatS        = constrain(v, 30, 600);

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
    if (pulses > (int)cfg.maxPulses) {
      publishAck(opId, "rejected", "too_many_pulses:max=" + String(cfg.maxPulses), pulses);
      return;
    }
    if (opId == lastCompletedOpId || isRecentOp(opId)) {
      publishAck(opId, "duplicate_ignored");
      return;
    }
    if (pulsePhase != P_IDLE && opId == activeOpId) {
      // A re-send of the op that is pulsing right now: it IS being done.
      publishAck(opId, "received", "", pulsesRequested);
      return;
    }
    if (pulsePhase != P_IDLE) {
      publishAck(opId, "busy", "device_busy");
      return;
    }

    // Timing: use the values sent with this command (the backend reads
    // them from the device's own settings); fall back to the last CONFIG.
    int w = doc["width"] | (int)cfg.pulseWidthMs;
    int d = doc["delay"] | (int)cfg.pulseDelayMs;
    uint16_t widthMs = constrain(w, 1, (int)MAX_PULSE_WIDTH_MS);
    uint16_t delayMs = constrain(d, 1, (int)MAX_PULSE_DELAY_MS);

    Serial.printf("Remote Start Triggered: op_id=%s pulses=%d width=%u delay=%u (max=%u)\n",
                  opId.c_str(), pulses, widthMs, delayMs, cfg.maxPulses);
    beginPulses(opId, pulses, widthMs, delayMs);
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
unsigned long mqttRetryDelay = 0;          // 0 = try immediately
const unsigned long MQTT_BACKOFF_MIN = 2000;
const unsigned long MQTT_BACKOFF_MAX = 60000;
bool bootAnnounced = false;

// Exponential backoff with +-30% jitter, so a fleet that lost the broker
// at the same moment does not reconnect at the same moment.
void scheduleMqttRetry() {
  unsigned long base = mqttRetryDelay == 0 ? MQTT_BACKOFF_MIN : min(mqttRetryDelay * 2, MQTT_BACKOFF_MAX);
  long jitter = (long)(base * 3 / 10);
  long offset = (long)(esp_random() % (uint32_t)(2 * jitter + 1)) - jitter;
  mqttRetryDelay = (unsigned long)max(1000L, (long)base + offset);
  Serial.printf("MQTT retry in %lu ms\n", mqttRetryDelay);
}

void tryMqttConnect() {
  if (WiFi.status() != WL_CONNECTED) {
    static unsigned long lastWifiKick = 0;
    if (millis() - lastWifiKick > 10000) {
      lastWifiKick = millis();
      Serial.println("WiFi down, reconnecting...");
      WiFi.reconnect();
    }
    mqttRetryDelay = MQTT_BACKOFF_MIN; // try MQTT again soon after WiFi returns
    return;
  }

  Serial.print("Attempting MQTT connection...");
  // LWT on the presence topic (retained): the broker publishes it if this
  // device disappears, and it stays as the device's current state.
  const char* lwtMessage = "{\"status\":\"offline\"}";

  if (client.connect(device_serial.c_str(), mqtt_user.c_str(), mqtt_pass.c_str(),
                     presence_topic.c_str(), 1, true, lwtMessage)) {
    Serial.println("connected");
    mqttRetryDelay = 0;
    client.subscribe(cmd_topic.c_str(), 1);

    String online = "{\"status\":\"online\",\"version\":\"" FW_VERSION "\"}";
    client.publish(presence_topic.c_str(), online.c_str(), true);
    // Delete the stale retained LWT that <= v1.5.5 left on the telemetry topic.
    client.publish(telemetry_topic.c_str(), "", true);

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
    scheduleMqttRetry();
  }
}

void setup() {
  Serial.begin(115200);

  Serial.println("Configuring Watchdog...");
  esp_task_wdt_reconfigure(&wdt_config);
  esp_task_wdt_add(NULL);

  loadIdentity();      // must come before BLE (name) and MQTT (topics/login)
  buildTopics();
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
  loadRecentOps();

  setup_wifi();
  client.setServer(mqtt_server.c_str(), mqtt_port); // String is global: pointer stays valid
  client.setCallback(callback);
  client.setBufferSize(512);   // default 256 is too small for a full CONFIG payload
  client.setSocketTimeout(3);  // keep any blocking connect/read short (default 15 s)

  applyCoinInterrupt();
}

// Called every loop(). Starts an async scan when requested and collects the
// result later, so nothing here blocks the main loop or starves the watchdog.
void serviceWifiScan() {
  if (bleScanRequested && !wifiScanRunning && pulsePhase == P_IDLE) {
    bleScanRequested = false;
    WiFi.scanDelete();
    if (WiFi.scanNetworks(true, false) == WIFI_SCAN_FAILED) {
      bleNotifyStatus("scan_failed:start");
    } else {
      wifiScanRunning = true;
      wifiScanStartedAt = millis();
      Serial.println("[BLE] WiFi scan started");
    }
    return;
  }

  if (!wifiScanRunning) return;

  int n = WiFi.scanComplete();
  if (n == WIFI_SCAN_RUNNING) {
    if (millis() - wifiScanStartedAt > 12000) {
      WiFi.scanDelete();
      wifiScanRunning = false;
      bleNotifyStatus("scan_failed:timeout");
    }
    return;
  }

  wifiScanRunning = false;
  if (n < 0) {
    bleNotifyStatus("scan_failed");
  } else {
    publishWifiScanResults(n);
  }
}

void loop() {
  esp_task_wdt_reset();
  serviceSerialCommands(); // USB provisioning: IDENT {...} / IDENT?

  // Handle a WiFi reprovisioning request received over BLE. Done here
  // rather than inside the BLE write callback so the ~15s connect
  // attempt doesn't run inside BLE's own callback/stack context.
  if (bleProvisioningRequested && pulsePhase == P_IDLE) {
    bleProvisioningRequested = false;
    applyNewWifiCredentials(pendingSsid, pendingPass);
  }

  serviceWifiScan(); // async WiFi scan requested over BLE (admin app)

  if (client.connected()) {
    client.loop();
  } else if (pulsePhase == P_IDLE && millis() - lastMqttAttempt >= mqttRetryDelay) {
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
  if (client.connected() && now - lastMsg > (unsigned long)cfg.heartbeatS * 1000UL) {
    lastMsg = now;
    sendHeartbeat();
  }
}
