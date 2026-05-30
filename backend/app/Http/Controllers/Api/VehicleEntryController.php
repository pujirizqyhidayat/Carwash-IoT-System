<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VehicleEntry;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class VehicleEntryController extends Controller
{
    use AuthorizesRequests;
    /**
     * GET /api/v1/vehicle-entries?location_id=1&date=2026-02-17
     */
    public function index(Request $request)
    {
        $request->validate([
            'location_id' => 'required|integer',
            'date' => 'nullable|date_format:Y-m-d',
        ]);

        $query = VehicleEntry::with('sensor')
            ->where('location_id', $request->location_id);

        if ($request->date) {
            $query->whereDate('entry_time', $request->date);
        } else {
            $query->whereDate('entry_time', now());
        }

        $entries = $query->orderBy('entry_time', 'desc')
            ->get()
            ->map(function ($entry) {
                return [
                    'id' => $entry->id,
                    'sensor_name' => $entry->sensor->sensor_name,
                    'entry_time' => $entry->entry_time,
                    'vehicle_count' => $entry->vehicle_count,
                    'detection_confidence' => $entry->detection_confidence,
                    'raw_distance' => $entry->raw_distance,
                ];
            });

        return response()->json($entries);
    }

    /**
     * GET /api/v1/vehicle-entries/{id}
     */
    public function show($id)
    {
        $entry = VehicleEntry::with('sensor')->findOrFail($id);

        return response()->json([
            'id' => $entry->id,
            'location_id' => $entry->location_id,
            'sensor_id' => $entry->sensor_id,
            'sensor_name' => $entry->sensor->sensor_name,
            'entry_time' => $entry->entry_time,
            'vehicle_count' => $entry->vehicle_count,
            'detection_confidence' => $entry->detection_confidence,
            'raw_distance' => $entry->raw_distance,
            'device_event_id' => $entry->device_event_id,
            'created_at' => $entry->created_at,
        ]);
    }

    /**
     * DELETE /api/v1/vehicle-entries/{id}
     */
    public function destroy(Request $request, $id)
    {
        $this->authorize('isAdmin');

        $entry = VehicleEntry::findOrFail($id);

        $entry->delete();

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'delete',
            'module' => 'vehicle_entry',
            'description' => "Deleted vehicle entry ID: {$id}",
            'status' => 'success',
        ]);

        return response()->json(['message' => 'Vehicle entry deleted']);
    }
}
