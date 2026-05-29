<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\VehicleCountSummary;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $locationId = $request->query('location_id');
        $start = $request->query('start_date');
        $end = $request->query('end_date');

        $q = VehicleCountSummary::query();
        if ($locationId) $q->where('location_id', $locationId);
        if ($start) $q->where('summary_date', '>=', $start);
        if ($end) $q->where('summary_date', '<=', $end);
        return response()->json($q->orderBy('summary_date', 'desc')->get());
    }

    public function generateDaily(Request $request)
    {
        $data = $request->validate([
            'location_id' => 'required|integer',
            'summary_date' => 'required|date',
        ]);

        // simplified generation: count VehicleEntry
        $total = \App\Models\VehicleEntry::where('location_id', $data['location_id'])
            ->whereDate('entry_time', $data['summary_date'])
            ->count();

        $summary = VehicleCountSummary::updateOrCreate(
            ['location_id' => $data['location_id'], 'summary_date' => $data['summary_date']],
            ['total_vehicle' => $total, 'generated_at' => now(), 'generated_by' => $request->user()->id ?? null]
        );

        return response()->json($summary);
    }

    public function exportPdf(Request $request)
    {
        return response()->json(['message' => 'PDF export not implemented yet']);
    }

    public function exportExcel(Request $request)
    {
        return response()->json(['message' => 'Excel export not implemented yet']);
    }
}
