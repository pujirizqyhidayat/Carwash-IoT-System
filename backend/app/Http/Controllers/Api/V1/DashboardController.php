<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\UltrasonicSensor;
use App\Models\VehicleEntry;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function summary(Request $request)
    {
        $locationId = $request->query('location_id');

        $today = now()->toDateString();

        $vehiclesToday = VehicleEntry::where('location_id', $locationId)
            ->whereDate('entry_time', $today)
            ->sum('vehicle_count');

        $startOfWeek = now()->startOfWeek();
        $startOfMonth = now()->startOfMonth();

        $vehiclesThisWeek = VehicleEntry::where('location_id', $locationId)
            ->where('entry_time', '>=', $startOfWeek)
            ->sum('vehicle_count');

        $vehiclesThisMonth = VehicleEntry::where('location_id', $locationId)
            ->where('entry_time', '>=', $startOfMonth)
            ->sum('vehicle_count');

        $sensorStatus = UltrasonicSensor::where('location_id', $locationId)
            ->orderByRaw("case status when 'disconnected' then 0 when 'inactive' then 1 else 2 end")
            ->value('status') ?? 'disconnected';

        return response()->json([
            'vehicles_today' => $vehiclesToday,
            'vehicles_this_week' => $vehiclesThisWeek,
            'vehicles_this_month' => $vehiclesThisMonth,
            'sensor_status' => $sensorStatus,
            'last_updated' => now()->toDateTimeString(),
        ]);
    }

    public function recentActivities(Request $request)
    {
        $locationId = $request->query('location_id');
        $limit = intval($request->query('limit', 10));

        $items = VehicleEntry::where('location_id', $locationId)
            ->orderBy('entry_time', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($e) {
                return [
                    'entry_id' => $e->id,
                    'sensor_name' => optional($e->sensor)->sensor_name,
                    'entry_time' => $e->entry_time->toDateTimeString(),
                    'vehicle_count' => $e->vehicle_count,
                ];
            });

        return response()->json($items);
    }

    public function chart(Request $request)
    {
        $locationId = $request->query('location_id');
        $period = $request->query('period', 'daily');
        $data = [];

        if ($period === 'hourly') {
            $date = $request->query('date', now()->toDateString());
            for ($h = 0; $h < 24; $h++) {
                $hourLabel = sprintf('%02d:00', $h);
                $start = Carbon::parse("$date $h:00:00");
                $end = Carbon::parse("$date $h:59:59");
                $count = VehicleEntry::where('location_id', $locationId)
                    ->whereBetween('entry_time', [$start, $end])
                    ->sum('vehicle_count');
                $data[] = ['label' => $hourLabel, 'value' => $count];
            }
        } else {
            $rangeStart = now();
            $labels = [];

            if ($period === 'weekly') {
                $rangeStart = now()->subDays(6);
                for ($i = 0; $i < 7; $i++) {
                    $labels[] = now()->subDays(6 - $i)->toDateString();
                }
            } elseif ($period === 'monthly') {
                $labels = collect(range(1, now()->daysInMonth))->map(fn ($day) => now()->startOfMonth()->addDays($day - 1)->toDateString())->toArray();
            } else {
                $rangeStart = now()->subDays(6);
                for ($i = 0; $i < 7; $i++) {
                    $labels[] = now()->subDays(6 - $i)->toDateString();
                }
            }

            foreach ($labels as $label) {
                $count = VehicleEntry::where('location_id', $locationId)
                    ->whereDate('entry_time', $label)
                    ->sum('vehicle_count');
                $data[] = ['label' => $label, 'value' => $count];
            }
        }

        return response()->json($data);
    }
}
