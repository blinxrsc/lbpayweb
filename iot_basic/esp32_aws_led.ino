#include <WiFi.h>
#include <WiFiClientSecure.h>
#include <PubSubClient.h>
#include <ArduinoJson.h>
#include "secrets.h"

WiFiClientSecure net;
PubSubClient client(net);

const int LED_PIN = 2; // Built-in LED on many ESP32 dev boards

String topicCmd   = String("esp32/") + THING_NAME + "/cmd";
String topicState = String("esp32/") + THING_NAME + "/state";

void publishState(const char* state) {
  StaticJsonDocument<128> doc;
  doc["thing"] = THING_NAME;
  doc["led"] = state;

  char out[128];
  serializeJson(doc, out);

  client.publish(topicState.c_str(), out, true); // retained
}

void messageHandler(char* topic, byte* payload, unsigned int length) {
  Serial.print("Incoming topic: ");
  Serial.println(topic);

  // Copy payload to a null-terminated buffer
  String msg;
  for (unsigned int i = 0; i < length; i++) msg += (char)payload[i];
  Serial.print("Payload: ");
  Serial.println(msg);

  // Parse JSON { "command":"ON", "pin":2 }
  StaticJsonDocument<256> doc;
  DeserializationError err = deserializeJson(doc, msg);
  if (err) {
    Serial.println("JSON parse failed");
    return;
  }

  String command = doc["command"] | "";
  int pin = doc["pin"] | LED_PIN;

  if (pin != LED_PIN) {
    // For simplicity, only allow controlling the LED_PIN in this demo
    Serial.println("Ignoring command: pin not allowed in demo");
    return;
  }

  if (command == "ON" || command == "on" || command == "1") {
    digitalWrite(LED_PIN, HIGH);
    publishState("ON");
  } else if (command == "OFF" || command == "off" || command == "0") {
    digitalWrite(LED_PIN, LOW);
    publishState("OFF");
  } else {
    Serial.println("Unknown command");
  }
}

void connectWiFi() {
  WiFi.mode(WIFI_STA);
  WiFi.begin(WIFI_SSID, WIFI_PASSWORD);

  Serial.print("Connecting WiFi");
  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
  }
  Serial.println("\nWiFi connected");
}

void connectAWS() {
  net.setCACert(AWS_CERT_CA);
  net.setCertificate(AWS_CERT_CRT);
  net.setPrivateKey(AWS_CERT_PRIVATE);

  client.setServer(AWS_IOT_ENDPOINT, 8883);
  client.setCallback(messageHandler);

  Serial.print("Connecting to AWS IoT");
  // IMPORTANT: clientId == Thing Name (best practice with thing policy variables). [1](https://docs.aws.amazon.com/iot/latest/developerguide/thing-policy-variables.html)
  while (!client.connected()) {
    if (client.connect(THING_NAME)) {
      Serial.println("\nConnected!");
      client.subscribe(topicCmd.c_str(), 1);
      publishState(digitalRead(LED_PIN) ? "ON" : "OFF");
    } else {
      Serial.print(".");
      delay(1000);
    }
  }
}

void setup() {
  Serial.begin(115200);
  pinMode(LED_PIN, OUTPUT);
  digitalWrite(LED_PIN, LOW);

  connectWiFi();
  connectAWS();
}

void loop() {
  if (!client.connected()) connectAWS();
  client.loop();
}