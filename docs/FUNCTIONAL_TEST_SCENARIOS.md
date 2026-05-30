# Functional Testing / Test Scenario
## Carwash IoT System

Dokumen ini dipakai untuk manual testing aplikasi web dashboard dan backend API berdasarkan scope PRD.

## Environment

| Item | Value |
|---|---|
| Frontend URL | `http://127.0.0.1:5173` |
| Backend URL | `http://127.0.0.1:8000` |
| API Base URL | `http://127.0.0.1:8000/api/v1` |
| Device Key | `sensor_device_secret_key` |
| Default Location ID | `1` |
| Default Sensor Code | `ENTRANCE-001` |

---

# 1. Test Data

## 1.1 User Login Data

| Role | Email | Username | Password | Expected Access |
|---|---|---|---|---|
| Admin | `admin@carwash.test` | `admin` | `password123` | Dashboard, Monitoring, Reports, Sensors, Locations, Users, Audit Log |
| Owner | `owner@carwash.test` | `owner` | `password123` | Dashboard, Monitoring, Reports, Sensors, Locations |
| Cashier | `cashier@carwash.test` | `cashier` | `password123` | Dashboard, Monitoring |
| Owner | `puji@carwash.test` | `puji` | `puji123` | Dashboard, Monitoring, Reports, Sensors, Locations |

## 1.2 Negative Login Data

| Case | Email | Password | Expected Result |
|---|---|---|---|
| Wrong password | `admin@carwash.test` | `wrong123` | Login rejected |
| Unknown email | `unknown@test.com` | `password123` | Login rejected |
| Empty email | empty | `password123` | Validation error |
| Empty password | `admin@carwash.test` | empty | Validation error |

## 1.3 Sensor Test Data

| Field | Valid Value | Invalid Value |
|---|---|---|
| `sensor_code` | `ENTRANCE-001` | `INVALID-SENSOR` |
| `location_id` | `1` | `999` |
| `raw_distance` | `35` | `999` |
| `vehicle_count` | `1` | `0` |
| `detection_confidence` | `98.75` | `abc` |
| `device_event_id` | unique string, e.g. `ESP32-TEST-001` | duplicate existing value |

## 1.4 Report Test Data

| Field | Valid Value | Invalid Value |
|---|---|---|
| `location_id` | `1` | `abc` |
| `start_date` | `2026-05-30` | `wrong-date` |
| `end_date` | `2026-05-30` | date before `start_date` |

---

# 2. Positive Test Scenarios

## 2.1 Authentication & RBAC

| ID | Scenario | Role/Data | Steps | Expected Result |
|---|---|---|---|---|
| POS-AUTH-01 | Login as Admin | Admin account | Open `/login`, enter admin email/password, click Sign in | Redirect to Dashboard, admin menu appears |
| POS-AUTH-02 | Login as Owner | Owner account | Login using owner credentials | Redirect to Dashboard, owner menu appears, Users/Audit Log hidden |
| POS-AUTH-03 | Login as Cashier | Cashier account | Login using cashier credentials | Redirect to Dashboard, only Dashboard and Monitoring visible |
| POS-AUTH-04 | Logout | Any logged in user | Click Logout button in sidebar | User redirected to Login page |
| POS-AUTH-05 | Access protected page after login | Admin | Open `/users` | User Management page appears |

## 2.2 Dashboard

| ID | Scenario | Role/Data | Steps | Expected Result |
|---|---|---|---|---|
| POS-DASH-01 | View dashboard summary | Admin/Owner/Cashier | Login, open Dashboard | Today, Week, Month, Sensor cards visible |
| POS-DASH-02 | View recent activity | Admin/Owner/Cashier | Open Dashboard | Recent Activity list visible or shows `No data available` |
| POS-DASH-03 | Change active location | Admin/Owner | Select location from topbar dropdown | Dashboard data refreshes for selected location |
| POS-DASH-04 | Auto refresh dashboard | Any allowed role | Wait 15 seconds on Dashboard | Dashboard refreshes without manual reload |

## 2.3 Monitoring

| ID | Scenario | Role/Data | Steps | Expected Result |
|---|---|---|---|---|
| POS-MON-01 | View cashier monitoring | Cashier | Login as cashier, open Monitoring | Today count and hourly breakdown visible |
| POS-MON-02 | Auto refresh monitoring | Cashier | Wait 10 seconds | Monitoring data refreshes |
| POS-MON-03 | Sensor status visible | Any allowed role | Open Monitoring | Sensor status shows Active/Inactive/Disconnected |

## 2.4 Reports

| ID | Scenario | Role/Data | Steps | Expected Result |
|---|---|---|---|---|
| POS-REP-01 | View report list | Owner/Admin | Open Reports | Daily summary table appears |
| POS-REP-02 | Filter report by date | Owner/Admin | Select start and end date, click Filter | Table updates based on date range |
| POS-REP-03 | Generate daily summary | Owner/Admin | Click Generate | Summary is generated, toast success appears |
| POS-REP-04 | Export PDF | Owner/Admin | Click PDF | PDF file downloads |
| POS-REP-05 | Export Excel | Owner/Admin | Click Excel | `.xlsx` file downloads |

## 2.5 Sensor Management

| ID | Scenario | Role/Data | Steps | Expected Result |
|---|---|---|---|---|
| POS-SEN-01 | View sensor list | Owner/Admin | Open Sensors page | Sensor table appears |
| POS-SEN-02 | Create sensor | Admin | Fill sensor form, click Add Sensor | Sensor created and appears in list |
| POS-SEN-03 | Edit sensor | Admin | Click Edit, update status/threshold, save | Sensor updates and toast success appears |
| POS-SEN-04 | Deactivate sensor | Admin | Click Deactivate | Sensor status becomes inactive |

## 2.6 User Management

| ID | Scenario | Role/Data | Steps | Expected Result |
|---|---|---|---|---|
| POS-USER-01 | View user list | Admin | Open Users page | User table appears |
| POS-USER-02 | Create user | Admin | Fill form and click Create User | User is created |
| POS-USER-03 | Edit user role/status | Admin | Click Edit, change role/status, save | User data updates |
| POS-USER-04 | Reset user password | Admin | Click Edit, enter new password, save | User can login with new password |
| POS-USER-05 | Deactivate user | Admin | Click Deactivate | User status becomes inactive |

## 2.7 Location Management

| ID | Scenario | Role/Data | Steps | Expected Result |
|---|---|---|---|---|
| POS-LOC-01 | View locations | Owner/Admin | Open Locations page | Location cards appear |
| POS-LOC-02 | Create location | Admin | Fill form and click Create Location | Location appears in list |
| POS-LOC-03 | Edit location | Admin | Click Edit, update fields, save | Location updates |
| POS-LOC-04 | Delete location | Admin | Click Delete | Location removed/soft deleted |

## 2.8 Audit Log

| ID | Scenario | Role/Data | Steps | Expected Result |
|---|---|---|---|---|
| POS-AUD-01 | View audit log | Admin | Open Audit Log page | Audit log table appears |
| POS-AUD-02 | Filter audit log | Admin | Fill module/action/date, click Filter | Filtered audit logs appear |
| POS-AUD-03 | Export audit log | Admin | Click Export | `.xlsx` audit log downloads |
| POS-AUD-04 | Login activity recorded | Admin | Login/logout, open Audit Log | Login activity appears |
| POS-AUD-05 | CRUD activity recorded | Admin | Create/edit sensor/user/location | Related audit log appears |

## 2.9 IoT API Mock Test

Use Postman, Insomnia, or curl.

| ID | Scenario | Method & Endpoint | Header | Payload | Expected Result |
|---|---|---|---|---|---|
| POS-IOT-01 | Send vehicle detection | `POST /api/v1/iot/vehicle-detections` | `X-DEVICE-KEY: sensor_device_secret_key` | See valid payload below | `Vehicle entry stored` |
| POS-IOT-02 | Send heartbeat | `POST /api/v1/iot/sensors/heartbeat` | `X-DEVICE-KEY: sensor_device_secret_key` | See heartbeat payload below | `Heartbeat received` |

Valid vehicle detection payload:

```json
{
  "sensor_code": "ENTRANCE-001",
  "location_id": 1,
  "entry_time": "2026-05-30 10:00:00",
  "vehicle_count": 1,
  "detection_confidence": 98.75,
  "raw_distance": 35,
  "device_event_id": "ESP32-TEST-001"
}
```

Valid heartbeat payload:

```json
{
  "sensor_code": "ENTRANCE-001",
  "status": "active",
  "last_distance": 120.5
}
```

---

# 3. Negative Test Scenarios

## 3.1 Authentication & RBAC

| ID | Scenario | Role/Data | Steps | Expected Result |
|---|---|---|---|---|
| NEG-AUTH-01 | Login wrong password | Admin email + wrong password | Submit login form | Error message appears |
| NEG-AUTH-02 | Login unknown email | Unknown email | Submit login form | Error message appears |
| NEG-AUTH-03 | Access dashboard without login | No token | Open `/dashboard` | Redirected to `/login` |
| NEG-AUTH-04 | Cashier access Users page | Cashier | Open `/users` manually | Redirected to Dashboard or Access denied |
| NEG-AUTH-05 | Owner access Audit Log | Owner | Open `/audit-logs` manually | Redirected to Dashboard or Access denied |
| NEG-AUTH-06 | Inactive user login | Inactive user | Submit login form | Login rejected |

## 3.2 Reports

| ID | Scenario | Role/Data | Steps | Expected Result |
|---|---|---|---|---|
| NEG-REP-01 | Cashier opens Reports | Cashier | Open `/reports` manually | Redirected to Dashboard |
| NEG-REP-02 | Invalid date range | Owner/Admin | Set end date before start date, export/generate | Validation error |
| NEG-REP-03 | No report data | Owner/Admin | Filter date with no data | `No data available` |

## 3.3 Sensor Management

| ID | Scenario | Role/Data | Steps | Expected Result |
|---|---|---|---|---|
| NEG-SEN-01 | Owner creates sensor | Owner | Try POST `/sensors` via API | `403 Access denied` |
| NEG-SEN-02 | Duplicate sensor code | Admin | Create sensor with existing `sensor_code` | Validation error |
| NEG-SEN-03 | Invalid threshold | Admin | Enter non-numeric threshold | Validation error |
| NEG-SEN-04 | Cashier opens Sensors page | Cashier | Open `/sensors` manually | Redirected to Dashboard |

## 3.4 User Management

| ID | Scenario | Role/Data | Steps | Expected Result |
|---|---|---|---|---|
| NEG-USER-01 | Owner opens Users page | Owner | Open `/users` manually | Redirected to Dashboard |
| NEG-USER-02 | Cashier opens Users page | Cashier | Open `/users` manually | Redirected to Dashboard |
| NEG-USER-03 | Duplicate email | Admin | Create user using existing email | Validation error |
| NEG-USER-04 | Weak password | Admin | Create user password less than 6 chars | Validation error |
| NEG-USER-05 | Invalid role | Admin/API | Send role outside `owner/cashier/admin` | Validation error |

## 3.5 Location Management

| ID | Scenario | Role/Data | Steps | Expected Result |
|---|---|---|---|---|
| NEG-LOC-01 | Owner creates location | Owner | Try create location via API | `403 Access denied` |
| NEG-LOC-02 | Cashier opens Locations | Cashier | Open `/locations` manually | Redirected to Dashboard |
| NEG-LOC-03 | Missing required fields | Admin | Submit empty location form | Validation error |

## 3.6 Audit Log

| ID | Scenario | Role/Data | Steps | Expected Result |
|---|---|---|---|---|
| NEG-AUD-01 | Owner opens Audit Log | Owner | Open `/audit-logs` manually | Redirected to Dashboard |
| NEG-AUD-02 | Cashier opens Audit Log | Cashier | Open `/audit-logs` manually | Redirected to Dashboard |
| NEG-AUD-03 | Non-admin calls audit API | Owner/Cashier | Call `GET /api/v1/audit-logs` | `403 Access denied` |

## 3.7 IoT API Negative Test

| ID | Scenario | Header/Payload | Expected Result |
|---|---|---|
| NEG-IOT-01 | Missing device key | No `X-DEVICE-KEY` | `401 Invalid device key` |
| NEG-IOT-02 | Wrong device key | `X-DEVICE-KEY: wrong-key` | `401 Invalid device key` |
| NEG-IOT-03 | Invalid sensor code | `sensor_code: INVALID-SENSOR` | `422 Invalid sensor_code` |
| NEG-IOT-04 | Duplicate event | Same `device_event_id` twice | First success, second `409 Duplicate event` |
| NEG-IOT-05 | Raw distance outside threshold | `raw_distance: 999` | `422 raw_distance is outside threshold` |
| NEG-IOT-06 | Sensor inactive | Set sensor status inactive, send detection | `422 Sensor is not active` |
| NEG-IOT-07 | Location mismatch | Valid sensor but wrong `location_id` | `422 Sensor does not belong to location` |
| NEG-IOT-08 | Missing required field | Missing `device_event_id` | Validation error |

---

# 4. API Testing Commands

## 4.1 Login

```bash
curl -X POST http://127.0.0.1:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d "{\"email\":\"admin@carwash.test\",\"password\":\"password123\"}"
```

## 4.2 Vehicle Detection

```bash
curl -X POST http://127.0.0.1:8000/api/v1/iot/vehicle-detections \
  -H "Content-Type: application/json" \
  -H "X-DEVICE-KEY: sensor_device_secret_key" \
  -d "{\"sensor_code\":\"ENTRANCE-001\",\"location_id\":1,\"entry_time\":\"2026-05-30 10:00:00\",\"vehicle_count\":1,\"detection_confidence\":98.75,\"raw_distance\":35,\"device_event_id\":\"ESP32-TEST-001\"}"
```

## 4.3 Heartbeat

```bash
curl -X POST http://127.0.0.1:8000/api/v1/iot/sensors/heartbeat \
  -H "Content-Type: application/json" \
  -H "X-DEVICE-KEY: sensor_device_secret_key" \
  -d "{\"sensor_code\":\"ENTRANCE-001\",\"status\":\"active\",\"last_distance\":120.5}"
```

---

# 5. Test Result Template

| Test ID | Tester | Date | Result | Notes |
|---|---|---|---|---|
| POS-AUTH-01 |  |  | Pass/Fail |  |
| NEG-AUTH-01 |  |  | Pass/Fail |  |
| POS-DASH-01 |  |  | Pass/Fail |  |
| POS-REP-04 |  |  | Pass/Fail |  |
| NEG-IOT-04 |  |  | Pass/Fail |  |
