<?php

namespace App\Exports;

use App\Models\VehicleCountSummary;
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
        return VehicleCountSummary::with('location')
            ->when($this->locationId, fn ($query) => $query->where('location_id', $this->locationId))
            ->when($this->startDate, fn ($query) => $query->where('summary_date', '>=', $this->startDate))
            ->when($this->endDate, fn ($query) => $query->where('summary_date', '<=', $this->endDate))
            ->orderBy('summary_date')
            ->get();
    }

    public function headings(): array
    {
        return [
            'Date',
            'Location',
            'Total Vehicle',
            'Generated At',
        ];
    }

    public function map($summary): array
    {
        return [
            $summary->summary_date?->toDateString(),
            $summary->location?->location_name,
            $summary->total_vehicle,
            $summary->generated_at?->toDateTimeString(),
        ];
    }
}
