<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\ParkingLocation;
use App\Models\VehicleCountSummary;
use App\Models\VehicleEntry;
use Illuminate\Console\Command;

class GenerateDailyVehicleSummary extends Command
{
    protected $signature = 'reports:generate-daily-summary {date?}';

    protected $description = 'Generate daily vehicle count summaries for every location.';

    public function handle(): int
    {
        $summaryDate = $this->argument('date') ?: now()->toDateString();

        ParkingLocation::query()->each(function (ParkingLocation $location) use ($summaryDate) {
            $total = VehicleEntry::where('location_id', $location->id)
                ->whereDate('entry_time', $summaryDate)
                ->sum('vehicle_count');

            VehicleCountSummary::updateOrCreate(
                ['location_id' => $location->id, 'summary_date' => $summaryDate],
                [
                    'total_vehicle' => $total,
                    'generated_by' => null,
                    'generated_at' => now(),
                ]
            );
        });

        AuditLog::create([
            'user_id' => null,
            'action' => 'create',
            'module' => 'report',
            'description' => "Generated daily vehicle summaries for {$summaryDate}.",
            'status' => 'success',
            'metadata' => ['summary_date' => $summaryDate],
        ]);

        $this->info("Daily summaries generated for {$summaryDate}.");

        return self::SUCCESS;
    }
}
