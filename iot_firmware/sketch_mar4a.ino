#include <WiFi.h>
#include <PubSubClient.h>
#include <ArduinoJson.h>
#include <Preferences.h> // Library for permanent storage
#include <esp_task_wdt.h> // ESP32 Watchdog library
#include <HTTPUpdate.h>

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

unsigned long lastMsg = 0;
int coinCount = 0; // Temporary storage for pulses before sending
volatile int pendingPulses = 0; // 'volatile' is required for interrupt variables
unsigned long lastInterruptTime = 0;
const int COIN_PIN = 14; 
const int COIN_OUTPUT_PIN = 27; // GPIO to simulate coin pulses to machine

// Topics
String telemetry_topic = "machines/" + String(device_serial) + "/telemetry";
String reboot_topic = "machines/" + String(device_serial) + "/cmd";
String status_topic = "machines/" + String(device_serial) + "/status";

WiFiClient espClient;
PubSubClient client(espClient);
Preferences preferences;

// watchdog, Use a configuration structure instead of just an int
esp_task_wdt_config_t wdt_config = {
    .timeout_ms = 5000,               // 5 seconds (must be in milliseconds now)
    .idle_core_mask = (1 << 2) - 1, // Watch all cores
    .trigger_panic = true             // Restart on timeout
};

// --- 1. ADDED CALLBACK FOR REMOTE RESET ---
void callback(char* topic, byte* payload, unsigned int length) {
  String message = "";
  StaticJsonDocument<256> doc;
  deserializeJson(doc, payload, length);
  const char* action = doc["action"];

  for (int i = 0; i < length; i++) {
    message += (char)payload[i];
  }

  Serial.print("Message arrived ["); Serial.print(topic); Serial.print("] ");
  Serial.println(message);

  // Check if the message is a REBOOT command
  if (doc["action"] == "REBOOT") {
      Serial.println("Remote Reset Triggered! Rebooting...");
      delay(1000);
      ESP.restart(); // This physically restarts the ESP32
  }

  if (doc["action"] == "REMOTE_START") {
      const char* type = doc["type"];
      int pulseWidth = doc["width"] | 100;   // How long the "button" is pressed
      int pulseDelay = doc["delay"] | 100;   // Gap between pulses
      float price = doc["price"];
      
      Serial.printf("Remote Start Triggered: %s at $%f\n", type, price);

      // Logic: If cold price is $1 and pulse_price is $0.10, send 10 pulses
      // You would pull the 'pulse_price' from your settings
      int pulsesToSend = (int)(price / 0.10); // Example calculation
      
      for(int i=0; i < pulsesToSend; i++) {
          digitalWrite(COIN_OUTPUT_PIN, LOW); // Trigger machine coin line
          delay(pulseWidth);        // Timing from Laravel 
          digitalWrite(COIN_OUTPUT_PIN, HIGH);
          delay(pulseDelay);        // Gap from Laravel
          esp_task_wdt_reset();
      }
      
      Serial.println("Pulses sent to machine!");
  }

  if (message.startsWith("UPDATE:")) {
    String updateUrl = message.substring(7); // Extract the URL
    Serial.println("Starting OTA Update from: " + updateUrl);
    
    // 1. Tell Laravel we are starting
    client.publish(status_topic.c_str(), "{\"ota_status\":\"downloading\"}");

    // This line handles everything. It will reboot the ESP32 when done.
    t_httpUpdate_return ret = httpUpdate.update(espClient, updateUrl);
    esp_task_wdt_reset();

    switch (ret) {
      case HTTP_UPDATE_FAILED: {
        Serial.printf("Update Failed! Error (%d): %s\n", 
          httpUpdate.getLastError(), 
          httpUpdate.getLastErrorString().c_str());
          String errorMsg = "{\"ota_status\":\"failed\", \"error\":\"" + httpUpdate.getLastErrorString() + "\"}";
          client.publish(status_topic.c_str(), errorMsg.c_str());
          break;
      }
      case HTTP_UPDATE_NO_UPDATES: {
        Serial.println("No update needed.");
        break;
      }
      case HTTP_UPDATE_OK: {
        Serial.println("Update successful! Sending status...");
        // ADD THIS LINE: Tell Laravel the update finished successfully
        client.publish(status_topic.c_str(), "{\"ota_status\":\"success\"}");
        // The ESP32 will now reboot
        ESP.restart(); 
        break;
      }
    }
  }
}

void setup_wifi() {
  unsigned long startAttemptTime = millis();
  delay(10);
  Serial.print("Connecting to "); Serial.println(ssid);
  WiFi.begin(ssid, password);
  while (WiFi.status() != WL_CONNECTED && millis()- startAttemptTime < 10000) {
    delay(100); 
    esp_task_wdt_reset(); // feed watchdog during WiFi connect
    Serial.print(".");
  }
  Serial.println("\nWiFi connected");
}

void reconnect() {
  while (!client.connected()) {
    Serial.print("Attempting MQTT connection...");
    
    // --- LAST WILL AND TESTAMENT (LWT) ---
    // If the ESP32 loses power, EMQX will publish this "offline" message automatically
    String lwtMessage = "{\"status\":\"offline\"}";
    Serial.println(lwtMessage);
    
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
    
    // CHANGE: Retain should be false for count-based pulses
    return client.publish(telemetry_topic.c_str(), buffer, false); 
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

  // Initialize Watchdog, Initialize using the structure
  Serial.println("Configuring Watchdog...");
  esp_task_wdt_reconfigure(&wdt_config); // Enable panic (restart) on timeout
  esp_task_wdt_add(NULL); // Add the current thread (loop) to WDT

  // --- INITIALIZE STORAGE ---
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

  pinMode(COIN_OUTPUT_PIN, OUTPUT);
  digitalWrite(COIN_OUTPUT_PIN, HIGH); // idle state

  // Send a boot event to the status topic
  String bootPayload = "{\"event\":\"boot\",\"status\":\"online\",\"version\":\"1.0.2\"}";
  client.publish(status_topic.c_str(), bootPayload.c_str());
}

void loop() {
  // 1. FEED THE DOG: This tells the hardware "I am still alive"
  esp_task_wdt_reset();

  if (!client.connected()) {
    reconnect();
  }
  client.loop();

  // 2. IMPROVISED PULSE LOGIC (Flash Protection FOR WRITE TO NVS)
  if (pendingPulses > 0) {
    if (client.connected()) {
      // Try to send
      if (sendCoinPulse()) {
        pendingPulses--;
        Serial.println("Pulse sent successfully.");
        
        // If we just sent a pulse that was previously stored in Flash, 
        // we update the flash to decrease the count.
        preferences.putInt("saved_pulses", pendingPulses);
      }
    } else {
      // INTERNET IS DOWN: Save to Flash once and wait
      static int lastSavedToFlash = -1;
      if (pendingPulses != lastSavedToFlash) {
        preferences.putInt("saved_pulses", pendingPulses);
        lastSavedToFlash = pendingPulses;
        Serial.println("Internet down. Pulse protected in Flash.");
      }
    }
  }
  
  // Send heartbeat every 60 seconds
  unsigned long now = millis();
  if (now - lastMsg > 60000) {
    lastMsg = now;
    sendHeartbeat();
  }
}



