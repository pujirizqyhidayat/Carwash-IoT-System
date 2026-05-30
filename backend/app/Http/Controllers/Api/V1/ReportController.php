<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Exports\VehicleCountSummaryExport;
use Illuminate\Http\Request;
use App\Models\VehicleCountSummary;
use App\Models\AuditLog;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;

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
            ->sum('vehicle_count');

        $summary = VehicleCountSummary::updateOrCreate(
            ['location_id' => $data['location_id'], 'summary_date' => $data['summary_date']],
            ['total_vehicle' => $total, 'generated_at' => now(), 'generated_by' => $request->user()->id ?? null]
        );

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'create',
            'module' => 'report',
            'description' => "Generated daily summary for {$data['summary_date']}.",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'status' => 'success',
            'metadata' => [
                'location_id' => $data['location_id'],
                'summary_date' => $data['summary_date'],
                'total_vehicle' => $total,
            ],
        ]);

        return response()->json($summary);
    }

    public function exportPdf(Request $request)
    {
        $data = $request->validate([
            'location_id' => 'nullable|integer',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $summaries = VehicleCountSummary::with('location')
            ->when($data['location_id'] ?? null, fn ($query, $locationId) => $query->where('location_id', $locationId))
            ->when($data['start_date'] ?? null, fn ($query, $startDate) => $query->where('summary_date', '>=', $startDate))
            ->when($data['end_date'] ?? null, fn ($query, $endDate) => $query->where('summary_date', '<=', $endDate))
            ->orderBy('summary_date')
            ->get();

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'export',
            'module' => 'report',
            'description' => 'Exported vehicle report to PDF.',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'status' => 'success',
            'metadata' => $data,
        ]);

        return Pdf::loadView('reports.vehicle-summary-pdf', [
            'summaries' => $summaries,
            'startDate' => $data['start_date'] ?? null,
            'endDate' => $data['end_date'] ?? null,
        ])->download($this->reportFileName('vehicle-report', 'pdf'));
    }

    public function exportExcel(Request $request)
    {
        $data = $request->validate([
            'location_id' => 'nullable|integer',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'export',
            'module' => 'report',
            'description' => 'Exported vehicle report to Excel.',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'status' => 'success',
            'metadata' => $data,
        ]);

        return Excel::download(
            new VehicleCountSummaryExport(
                $data['location_id'] ?? null,
                $data['start_date'] ?? null,
                $data['end_date'] ?? null
            ),
            $this->reportFileName('vehicle-report', 'xlsx')
        );
    }

    private function reportFileName(string $prefix, string $extension): string
    {
        return $prefix.'-'.now()->format('Ymd-His').'.'.$extension;
    }
}
