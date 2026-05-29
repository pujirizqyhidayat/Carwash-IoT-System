<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UltrasonicSensor;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class SensorController extends Controller
{
    use AuthorizesRequests;
    /**
     * GET /api/v1/sensors?location_id=1
     */
    public function index(Request $request)
    {
        $request->validate(['location_id' => 'required|integer']);

        $sensors = UltrasonicSensor::where('location_id', $request->location_id)
            ->get()
            ->map(function ($sensor) {
                return [
                    'id' => $sensor->id,
                    'sensor_name' => $sensor->sensor_name,
                    'sensor_code' => $sensor->sensor_code,
                    'sensor_position' => $sensor->sensor_position,
                    'status' => $sensor->status,
                    'threshold_distance' => $sensor->threshold_distance,
                    'last_seen_at' => $sensor->last_seen_at,
                ];
            });

        return response()->json($sensors);
    }

    /**
     * POST /api/v1/sensors
     */
    public function store(Request $request)
    {
        $this->authorize('isAdmin');

        $validated = $request->validate([
            'location_id' => 'required|exists:parking_locations,id',
            'sensor_name' => 'required|string|max:150',
            'sensor_code' => 'required|unique:ultrasonic_sensors',
            'sensor_position' => 'required|in:entry,exit',
            'threshold_distance' => 'nullable|numeric',
        ]);

        $sensor = UltrasonicSensor::create($validated);

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'create',
            'module' => 'sensor',
            'description' => "Created sensor: {$sensor->sensor_name}",
            'status' => 'success',
        ]);

        return response()->json([
            'message' => 'Sensor created',
            'sensor' => $sensor,
        ], 201);
    }

    /**
     * PUT /api/v1/sensors/{id}
     */
    public function update(Request $request, $id)
    {
        $this->authorize('isAdmin');

        $sensor = UltrasonicSensor::findOrFail($id);

        $validated = $request->validate([
            'sensor_name' => 'string|max:150',
            'sensor_position' => 'in:entry,exit',
            'status' => 'in:active,inactive,disconnected',
            'threshold_distance' => 'nullable|numeric',
        ]);

        $sensor->update($validated);

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'update',
            'module' => 'sensor',
            'description' => "Updated sensor: {$sensor->sensor_name}",
            'status' => 'success',
        ]);

        return response()->json(['message' => 'Sensor updated', 'sensor' => $sensor]);
    }

    /**
     * DELETE /api/v1/sensors/{id}
     */
    public function destroy(Request $request, $id)
    {
        $this->authorize('isAdmin');

        $sensor = UltrasonicSensor::findOrFail($id);
        $sensorName = $sensor->sensor_name;

        $sensor->update(['status' => 'inactive']);

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'delete',
            'module' => 'sensor',
            'description' => "Deactivated sensor: {$sensorName}",
            'status' => 'success',
        ]);

        return response()->json(['message' => 'Sensor deactivated']);
    }

    /**
     * GET /api/v1/sensors/{id}/status
     */
    public function status($id)
    {
        $sensor = UltrasonicSensor::findOrFail($id);

        return response()->json([
            'sensor_id' => $sensor->id,
            'sensor_name' => $sensor->sensor_name,
            'status' => $sensor->status,
            'last_seen_at' => $sensor->last_seen_at,
        ]);
    }
}
