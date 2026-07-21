# Installation Guide - Carwash IoT System

This document explains how to install and configure the **Carwash IoT System** from the backend, frontend, database, and IoT device sides. The project consists of a Laravel 10 backend, a Vue 3 frontend with Vite, a PostgreSQL database, and an ESP32 with an HC-SR04 ultrasonic sensor. For the hardware-to-software connection, this guide uses **MQTT** as the main communication channel between the ESP32 device and the backend system.

## 1. System Requirements

Before running the project, make sure your computer or server has the following software installed:

- PHP version 8.1 or higher.
- Composer.
- PostgreSQL.
- Node.js version `^20.19.0` or `>=22.12.0`.
- npm.
- Git.
- Arduino IDE.
- ESP32 board package for Arduino IDE.
- MQTT broker, for example Mosquitto.
- Arduino MQTT library, for example `PubSubClient`.

The backend uses Laravel 10, Laravel Sanctum, DomPDF, and Laravel Excel. The frontend uses Vue 3, Vite, Pinia, Axios, Tailwind CSS, Headless UI, and Lucide icons. The main database used by the system is PostgreSQL.

## 2. Project Structure

The main repository structure is:

```txt
Carwash-IoT-System/
+-- backend/          # Laravel API, database migrations, seeders, reports, audit logs
+-- frontend/         # Vue dashboard built with Vite
+-- sketch_jun6a/     # ESP32 sketch for the ultrasonic sensor
+-- docs/             # PRD, functional testing, and supporting documentation
+-- deploy/           # Deployment support files such as auditd configuration
+-- scripts/          # Additional scripts if needed
```

The backend provides APIs for authentication, dashboard, monitoring, reports, sensor management, user management, location management, audit logs, and IoT data receiving. The frontend provides a web dashboard for admin, owner, and cashier users. The ESP32 reads data from the ultrasonic sensor, calculates vehicle detection based on the distance threshold, and sends the data to the system through MQTT.

## 3. PostgreSQL Database Setup

Create a PostgreSQL database for the application. The database name is configurable, but this guide uses `laravel` as the example database name.

Example using `psql`:

```bash
createdb laravel
```

Or through the PostgreSQL shell:

```sql
CREATE DATABASE laravel;
```

Make sure the PostgreSQL user has permission to create tables, read data, write data, and update records in the database.

## 4. Laravel Backend Installation

Go to the backend directory:

```bash
cd backend
```

Install PHP dependencies:

```bash
composer install
```

Copy the environment file:

```bash
copy .env.example .env
```

If you are using Linux or macOS, use:

```bash
cp .env.example .env
```

Generate the Laravel application key:

```bash
php artisan key:generate
```

Edit the `.env` file inside the `backend` directory and adjust the main configuration:

```env
APP_NAME="Carwash IoT System"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000
APP_TIMEZONE=Asia/Jakarta

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=laravel
DB_USERNAME=postgres
DB_PASSWORD=

IOT_DEVICE_KEY=sensor_device_secret_key
```

`IOT_DEVICE_KEY` can still be used as an internal shared secret for the bridge process or payload validation. However, in this guide the ESP32-to-software communication uses MQTT, not direct HTTP requests from the ESP32 to Laravel.

Run the migrations and seeders:

```bash
php artisan migrate --seed
```

The seeder creates the following initial data:

- Admin: `admin@carwash.test` / `password123`
- Owner: `owner@carwash.test` / `password123`
- Cashier: `cashier@carwash.test` / `password123`
- Initial location: `Rizki Car Wash`
- Initial sensor: `ENTRANCE-001`
- Default location ID: `1`
- Default threshold distance: `40 cm`

Run the backend server:

```bash
php artisan serve
```

By default, the backend runs at:

```txt
http://127.0.0.1:8000
```

The API base URL is:

```txt
http://127.0.0.1:8000/api/v1
```

## 5. Vue Frontend Installation

Open a new terminal, then go to the frontend directory:

```bash
cd frontend
```

Install JavaScript dependencies:

```bash
npm install
```

Create a `.env` file inside the `frontend` directory if it does not exist, then add:

```env
VITE_API_BASE_URL=http://127.0.0.1:8000/api/v1
```

Run the frontend development server:

```bash
npm run dev
```

By default, the frontend runs at:

```txt
http://127.0.0.1:5173
```

Open that URL in your browser and log in using one of the seeded accounts.

## 6. Frontend Production Build

If you want to build the frontend for production, run:

```bash
cd frontend
npm run build
```

The production build output will be generated in:

```txt
frontend/dist
```

This folder can be served using a web server such as Nginx, Apache, or another static hosting service. Make sure `VITE_API_BASE_URL` points to the production backend URL before running the build command.

## 7. Laravel Scheduler for Daily Reports

The backend has a command for generating daily vehicle count summaries:

```bash
php artisan reports:generate-daily-summary
```

This command is scheduled to run automatically at 21:00 through Laravel Scheduler. On a production server, add the following cron entry:

```bash
* * * * * cd /path/to/Carwash-IoT-System/backend && php artisan schedule:run >> /dev/null 2>&1
```

For Windows development, the scheduler command can be run manually during testing:

```bash
php artisan schedule:run
```

You can also generate a summary for a specific date:

```bash
php artisan reports:generate-daily-summary 2026-05-30
```

## 8. MQTT Broker Installation

The MQTT broker acts as the intermediary between the ESP32 and the backend software. The ESP32 publishes data to the broker, then the backend or a bridge service subscribes to the broker and stores the data in the database.

One recommended broker is Mosquitto.

### 8.1 Installing Mosquitto on Windows

Download the Mosquitto installer from the official Eclipse Mosquitto website, then install it as a service. After installation, start the Mosquitto service through Windows Services or from the terminal.

To run it manually:

```bash
mosquitto -v
```

If the broker runs on your development computer, the broker address used by the ESP32 must be that computer's WiFi IP address, not `127.0.0.1`.

Example:

```txt
MQTT_HOST=192.168.1.10
MQTT_PORT=1883
```

### 8.2 Installing Mosquitto on Linux

Example for Ubuntu:

```bash
sudo apt update
sudo apt install mosquitto mosquitto-clients
sudo systemctl enable mosquitto
sudo systemctl start mosquitto
```

Check the broker status:

```bash
sudo systemctl status mosquitto
```

### 8.3 MQTT Topics

Use the following topics for ESP32 communication:

```txt
carwash/iot/vehicle-detections
carwash/iot/sensors/heartbeat
```

The `carwash/iot/vehicle-detections` topic is used for vehicle entry events. The `carwash/iot/sensors/heartbeat` topic is used for periodic sensor status updates.

## 9. MQTT Payloads

MQTT payloads are sent in JSON format so they can be processed easily by the backend bridge.

### 9.1 Vehicle Detection Payload

The ESP32 publishes the following payload to:

```txt
carwash/iot/vehicle-detections
```

Example payload:

```json
{
  "sensor_code": "ENTRANCE-001",
  "location_id": 1,
  "entry_time": "2026-05-30 10:00:00",
  "vehicle_count": 1,
  "detection_confidence": 98.75,
  "raw_distance": 35,
  "device_event_id": "ESP32-ENTRANCE-001-20260530100000-1"
}
```

Required fields:

- `sensor_code`: the sensor code registered in the database.
- `location_id`: the carwash location ID.
- `entry_time`: the time when the vehicle was detected.
- `vehicle_count`: the number of vehicles in one event, usually `1`.
- `detection_confidence`: the detection confidence value.
- `raw_distance`: the sensor distance reading when the vehicle was detected.
- `device_event_id`: a unique ID generated by the ESP32 to prevent duplicate entries.

### 9.2 Heartbeat Payload

The ESP32 publishes the following payload to:

```txt
carwash/iot/sensors/heartbeat
```

Example payload:

```json
{
  "sensor_code": "ENTRANCE-001",
  "status": "active",
  "last_distance": 120.5
}
```

Required fields:

- `sensor_code`: the registered sensor code.
- `status`: one of `active`, `inactive`, or `disconnected`.
- `last_distance`: the latest distance value read by the sensor.

## 10. MQTT Bridge to Laravel Backend

In the current repository, the backend already has HTTP endpoints for receiving IoT data:

```txt
POST /api/v1/iot/vehicle-detections
POST /api/v1/iot/sensors/heartbeat
```

Because this guide uses MQTT for hardware-to-software communication, one additional component is required: an **MQTT subscriber** or **MQTT bridge**. This component subscribes to the MQTT broker, receives payloads from the ESP32, and forwards the data to the Laravel backend or directly calls the Laravel storage service.

Recommended flow:

```txt
ESP32 + HC-SR04
  -> publish MQTT
MQTT Broker
  -> subscribed by bridge/worker
Laravel Backend
  -> validate payload
PostgreSQL
  -> store vehicle_entries, sensor_raw_logs, audit_logs
Vue Dashboard
  -> read data from Laravel API
```

The bridge can be implemented in two common ways:

1. A Laravel command or worker that subscribes directly to the MQTT broker.
2. A separate service, such as a Node.js or PHP script, that subscribes to MQTT and performs an internal HTTP POST to the Laravel endpoints.

For a simple implementation, use a bridge service that subscribes to MQTT and forwards the payload to the existing Laravel endpoints. With this approach, the existing validation in `IoTController` can still be reused.

### 10.1 Example Bridge Configuration

The bridge needs configuration similar to this:

```env
MQTT_HOST=192.168.1.10
MQTT_PORT=1883
MQTT_USERNAME=
MQTT_PASSWORD=
MQTT_TOPIC_VEHICLE=carwash/iot/vehicle-detections
MQTT_TOPIC_HEARTBEAT=carwash/iot/sensors/heartbeat

LARAVEL_API_BASE_URL=http://127.0.0.1:8000/api/v1
IOT_DEVICE_KEY=sensor_device_secret_key
```

If the bridge performs HTTP POST requests to Laravel, include these headers:

```http
Content-Type: application/json
X-DEVICE-KEY: sensor_device_secret_key
```

The bridge should forward vehicle detection payloads to:

```txt
http://127.0.0.1:8000/api/v1/iot/vehicle-detections
```

The bridge should forward heartbeat payloads to:

```txt
http://127.0.0.1:8000/api/v1/iot/sensors/heartbeat
```

With this approach, the ESP32 no longer needs to know the Laravel URL and does not need to perform direct HTTP requests. The ESP32 only publishes messages to the MQTT broker.

## 11. ESP32 and HC-SR04 Configuration

The hardware components are:

- ESP32 DevKit V1.
- HC-SR04 ultrasonic sensor.
- Jumper wires.
- 5V power supply.
- Voltage divider for the HC-SR04 ECHO pin.

In the sketch currently included in the repository, the active pins are:

```cpp
const int TRIG_PIN = 27;
const int ECHO_PIN = 26;
```

Recommended wiring:

```txt
HC-SR04 VCC  -> ESP32 5V
HC-SR04 GND  -> ESP32 GND
HC-SR04 TRIG -> ESP32 GPIO 27
HC-SR04 ECHO -> ESP32 GPIO 26 through a voltage divider
```

Important note: the HC-SR04 ECHO pin usually outputs a 5V signal, while the ESP32 uses 3.3V logic. Use a voltage divider so the ECHO signal is lowered to a safe level for the ESP32.

## 12. ESP32 Sketch Configuration for MQTT

The ESP32 sketch must be adjusted to use MQTT. A commonly used library is:

```cpp
#include <WiFi.h>
#include <PubSubClient.h>
#include <time.h>
```

The main sketch configuration should include:

```cpp
const char* WIFI_SSID = "wifi_name";
const char* WIFI_PASSWORD = "wifi_password";

const char* MQTT_HOST = "192.168.1.10";
const int MQTT_PORT = 1883;

const char* TOPIC_VEHICLE = "carwash/iot/vehicle-detections";
const char* TOPIC_HEARTBEAT = "carwash/iot/sensors/heartbeat";

const String SENSOR_CODE = "ENTRANCE-001";
const int LOCATION_ID = 1;
```

Make sure `MQTT_HOST` uses the IP address of the computer or server where the MQTT broker is running. Do not use `127.0.0.1` on the ESP32, because `127.0.0.1` points to the ESP32 itself, not to your laptop or server.

The ESP32 logic remains the same:

- Connect to WiFi.
- Connect to the MQTT broker.
- Synchronize time using NTP.
- Read distance from the HC-SR04 sensor.
- If the distance is inside the threshold, apply debounce.
- If the detection is valid, create a vehicle event.
- Publish the payload to `carwash/iot/vehicle-detections`.
- Send periodic heartbeat messages to `carwash/iot/sensors/heartbeat`.
- Reconnect automatically if the connection drops.
- If publishing fails, store the event temporarily in a buffer and retry when the connection is restored.

## 13. MQTT Testing

Before using the ESP32, test the broker with Mosquitto clients.

Terminal 1, subscribe to the vehicle detection topic:

```bash
mosquitto_sub -h 127.0.0.1 -p 1883 -t carwash/iot/vehicle-detections -v
```

Terminal 2, publish a test payload:

```bash
mosquitto_pub -h 127.0.0.1 -p 1883 -t carwash/iot/vehicle-detections -m "{\"sensor_code\":\"ENTRANCE-001\",\"location_id\":1,\"entry_time\":\"2026-05-30 10:00:00\",\"vehicle_count\":1,\"detection_confidence\":98.75,\"raw_distance\":35,\"device_event_id\":\"MQTT-TEST-001\"}"
```

For heartbeat testing:

```bash
mosquitto_sub -h 127.0.0.1 -p 1883 -t carwash/iot/sensors/heartbeat -v
```

Publish a heartbeat test payload:

```bash
mosquitto_pub -h 127.0.0.1 -p 1883 -t carwash/iot/sensors/heartbeat -m "{\"sensor_code\":\"ENTRANCE-001\",\"status\":\"active\",\"last_distance\":120.5}"
```

If the bridge is already running, the test data should be forwarded to the backend and appear on the dashboard after the frontend refreshes its data.

## 14. Backend API Testing

Even though the hardware uses MQTT, the backend HTTP endpoints are still useful for manual testing and for the MQTT bridge.

Test login:

```bash
curl -X POST http://127.0.0.1:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d "{\"email\":\"admin@carwash.test\",\"password\":\"password123\"}"
```

Test vehicle detection directly to the backend:

```bash
curl -X POST http://127.0.0.1:8000/api/v1/iot/vehicle-detections \
  -H "Content-Type: application/json" \
  -H "X-DEVICE-KEY: sensor_device_secret_key" \
  -d "{\"sensor_code\":\"ENTRANCE-001\",\"location_id\":1,\"entry_time\":\"2026-05-30 10:00:00\",\"vehicle_count\":1,\"detection_confidence\":98.75,\"raw_distance\":35,\"device_event_id\":\"HTTP-TEST-001\"}"
```

Test heartbeat directly to the backend:

```bash
curl -X POST http://127.0.0.1:8000/api/v1/iot/sensors/heartbeat \
  -H "Content-Type: application/json" \
  -H "X-DEVICE-KEY: sensor_device_secret_key" \
  -d "{\"sensor_code\":\"ENTRANCE-001\",\"status\":\"active\",\"last_distance\":120.5}"
```

## 15. Running All Components During Development

During local development, run each component in a separate terminal.

Terminal 1, Laravel backend:

```bash
cd backend
php artisan serve
```

Terminal 2, Vue frontend:

```bash
cd frontend
npm run dev
```

Terminal 3, MQTT broker:

```bash
mosquitto -v
```

Terminal 4, MQTT bridge or worker:

```bash
# Run the bridge script according to the implementation you use
```

After that, upload the MQTT-based sketch to the ESP32 through Arduino IDE. Open Serial Monitor to check the WiFi status, MQTT status, sensor distance, vehicle events, heartbeat messages, and retry buffer.

## 16. Troubleshooting

If the frontend cannot fetch data, make sure the backend is running at `http://127.0.0.1:8000` and `VITE_API_BASE_URL` is correct. If login fails, make sure `php artisan migrate --seed` has been executed and use one of the seeded accounts. If migrations fail, check the PostgreSQL connection in the `.env` file.

If the ESP32 cannot connect to the MQTT broker, make sure the ESP32 and broker are on the same WiFi network, use the correct broker IP address, and ensure port `1883` is not blocked by the firewall. Do not use `127.0.0.1` as the MQTT host on the ESP32. If MQTT payloads are published but do not enter the database, check whether the bridge is running, whether the subscribed topics are correct, and whether Laravel rejects the payload because of an invalid `sensor_code`, inactive sensor, mismatched `location_id`, `raw_distance` above the threshold, or duplicate `device_event_id`.

If vehicles are counted multiple times, increase the cooldown or debounce value in the ESP32 sketch. If vehicles are not detected, check the HC-SR04 wiring, the voltage divider on the ECHO pin, the threshold distance, and the sensor position relative to the vehicle entry lane.

## 17. Production Notes

For production, use HTTPS for the backend and frontend, enable MQTT authentication if the broker is accessible outside the local network, use username/password or certificates for the MQTT broker, restrict publish/subscribe permissions per device, and avoid hardcoding WiFi credentials or secrets in a public repository. The backend should be served through a web server such as Nginx or Apache with PHP-FPM, while the frontend can be served from the `frontend/dist` build output.

Also make sure Laravel Scheduler is active so daily reports are generated automatically. For stability, the MQTT bridge should run as a managed service using Supervisor, systemd, PM2, or another service manager that fits the server platform.
