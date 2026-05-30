<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ParkingLocation;
use App\Models\AuditLog;

class LocationController extends Controller
{
    public function index()
    {
        return response()->json(ParkingLocation::all());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'owner_id' => 'required|integer',
            'location_name' => 'required|string',
            'address' => 'required|string',
            'capacity' => 'nullable|integer',
        ]);
        $loc = ParkingLocation::create($data);

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'create',
            'module' => 'location',
            'description' => "Created location {$loc->location_name}.",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'status' => 'success',
            'metadata' => ['location_id' => $loc->id],
        ]);

        return response()->json($loc, 201);
    }

    public function update(Request $request, $id)
    {
        $loc = ParkingLocation::findOrFail($id);
        $data = $request->validate([
            'location_name' => 'sometimes|string',
            'address' => 'sometimes|string',
            'capacity' => 'nullable|integer',
        ]);
        $loc->update($data);

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'update',
            'module' => 'location',
            'description' => "Updated location {$loc->location_name}.",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'status' => 'success',
            'metadata' => ['location_id' => $loc->id, 'changes' => array_keys($data)],
        ]);

        return response()->json($loc);
    }

    public function show($id)
    {
        return response()->json(ParkingLocation::findOrFail($id));
    }

    public function destroy($id)
    {
        $loc = ParkingLocation::findOrFail($id);
        $loc->delete();

        AuditLog::create([
            'user_id' => request()->user()->id,
            'action' => 'delete',
            'module' => 'location',
            'description' => "Soft deleted location {$loc->location_name}.",
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'status' => 'success',
            'metadata' => ['location_id' => $loc->id],
        ]);

        return response()->json(['message' => 'Location deleted']);
    }
}
