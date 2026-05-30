<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\UltrasonicSensor;
use App\Models\AuditLog;

class SensorController extends Controller
{
    public function index(Request $request)
    {
        $locationId = $request->query('location_id');
        $q = UltrasonicSensor::query();
        if ($locationId) $q->where('location_id', $locationId);
        return response()->json($q->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'location_id' => 'required|integer',
            'sensor_name' => 'required|string',
            'sensor_code' => 'required|string|unique:ultrasonic_sensors,sensor_code',
            'sensor_position' => 'required|in:entry,exit',
            'threshold_distance' => 'nullable|numeric',
        ]);

        $sensor = UltrasonicSensor::create($data + ['status' => 'active']);

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'create',
            'module' => 'sensor',
            'description' => "Created sensor {$sensor->sensor_code}.",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'status' => 'success',
            'metadata' => ['sensor_id' => $sensor->id],
        ]);

        return response()->json($sensor, 201);
    }

    public function update(Request $request, $id)
    {
        $sensor = UltrasonicSensor::findOrFail($id);
        $data = $request->validate([
            'sensor_name' => 'sometimes|string',
            'sensor_position' => 'sometimes|in:entry,exit',
            'status' => 'sometimes|in:active,inactive,disconnected',
            'threshold_distance' => 'nullable|numeric',
        ]);
        $sensor->update($data);

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'update',
            'module' => 'sensor',
            'description' => "Updated sensor {$sensor->sensor_code}.",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'status' => 'success',
            'metadata' => ['sensor_id' => $sensor->id, 'changes' => array_keys($data)],
        ]);

        return response()->json($sensor);
    }

    public function show($id)
    {
        return response()->json(UltrasonicSensor::findOrFail($id));
    }

    public function status($id)
    {
        $sensor = UltrasonicSensor::findOrFail($id);
        return response()->json([
            'sensor_id' => $sensor->id,
            'sensor_name' => $sensor->sensor_name,
            'status' => $sensor->status,
            'last_seen_at' => $sensor->last_seen_at?->toDateTimeString(),
        ]);
    }

    public function destroy($id)
    {
        $sensor = UltrasonicSensor::findOrFail($id);
        $sensor->update(['status' => 'inactive']);

        AuditLog::create([
            'user_id' => request()->user()->id,
            'action' => 'delete',
            'module' => 'sensor',
            'description' => "Deactivated sensor {$sensor->sensor_code}.",
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'status' => 'success',
            'metadata' => ['sensor_id' => $sensor->id],
        ]);

        return response()->json(['message' => 'Sensor deactivated']);
    }
}
