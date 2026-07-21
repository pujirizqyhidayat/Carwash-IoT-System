// ================= PIN =================
#define TRIG_LEFT 27
#define ECHO_LEFT 26

#define TRIG_RIGHT 32
#define ECHO_RIGHT 35

// =============== CONFIG ================

// Jarak antar sensor prototype
const float GATE_DISTANCE = 20.0;

// Jika objek berada <= 8 cm dari sensor,
// maka dianggap menutupi sensor.
const float BLOCK_THRESHOLD = 8.0;

// Selisih maksimal kiri-kanan
const float MAX_DIFFERENCE = 2.0;

const int REQUIRED_STABLE_READS = 3;
const int READ_INTERVAL = 250;

// =======================================

bool vehiclePresent = false;
int stableCounter = 0;

unsigned long lastRead = 0;

// =======================================

float readDistance(int trigPin, int echoPin)
{
    digitalWrite(trigPin, LOW);
    delayMicroseconds(2);

    digitalWrite(trigPin, HIGH);
    delayMicroseconds(10);

    digitalWrite(trigPin, LOW);

    long duration = pulseIn(echoPin, HIGH, 30000);

    if (duration == 0)
        return -1;

    return duration * 0.0343 / 2.0;
}

float filteredDistance(int trigPin, int echoPin)
{
    const int samples = 5;

    float values[samples];
    int valid = 0;

    for (int i = 0; i < samples; i++)
    {
        float d = readDistance(trigPin, echoPin);

        if (d > 0 && d < 400)
        {
            values[valid++] = d;
        }

        delay(20);
    }

    if (valid == 0)
        return -1;

    for (int i = 0; i < valid - 1; i++)
    {
        for (int j = i + 1; j < valid; j++)
        {
            if (values[j] < values[i])
            {
                float temp = values[i];
                values[i] = values[j];
                values[j] = temp;
            }
        }
    }

    return values[valid / 2];
}

void setup()
{
    Serial.begin(115200);

    pinMode(TRIG_LEFT, OUTPUT);
    pinMode(ECHO_LEFT, INPUT);

    pinMode(TRIG_RIGHT, OUTPUT);
    pinMode(ECHO_RIGHT, INPUT);

    Serial.println("===== Prototype Vehicle Detection =====");
}

void loop()
{
    if (millis() - lastRead < READ_INTERVAL)
        return;

    lastRead = millis();

    // Baca sensor kiri dulu
    float left = filteredDistance(TRIG_LEFT, ECHO_LEFT);

    // Hindari cross-talk ultrasonic
    delay(60);

    // Baru baca sensor kanan
    float right = filteredDistance(TRIG_RIGHT, ECHO_RIGHT);

    Serial.println("--------------------------------");

    Serial.print("Left  : ");
    Serial.print(left);
    Serial.println(" cm");

    Serial.print("Right : ");
    Serial.print(right);
    Serial.println(" cm");

    float difference = abs(left - right);

    Serial.print("Difference : ");
    Serial.println(difference);

    bool leftBlocked =
        left > 0 &&
        left <= BLOCK_THRESHOLD;

    bool rightBlocked =
        right > 0 &&
        right <= BLOCK_THRESHOLD;

    bool balanced =
        difference <= MAX_DIFFERENCE;

    if (leftBlocked && rightBlocked && balanced)
    {
        stableCounter++;

        Serial.print("Stable Counter : ");
        Serial.println(stableCounter);
    }
    else
    {
        stableCounter = 0;
    }

    if (!vehiclePresent &&
        stableCounter >= REQUIRED_STABLE_READS)
    {
        vehiclePresent = true;

        Serial.println();
        Serial.println("############################");
        Serial.println("VEHICLE DETECTED");
        Serial.println("############################");
        Serial.println();
    }

    // Release ketika kedua sensor kembali
    // membaca mendekati jarak normal (20 cm)
    if (vehiclePresent &&
        left >= 18 &&
        right >= 18)
    {
        vehiclePresent = false;
        stableCounter = 0;

        Serial.println();
        Serial.println("Vehicle Released");
        Serial.println();
    }
}