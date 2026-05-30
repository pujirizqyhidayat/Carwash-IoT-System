<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\ParkingLocation;
use App\Models\UltrasonicSensor;
use App\Models\User;
use App\Models\VehicleCountSummary;
use App\Models\VehicleEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiRequirementTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_and_failed_login_is_audited(): void
    {
        $user = User::factory()->owner()->create([
            'email' => 'owner@example.test',
            'password' => Hash::make('password123'),
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'owner@example.test',
            'password' => 'password123',
        ])
            ->assertOk()
            ->assertJsonPath('message', 'Login success')
            ->assertJsonStructure(['token']);

        $this->assertNotNull($user->fresh()->last_login_at);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'login',
            'module' => 'auth',
            'status' => 'success',
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'owner@example.test',
            'password' => 'wrong-password',
        ])
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Invalid credentials');

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'login',
            'module' => 'auth',
            'status' => 'failed',
        ]);
    }

    public function test_inactive_user_cannot_login(): void
    {
        $user = User::factory()->inactive()->create([
            'email' => 'inactive@example.test',
            'password' => Hash::make('password123'),
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'inactive@example.test',
            'password' => 'password123',
        ])
            ->assertForbidden()
            ->assertJsonPath('message', 'Account inactive');

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'login',
            'module' => 'auth',
            'status' => 'failed',
        ]);
    }

    public function test_cashier_cannot_access_admin_user_management(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'cashier']));

        $this->getJson('/api/v1/users')
            ->assertForbidden()
            ->assertJsonPath('message', 'Access denied');

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'failed_access',
            'module' => 'authorization',
            'status' => 'failed',
        ]);
    }

    public function test_iot_detection_requires_device_key_and_stores_valid_detection(): void
    {
        $sensor = $this->createActiveSensor(thresholdDistance: 40);

        $payload = $this->iotPayload($sensor, [
            'raw_distance' => 35,
            'device_event_id' => 'ESP32-001',
        ]);

        $this->postJson('/api/v1/iot/vehicle-detections', $payload)
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Invalid device key');

        $this->withHeader('X-DEVICE-KEY', 'test-device-key')
            ->postJson('/api/v1/iot/vehicle-detections', $payload)
            ->assertOk()
            ->assertJsonPath('message', 'Vehicle entry stored')
            ->assertJsonPath('vehicles_today', 1);

        $this->assertDatabaseHas('vehicle_entries', [
            'sensor_id' => $sensor->id,
            'device_event_id' => 'ESP32-001',
            'vehicle_count' => 1,
        ]);
    }

    public function test_iot_rejects_duplicate_events_and_distance_outside_threshold(): void
    {
        $sensor = $this->createActiveSensor(thresholdDistance: 40);

        VehicleEntry::create([
            'location_id' => $sensor->location_id,
            'sensor_id' => $sensor->id,
            'entry_time' => now(),
            'vehicle_count' => 1,
            'device_event_id' => 'DUPLICATE-001',
        ]);

        $this->withHeader('X-DEVICE-KEY', 'test-device-key')
            ->postJson('/api/v1/iot/vehicle-detections', $this->iotPayload($sensor, [
                'raw_distance' => 35,
                'device_event_id' => 'DUPLICATE-001',
            ]))
            ->assertStatus(409)
            ->assertJsonPath('message', 'Duplicate event');

        $this->withHeader('X-DEVICE-KEY', 'test-device-key')
            ->postJson('/api/v1/iot/vehicle-detections', $this->iotPayload($sensor, [
                'raw_distance' => 41,
                'device_event_id' => 'FAR-001',
            ]))
            ->assertUnprocessable()
            ->assertJsonPath('message', 'raw_distance is outside threshold');
    }

    public function test_dashboard_summary_sums_vehicle_count_and_reports_sensor_status(): void
    {
        Sanctum::actingAs(User::factory()->owner()->create());
        $sensor = $this->createActiveSensor();

        VehicleEntry::create([
            'location_id' => $sensor->location_id,
            'sensor_id' => $sensor->id,
            'entry_time' => now(),
            'vehicle_count' => 2,
            'device_event_id' => 'COUNT-001',
        ]);
        VehicleEntry::create([
            'location_id' => $sensor->location_id,
            'sensor_id' => $sensor->id,
            'entry_time' => now(),
            'vehicle_count' => 3,
            'device_event_id' => 'COUNT-002',
        ]);

        $this->getJson("/api/v1/dashboard/summary?location_id={$sensor->location_id}")
            ->assertOk()
            ->assertJsonPath('vehicles_today', 5)
            ->assertJsonPath('sensor_status', 'active');
    }

    public function test_owner_can_export_report_and_admin_can_export_audit_logs(): void
    {
        $sensor = $this->createActiveSensor();
        VehicleCountSummary::create([
            'location_id' => $sensor->location_id,
            'summary_date' => now()->toDateString(),
            'total_vehicle' => 5,
            'generated_at' => now(),
        ]);

        Sanctum::actingAs(User::factory()->owner()->create());

        $this->getJson("/api/v1/reports/export/pdf?location_id={$sensor->location_id}")
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->getJson("/api/v1/reports/export/excel?location_id={$sensor->location_id}")
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        Sanctum::actingAs(User::factory()->admin()->create());
        AuditLog::create([
            'action' => 'login',
            'module' => 'auth',
            'description' => 'Login success.',
            'status' => 'success',
        ]);

        $this->getJson('/api/v1/audit-logs/export')
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    private function createActiveSensor(float $thresholdDistance = 40): UltrasonicSensor
    {
        $owner = User::factory()->owner()->create();
        $location = ParkingLocation::create([
            'owner_id' => $owner->id,
            'location_name' => 'Test Car Wash',
            'address' => 'Test Address',
            'capacity' => 20,
        ]);

        return UltrasonicSensor::create([
            'location_id' => $location->id,
            'sensor_name' => 'Entrance Sensor',
            'sensor_code' => 'ENTRANCE-TEST-'.$location->id,
            'sensor_position' => 'entry',
            'status' => 'active',
            'threshold_distance' => $thresholdDistance,
            'installed_at' => now(),
            'last_seen_at' => now(),
        ]);
    }

    private function iotPayload(UltrasonicSensor $sensor, array $overrides = []): array
    {
        return array_merge([
            'sensor_code' => $sensor->sensor_code,
            'location_id' => $sensor->location_id,
            'entry_time' => now()->toDateTimeString(),
            'vehicle_count' => 1,
            'detection_confidence' => 98.75,
            'raw_distance' => 35,
            'device_event_id' => 'ESP32-'.uniqid(),
        ], $overrides);
    }
}
