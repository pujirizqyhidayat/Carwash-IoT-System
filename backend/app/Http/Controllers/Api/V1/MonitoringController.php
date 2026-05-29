<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\VehicleEntry;

class MonitoringController extends Controller
{
    public function today(Request $request)
    {
        $locationId = $request->query('location_id');
        $date = $request->query('date', now()->toDateString());

        $vehiclesToday = VehicleEntry::where('location_id', $locationId)
            ->whereDate('entry_time', $date)
            ->count();

        return response()->json([
            'date' => $date,
            'vehicles_today' => $vehiclesToday,
            'sensor_status' => 'active',
        ]);
    }

    public function hourly(Request $request)
    {
        $locationId = $request->query('location_id');
        $date = $request->query('date', now()->toDateString());

        $hours = [];
        for ($h = 0; $h < 24; $h++) {
            $start = date('Y-m-d H:i:s', strtotime("$date $h:00:00"));
            $end = date('Y-m-d H:i:s', strtotime("$date $h:59:59"));
            $count = VehicleEntry::where('location_id', $locationId)
                ->whereBetween('entry_time', [$start, $end])
                ->count();
            $hours[] = ['hour' => sprintf('%02d:00', $h), 'total_vehicle' => $count];
        }

        return response()->json($hours);
    }
}
