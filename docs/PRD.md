# Development Scope  
## Implementation of an IoT-Based Vehicle Counting System with Secure Web Dashboard for Carwash Operations

**Source:**  
- F2 Capstone Proposal FINAL.pdf  
- F3 CAPSTONE REVISI.pdf  

Dokumen ini merangkum kebutuhan development aplikasi berdasarkan Form 2 dan Form 3 agar kerja sama antara **backend developer**, **frontend developer**, dan **IoT developer** tetap jelas dan tidak keluar dari scope.

---

# 1. Project Overview

Project ini adalah **IoT-Based Vehicle Counting System with Secure Web Dashboard for Carwash Operations**.

Tujuan utama sistem adalah menghitung kendaraan yang masuk ke area carwash secara otomatis menggunakan **ESP32** dan **ultrasonic sensor**, lalu data tersebut dikirim ke server dan ditampilkan melalui **web dashboard** yang aman.

Sistem ini dibuat untuk membantu owner carwash melakukan monitoring operasional tanpa harus selalu berada di lokasi. Dengan sistem ini, owner dapat melihat jumlah kendaraan harian, riwayat kendaraan, laporan operasional, dan status sensor melalui dashboard.

## Masalah yang Diselesaikan

- Owner masih perlu melakukan pengawasan langsung di lokasi.
- Pencatatan kendaraan berpotensi manual dan rawan human error.
- Ada risiko manipulasi data operasional.
- Owner membutuhkan data real-time dan laporan harian.
- Operasional carwash diharapkan dapat berjalan lebih mandiri atau semi auto-pilot.

## Alur Sistem Utama

1. Kendaraan masuk ke area carwash.
2. Ultrasonic sensor mendeteksi objek kendaraan.
3. ESP32 membaca data sensor dan memvalidasi threshold jarak.
4. ESP32 mengirim data deteksi ke backend melalui HTTP/HTTPS.
5. Backend memvalidasi data yang diterima.
6. Data kendaraan disimpan ke PostgreSQL.
7. Dashboard menampilkan jumlah kendaraan secara real-time atau auto-refresh.
8. Sistem membuat rekap harian otomatis.
9. Aktivitas penting user dan sistem dicatat ke audit log.

## Fokus MVP

Fokus MVP adalah:

> Sensor menghitung kendaraan → data masuk database → dashboard menampilkan data → report dapat diexport → aktivitas tercatat aman.

---

# 2. Tech Stack

## 2.1 Hardware / IoT

| Komponen | Fungsi |
|---|---|
| ESP32 DevKit V1 | Microcontroller utama untuk membaca sensor dan mengirim data melalui WiFi |
| Ultrasonic Sensor / HC-SR04 | Mendeteksi keberadaan kendaraan berdasarkan jarak |
| Power Supply 5V | Menyediakan daya untuk ESP32 dan sensor |
| Kabel jumper | Menghubungkan ESP32 dengan sensor |
| WiFi Network | Menghubungkan ESP32 ke backend server |

## 2.2 Backend

| Komponen | Fungsi |
|---|---|
| Laravel 10.x | Framework backend utama |
| PHP 8.x | Bahasa pemrograman backend |
| REST API | Komunikasi antara ESP32, frontend, dan backend |
| Laravel Eloquent ORM | Pengelolaan query database |
| Laravel Authentication | Login, session, dan role-based access |
| DomPDF | Export laporan ke PDF |
| Laravel Excel | Export laporan ke Excel |
| Queue / Cache | Disarankan untuk report besar, high traffic, dan auto-refresh dashboard |

## 2.3 Frontend

| Komponen | Fungsi |
|---|---|
| Vue.js | Membangun dashboard web interaktif |
| JavaScript ES6 | Logic frontend dan dynamic update |
| HTML/CSS | Struktur dan styling halaman |
| Browser Chrome / Edge | Media akses dashboard |

## 2.4 Database

| Komponen | Fungsi |
|---|---|
| PostgreSQL 14 | Database utama |
| SQL Relational Schema | Struktur data berbasis tabel dan relasi |
| JSONB | Penyimpanan metadata audit atau payload sensor |

## 2.5 Security & DevOps

| Komponen | Fungsi |
|---|---|
| HTTPS | Mengamankan komunikasi data |
| SSH | Remote server access dan server management |
| Linux Auditd | Monitoring dan logging aktivitas sistem |
| Git / GitHub | Version control dan collaboration |
| VS Code | IDE development |
| Arduino IDE | Upload program ke ESP32 |

---

# 3. User Roles

## 3.1 Owner / President Director

Owner adalah user utama yang menggunakan dashboard untuk monitoring bisnis.

### Hak Akses

- Login ke dashboard.
- Melihat jumlah kendaraan hari ini.
- Melihat historical vehicle count.
- Melihat daily report.
- Export report ke PDF dan Excel.
- Melihat performa bisnis.
- Monitoring status sensor.
- Melihat ringkasan aktivitas operasional.

### Menu yang Diakses

- Dashboard
- Daily Report
- Historical Data
- Monitoring View
- Export Report

---

## 3.2 Cashier / Staff / Operator

Cashier atau staff menggunakan sistem untuk melihat data kendaraan harian dan membandingkannya dengan transaksi operasional.

### Hak Akses

- Login ke dashboard.
- Melihat jumlah kendaraan hari ini.
- Melihat basic report.
- Melihat status sensor.
- Tidak dapat mengubah konfigurasi sistem.
- Tidak dapat mengakses audit log penuh.
- Tidak dapat melakukan user management.

### Menu yang Diakses

- Cashier Monitoring View
- Daily Count Display
- Basic Report

---

## 3.3 System Admin

System Admin bertanggung jawab terhadap konfigurasi teknis sistem.

### Hak Akses

- Login sebagai admin.
- Manage user.
- Manage sensor.
- Manage location.
- Manage system configuration.
- View audit log.
- Review failed access dan security alert.
- Monitor database operation melalui aplikasi.
- Manage server access melalui SSH.

### Menu yang Diakses

- User Management
- Sensor Management
- Location Management
- Audit Log
- System Configuration
- Database Management

---

## 3.4 ESP32 / IoT Device

ESP32 bukan user manusia, tetapi dianggap sebagai aktor sistem.

### Fungsi

- Membaca data ultrasonic sensor.
- Menghitung perubahan jarak.
- Menentukan valid vehicle detection.
- Mengirim data detection ke backend.
- Mengirim heartbeat sensor.
- Menyimpan data sementara jika WiFi terputus.
- Mengirim ulang data ketika koneksi pulih.

---

# 4. Functional Requirements

## 4.1 Authentication & Authorization

| Kode | Requirement |
|---|---|
| FR-AUTH-01 | User dapat login menggunakan email/username dan password |
| FR-AUTH-02 | Sistem memvalidasi credential user |
| FR-AUTH-03 | Sistem membedakan role Owner, Cashier, dan Admin |
| FR-AUTH-04 | User tanpa login diarahkan ke login page |
| FR-AUTH-05 | Role yang tidak sesuai tidak boleh mengakses menu restricted |
| FR-AUTH-06 | Sistem mendukung session timeout |
| FR-AUTH-07 | Sistem mencatat login success dan login failed ke audit log |

---

## 4.2 Vehicle Detection

| Kode | Requirement |
|---|---|
| FR-IOT-01 | Sensor membaca jarak secara terus-menerus |
| FR-IOT-02 | ESP32 menentukan apakah objek masuk threshold kendaraan |
| FR-IOT-03 | Sistem menggunakan debounce/cooldown agar kendaraan berhenti tidak dihitung berkali-kali |
| FR-IOT-04 | ESP32 mengirim detection data ke backend |
| FR-IOT-05 | Jika WiFi putus, data disimpan sementara di device |
| FR-IOT-06 | Jika koneksi pulih, data dikirim ulang |
| FR-IOT-07 | Sistem menolak duplicate detection |

---

## 4.3 Vehicle Entry Data

| Kode | Requirement |
|---|---|
| FR-VEH-01 | Backend menerima data kendaraan dari ESP32 |
| FR-VEH-02 | Backend memvalidasi sensor_id, location_id, timestamp, dan detection confidence |
| FR-VEH-03 | Backend menyimpan setiap kendaraan sebagai satu vehicle_entry |
| FR-VEH-04 | Backend menambah vehicle_count |
| FR-VEH-05 | Backend mengupdate dashboard secara real-time atau periodic refresh |
| FR-VEH-06 | Backend mencatat proses insert ke audit log |

---

## 4.4 Dashboard

| Kode | Requirement |
|---|---|
| FR-DASH-01 | User dapat melihat jumlah kendaraan hari ini |
| FR-DASH-02 | User dapat melihat total kendaraan minggu ini |
| FR-DASH-03 | User dapat melihat total kendaraan bulan ini |
| FR-DASH-04 | User dapat melihat status sensor: active, inactive, atau disconnected |
| FR-DASH-05 | Dashboard menampilkan recent activity |
| FR-DASH-06 | Dashboard auto-refresh tanpa reload manual |
| FR-DASH-07 | Jika sensor offline, dashboard menampilkan “Sensor Disconnected” |

---

## 4.5 Report

| Kode | Requirement |
|---|---|
| FR-REP-01 | Owner dapat memilih date range laporan |
| FR-REP-02 | Sistem mengambil data vehicle_entry berdasarkan tanggal |
| FR-REP-03 | Sistem membuat daily summary |
| FR-REP-04 | Sistem menampilkan laporan di dashboard |
| FR-REP-05 | Sistem dapat export laporan ke PDF |
| FR-REP-06 | Sistem dapat export laporan ke Excel |
| FR-REP-07 | Jika tidak ada data, sistem menampilkan “No data available” |
| FR-REP-08 | Sistem melakukan rekap otomatis harian, idealnya pada jam 21:00 |

---

## 4.6 Sensor Management

| Kode | Requirement |
|---|---|
| FR-SEN-01 | Admin dapat melihat daftar sensor |
| FR-SEN-02 | Admin dapat menambah sensor |
| FR-SEN-03 | Admin dapat mengubah nama sensor |
| FR-SEN-04 | Admin dapat mengubah posisi sensor |
| FR-SEN-05 | Admin dapat melihat status sensor |
| FR-SEN-06 | Admin dapat menonaktifkan sensor |
| FR-SEN-07 | Perubahan sensor dicatat ke audit log |

---

## 4.7 User Management

| Kode | Requirement |
|---|---|
| FR-USER-01 | Admin dapat melihat daftar user |
| FR-USER-02 | Admin dapat membuat user baru |
| FR-USER-03 | Admin dapat mengubah role user |
| FR-USER-04 | Admin dapat reset password user |
| FR-USER-05 | Admin dapat menonaktifkan user |
| FR-USER-06 | Semua perubahan user dicatat ke audit log |

---

## 4.8 Audit Log

| Kode | Requirement |
|---|---|
| FR-AUD-01 | Sistem mencatat aktivitas login |
| FR-AUD-02 | Sistem mencatat create, update, dan delete data penting |
| FR-AUD-03 | Sistem mencatat failed access |
| FR-AUD-04 | Admin dapat filter audit log berdasarkan user, action, dan timestamp |
| FR-AUD-05 | Admin dapat export audit log jika dibutuhkan |
| FR-AUD-06 | Audit log tidak boleh dihapus oleh non-admin |

---

# 5. Database Schema

Berikut adalah database schema yang disarankan agar sesuai dengan kebutuhan F2/F3 dan siap digunakan untuk development.

---

## 5.1 users

Menyimpan data user dashboard.

| Field | Type | Key | Description |
|---|---|---|---|
| id | BIGSERIAL | PK | User ID |
| full_name | VARCHAR(150) |  | Nama lengkap user |
| username | VARCHAR(100) | UNIQUE | Username login |
| email | VARCHAR(150) | UNIQUE | Email login |
| password | VARCHAR(255) |  | Hashed password |
| role | VARCHAR(50) | INDEX | owner, cashier, admin |
| status | VARCHAR(20) |  | active, inactive |
| last_login_at | TIMESTAMP | nullable | Login terakhir |
| created_at | TIMESTAMP |  | Created time |
| updated_at | TIMESTAMP |  | Updated time |

---

## 5.2 parking_locations

Menyimpan data lokasi carwash.

| Field | Type | Key | Description |
|---|---|---|---|
| id | BIGSERIAL | PK | Location ID |
| owner_id | BIGINT | FK users.id | Owner lokasi |
| location_name | VARCHAR(150) |  | Nama carwash |
| address | TEXT |  | Alamat |
| capacity | INT | nullable | Kapasitas kendaraan |
| created_at | TIMESTAMP |  | Created time |
| updated_at | TIMESTAMP |  | Updated time |

---

## 5.3 ultrasonic_sensors

Menyimpan data sensor ultrasonic.

| Field | Type | Key | Description |
|---|---|---|---|
| id | BIGSERIAL | PK | Sensor ID |
| location_id | BIGINT | FK parking_locations.id | Lokasi sensor |
| sensor_name | VARCHAR(150) |  | Nama sensor |
| sensor_code | VARCHAR(100) | UNIQUE | Kode unik ESP32/sensor |
| sensor_position | VARCHAR(50) |  | entry atau exit |
| status | VARCHAR(50) | INDEX | active, inactive, disconnected |
| threshold_distance | DECIMAL(8,2) | nullable | Threshold jarak deteksi |
| installed_at | TIMESTAMP | nullable | Waktu instalasi sensor |
| last_seen_at | TIMESTAMP | nullable | Terakhir sensor mengirim heartbeat |
| created_at | TIMESTAMP |  | Created time |
| updated_at | TIMESTAMP |  | Updated time |

---

## 5.4 vehicle_entries

Menyimpan setiap event kendaraan yang terdeteksi.

| Field | Type | Key | Description |
|---|---|---|---|
| id | BIGSERIAL | PK | Entry ID |
| location_id | BIGINT | FK parking_locations.id | Lokasi kendaraan masuk |
| sensor_id | BIGINT | FK ultrasonic_sensors.id | Sensor pendeteksi |
| entry_time | TIMESTAMP | INDEX | Waktu kendaraan terdeteksi |
| vehicle_count | INT | default 1 | Jumlah kendaraan per event |
| detection_confidence | DECIMAL(5,2) | nullable | Confidence detection |
| raw_distance | DECIMAL(8,2) | nullable | Data jarak sensor |
| device_event_id | VARCHAR(150) | UNIQUE nullable | ID unik dari ESP32 untuk anti-duplicate |
| created_at | TIMESTAMP |  | Stored time |
| updated_at | TIMESTAMP |  | Updated time |

---

## 5.5 vehicle_count_summaries

Menyimpan rekap kendaraan harian.

| Field | Type | Key | Description |
|---|---|---|---|
| id | BIGSERIAL | PK | Summary ID |
| location_id | BIGINT | FK parking_locations.id | Lokasi carwash |
| summary_date | DATE | INDEX | Tanggal summary |
| total_vehicle | INT |  | Total kendaraan harian |
| generated_by | BIGINT | FK users.id nullable | User/system yang generate |
| generated_at | TIMESTAMP |  | Waktu generate |
| created_at | TIMESTAMP |  | Created time |
| updated_at | TIMESTAMP |  | Updated time |

### Constraint

```sql
UNIQUE(location_id, summary_date)
```

---

## 5.6 audit_logs

Menyimpan aktivitas user dan sistem.

| Field | Type | Key | Description |
|---|---|---|---|
| id | BIGSERIAL | PK | Audit ID |
| user_id | BIGINT | FK users.id nullable | User yang melakukan aksi |
| action | VARCHAR(100) | INDEX | login, create, update, delete, export, failed_access |
| module | VARCHAR(100) | INDEX | dashboard, report, sensor, user, auth, database |
| description | TEXT |  | Detail aktivitas |
| ip_address | VARCHAR(50) | nullable | IP user |
| user_agent | TEXT | nullable | Browser/device info |
| status | VARCHAR(50) |  | success, failed, warning |
| metadata | JSONB | nullable | Data tambahan |
| created_at | TIMESTAMP | INDEX | Waktu log |

---

## 5.7 sensor_raw_logs Optional

Tabel ini bersifat optional, tetapi disarankan untuk debugging IoT.

| Field | Type | Key | Description |
|---|---|---|---|
| id | BIGSERIAL | PK | Raw log ID |
| sensor_id | BIGINT | FK ultrasonic_sensors.id | Sensor |
| distance_value | DECIMAL(8,2) |  | Nilai jarak |
| is_detected | BOOLEAN |  | Status deteksi |
| payload | JSONB |  | Payload asli dari ESP32 |
| received_at | TIMESTAMP |  | Waktu diterima |

---

## 5.8 Relasi Utama

| Relasi | Cardinality |
|---|---|
| users → parking_locations | One to Many |
| parking_locations → ultrasonic_sensors | One to Many |
| parking_locations → vehicle_entries | One to Many |
| ultrasonic_sensors → vehicle_entries | One to Many |
| parking_locations → vehicle_count_summaries | One to Many |
| users → audit_logs | One to Many |
| users → vehicle_count_summaries | One to Many, nullable untuk system-generated summary |

---

# 6. API Endpoint dari Setiap Menu

Base URL:

```txt
/api/v1
```

Authentication:

- Dashboard web dapat menggunakan Laravel session-based authentication.
- ESP32 disarankan menggunakan device token atau API key melalui header `X-DEVICE-KEY`.

---

## 6.1 Auth Menu

### Login

```http
POST /api/v1/auth/login
```

Request:

```json
{
  "email": "owner@example.com",
  "password": "password"
}
```

Response:

```json
{
  "message": "Login success",
  "user": {
    "id": 1,
    "full_name": "Owner",
    "role": "owner"
  }
}
```

Access:

- Public

---

### Logout

```http
POST /api/v1/auth/logout
```

Access:

- owner
- cashier
- admin

---

### Get Current User

```http
GET /api/v1/auth/me
```

Access:

- owner
- cashier
- admin

---

## 6.2 Dashboard Menu

### Get Dashboard Summary

```http
GET /api/v1/dashboard/summary?location_id=1
```

Response:

```json
{
  "vehicles_today": 60,
  "vehicles_this_week": 312,
  "vehicles_this_month": 1248,
  "sensor_status": "active",
  "last_updated": "2026-02-17 08:45:11"
}
```

Access:

- owner
- cashier
- admin

---

### Get Recent Vehicle Activities

```http
GET /api/v1/dashboard/recent-activities?location_id=1&limit=10
```

Response:

```json
[
  {
    "entry_id": 900001,
    "sensor_name": "Entrance Sensor 1",
    "entry_time": "2026-02-17 08:45:10",
    "vehicle_count": 1
  }
]
```

Access:

- owner
- cashier
- admin

---

### Get Dashboard Chart

```http
GET /api/v1/dashboard/chart?location_id=1&period=daily
```

Query `period`:

```txt
hourly | daily | weekly | monthly
```

Access:

- owner
- admin

---

## 6.3 Cashier Monitoring View

### Get Today Count

```http
GET /api/v1/monitoring/today?location_id=1
```

Response:

```json
{
  "date": "2026-02-17",
  "vehicles_today": 60,
  "sensor_status": "active"
}
```

Access:

- cashier
- owner
- admin

---

### Get Hourly Breakdown

```http
GET /api/v1/monitoring/hourly?location_id=1&date=2026-02-17
```

Response:

```json
[
  {
    "hour": "08:00",
    "total_vehicle": 12
  },
  {
    "hour": "09:00",
    "total_vehicle": 18
  }
]
```

Access:

- cashier
- owner
- admin

---

## 6.4 IoT Vehicle Detection API

Endpoint ini digunakan oleh ESP32.

### Send Vehicle Detection

```http
POST /api/v1/iot/vehicle-detections
```

Headers:

```http
X-DEVICE-KEY: sensor_device_secret_key
Content-Type: application/json
```

Request:

```json
{
  "sensor_code": "ENTRANCE-001",
  "location_id": 1,
  "entry_time": "2026-02-17 08:45:10",
  "vehicle_count": 1,
  "detection_confidence": 98.75,
  "raw_distance": 35.5,
  "device_event_id": "ESP32-ENTRANCE-001-20260217084510"
}
```

Response:

```json
{
  "message": "Vehicle entry stored",
  "entry_id": 900001,
  "vehicles_today": 60
}
```

Validation:

- `sensor_code` wajib valid.
- `device_event_id` tidak boleh duplicate.
- `raw_distance` harus masuk threshold.
- Sensor status harus active.

Access:

- ESP32 device only

---

### Sensor Heartbeat

```http
POST /api/v1/iot/sensors/heartbeat
```

Headers:

```http
X-DEVICE-KEY: sensor_device_secret_key
Content-Type: application/json
```

Request:

```json
{
  "sensor_code": "ENTRANCE-001",
  "status": "active",
  "last_distance": 120.5
}
```

Response:

```json
{
  "message": "Heartbeat received",
  "server_time": "2026-02-17 08:45:10"
}
```

Fungsi:

- Update `last_seen_at`.
- Menentukan status sensor active atau disconnected.

Access:

- ESP32 device only

---

## 6.5 Report Menu

### Get Report by Date Range

```http
GET /api/v1/reports?location_id=1&start_date=2026-02-01&end_date=2026-02-17
```

Response:

```json
[
  {
    "summary_date": "2026-02-17",
    "total_vehicle": 57,
    "generated_at": "2026-02-17 21:00:00"
  }
]
```

Access:

- owner
- admin

---

### Generate Daily Summary Manual

```http
POST /api/v1/reports/generate-daily
```

Request:

```json
{
  "location_id": 1,
  "summary_date": "2026-02-17"
}
```

Access:

- owner
- admin

Catatan backend:

- Auto-generate daily summary via Laravel Scheduler pada jam 21:00.
- Manual generate digunakan jika summary gagal atau perlu rekap ulang.

---

### Export PDF

```http
GET /api/v1/reports/export/pdf?location_id=1&start_date=2026-02-01&end_date=2026-02-17
```

Access:

- owner
- admin

---

### Export Excel

```http
GET /api/v1/reports/export/excel?location_id=1&start_date=2026-02-01&end_date=2026-02-17
```

Access:

- owner
- admin

---

## 6.6 Vehicle Entry Menu

### Get Vehicle Entries

```http
GET /api/v1/vehicle-entries?location_id=1&date=2026-02-17
```

Access:

- owner
- admin

---

### Get Vehicle Entry Detail

```http
GET /api/v1/vehicle-entries/{id}
```

Access:

- owner
- admin

---

### Delete Vehicle Entry

```http
DELETE /api/v1/vehicle-entries/{id}
```

Access:

- admin only

Catatan:

- Delete sebaiknya menggunakan soft delete.
- Semua perubahan harus masuk audit log.

---

## 6.7 Sensor Management Menu

### Get Sensor List

```http
GET /api/v1/sensors?location_id=1
```

Access:

- owner
- admin

---

### Create Sensor

```http
POST /api/v1/sensors
```

Request:

```json
{
  "location_id": 1,
  "sensor_name": "Entrance Sensor 1",
  "sensor_code": "ENTRANCE-001",
  "sensor_position": "entry",
  "threshold_distance": 40
}
```

Access:

- admin only

---

### Update Sensor

```http
PUT /api/v1/sensors/{id}
```

Request:

```json
{
  "sensor_name": "Entrance Sensor Updated",
  "sensor_position": "entry",
  "status": "active",
  "threshold_distance": 35
}
```

Access:

- admin only

---

### Delete / Deactivate Sensor

```http
DELETE /api/v1/sensors/{id}
```

Access:

- admin only

Catatan:

- Jangan hard delete untuk sensor yang pernah digunakan.
- Ubah status menjadi inactive.

---

### Get Sensor Status

```http
GET /api/v1/sensors/{id}/status
```

Response:

```json
{
  "sensor_id": 501,
  "sensor_name": "Entrance Sensor 1",
  "status": "active",
  "last_seen_at": "2026-02-17 08:45:10"
}
```

Access:

- owner
- cashier
- admin

---

## 6.8 User Management Menu

### Get Users

```http
GET /api/v1/users
```

Access:

- admin only

---

### Create User

```http
POST /api/v1/users
```

Request:

```json
{
  "full_name": "Cashier 1",
  "username": "cashier01",
  "email": "cashier@example.com",
  "password": "password",
  "role": "cashier"
}
```

Access:

- admin only

---

### Update User

```http
PUT /api/v1/users/{id}
```

Request:

```json
{
  "full_name": "Cashier Updated",
  "role": "cashier",
  "status": "active"
}
```

Access:

- admin only

---

### Reset Password

```http
POST /api/v1/users/{id}/reset-password
```

Request:

```json
{
  "new_password": "newpassword123"
}
```

Access:

- admin only

---

### Deactivate User

```http
DELETE /api/v1/users/{id}
```

Access:

- admin only

---

## 6.9 Location Management Menu

### Get Locations

```http
GET /api/v1/locations
```

Access:

- owner
- admin

---

### Create Location

```http
POST /api/v1/locations
```

Request:

```json
{
  "owner_id": 1,
  "location_name": "Rizki Car Wash",
  "address": "Jl. Sudirman No. 15",
  "capacity": 20
}
```

Access:

- admin only

---

### Update Location

```http
PUT /api/v1/locations/{id}
```

Access:

- admin only

---

### Delete / Deactivate Location

```http
DELETE /api/v1/locations/{id}
```

Access:

- admin only

---

## 6.10 Audit Log Menu

### Get Audit Logs

```http
GET /api/v1/audit-logs?module=sensor&action=update&start_date=2026-02-01&end_date=2026-02-17
```

Access:

- admin only

---

### Get Audit Log Detail

```http
GET /api/v1/audit-logs/{id}
```

Access:

- admin only

---

### Export Audit Logs

```http
GET /api/v1/audit-logs/export?start_date=2026-02-01&end_date=2026-02-17
```

Access:

- admin only

---

# 7. Scope Boundary Backend vs Frontend vs IoT

## 7.1 Backend In Scope

Backend wajib mengerjakan:

- Setup Laravel dan PostgreSQL.
- Database migration.
- Authentication dan role-based access.
- REST API untuk dashboard.
- API receiver untuk ESP32.
- Validasi duplicate detection.
- CRUD sensor.
- CRUD user.
- CRUD location.
- Vehicle entry storage.
- Daily summary scheduler.
- Report export PDF/Excel.
- Audit log.
- Sensor heartbeat/status.
- Error handling untuk database failure dan invalid payload.

---

## 7.2 Frontend In Scope

Frontend wajib mengerjakan:

- Login page.
- Owner dashboard.
- Cashier monitoring view.
- Daily report page.
- Historical report page.
- Sensor management page.
- User management page untuk admin.
- Audit log page.
- Export button PDF/Excel.
- Status indicator sensor.
- Warning message:
  - “Sensor Disconnected”
  - “No data available”
  - “Access denied”
  - “Invalid credentials”

---

## 7.3 IoT / ESP32 In Scope

IoT wajib mengerjakan:

- Membaca ultrasonic sensor.
- Menentukan threshold distance.
- Debounce/cooldown detection.
- Generate unique `device_event_id`.
- Mengirim data ke API backend.
- Mengirim heartbeat berkala.
- Buffer data jika WiFi putus.
- Reconnect WiFi otomatis.

---

# 8. MVP Development Priority

## Sprint 1 — Core Backend + Auth

- Setup Laravel + PostgreSQL.
- Buat migration database.
- Buat login/logout.
- Implement role-based access.
- Buat user seeder.
- Buat basic dashboard API.

---

## Sprint 2 — IoT Integration

- ESP32 membaca sensor.
- Endpoint vehicle detection.
- Store `vehicle_entries`.
- Duplicate prevention.
- Sensor heartbeat.

---

## Sprint 3 — Dashboard UI

- Login page.
- Owner dashboard.
- Cashier monitoring view.
- Real-time atau auto-refresh count.
- Sensor status display.

---

## Sprint 4 — Report

- Daily summary.
- Date range report.
- Export PDF.
- Export Excel.
- Scheduled summary jam 21:00.

---

## Sprint 5 — Admin & Security

- User management.
- Sensor management.
- Location management.
- Audit log.
- Unauthorized access handling.
- Session timeout.
- Testing edge cases.

---

# 9. In Scope dan Out of Scope

## 9.1 In Scope

Yang termasuk dalam development MVP:

- Menghitung jumlah kendaraan masuk.
- Monitoring jumlah kendaraan.
- Dashboard web.
- Report harian dan historical.
- Sensor management.
- User management.
- Location management.
- Audit log.
- Role-based access.
- Export PDF dan Excel.
- ESP32 sensor integration.
- Sensor heartbeat/status monitoring.

---

## 9.2 Out of Scope untuk MVP

Yang tidak termasuk dalam MVP:

- License plate recognition.
- Camera-based vehicle detection.
- Identifikasi jenis kendaraan.
- Payment system.
- POS cashier system.
- Customer database.
- Native mobile app.
- AI vehicle classification.
- Multi-branch advanced analytics.
- Face recognition.
- CCTV streaming.

---

# 10. Notes untuk Developer

## Backend Notes

- Semua API harus memiliki validasi request.
- Endpoint ESP32 harus menggunakan device key/API key.
- Hindari duplicate entry dengan `device_event_id`.
- Gunakan transaction saat insert vehicle entry dan update summary jika diperlukan.
- Audit log harus dibuat untuk aktivitas penting.
- Gunakan scheduler untuk daily summary jam 21:00.
- Gunakan soft delete untuk sensor, user, dan location jika data pernah dipakai.

## Frontend Notes

- Frontend harus menyesuaikan tampilan berdasarkan role user.
- Jangan tampilkan menu admin untuk owner/cashier.
- Dashboard harus auto-refresh.
- Tampilkan status sensor secara jelas.
- Error message harus informatif.
- Export button hanya muncul untuk owner/admin.

## IoT Notes

- ESP32 harus menghindari multiple counting untuk kendaraan yang berhenti di depan sensor.
- Gunakan debounce atau cooldown.
- Gunakan unique event ID untuk setiap detection.
- Jika WiFi terputus, simpan data sementara.
- Kirim heartbeat berkala agar backend dapat mendeteksi sensor disconnected.

---

# 11. Final Development Scope Statement

Sistem yang akan dikembangkan adalah aplikasi monitoring kendaraan masuk berbasis IoT untuk operasional carwash. Sistem terdiri dari ESP32 dan ultrasonic sensor sebagai alat deteksi, Laravel sebagai backend, PostgreSQL sebagai database, dan Vue.js sebagai frontend dashboard.

Development harus fokus pada pencatatan kendaraan otomatis, monitoring dashboard, laporan harian/historical, manajemen sensor dan user, serta audit log. Fitur di luar deteksi jumlah kendaraan seperti license plate recognition, payment system, POS, dan AI classification tidak termasuk scope MVP.
