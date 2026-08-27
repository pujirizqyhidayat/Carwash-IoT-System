<?php

namespace App\Exports;

use App\Models\ParkingLocation;
use App\Models\VehicleEntry;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class VehicleCountSummaryExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    public function __construct(
        private readonly ?int $locationId,
        private readonly ?string $startDate,
        private readonly ?string $endDate
    ) {
    }

    public function collection(): Collection
    {
        $summaries = VehicleEntry::query()
            ->leftJoin('carwash_transactions', 'carwash_transactions.vehicle_entry_id', '=', 'vehicle_entries.id')
            ->selectRaw('vehicle_entries.location_id, DATE(vehicle_entries.entry_time) as summary_date, SUM(vehicle_entries.vehicle_count) as total_vehicle, COUNT(carwash_transactions.id) as total_transactions, COALESCE(SUM(carwash_transactions.price), 0) as total_revenue')
            ->when($this->locationId, fn ($query) => $query->where('vehicle_entries.location_id', $this->locationId))
            ->when($this->startDate, fn ($query) => $query->whereDate('vehicle_entries.entry_time', '>=', $this->startDate))
            ->when($this->endDate, fn ($query) => $query->whereDate('vehicle_entries.entry_time', '<=', $this->endDate))
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

    public function headings(): array
    {
        return [
            'Period',
            'Location',
            'Vehicle Entry (In Days)',
            'Total Transactions',
            'Total Revenue',
        ];
    }

    public function map($summary): array
    {
        return [
            $summary->summary_date?->format('j-n-Y'),
            $summary->location?->location_name,
            $summary->total_vehicle,
            $summary->total_transactions,
            $summary->total_revenue,
        ];
    }
}
