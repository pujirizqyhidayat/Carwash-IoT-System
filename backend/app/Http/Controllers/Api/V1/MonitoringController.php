<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\VehicleEntry;
use App\Models\UltrasonicSensor;

class MonitoringController extends Controller
{
    public function today(Request $request)
    {
        $locationId = $request->query('location_id');
        $date = $request->query('date', now()->toDateString());

        $vehiclesToday = VehicleEntry::where('location_id', $locationId)
            ->whereDate('entry_time', $date)
            ->sum('vehicle_count');

        $sensorStatus = UltrasonicSensor::where('location_id', $locationId)
            ->orderByRaw("case status when 'disconnected' then 0 when 'inactive' then 1 else 2 end")
            ->value('status') ?? 'disconnected';

        return response()->json([
            'date' => $date,
            'vehicles_today' => $vehiclesToday,
            'sensor_status' => $sensorStatus,
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
                ->sum('vehicle_count');
            $hours[] = ['hour' => sprintf('%02d:00', $h), 'total_vehicle' => $count];
        }

        return response()->json($hours);
    }
}
