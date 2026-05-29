<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\UltrasonicSensor;
use App\Models\VehicleEntry;
use App\Models\SensorRawLog;
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
            'device_event_id' => 'nullable|string',
        ]);

        $sensor = UltrasonicSensor::where('sensor_code', $data['sensor_code'])->first();
        if (!$sensor) {
            return response()->json(['message' => 'Invalid sensor_code'], 422);
        }

        if (!empty($data['device_event_id'])) {
            $exists = VehicleEntry::where('device_event_id', $data['device_event_id'])->exists();
            if ($exists) return response()->json(['message' => 'Duplicate event'], 409);
        }

        $entry = VehicleEntry::create([
            'location_id' => $data['location_id'],
            'sensor_id' => $sensor->id,
            'entry_time' => $data['entry_time'],
            'vehicle_count' => $data['vehicle_count'],
            'detection_confidence' => $data['detection_confidence'] ?? null,
            'raw_distance' => $data['raw_distance'] ?? null,
            'device_event_id' => $data['device_event_id'] ?? null,
        ]);

        // optional: store raw log
        if ($request->has('raw_distance')) {
            SensorRawLog::create([
                'sensor_id' => $sensor->id,
                'distance_value' => $data['raw_distance'],
                'is_detected' => true,
                'payload' => $request->all(),
                'received_at' => now(),
            ]);
        }

        return response()->json(['message' => 'Vehicle entry stored', 'entry_id' => $entry->id]);
    }

    public function heartbeat(Request $request)
    {
        $data = $request->validate([
            'sensor_code' => 'required|string',
            'status' => 'required|string',
            'last_distance' => 'nullable|numeric',
        ]);

        $sensor = UltrasonicSensor::where('sensor_code', $data['sensor_code'])->first();
        if (!$sensor) return response()->json(['message' => 'Invalid sensor_code'], 422);

        $sensor->update([ 'status' => $data['status'], 'last_seen_at' => now() ]);

        return response()->json(['message' => 'Heartbeat received', 'server_time' => now()->toDateTimeString()]);
    }
}
