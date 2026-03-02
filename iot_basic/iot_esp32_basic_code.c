#include <WiFi.h>
#include <WiFiClientSecure.h>
#include <PubSubClient.h>

// Wi-Fi credentials
const char* ssid = "YOUR_WIFI_SSID";
const char* password = "YOUR_WIFI_PASSWORD";

// AWS IoT Core endpoint
const char* aws_endpoint = "YOUR_AWS_IOT_ENDPOINT"; // e.g., a123456789-ats.iot.us-east-1.amazonaws.com

// MQTT port
const int mqttPort = 8883;

// Certificates
const char* root_ca = \
"-----BEGIN CERTIFICATE-----\n" \
"YOUR_ROOT_CA_CERT\n" \
"-----END CERTIFICATE-----\n";

const char* device_cert = \
"-----BEGIN CERTIFICATE-----\n" \
"YOUR_DEVICE_CERT\n" \
"-----END CERTIFICATE-----\n";

const char* private_key = \
"-----BEGIN PRIVATE KEY-----\n" \
"YOUR_PRIVATE_KEY\n" \
"-----END PRIVATE KEY-----\n";

// Topics
const char* publishTopic = "esp32/pub";
const char* subscribeTopic = "esp32/sub";

WiFiClientSecure net;
PubSubClient client(net);

void connectAWS() {
  net.setCACert(root_ca);
  net.setCertificate(device_cert);
  net.setPrivateKey(private_key);

  client.setServer(aws_endpoint, mqttPort);
  client.setCallback(messageHandler);

  Serial.println("Connecting to AWS IoT...");
  while (!client.connected()) {
    if (client.connect("ESP32Client")) {
      Serial.println("Connected to AWS IoT");
      client.subscribe(subscribeTopic);
    } else {
      Serial.print(".");
      delay(1000);
    }
  }
}

void messageHandler(char* topic, byte* payload, unsigned int length) {
  Serial.print("Message arrived [");
  Serial.print(topic);
  Serial.print("]: ");
  for (int i = 0; i < length; i++) {
    Serial.print((char)payload[i]);
  }
  Serial.println();
}

void setup() {
  Serial.begin(115200);
  WiFi.begin(ssid, password);
  Serial.print("Connecting to Wi-Fi");
  while (WiFi.status() != WL_CONNECTED) {
    Serial.print(".");
    delay(1000);
  }
  Serial.println("Connected to Wi-Fi");
  connectAWS();
}

void loop() {
  if (!client.connected()) {
    connectAWS();
  }
  client.loop();

  // Publish a message every 5 seconds
  static unsigned long lastPublish = 0;
  if (millis() - lastPublish > 5000) {
    lastPublish = millis();
    String message = "{\"status\":\"ON\"}";
    client.publish(publishTopic, message.c_str());
    Serial.println("Published: " + message);
  }
}