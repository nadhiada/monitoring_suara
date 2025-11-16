#include <ESP8266WiFi.h>
#include <ESP8266HTTPClient.h>

const char* ssid = "ARTIFA";
const char* password = "artifasari";

int STUDIO_ID = 1;   // Studio 1 / 2 / 3

const String serverURL = "http://192.168.1.8/monitoring_suara/insert.php";

// Sensor KY-038
const int sensorPin = A0;
const int lampMerah = D1;
const int lampKuning = D2;
const int lampHijau = D3;

const int sampleWindow = 50;

void setup() {
  Serial.begin(115200);
  WiFi.begin(ssid, password);

  Serial.print("Menghubungkan WiFi...");
  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
  }
  Serial.println("\nWiFi Terhubung!");

  pinMode(lampMerah, OUTPUT);
  pinMode(lampKuning, OUTPUT);
  pinMode(lampHijau, OUTPUT);
}

void loop() {
  unsigned long startMillis = millis();
  unsigned int signalMax = 0;
  unsigned int signalMin = 1024;

  while (millis() - startMillis < sampleWindow) {
    int sample = analogRead(sensorPin);
    if (sample < 1024) {
      if (sample > signalMax) signalMax = sample;
      if (sample < signalMin) signalMin = sample;
    }
  }

  int peakToPeak = signalMax - signalMin;
  int db = map(peakToPeak, 5, 600, 20, 120);
  if (db < 0) db = 0;

  String status = "RENDAH";
  int lampID = 3;

  digitalWrite(lampMerah, LOW);
  digitalWrite(lampKuning, LOW);
  digitalWrite(lampHijau, LOW);

  if (db < 50) {
    status = "RENDAH";
    lampID = 3;
    digitalWrite(lampHijau, HIGH);
  } 
  else if (db >= 50 && db <= 90) {
    status = "SEDANG";
    lampID = 2;
    digitalWrite(lampKuning, HIGH);
  } 
  else {
    status = "TINGGI";
    lampID = 1;
    digitalWrite(lampMerah, HIGH);
  }

  Serial.printf("Studio %d | %d dB | %s\n", STUDIO_ID, db, status);

  // Kirim ke server
  if (WiFi.status() == WL_CONNECTED) {
    WiFiClient client;
    HTTPClient http;

    // PARAMETER YANG BENAR:
    // ?studio_id=1&level=20&status=RENDAH&lamp=3
    String url = serverURL +
                 "?studio_id=" + STUDIO_ID +
                 "&level=" + db +
                 "&status=" + status +
                 "&lamp=" + lampID;

    http.begin(client, url);

    int httpCode = http.GET();
    Serial.print("Server Response: ");
    Serial.println(httpCode);

    http.end();
  }

  delay(1000);
}
