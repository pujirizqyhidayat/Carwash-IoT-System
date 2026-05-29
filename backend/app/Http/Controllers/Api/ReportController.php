<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VehicleCountSummary;
use App\Models\VehicleEntry;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    use AuthorizesRequests;
    /**
     * GET /api/v1/reports?location_id=1&start_date=2026-02-01&end_date=2026-02-17
     */
    public function index(Request $request)
    {
        $request->validate([
            'location_id' => 'required|integer',
            'start_date' => 'required|date_format:Y-m-d',
            'end_date' => 'required|date_format:Y-m-d',
        ]);

        $reports = VehicleCountSummary::where('location_id', $request->location_id)
            ->whereBetween('summary_date', [$request->start_date, $request->end_date])
            ->orderBy('summary_date', 'desc')
            ->get()
            ->map(function ($report) {
                return [
                    'summary_date' => $report->summary_date,
                    'total_vehicle' => $report->total_vehicle,
                    'generated_at' => $report->generated_at,
                ];
            });

        return response()->json($reports);
    }

    /**
     * POST /api/v1/reports/generate-daily
     */
    public function generateDaily(Request $request)
    {
        $this->authorize('isAdmin');

        $validated = $request->validate([
            'location_id' => 'required|exists:parking_locations,id',
            'summary_date' => 'required|date_format:Y-m-d',
        ]);

        $date = $validated['summary_date'];
        $locationId = $validated['location_id'];

        // Check if summary already exists
        $existing = VehicleCountSummary::where('location_id', $locationId)
            ->where('summary_date', $date)
            ->first();

        if ($existing) {
            // Update existing
            $total = VehicleEntry::where('location_id', $locationId)
                ->whereDate('entry_time', $date)
                ->sum('vehicle_count');

            $existing->update([
                'total_vehicle' => $total,
                'generated_by' => $request->user()->id,
                'generated_at' => now(),
            ]);

            $summary = $existing;
        } else {
            // Create new
            $total = VehicleEntry::where('location_id', $locationId)
                ->whereDate('entry_time', $date)
                ->sum('vehicle_count');

            $summary = VehicleCountSummary::create([
                'location_id' => $locationId,
                'summary_date' => $date,
                'total_vehicle' => $total,
                'generated_by' => $request->user()->id,
                'generated_at' => now(),
            ]);
        }

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'create',
            'module' => 'report',
            'description' => "Generated daily summary for {$date}",
            'status' => 'success',
        ]);

        return response()->json([
            'message' => 'Daily summary generated',
            'summary' => $summary,
        ], 201);
    }

    /**
     * GET /api/v1/reports/export/pdf?location_id=1&start_date=2026-02-01&end_date=2026-02-17
     */
    public function exportPdf(Request $request)
    {
        $request->validate([
            'location_id' => 'required|integer',
            'start_date' => 'required|date_format:Y-m-d',
            'end_date' => 'required|date_format:Y-m-d',
        ]);

        $reports = VehicleCountSummary::where('location_id', $request->location_id)
            ->whereBetween('summary_date', [$request->start_date, $request->end_date])
            ->orderBy('summary_date')
            ->get();

        // TODO: Implement PDF export using DomPDF
        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'export',
            'module' => 'report',
            'description' => "Exported report to PDF from {$request->start_date} to {$request->end_date}",
            'status' => 'success',
        ]);

        return response()->json([
            'message' => 'PDF export ready',
            'data' => $reports,
        ]);
    }

    /**
     * GET /api/v1/reports/export/excel?location_id=1&start_date=2026-02-01&end_date=2026-02-17
     */
    public function exportExcel(Request $request)
    {
        $request->validate([
            'location_id' => 'required|integer',
            'start_date' => 'required|date_format:Y-m-d',
            'end_date' => 'required|date_format:Y-m-d',
        ]);

        $reports = VehicleCountSummary::where('location_id', $request->location_id)
            ->whereBetween('summary_date', [$request->start_date, $request->end_date])
            ->orderBy('summary_date')
            ->get();

        // TODO: Implement Excel export using Laravel Excel
        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'export',
            'module' => 'report',
            'description' => "Exported report to Excel from {$request->start_date} to {$request->end_date}",
            'status' => 'success',
        ]);

        return response()->json([
            'message' => 'Excel export ready',
            'data' => $reports,
        ]);
    }
}
