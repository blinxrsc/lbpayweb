/** 
Pulse setting:
- pulse pull down or Up = 0, 
- pulse width (ms) = 40, 
- pulse Delay(ms) = 100, 
- coin signal sensitive = 0, 
- coin signal idle level = 1, 
- coin signal width (ms) = 40, 
- per pulse price = 100 and to start the machine is 4 token = 400 per pulse price

Signal Specification Breakdown:
- Idle Level (1) & Pull Down/Up (0): The signal line rests at HIGH logic voltage. To simulate a token, the ESP32 must pull the signal line LOW (GND).
- Pulse Width (40 ms): The line must stay LOW for exactly 40 ms per token pulse.
- Pulse Delay (100 ms): The line must return to HIGH for 100 ms between pulses.
- Tokens Needed (4 Tokens = 4 Pulses): To reach the 400 price target (4 × 100), the ESP32 must transmit 4 low pulses in sequence.

Recommended Pins: GPIO 4, 13, 14, 18, 19, 23, 25, 26, 27, 32, or 33.

Wiring Diagram:
To simulate the active-low pulse visually:
1. Connect the Long Leg (Anode / +) of the LED to ESP32 3.3V.
2. Connect the Short Leg (Cathode / -) of the LED to one end of the 220Ω resistor.
3. Connect the other end of the resistor to GPIO 23.
4. How it works visually: When GPIO 23 goes LOW (0V), current flows from 3.3V through the LED to GPIO 23, lighting up the LED for 40 ms to represent 1 token pulse.
*/

#define COIN_SIGNAL_PIN 23  // Connected to LED cathode (-) via 220 ohm resistor

void setup() {
  Serial.begin(115200);
  
  // Set GPIO pin as output
  pinMode(COIN_SIGNAL_PIN, OUTPUT);
  
  // Idle state: HIGH (LED is OFF, signal sits at logic 1)
  digitalWrite(COIN_SIGNAL_PIN, HIGH);
  
  Serial.println("ESP32 Coin Simulator Ready!");
  Serial.println("Type a number (e.g., '4') in Serial Monitor and press Enter to test.");
}

// Function to simulate sending pulse tokens
void sendTokens(int tokenCount) {
  Serial.print("Sending ");
  Serial.print(tokenCount);
  Serial.println(" token pulses...");

  for (int i = 0; i < tokenCount; i++) {
    // Pulse Active: Pull LOW (LED turns ON)
    digitalWrite(COIN_SIGNAL_PIN, LOW); 
    delay(40); // Pulse Width = 40 ms
    
    // Pulse Idle: Set HIGH (LED turns OFF)
    digitalWrite(COIN_SIGNAL_PIN, HIGH); 
    delay(100); // Pulse Delay = 100 ms
  }
  
  Serial.println("Done! Machine start sequence complete.");
}

void loop() {
  // Read inputs from the Serial Monitor for testing
  if (Serial.available() > 0) {
    int count = Serial.parseInt(); // Reads number entered in Serial Monitor
    if (count > 0) {
      sendTokens(count);
    }
  }
}