#pragma once

// Wi-Fi
#define WIFI_SSID       "YOUR_WIFI_SSID"
#define WIFI_PASSWORD   "YOUR_WIFI_PASSWORD"

// AWS IoT
#define AWS_IOT_ENDPOINT "YOUR_ENDPOINT_HERE"  
// Example: "abc123defghijk-ats.iot.ap-southeast-1.amazonaws.com"

#define THING_NAME "esp32-001"

// Certificates (paste PEM text exactly)
static const char AWS_CERT_CA[] PROGMEM = R"EOF(
-----BEGIN CERTIFICATE-----
... AmazonRootCA1.pem contents ...
-----END CERTIFICATE-----
)EOF";

static const char AWS_CERT_CRT[] PROGMEM = R"KEY(
-----BEGIN CERTIFICATE-----
... device.crt.pem contents ...
-----END CERTIFICATE-----
)KEY";

static const char AWS_CERT_PRIVATE[] PROGMEM = R"KEY(
-----BEGIN RSA PRIVATE KEY-----
... private.key.pem contents ...
-----END RSA PRIVATE KEY-----
)KEY";