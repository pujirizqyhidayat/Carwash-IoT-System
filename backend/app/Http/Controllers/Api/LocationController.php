<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ParkingLocation;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    use AuthorizesRequests;
    /**
     * GET /api/v1/locations
     */
    public function index(Request $request)
    {
        $locations = ParkingLocation::all()
            ->map(function ($location) {
                return [
                    'id' => $location->id,
                    'owner_id' => $location->owner_id,
                    'location_name' => $location->location_name,
                    'address' => $location->address,
                    'capacity' => $location->capacity,
                ];
            });

        return response()->json($locations);
    }

    /**
     * POST /api/v1/locations
     */
    public function store(Request $request)
    {
        $this->authorize('isAdmin');

        $validated = $request->validate([
            'owner_id' => 'required|exists:users,id',
            'location_name' => 'required|string|max:150',
            'address' => 'required|string',
            'capacity' => 'nullable|integer',
        ]);

        $location = ParkingLocation::create($validated);

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'create',
            'module' => 'location',
            'description' => "Created location: {$location->location_name}",
            'status' => 'success',
        ]);

        return response()->json([
            'message' => 'Location created',
            'location' => $location,
        ], 201);
    }

    /**
     * PUT /api/v1/locations/{id}
     */
    public function update(Request $request, $id)
    {
        $this->authorize('isAdmin');

        $location = ParkingLocation::findOrFail($id);

        $validated = $request->validate([
            'location_name' => 'string|max:150',
            'address' => 'string',
            'capacity' => 'nullable|integer',
        ]);

        $location->update($validated);

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'update',
            'module' => 'location',
            'description' => "Updated location: {$location->location_name}",
            'status' => 'success',
        ]);

        return response()->json(['message' => 'Location updated', 'location' => $location]);
    }

    /**
     * DELETE /api/v1/locations/{id}
     */
    public function destroy(Request $request, $id)
    {
        $this->authorize('isAdmin');

        $location = ParkingLocation::findOrFail($id);
        $locationName = $location->location_name;

        $location->delete();

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'delete',
            'module' => 'location',
            'description' => "Deleted location: {$locationName}",
            'status' => 'success',
        ]);

        return response()->json(['message' => 'Location deleted']);
    }
}
