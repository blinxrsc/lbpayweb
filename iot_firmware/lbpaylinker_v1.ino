#include <WiFi.h>
#include <PubSubClient.h>
#include <ArduinoJson.h>
#include <Preferences.h> // Library for permanent storage

// --- Configuration ---
const char* ssid = "paynwash";
const char* password = "p@ynw@sh9@1@";
// --EMQX Instance 2 (Use Public IP or Domain if outside AWS, Private IP if inside same VPC)
const char* mqtt_server = "56.68.70.106"; 
const int mqtt_port = 1883;
const char* mqtt_user = "NYJ312007A100216290"; // From EMQX Authentication
const char* mqtt_pass = "paynwash9010";
// --Device Identity (Match this with device_serial_number in your table)
const char* device_serial = "NYJ312007A100216290"; 

// Topics
String telemetry_topic = "machines/" + String(device_serial) + "/telemetry";
String reboot_topic = "machines/" + String(device_serial) + "/cmd";

WiFiClient espClient;
PubSubClient client(espClient);
Preferences preferences;

unsigned long lastMsg = 0;
int coinCount = 0; // Temporary storage for pulses before sending
volatile int pendingPulses = 0; // 'volatile' is required for interrupt variables
unsigned long lastInterruptTime = 0;
const int COIN_PIN = 14;

// --- 1. ADDED CALLBACK FOR REMOTE RESET ---
void callback(char* topic, byte* payload, unsigned int length) {
    String message = "";
    for (int i = 0; i < length; i++) {
        message += (char)payload[i];
    }

    Serial.print("Message arrived ["); Serial.print(topic); Serial.print("] ");
    Serial.println(message);

    // Check if the message is a REBOOT command
    if (String(topic) == reboot_topic && message == "REBOOT") {
        Serial.println("Remote Reset Triggered! Rebooting...");
        delay(1000);
        ESP.restart(); // This physically restarts the ESP32
    }
}

void setup_wifi() {
  delay(10);
  Serial.print("Connecting to "); Serial.println(ssid);
  WiFi.begin(ssid, password);
  while (WiFi.status() != WL_CONNECTED) {
    delay(500); Serial.print(".");
  }
  Serial.println("\nWiFi connected");
}

void reconnect() {
  while (!client.connected()) {
    Serial.print("Attempting MQTT connection...");
    
    // --- LAST WILL AND TESTAMENT (LWT) ---
    // If the ESP32 loses power, EMQX will publish this "offline" message automatically
    String lwtMessage = "{\"status\":\"offline\"}";
    
    if (client.connect(device_serial, mqtt_user, mqtt_pass, telemetry_topic.c_str(), 1, true, lwtMessage.c_str())) {
      Serial.println("connected");

      // --- 2. FIXED: SUBSCRIBE HERE, NOT IN SETUP ---
      client.subscribe(reboot_topic.c_str());

      // Once connected, tell Laravel we are "online"
      sendHeartbeat();
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
  doc["coins"] = 0; // Heartbeat doesn't add coins

  char buffer[256];
  serializeJson(doc, buffer);
  client.publish(telemetry_topic.c_str(), buffer);
}

// Update your send function to return a boolean
bool sendCoinPulse() {
    StaticJsonDocument<200> doc;
    doc["status"] = "online";
    doc["coins"] = 1; 

    char buffer[256];
    serializeJson(doc, buffer);
    
    // Returns true if the message was actually published
    return client.publish(telemetry_topic.c_str(), buffer, true);
}

// This function runs INSTANTLY when the coin pin is pulled LOW
void IRAM_ATTR handleCoinPulse() {
    unsigned long interruptTime = millis();
    // Debounce: Only count if pulses are more than 200ms apart
    if (interruptTime - lastInterruptTime > 200) {
        pendingPulses++; 
    }
    lastInterruptTime = interruptTime;
}

void setup() {
  Serial.begin(115200);

  // --- 1. INITIALIZE STORAGE ---
  // "coin-vault" is the namespace, false means we want to read/write
  preferences.begin("coin-vault", false);

  // Load any pulses that were saved before the last power-off
  pendingPulses = preferences.getInt("saved_pulses", 0);
  if (pendingPulses > 0) {
      Serial.printf("Recovered %d pulses from memory!\n", pendingPulses);
  }

  setup_wifi();
  client.setServer(mqtt_server, mqtt_port);

  // --- 3. ADDED: LINK THE CALLBACK ---
  client.setCallback(callback);

  // Example: Pin for Coin Acceptor
  pinMode(COIN_PIN, INPUT_PULLUP); 
  // --- CONFIGURE INTERRUPT ---
  attachInterrupt(digitalPinToInterrupt(COIN_PIN), handleCoinPulse, FALLING);
}

void loop() {
  if (!client.connected()) {
    reconnect();
  }
  client.loop();

  // --- 2. PERSISTENT STORAGE LOGIC ---
  static int lastSavedCount = 0;
  if (pendingPulses != lastSavedCount) {
      // Save to NVS whenever the count changes
      preferences.putInt("saved_pulses", pendingPulses);
      lastSavedCount = pendingPulses;
  }
  // --- CHECK FOR PENDING PULSES ---
  if (pendingPulses > 0 && client.connected()) {
      // Try to send the pulse to Laravel
      if (sendCoinPulse()) {
          pendingPulses--;
          preferences.putInt("saved_pulses", pendingPulses); // Update storage
          Serial.println("Pulse sent and cleared from memory.");
      }
  }
  
  // Send heartbeat every 60 seconds
  unsigned long now = millis();
  if (now - lastMsg > 60000) {
    lastMsg = now;
    sendHeartbeat();
  }
}
