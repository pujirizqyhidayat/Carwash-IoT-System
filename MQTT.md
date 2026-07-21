# PRD - Migrasi Komunikasi ESP32 ke MQTT (HiveMQ Cloud)

## 1. Tujuan

Migrasikan jalur komunikasi perangkat ESP32 ke backend Laravel dari HTTP POST menjadi MQTT melalui HiveMQ Cloud.

Migrasi ini hanya mengganti lapisan komunikasi perangkat. Struktur database, REST API dashboard, fitur CRUD, role, autentikasi user, dan tampilan Vue harus tetap berjalan seperti kondisi repo saat ini.

## 2. Kondisi Repo Saat Ini

### Struktur aplikasi

- Backend: Laravel 10, berada di `backend/`.
- Frontend: Vue, berada di `frontend/`.
- Firmware/prototype ESP32: `sketch_jun6a/sketch_jun6a.ino`.
- Database: PostgreSQL.

### Komunikasi perangkat yang aktif

Route aktif untuk perangkat berada di:

```text
POST /api/v1/iot/vehicle-detections
POST /api/v1/iot/sensors/heartbeat
```

Route tersebut didefinisikan di `backend/routes/api.php` dan memakai middleware `device.key`, sehingga HTTP request wajib membawa header:

```text
X-DEVICE-KEY: <IOT_DEVICE_KEY>
```

Controller aktifnya adalah:

```text
backend/app/Http/Controllers/Api/V1/IoTController.php
```

Catatan: ada juga `backend/app/Http/Controllers/Api/VehicleDetectionController.php`, tetapi file itu tidak dipakai oleh route API aktif saat ini.

### Status firmware

`sketch_jun6a/sketch_jun6a.ino` saat ini baru:

- membaca dua sensor ultrasonic kiri dan kanan,
- melakukan filtering distance,
- menentukan `VEHICLE DETECTED` saat kedua sensor stabil terhalang,
- menulis hasil ke Serial Monitor.

Firmware saat ini belum memiliki kode WiFi, HTTP POST, MQTT publish, ataupun payload JSON ke backend.

### Data default

Seeder saat ini membuat:

- lokasi: `Rizki Car Wash`,
- sensor: `ENTRANCE-001`,
- posisi sensor: `entry`,
- status sensor: `active`,
- threshold distance: `40`.

Walaupun prototype memakai sensor fisik kiri dan kanan, database backend saat ini merepresentasikan event kendaraan melalui satu record sensor, yaitu `sensor_code`.

## 3. Arsitektur Saat Ini

```text
ESP32 prototype
    |
Serial Monitor only

Existing HTTP API contract in backend:

ESP32 / external device
    |
HTTP POST + X-DEVICE-KEY
    |
Laravel API /api/v1/iot/*
    |
IoTController
    |
VehicleEntry, SensorRawLog, AuditLog, UltrasonicSensor
    |
PostgreSQL
    |
REST API
    |
Vue Dashboard
```

## 4. Arsitektur Target

```text
ESP32
    |
MQTT Publish
    |
HiveMQ Cloud
    |
Laravel MQTT subscriber command
    |
Shared IoT service
    |
VehicleEntry, SensorRawLog, AuditLog, UltrasonicSensor
    |
PostgreSQL
    |
Existing REST API
    |
Existing Vue Dashboard
```

Frontend Vue tidak perlu diubah karena dashboard tetap membaca data dari REST API yang sudah ada.

## 5. Scope Implementasi

Termasuk:

- menambahkan MQTT client Laravel,
- menambahkan konfigurasi MQTT di `.env.example` dan `config/mqtt.php`,
- membuat command subscriber `php artisan mqtt:listen`,
- menerima message dari HiveMQ Cloud,
- validasi payload JSON,
- menjalankan business logic yang sama dengan endpoint HTTP saat ini,
- update `vehicle_entries`, `sensor_raw_logs`, `audit_logs`, dan `ultrasonic_sensors` sesuai flow existing,
- logging error dan event penting,
- reconnect subscriber saat koneksi broker terputus.

Tidak termasuk:

- perubahan frontend Vue,
- perubahan schema database atau migration,
- perubahan CRUD user, role, sensor, lokasi, monitoring, report, audit log,
- perubahan autentikasi user / RBAC,
- redesign dashboard.

## 6. Batasan Wajib

Jangan mengubah:

- file migration yang sudah ada,
- nama tabel,
- nama kolom,
- foreign key,
- endpoint REST dashboard/frontend,
- kontrak response endpoint existing.

Tidak perlu membuat migration baru.

## 7. Package MQTT

Gunakan package Laravel/PHP MQTT yang stabil dan mendukung TLS untuk HiveMQ Cloud. Rekomendasi:

```bash
composer require php-mqtt/client
```

Package tersebut umum dipakai untuk MQTT v3.1.1/v3.1, TLS, QoS, clean session, dan loop subscriber di aplikasi PHP/Laravel.

## 8. Konfigurasi

Tambahkan ke `backend/.env.example` dan `.env` lokal:

```env
MQTT_HOST=
MQTT_PORT=8883
MQTT_USERNAME=
MQTT_PASSWORD=
MQTT_CLIENT_ID=carwash-backend-subscriber
MQTT_TLS=true
MQTT_CLEAN_SESSION=true
MQTT_QOS=1
MQTT_TOPIC_DETECTIONS=parking/+/vehicle-detections
MQTT_TOPIC_HEARTBEATS=parking/+/sensors/heartbeat
```

Buat file:

```text
backend/config/mqtt.php
```

Tidak boleh ada credential HiveMQ yang hardcoded di source code.

## 9. Topic Convention

Gunakan topic per lokasi agar tetap bisa berkembang ke banyak lokasi.

```text
parking/{locationId}/vehicle-detections
parking/{locationId}/sensors/heartbeat
```

Contoh:

```text
parking/1/vehicle-detections
parking/1/sensors/heartbeat
```

Subscriber Laravel harus subscribe ke:

```text
parking/+/vehicle-detections
parking/+/sensors/heartbeat
```

`locationId` di topic harus divalidasi terhadap `location_id` di payload.

## 10. Payload MQTT

Payload MQTT harus mengikuti kontrak backend yang sudah ada, bukan format lama `left_distance/right_distance/vehicle_present`.

### Vehicle detection

Topic:

```text
parking/{locationId}/vehicle-detections
```

Payload:

```json
{
  "sensor_code": "ENTRANCE-001",
  "location_id": 1,
  "entry_time": "2026-07-21 15:00:00",
  "vehicle_count": 1,
  "detection_confidence": 98.75,
  "raw_distance": 35,
  "device_event_id": "ESP32-001-20260721150000"
}
```

Field wajib:

- `sensor_code`
- `location_id`
- `entry_time`
- `vehicle_count`
- `device_event_id`

Field opsional:

- `detection_confidence`
- `raw_distance`

### Heartbeat

Topic:

```text
parking/{locationId}/sensors/heartbeat
```

Payload:

```json
{
  "sensor_code": "ENTRANCE-001",
  "status": "active",
  "last_distance": 35
}
```

Field wajib:

- `sensor_code`
- `status`

Field opsional:

- `last_distance`

`status` hanya boleh bernilai:

- `active`
- `inactive`
- `disconnected`

## 11. Business Logic yang Harus Dipertahankan

Logic endpoint `vehicleDetections` saat ini:

1. validasi payload,
2. cari sensor berdasarkan `sensor_code`,
3. pastikan sensor milik `location_id` yang dikirim,
4. pastikan status sensor `active`,
5. jika sensor memiliki `threshold_distance`, validasi `raw_distance`,
6. tolak duplicate `device_event_id`,
7. buat `VehicleEntry`,
8. jika ada `raw_distance`, buat `SensorRawLog`,
9. buat `AuditLog`,
10. hitung ulang `vehicles_today`.

Logic endpoint `heartbeat` saat ini:

1. validasi payload,
2. cari sensor berdasarkan `sensor_code`,
3. update `status` dan `last_seen_at`.

Implementasi MQTT harus memakai logic yang sama. Jangan menyalin logic panjang ke command subscriber. Ekstrak logic ke service agar HTTP controller dan MQTT subscriber memakai jalur yang sama.

## 12. Struktur Kode Target

Tambahkan struktur berikut:

```text
backend/
|-- app/
|   |-- Console/
|   |   `-- Commands/
|   |       `-- MqttListenCommand.php
|   |-- MQTT/
|   |   `-- ParkingMqttSubscriber.php
|   `-- Services/
|       `-- IoTIngestionService.php
`-- config/
    `-- mqtt.php
```

Refactor `IoTController` agar:

- `vehicleDetections()` memanggil `IoTIngestionService::storeVehicleDetection()`,
- `heartbeat()` memanggil `IoTIngestionService::storeHeartbeat()`,
- response HTTP tetap sama seperti sekarang.

`MqttListenCommand` hanya bertugas menjalankan subscriber.

`ParkingMqttSubscriber` bertugas:

- connect ke HiveMQ,
- subscribe topic,
- decode payload,
- validasi topic dan JSON,
- panggil `IoTIngestionService`.

`IoTIngestionService` menjadi tempat business logic bersama.

## 13. Command Subscriber

Command:

```bash
php artisan mqtt:listen
```

Perilaku command:

- connect ke HiveMQ Cloud,
- subscribe ke detection dan heartbeat topic,
- berjalan terus sampai dihentikan manual,
- reconnect saat koneksi gagal,
- log error tanpa menghentikan proses,
- tidak membuat perubahan schema.

Untuk production, jalankan command melalui process manager seperti Supervisor.

## 14. Error Handling

Tangani kondisi berikut:

- broker tidak dapat diakses,
- TLS/credential salah,
- timeout koneksi,
- topic tidak sesuai format,
- `location_id` topic berbeda dengan payload,
- JSON invalid,
- payload tidak valid,
- `sensor_code` tidak ditemukan,
- sensor tidak aktif,
- jarak melebihi threshold,
- duplicate `device_event_id`,
- reconnect gagal.

Error harus masuk log dan subscriber tetap hidup.

## 15. Logging

Log minimal:

- berhasil connect ke HiveMQ Cloud,
- disconnect/reconnect,
- message diterima beserta topic,
- payload invalid,
- vehicle detection tersimpan,
- heartbeat tersimpan,
- duplicate event,
- sensor invalid/inactive.

Gunakan Laravel logger. Jangan log password atau credential MQTT.

## 16. Firmware ESP32

Firmware perlu ditambah tahap berikut:

- koneksi WiFi,
- MQTT client TLS ke HiveMQ Cloud,
- publish event saat `VEHICLE DETECTED`,
- publish heartbeat berkala,
- generate `device_event_id` unik,
- mapping hasil dua sensor fisik menjadi satu event backend:
  - `sensor_code`: misalnya `ENTRANCE-001`,
  - `raw_distance`: bisa memakai rata-rata/minimum distance yang valid,
  - `detection_confidence`: bisa dihitung dari stabilitas pembacaan.

Business rule deteksi kiri/kanan di firmware boleh tetap seperti sekarang, karena backend saat ini hanya menyimpan event kendaraan, bukan menyimpan `left_distance` dan `right_distance` sebagai kolom terpisah.

## 17. Testing

Tambahkan atau sesuaikan test backend untuk memastikan:

- HTTP endpoint existing tetap lulus,
- service menolak duplicate `device_event_id`,
- service menolak sensor inactive,
- service menolak jarak di luar threshold,
- MQTT subscriber memanggil service saat payload valid,
- MQTT subscriber menolak JSON/topic/payload invalid.

Test yang sudah ada di `backend/tests/Feature/ApiRequirementTest.php` harus tetap lulus.

## 18. Acceptance Criteria

- Laravel 10 backend dapat connect ke HiveMQ Cloud.
- Command `php artisan mqtt:listen` tersedia.
- Subscriber menerima topic `parking/+/vehicle-detections`.
- Subscriber menerima topic `parking/+/sensors/heartbeat`.
- Payload MQTT mengikuti kontrak field backend existing.
- Business logic HTTP dan MQTT memakai service yang sama.
- Duplicate `device_event_id` tetap ditolak.
- Threshold sensor tetap divalidasi.
- Data masuk ke tabel existing tanpa migration baru.
- REST API existing tetap berjalan.
- Vue frontend tetap berjalan tanpa perubahan.
- Test existing tetap lulus.

## 19. Catatan Implementasi

- Dokumen lama menyebut Laravel 12; repo saat ini memakai Laravel 10.
- Dokumen lama menyebut `left_distance`, `right_distance`, dan `vehicle_present`; field itu tidak sesuai schema/backend saat ini.
- Dokumen lama menyebut `Left Sensor` dan `Right Sensor` sebagai sensor backend; repo saat ini hanya seed `ENTRANCE-001`.
- Jangan hardcode `location_id = 1` di business logic. Pakai payload dan validasi ke sensor/lokasi yang ada.
- Jika nanti ingin menyimpan distance kiri/kanan sebagai data historis terpisah, itu membutuhkan perubahan schema dan berada di luar scope migrasi MQTT ini.
