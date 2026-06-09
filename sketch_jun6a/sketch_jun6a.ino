#include <WiFi.h>
#include <HTTPClient.h>
#include <WiFiClientSecure.h>
#include <time.h>

// ===================== CONFIG =====================
const char* WIFI_SSID = "yaudahiya";
const char* WIFI_PASSWORD = "minimal8";

// Kalau backend di laptop, pakai IP laptop, bukan 127.0.0.1
const String API_BASE_URL = "http://10.165.107.134:8000/api/v1";

const String DEVICE_KEY = "sensor_device_secret_key";
const String SENSOR_CODE = "ENTRANCE-001";
const int LOCATION_ID = 1;

// HC-SR04 wiring
// TRIG -> GPIO 5
// ECHO -> GPIO 18
// VCC  -> 5V
// GND  -> GND
//
// Penting: pin ECHO HC-SR04 biasanya 5V.
// Untuk ESP32, sebaiknya pakai voltage divider agar ECHO turun ke 3.3V.
const int TRIG_PIN = 27;
const int ECHO_PIN = 26;

// Sesuai seed backend: threshold_distance = 40 cm
const float THRESHOLD_CM = 40.0;
const float RELEASE_MARGIN_CM = 12.0;

// Debounce/cooldown
const int REQUIRED_STABLE_READS = 3;
const unsigned long READ_INTERVAL_MS = 250;
const unsigned long DETECTION_COOLDOWN_MS = 6000;
const unsigned long HEARTBEAT_INTERVAL_MS = 30000;
const unsigned long WIFI_RECONNECT_INTERVAL_MS = 5000;

// Buffer event offline
const int MAX_BUFFERED_EVENTS = 20;

// Timezone Asia/Jakarta UTC+7
const long GMT_OFFSET_SEC = 7 * 3600;
const int DAYLIGHT_OFFSET_SEC = 0;

// ===================== STATE =====================
struct VehicleEvent {
  String sensorCode;
  int locationId;
  String entryTime;
  int vehicleCount;
  float detectionConfidence;
  float rawDistance;
  String deviceEventId;
};

VehicleEvent eventBuffer[MAX_BUFFERED_EVENTS];
int bufferCount = 0;

bool vehiclePresent = false;
int stableDetectedReads = 0;

unsigned long lastReadAt = 0;
unsigned long lastDetectionAt = 0;
unsigned long lastHeartbeatAt = 0;
unsigned long lastWifiReconnectAt = 0;

unsigned long eventCounter = 0;

// ===================== HELPERS =====================
bool isHttpsUrl(const String& url) {
  return url.startsWith("https://");
}

String twoDigits(int value) {
  if (value < 10) return "0" + String(value);
  return String(value);
}

bool isTimeReady() {
  struct tm timeinfo;
  return getLocalTime(&timeinfo, 100);
}

String nowDateTime() {
  struct tm timeinfo;

  if (!getLocalTime(&timeinfo, 100)) {
    // Fallback kalau NTP belum siap. Sebaiknya tunggu NTP di setup.
    return "2026-01-01 00:00:00";
  }

  String year = String(timeinfo.tm_year + 1900);
  String month = twoDigits(timeinfo.tm_mon + 1);
  String day = twoDigits(timeinfo.tm_mday);
  String hour = twoDigits(timeinfo.tm_hour);
  String minute = twoDigits(timeinfo.tm_min);
  String second = twoDigits(timeinfo.tm_sec);

  return year + "-" + month + "-" + day + " " + hour + ":" + minute + ":" + second;
}

String makeDeviceEventId() {
  time_t nowEpoch;
  time(&nowEpoch);
  eventCounter++;

  return "ESP32-" + SENSOR_CODE + "-" + String((unsigned long)nowEpoch) + "-" + String(eventCounter);
}

void connectWiFi() {
  if (WiFi.status() == WL_CONNECTED) return;

  Serial.println();
  Serial.print("Connecting WiFi to: ");
  Serial.println(WIFI_SSID);

  WiFi.mode(WIFI_STA);
  WiFi.disconnect(true);
  delay(1000);

  WiFi.setSleep(false);
  WiFi.begin(WIFI_SSID, WIFI_PASSWORD);

  unsigned long startedAt = millis();

  while (WiFi.status() != WL_CONNECTED && millis() - startedAt < 15000) {
    delay(500);
    Serial.print(".");
  }

  Serial.println();

  if (WiFi.status() == WL_CONNECTED) {
    Serial.print("WiFi connected. IP: ");
    Serial.println(WiFi.localIP());
  } else {
    Serial.print("WiFi connection failed. Status: ");
    Serial.println(WiFi.status());
  }
}

void maintainWiFi() {
  if (WiFi.status() == WL_CONNECTED) return;

  unsigned long nowMs = millis();

  if (nowMs - lastWifiReconnectAt >= WIFI_RECONNECT_INTERVAL_MS) {
    lastWifiReconnectAt = nowMs;
    connectWiFi();
  }
}

float readDistanceCm() {
  digitalWrite(TRIG_PIN, LOW);
  delayMicroseconds(3);

  digitalWrite(TRIG_PIN, HIGH);
  delayMicroseconds(10);
  digitalWrite(TRIG_PIN, LOW);

  long duration = pulseIn(ECHO_PIN, HIGH, 30000);

  if (duration == 0) {
    return -1.0;
  }

  float distance = duration * 0.0343 / 2.0;
  return distance;
}

float readFilteredDistanceCm() {
  const int samples = 5;
  float values[samples];
  int validCount = 0;

  for (int i = 0; i < samples; i++) {
    float distance = readDistanceCm();

    if (distance > 1.0 && distance < 400.0) {
      values[validCount] = distance;
      validCount++;
    }

    delay(30);
  }

  if (validCount == 0) return -1.0;

  for (int i = 0; i < validCount - 1; i++) {
    for (int j = i + 1; j < validCount; j++) {
      if (values[j] < values[i]) {
        float temp = values[i];
        values[i] = values[j];
        values[j] = temp;
      }
    }
  }

  return values[validCount / 2];
}

float calculateConfidence(float distanceCm) {
  if (distanceCm <= 0) return 0.0;

  float ratio = distanceCm / THRESHOLD_CM;
  float confidence = 100.0 - (ratio * 20.0);

  if (confidence > 99.0) confidence = 99.0;
  if (confidence < 75.0) confidence = 75.0;

  return confidence;
}

String vehiclePayload(const VehicleEvent& event) {
  String json = "{";
  json += "\"sensor_code\":\"" + event.sensorCode + "\",";
  json += "\"location_id\":" + String(event.locationId) + ",";
  json += "\"entry_time\":\"" + event.entryTime + "\",";
  json += "\"vehicle_count\":" + String(event.vehicleCount) + ",";
  json += "\"detection_confidence\":" + String(event.detectionConfidence, 2) + ",";
  json += "\"raw_distance\":" + String(event.rawDistance, 2) + ",";
  json += "\"device_event_id\":\"" + event.deviceEventId + "\"";
  json += "}";

  return json;
}

String heartbeatPayload(float lastDistance) {
  String json = "{";
  json += "\"sensor_code\":\"" + SENSOR_CODE + "\",";
  json += "\"status\":\"active\",";
  json += "\"last_distance\":" + String(lastDistance, 2);
  json += "}";

  return json;
}

bool postJson(const String& url, const String& payload) {
  if (WiFi.status() != WL_CONNECTED) return false;

  HTTPClient http;
  WiFiClient client;
  WiFiClientSecure secureClient;

  bool started = false;

  if (isHttpsUrl(url)) {
    secureClient.setInsecure();
    started = http.begin(secureClient, url);
  } else {
    started = http.begin(client, url);
  }

  if (!started) {
    Serial.println("HTTP begin failed.");
    return false;
  }

  http.addHeader("Content-Type", "application/json");
  http.addHeader("X-DEVICE-KEY", DEVICE_KEY);

  int statusCode = http.POST(payload);
  String response = http.getString();

  Serial.print("POST ");
  Serial.print(url);
  Serial.print(" -> ");
  Serial.println(statusCode);
  Serial.println(response);

  http.end();

  if (statusCode >= 200 && statusCode < 300) return true;

  // Duplicate artinya backend sudah punya event ini, jadi tidak perlu retry.
  if (statusCode == 409) return true;

  return false;
}

void bufferEvent(const VehicleEvent& event) {
  if (bufferCount >= MAX_BUFFERED_EVENTS) {
    for (int i = 1; i < MAX_BUFFERED_EVENTS; i++) {
      eventBuffer[i - 1] = eventBuffer[i];
    }
    bufferCount = MAX_BUFFERED_EVENTS - 1;
  }

  eventBuffer[bufferCount] = event;
  bufferCount++;

  Serial.print("Event buffered. Buffer count: ");
  Serial.println(bufferCount);
}

void removeFirstBufferedEvent() {
  if (bufferCount <= 0) return;

  for (int i = 1; i < bufferCount; i++) {
    eventBuffer[i - 1] = eventBuffer[i];
  }

  bufferCount--;
}

bool sendVehicleEvent(const VehicleEvent& event) {
  String url = API_BASE_URL + "/iot/vehicle-detections";
  String payload = vehiclePayload(event);

  return postJson(url, payload);
}

void retryBufferedEvents() {
  if (WiFi.status() != WL_CONNECTED) return;
  if (bufferCount <= 0) return;

  Serial.print("Retrying buffered events: ");
  Serial.println(bufferCount);

  while (bufferCount > 0) {
    bool sent = sendVehicleEvent(eventBuffer[0]);

    if (sent) {
      removeFirstBufferedEvent();
      delay(300);
    } else {
      break;
    }
  }
}

void sendHeartbeat(float lastDistance) {
  String url = API_BASE_URL + "/iot/sensors/heartbeat";
  String payload = heartbeatPayload(lastDistance);

  postJson(url, payload);
}

void createDetection(float distanceCm) {
  VehicleEvent event;
  event.sensorCode = SENSOR_CODE;
  event.locationId = LOCATION_ID;
  event.entryTime = nowDateTime();
  event.vehicleCount = 1;
  event.detectionConfidence = calculateConfidence(distanceCm);
  event.rawDistance = distanceCm;
  event.deviceEventId = makeDeviceEventId();

  Serial.println("Vehicle detected.");
  Serial.print("Distance: ");
  Serial.println(distanceCm);
  Serial.print("Event ID: ");
  Serial.println(event.deviceEventId);

  bool sent = sendVehicleEvent(event);

  if (!sent) {
    bufferEvent(event);
  }
}

// ===================== ARDUINO =====================
void setup() {
  Serial.begin(115200);
  delay(1000);

  pinMode(TRIG_PIN, OUTPUT);
  pinMode(ECHO_PIN, INPUT);

  connectWiFi();

  configTime(GMT_OFFSET_SEC, DAYLIGHT_OFFSET_SEC, "pool.ntp.org", "time.google.com");

  Serial.print("Syncing time");
  unsigned long startedAt = millis();
  while (!isTimeReady() && millis() - startedAt < 20000) {
    delay(500);
    Serial.print(".");
  }

  Serial.println();

  if (isTimeReady()) {
    Serial.print("Time ready: ");
    Serial.println(nowDateTime());
  } else {
    Serial.println("Time sync failed. Check WiFi/NTP.");
  }

  float initialDistance = readFilteredDistanceCm();
  sendHeartbeat(initialDistance);
  lastHeartbeatAt = millis();
}

void loop() {
  maintainWiFi();

  unsigned long nowMs = millis();

  if (nowMs - lastReadAt >= READ_INTERVAL_MS) {
    lastReadAt = nowMs;

    float distanceCm = readFilteredDistanceCm();

    Serial.print("Distance cm: ");
    Serial.println(distanceCm);

    bool validDistance = distanceCm > 0;
    bool detected = validDistance && distanceCm <= THRESHOLD_CM;
    bool released = !validDistance || distanceCm > (THRESHOLD_CM + RELEASE_MARGIN_CM);

    if (detected) {
      stableDetectedReads++;
    } else {
      stableDetectedReads = 0;
    }

    if (!vehiclePresent &&
        stableDetectedReads >= REQUIRED_STABLE_READS &&
        nowMs - lastDetectionAt >= DETECTION_COOLDOWN_MS) {
      vehiclePresent = true;
      lastDetectionAt = nowMs;
      createDetection(distanceCm);
    }

    if (vehiclePresent && released) {
      vehiclePresent = false;
      stableDetectedReads = 0;
      Serial.println("Vehicle released.");
    }

    if (nowMs - lastHeartbeatAt >= HEARTBEAT_INTERVAL_MS) {
      lastHeartbeatAt = nowMs;
      sendHeartbeat(distanceCm);
    }
  }

  retryBufferedEvents();
}