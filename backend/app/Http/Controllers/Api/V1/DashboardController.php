<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\UltrasonicSensor;
use App\Models\VehicleEntry;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function summary(Request $request)
    {
        $locationId = $request->user()?->assigned_location_id ?? $request->query('location_id');
        $now = now();

        $vehiclesToday = VehicleEntry::where('location_id', $locationId)
            ->whereDate('entry_time', $now->toDateString())
            ->sum('vehicle_count');

        $vehiclesThisWeek = VehicleEntry::where('location_id', $locationId)
            ->whereBetween('entry_time', [
                $now->copy()->startOfWeek()->startOfDay(),
                $now->copy()->endOfWeek()->endOfDay(),
            ])
            ->sum('vehicle_count');

        $vehiclesThisMonth = VehicleEntry::where('location_id', $locationId)
            ->whereBetween('entry_time', [
                $now->copy()->startOfMonth()->startOfDay(),
                $now->copy()->endOfMonth()->endOfDay(),
            ])
            ->sum('vehicle_count');

        $sensorStatus = UltrasonicSensor::aggregateStatus(
            UltrasonicSensor::where('location_id', $locationId)->get()
        );

        return response()->json([
            'vehicles_today' => $vehiclesToday,
            'vehicles_this_week' => $vehiclesThisWeek,
            'vehicles_this_month' => $vehiclesThisMonth,
            'sensor_status' => $sensorStatus,
            'last_updated' => $now->toDateTimeString(),
        ]);
    }

    public function recentActivities(Request $request)
    {
        $locationId = $request->user()?->assigned_location_id ?? $request->query('location_id');
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
        $locationId = $request->user()?->assigned_location_id ?? $request->query('location_id');
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

            return response()->json($data);
        }

        $labels = [];
        if ($period === 'monthly') {
            for ($day = 1; $day <= now()->daysInMonth; $day++) {
                $labels[] = now()->copy()->startOfMonth()->addDays($day - 1)->toDateString();
            }
        } else {
            for ($i = 0; $i < 7; $i++) {
                $labels[] = now()->copy()->subDays(6 - $i)->toDateString();
            }
        }

        foreach ($labels as $label) {
            $count = VehicleEntry::where('location_id', $locationId)
                ->whereDate('entry_time', $label)
                ->sum('vehicle_count');
            $data[] = ['label' => $label, 'value' => $count];
        }

        return response()->json($data);
    }
}
