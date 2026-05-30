<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\VehicleEntry;
use App\Models\AuditLog;

class VehicleEntryController extends Controller
{
    public function index(Request $request)
    {
        $locationId = $request->query('location_id');
        $date = $request->query('date');
        $q = VehicleEntry::query();
        if ($locationId) $q->where('location_id', $locationId);
        if ($date) $q->whereDate('entry_time', $date);
        return response()->json($q->orderBy('entry_time', 'desc')->paginate(25));
    }

    public function show($id)
    {
        return response()->json(VehicleEntry::findOrFail($id));
    }

    public function destroy($id)
    {
        // soft delete not enabled; perform delete
        $entry = VehicleEntry::findOrFail($id);
        $entry->delete();

        AuditLog::create([
            'user_id' => request()->user()->id,
            'action' => 'delete',
            'module' => 'vehicle_entry',
            'description' => "Soft deleted vehicle entry {$entry->id}.",
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'status' => 'success',
            'metadata' => [
                'entry_id' => $entry->id,
                'location_id' => $entry->location_id,
                'sensor_id' => $entry->sensor_id,
            ],
        ]);

        return response()->json(['message' => 'Entry deleted']);
    }
}
