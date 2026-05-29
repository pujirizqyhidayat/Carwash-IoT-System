<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VehicleEntry;
use App\Models\AuditLog;
use App\Models\UltrasonicSensor;
use App\Models\SensorRawLog;
use Illuminate\Http\Request;

class VehicleDetectionController extends Controller
{
    /**
     * POST /api/v1/iot/vehicle-detections
     * Endpoint for ESP32 to send vehicle detection data
     */
    public function store(Request $request)
    {
        // Validate device key from header
        $deviceKey = $request->header('X-DEVICE-KEY');
        if (!$deviceKey) {
            return response()->json(['message' => 'Device key required'], 401);
        }

        $validated = $request->validate([
            'sensor_code' => 'required|string',
            'location_id' => 'required|integer',
            'entry_time' => 'required|date_format:Y-m-d H:i:s',
            'vehicle_count' => 'required|integer|min:1',
            'detection_confidence' => 'nullable|numeric|min:0|max:100',
            'raw_distance' => 'nullable|numeric',
            'device_event_id' => 'required|string',
        ]);

        // Check if device_event_id already exists (prevent duplicates)
        if (VehicleEntry::where('device_event_id', $validated['device_event_id'])->exists()) {
            return response()->json(['message' => 'Duplicate detection'], 409);
        }

        // Find sensor by code
        $sensor = UltrasonicSensor::where('sensor_code', $validated['sensor_code'])->first();
        if (!$sensor || $sensor->status !== 'active') {
            return response()->json(['message' => 'Sensor not found or inactive'], 404);
        }

        // Validate threshold distance
        if ($validated['raw_distance'] && $sensor->threshold_distance) {
            if ($validated['raw_distance'] > $sensor->threshold_distance) {
                return response()->json(['message' => 'Distance exceeds threshold'], 422);
            }
        }

        // Create vehicle entry
        $entry = VehicleEntry::create([
            'location_id' => $validated['location_id'],
            'sensor_id' => $sensor->id,
            'entry_time' => $validated['entry_time'],
            'vehicle_count' => $validated['vehicle_count'],
            'detection_confidence' => $validated['detection_confidence'],
            'raw_distance' => $validated['raw_distance'],
            'device_event_id' => $validated['device_event_id'],
        ]);

        // Log to audit log
        AuditLog::create([
            'user_id' => null,
            'action' => 'create',
            'module' => 'vehicle_detection',
            'description' => "Vehicle entry created from sensor {$sensor->sensor_code}",
            'status' => 'success',
            'metadata' => [
                'sensor_id' => $sensor->id,
                'vehicle_count' => $validated['vehicle_count'],
            ],
        ]);

        // Get today's vehicle count
        $vehiclesToday = VehicleEntry::where('location_id', $validated['location_id'])
            ->whereDate('entry_time', now())
            ->sum('vehicle_count');

        return response()->json([
            'message' => 'Vehicle entry stored',
            'entry_id' => $entry->id,
            'vehicles_today' => $vehiclesToday,
        ], 201);
    }

    /**
     * POST /api/v1/iot/sensors/heartbeat
     * Sensor heartbeat to update last_seen_at
     */
    public function heartbeat(Request $request)
    {
        $deviceKey = $request->header('X-DEVICE-KEY');
        if (!$deviceKey) {
            return response()->json(['message' => 'Device key required'], 401);
        }

        $validated = $request->validate([
            'sensor_code' => 'required|string',
            'status' => 'required|in:active,inactive,disconnected',
            'last_distance' => 'nullable|numeric',
        ]);

        $sensor = UltrasonicSensor::where('sensor_code', $validated['sensor_code'])->first();
        if (!$sensor) {
            return response()->json(['message' => 'Sensor not found'], 404);
        }

        // Update sensor status and last_seen_at
        $sensor->update([
            'status' => $validated['status'],
            'last_seen_at' => now(),
        ]);

        // Log raw sensor data if distance provided
        if ($validated['last_distance'] !== null) {
            SensorRawLog::create([
                'sensor_id' => $sensor->id,
                'distance_value' => $validated['last_distance'],
                'is_detected' => $validated['last_distance'] <= ($sensor->threshold_distance ?? 50),
                'payload' => $request->all(),
                'received_at' => now(),
            ]);
        }

        return response()->json([
            'message' => 'Heartbeat received',
            'server_time' => now(),
        ]);
    }
}
