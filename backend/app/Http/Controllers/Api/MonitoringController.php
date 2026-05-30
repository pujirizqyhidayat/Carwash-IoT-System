<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VehicleEntry;
use Illuminate\Http\Request;

class MonitoringController extends Controller
{
    /**
     * GET /api/v1/monitoring/today?location_id=1
     */
    public function today(Request $request)
    {
        $request->validate(['location_id' => 'required|integer']);

        $today = now()->format('Y-m-d');
        $vehiclesToday = VehicleEntry::where('location_id', $request->location_id)
            ->whereDate('entry_time', $today)
            ->sum('vehicle_count');

        return response()->json([
            'date' => $today,
            'vehicles_today' => $vehiclesToday,
            'sensor_status' => 'active',
        ]);
    }

    /**
     * GET /api/v1/monitoring/hourly?location_id=1&date=2026-02-17
     */
    public function hourly(Request $request)
    {
        $request->validate([
            'location_id' => 'required|integer',
            'date' => 'required|date_format:Y-m-d',
        ]);

        $date = $request->date;
        $hourly = [];

        for ($hour = 0; $hour < 24; $hour++) {
            $startTime = "{$date} " . str_pad($hour, 2, '0', STR_PAD_LEFT) . ":00:00";
            $endTime = "{$date} " . str_pad($hour, 2, '0', STR_PAD_LEFT) . ":59:59";

            $count = VehicleEntry::where('location_id', $request->location_id)
                ->whereBetween('entry_time', [$startTime, $endTime])
                ->sum('vehicle_count');

            $hourly[] = [
                'hour' => str_pad($hour, 2, '0', STR_PAD_LEFT) . ':00',
                'total_vehicle' => $count,
            ];
        }

        return response()->json($hourly);
    }
}
