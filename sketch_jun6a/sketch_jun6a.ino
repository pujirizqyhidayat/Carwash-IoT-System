// ======================================================
// LIBRARIES
// ======================================================

#include <WiFi.h>
#include <WiFiClientSecure.h>
#include <PubSubClient.h>
#include <ArduinoJson.h>

// ======================================================
// WIFI CONFIGURATION
// ======================================================

const char* WIFI_SSID = "yaudahiya";
const char* WIFI_PASSWORD = "minimal8";

// ======================================================
// MQTT CONFIGURATION
// ======================================================

const char* MQTT_HOST = "h742d878.ala.asia-southeast1.emqxsl.com";
const int MQTT_PORT = 8883;

const char* MQTT_USERNAME = "parking";
const char* MQTT_PASSWORD = "parking12345";

const char* MQTT_CLIENT_ID = "ESP32-PARKING-001";

const char* MQTT_TOPIC_STATUS = "parking/1/status";

// ======================================================
// DEVICE CONFIGURATION
// ======================================================

const char* DEVICE_ID = "ESP32-001";
const char* SENSOR_CODE = "ENTRANCE-001";
const int LOCATION_ID = 5;

// ======================================================
// SENSOR PIN
// ======================================================

#define TRIG_LEFT 27
#define ECHO_LEFT 26

#define TRIG_RIGHT 32
#define ECHO_RIGHT 35

// ======================================================
// SENSOR CONFIGURATION
// ======================================================

// Jarak antar sensor prototype
const float GATE_DISTANCE = 20.0;

// Kendaraan dianggap menutupi sensor
const float BLOCK_THRESHOLD = 8.0;

// Maksimal selisih kiri-kanan
const float MAX_DIFFERENCE = 2.0;

// Threshold release
const float RELEASE_THRESHOLD = GATE_DISTANCE - 2.0;

// Filter
const int FILTER_SAMPLES = 5;

// Stabil detection
const int REQUIRED_STABLE_READS = 3;

// Interval pembacaan
const int READ_INTERVAL = 250;

// Delay agar ultrasonic tidak saling ganggu
const int SENSOR_DELAY = 60;

// ======================================================
// MQTT CLIENT
// ======================================================

WiFiClientSecure wifiClient;
PubSubClient mqttClient(wifiClient);

// ======================================================
// GLOBAL STATE
// ======================================================

bool vehiclePresent = false;

int stableCounter = 0;

unsigned long lastRead = 0;

unsigned long lastReconnectAttempt = 0;

unsigned long lastWifiReconnectAttempt = 0;

const unsigned long WIFI_RECONNECT_INTERVAL = 5000;
const unsigned long MQTT_RECONNECT_INTERVAL = 5000;

const char* FW_VERSION = "1.0.0";

float leftDistance = -1;

float rightDistance = -1;

// ======================================================
// WIFI FUNCTIONS
// ======================================================

void connectWiFi()
{
    if (WiFi.status() == WL_CONNECTED)
        return;

    Serial.println();
    Serial.println("================================");
    Serial.println("Connecting WiFi...");
    Serial.println("================================");

    WiFi.mode(WIFI_STA);

    WiFi.begin(
        WIFI_SSID,
        WIFI_PASSWORD);

    while (WiFi.status() != WL_CONNECTED)
    {
        delay(500);
        Serial.print(".");
    }

    Serial.println();
    Serial.println("WiFi Connected");

    Serial.print("IP : ");
    Serial.println(WiFi.localIP());

    if (WiFi.status() != WL_CONNECTED)
    {
    WiFi.reconnect();
    }
}

// ======================================================
// MQTT FUNCTIONS
// ======================================================

void connectMQTT()
{
    if (mqttClient.connected())
        return;

    unsigned long now = millis();

    if (now - lastReconnectAttempt < MQTT_RECONNECT_INTERVAL)
        return;

    lastReconnectAttempt = now;

    wifiClient.setInsecure();

    mqttClient.setServer(
        MQTT_HOST,
        MQTT_PORT);

    Serial.println("Connecting MQTT...");

    bool connected =
        mqttClient.connect(
            MQTT_CLIENT_ID,
            MQTT_USERNAME,
            MQTT_PASSWORD);

    if (connected)
    {
        Serial.println("MQTT Connected");

        mqttClient.publish(
            MQTT_TOPIC_STATUS,
            "{\"status\":\"online\"}",
            false);
    }
    else
    {
        Serial.print("MQTT Error : ");
        Serial.println(mqttClient.state());
    }
}

void maintainConnection()
{
    if (WiFi.status() != WL_CONNECTED)
    {
        connectWiFi();
    }

    if (!mqttClient.connected())
    {
        connectMQTT();
    }

    mqttClient.loop();
}

// ======================================================
// MQTT PUBLISH
// ======================================================

void publishVehicleStatus(
    bool vehiclePresent,
    float leftDistance,
    float rightDistance)
{
    StaticJsonDocument<256> doc;

    doc["device_id"] = DEVICE_ID;
    doc["sensor_code"] = SENSOR_CODE;
    doc["location_id"] = LOCATION_ID;

    doc["vehicle_present"] = vehiclePresent;

    doc["left_distance"] = leftDistance;
    doc["right_distance"] = rightDistance;

    doc["event"] =
        vehiclePresent
            ? "vehicle_detected"
            : "vehicle_released";
    doc["fw"] = FW_VERSION;
    doc["wifi_rssi"] = WiFi.RSSI();
    doc["uptime"] = millis() / 1000;
    doc["read_time"] = millis();
    

    char payload[256];

    serializeJson(doc, payload);

        bool success = mqttClient.publish(
            MQTT_TOPIC_STATUS,
            payload,
            false);

    Serial.println("--------------------------------");
    Serial.println("MQTT Publish");

    if(success)
    {
        Serial.println("SUCCESS");
    }
    else
    {
        Serial.println("FAILED");
    }

    Serial.println(payload);

    Serial.println("--------------------------------");
}
// ======================================================
// SENSOR FUNCTIONS
// ======================================================

float readDistance(int trigPin, int echoPin)
{
    digitalWrite(trigPin, LOW);
    delayMicroseconds(2);

    digitalWrite(trigPin, HIGH);
    delayMicroseconds(10);

    digitalWrite(trigPin, LOW);

    long duration = pulseIn(echoPin, HIGH, 30000);

    if(duration == 0)
        return -1;

    return duration * 0.0343 / 2.0;
}

float filteredDistance(int trigPin, int echoPin)
{
    float values[FILTER_SAMPLES];

    int valid = 0;

    for(int i=0;i<FILTER_SAMPLES;i++)
    {
        float d = readDistance(trigPin,echoPin);

        if(d>0 && d<400)
        {
            values[valid++] = d;
        }

        delay(20);
    }

    if(valid==0)
        return -1;

    for(int i=0;i<valid-1;i++)
    {
        for(int j=i+1;j<valid;j++)
        {
            if(values[j] < values[i])
            {
                float temp = values[i];
                values[i] = values[j];
                values[j] = temp;
            }
        }
    }

    return values[valid/2];
}

// ======================================================
// PARKING BUSINESS LOGIC
// ======================================================

void processParking()
{
    if (millis() - lastRead < READ_INTERVAL)
        return;

    lastRead = millis();

    // =============================
    // Read Left Sensor
    // =============================

    leftDistance = filteredDistance(
        TRIG_LEFT,
        ECHO_LEFT);

    // Hindari cross-talk
    delay(SENSOR_DELAY);

    // =============================
    // Read Right Sensor
    // =============================

    rightDistance = filteredDistance(
        TRIG_RIGHT,
        ECHO_RIGHT);

    Serial.println("--------------------------------");

    Serial.print("Left  : ");
    Serial.print(leftDistance);
    Serial.println(" cm");

    Serial.print("Right : ");
    Serial.print(rightDistance);
    Serial.println(" cm");

    float difference =
        abs(leftDistance - rightDistance);

    Serial.print("Difference : ");
    Serial.println(difference);

    bool leftBlocked =
        leftDistance > 0 &&
        leftDistance <= BLOCK_THRESHOLD;

    bool rightBlocked =
        rightDistance > 0 &&
        rightDistance <= BLOCK_THRESHOLD;

    bool balanced =
        difference <= MAX_DIFFERENCE;

    if (leftBlocked &&
        rightBlocked &&
        balanced)
    {
        stableCounter++;

        Serial.print("Stable Counter : ");
        Serial.println(stableCounter);
    }
    else
    {
        stableCounter = 0;
    }

    // =====================================
    // VEHICLE DETECTED
    // =====================================

    if (!vehiclePresent &&
        stableCounter >= REQUIRED_STABLE_READS)
    {
        vehiclePresent = true;

        Serial.println();
        Serial.println("############################");
        Serial.println("VEHICLE DETECTED");
        Serial.println("############################");
        Serial.println();

        publishVehicleStatus(
            true,
            leftDistance,
            rightDistance);
    }

    // =====================================
    // VEHICLE RELEASED
    // =====================================

    if (vehiclePresent &&
        leftDistance >= RELEASE_THRESHOLD &&
        rightDistance >= RELEASE_THRESHOLD)
    {
        vehiclePresent = false;

        stableCounter = 0;

        Serial.println();
        Serial.println("Vehicle Released");
        Serial.println();

        publishVehicleStatus(
            false,
            leftDistance,
            rightDistance);
    }
}

// ======================================================
// SETUP
// ======================================================

void setup()
{
    Serial.begin(115200);

    pinMode(TRIG_LEFT, OUTPUT);
    pinMode(ECHO_LEFT, INPUT);

    pinMode(TRIG_RIGHT, OUTPUT);
    pinMode(ECHO_RIGHT, INPUT);

    Serial.println();
    Serial.println("================================");
    Serial.println("Parking Sensor v2");
    Serial.println("================================");

    connectWiFi();
        while (WiFi.status() != WL_CONNECTED)
{
    delay(500);
    Serial.print(".");
}

Serial.println();
Serial.println("WiFi Connected");
Serial.println(WiFi.localIP());

    connectMQTT();

    Serial.println();
    Serial.println("System Ready");
}

// ======================================================
// LOOP
// ======================================================

void loop()
{
    maintainConnection();

    processParking();
}