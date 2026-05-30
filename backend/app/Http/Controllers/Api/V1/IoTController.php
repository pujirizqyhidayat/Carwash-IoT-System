<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\UltrasonicSensor;
use App\Models\VehicleEntry;
use App\Models\SensorRawLog;
use App\Models\AuditLog;
use Illuminate\Support\Facades\DB;

class IoTController extends Controller
{
    public function vehicleDetections(Request $request)
    {
        $data = $request->validate([
            'sensor_code' => 'required|string',
            'location_id' => 'required|integer',
            'entry_time' => 'required|date',
            'vehicle_count' => 'required|integer|min:1',
            'detection_confidence' => 'nullable|numeric',
            'raw_distance' => 'nullable|numeric',
            'device_event_id' => 'required|string',
        ]);

        $sensor = UltrasonicSensor::where('sensor_code', $data['sensor_code'])->first();
        if (!$sensor) {
            return response()->json(['message' => 'Invalid sensor_code'], 422);
        }

        if ((int) $sensor->location_id !== (int) $data['location_id']) {
            return response()->json(['message' => 'Sensor does not belong to location'], 422);
        }

        if ($sensor->status !== 'active') {
            return response()->json(['message' => 'Sensor is not active'], 422);
        }

        if ($sensor->threshold_distance !== null) {
            if (!isset($data['raw_distance'])) {
                return response()->json(['message' => 'raw_distance is required for threshold validation'], 422);
            }

            if ((float) $data['raw_distance'] > (float) $sensor->threshold_distance) {
                return response()->json(['message' => 'raw_distance is outside threshold'], 422);
            }
        }

        $exists = VehicleEntry::where('device_event_id', $data['device_event_id'])->exists();
        if ($exists) {
            return response()->json(['message' => 'Duplicate event'], 409);
        }

        $entry = DB::transaction(function () use ($data, $sensor, $request) {
            $entry = VehicleEntry::create([
                'location_id' => $data['location_id'],
                'sensor_id' => $sensor->id,
                'entry_time' => $data['entry_time'],
                'vehicle_count' => $data['vehicle_count'],
                'detection_confidence' => $data['detection_confidence'] ?? null,
                'raw_distance' => $data['raw_distance'] ?? null,
                'device_event_id' => $data['device_event_id'],
            ]);

            if ($request->has('raw_distance')) {
                SensorRawLog::create([
                    'sensor_id' => $sensor->id,
                    'distance_value' => $data['raw_distance'],
                    'is_detected' => true,
                    'payload' => $request->all(),
                    'received_at' => now(),
                ]);
            }

            AuditLog::create([
                'user_id' => null,
                'action' => 'create',
                'module' => 'vehicle_entry',
                'description' => 'Vehicle entry stored from IoT device.',
                'status' => 'success',
                'metadata' => [
                    'entry_id' => $entry->id,
                    'sensor_code' => $sensor->sensor_code,
                    'device_event_id' => $data['device_event_id'],
                ],
            ]);

            return $entry;
        });

        $vehiclesToday = VehicleEntry::where('location_id', $data['location_id'])
            ->whereDate('entry_time', now()->toDateString())
            ->sum('vehicle_count');

        return response()->json([
            'message' => 'Vehicle entry stored',
            'entry_id' => $entry->id,
            'vehicles_today' => $vehiclesToday,
        ]);
    }

    public function heartbeat(Request $request)
    {
        $data = $request->validate([
            'sensor_code' => 'required|string',
            'status' => 'required|in:active,inactive,disconnected',
            'last_distance' => 'nullable|numeric',
        ]);

        $sensor = UltrasonicSensor::where('sensor_code', $data['sensor_code'])->first();
        if (!$sensor) return response()->json(['message' => 'Invalid sensor_code'], 422);

        $sensor->update([ 'status' => $data['status'], 'last_seen_at' => now() ]);

        return response()->json(['message' => 'Heartbeat received', 'server_time' => now()->toDateTimeString()]);
    }
}
