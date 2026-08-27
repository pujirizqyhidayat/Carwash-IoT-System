<?php

namespace App\Http\Controllers\Api\V1;

use App\Exports\VehicleCountSummaryExport;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\ParkingLocation;
use App\Models\VehicleCountSummary;
use App\Models\VehicleEntry;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->validate([
            'location_id' => 'nullable|integer',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $summaries = $this->reportSummaries($data)
            ->map(function ($summary) {
                return [
                    'id' => $summary->location_id.'-'.$summary->summary_date->format('Y-m-d'),
                    'location_id' => $summary->location_id,
                    'summary_date' => $summary->summary_date?->format('Y-m-d'),
                    'total_vehicle' => $summary->total_vehicle,
                    'total_transactions' => $summary->total_transactions,
                    'total_revenue' => $summary->total_revenue,
                    'location' => $summary->location,
                ];
            });

        return response()->json($summaries);
    }

    public function generateDaily(Request $request)
    {
        $data = $request->validate([
            'location_id' => 'required|integer',
            'summary_date' => 'required|date',
        ]);

        $total = VehicleEntry::where('location_id', $data['location_id'])
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
        $data = $this->validatedExportData($request);
        $summaries = $this->reportSummaries($data);
        $locationName = $this->locationName($data['location_id'] ?? null, $summaries);

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
            'reportTitle' => $this->reportTitle($data['start_date'] ?? null, $data['end_date'] ?? null),
            'locationName' => $locationName,
        ])->download($this->reportFileName($data, $locationName, 'pdf'));
    }

    public function exportExcel(Request $request)
    {
        $data = $this->validatedExportData($request);
        $locationName = $this->locationName($data['location_id'] ?? null);

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
            $this->reportFileName($data, $locationName, 'xlsx')
        );
    }

    private function validatedExportData(Request $request): array
    {
        return $request->validate([
            'location_id' => 'nullable|integer',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);
    }

    private function reportSummaries(array $data)
    {
        $summaries = VehicleEntry::query()
            ->leftJoin('carwash_transactions', 'carwash_transactions.vehicle_entry_id', '=', 'vehicle_entries.id')
            ->selectRaw('vehicle_entries.location_id, DATE(vehicle_entries.entry_time) as summary_date, SUM(vehicle_entries.vehicle_count) as total_vehicle, COUNT(carwash_transactions.id) as total_transactions, COALESCE(SUM(carwash_transactions.price), 0) as total_revenue')
            ->when($data['location_id'] ?? null, fn ($query, $locationId) => $query->where('vehicle_entries.location_id', $locationId))
            ->when($data['start_date'] ?? null, fn ($query, $startDate) => $query->whereDate('vehicle_entries.entry_time', '>=', $startDate))
            ->when($data['end_date'] ?? null, fn ($query, $endDate) => $query->whereDate('vehicle_entries.entry_time', '<=', $endDate))
            ->groupByRaw('vehicle_entries.location_id, DATE(vehicle_entries.entry_time)')
            ->orderBy('summary_date')
            ->get();

        $locations = ParkingLocation::whereIn('id', $summaries->pluck('location_id')->unique())
            ->get()
            ->keyBy('id');

        return $summaries->map(function ($summary) use ($locations) {
            return (object) [
                'location_id' => (int) $summary->location_id,
                'summary_date' => Carbon::parse($summary->summary_date),
                'total_vehicle' => (int) $summary->total_vehicle,
                'total_transactions' => (int) $summary->total_transactions,
                'total_revenue' => (int) $summary->total_revenue,
                'location' => $locations->get($summary->location_id),
            ];
        });
    }

    private function reportTitle(?string $startDate, ?string $endDate): string
    {
        return $this->monthName($startDate).' - '.$this->monthName($endDate).' Report';
    }

    private function reportFileName(array $data, string $locationName, string $extension): string
    {
        $name = $this->monthName($data['start_date'] ?? null).' - '.$this->monthName($data['end_date'] ?? null).' '.$locationName.' Reports';
        return $this->sanitizeFileName($name).'.'.$extension;
    }

    private function monthName(?string $date): string
    {
        return $date ? Carbon::parse($date)->format('F') : 'All Periods';
    }

    private function locationName(?int $locationId, $summaries = null): string
    {
        if ($locationId) {
            return ParkingLocation::find($locationId)?->location_name ?? 'Selected Location';
        }

        return $summaries?->first()?->location?->location_name ?? 'All Locations';
    }

    private function sanitizeFileName(string $name): string
    {
        return trim(preg_replace('/[^A-Za-z0-9 \-]+/', '', $name));
    }
}
