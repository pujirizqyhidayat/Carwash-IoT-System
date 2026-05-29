<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VehicleEntry;
use App\Models\AuditLog;
use App\Models\UltrasonicSensor;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * GET /api/v1/dashboard/summary?location_id=1
     */
    public function summary(Request $request)
    {
        $request->validate(['location_id' => 'required|integer']);
        $locationId = $request->location_id;

        $today = now()->startOfDay();
        $weekStart = now()->startOfWeek();
        $monthStart = now()->startOfMonth();

        $vehiclesToday = VehicleEntry::where('location_id', $locationId)
            ->where('entry_time', '>=', $today)
            ->sum('vehicle_count');

        $vehiclesWeek = VehicleEntry::where('location_id', $locationId)
            ->where('entry_time', '>=', $weekStart)
            ->sum('vehicle_count');

        $vehiclesMonth = VehicleEntry::where('location_id', $locationId)
            ->where('entry_time', '>=', $monthStart)
            ->sum('vehicle_count');

        $sensorStatus = UltrasonicSensor::where('location_id', $locationId)
            ->orderBy('status')
            ->pluck('status')
            ->first() ?? 'disconnected';

        $lastUpdated = VehicleEntry::where('location_id', $locationId)
            ->orderBy('created_at', 'desc')
            ->first()?->created_at ?? now();

        return response()->json([
            'vehicles_today' => $vehiclesToday,
            'vehicles_this_week' => $vehiclesWeek,
            'vehicles_this_month' => $vehiclesMonth,
            'sensor_status' => $sensorStatus,
            'last_updated' => $lastUpdated,
        ]);
    }

    /**
     * GET /api/v1/dashboard/recent-activities?location_id=1&limit=10
     */
    public function recentActivities(Request $request)
    {
        $request->validate([
            'location_id' => 'required|integer',
            'limit' => 'integer|min:1|max:100',
        ]);

        $limit = $request->limit ?? 10;

        $activities = VehicleEntry::with('sensor')
            ->where('location_id', $request->location_id)
            ->orderBy('entry_time', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($entry) {
                return [
                    'entry_id' => $entry->id,
                    'sensor_name' => $entry->sensor->sensor_name,
                    'entry_time' => $entry->entry_time,
                    'vehicle_count' => $entry->vehicle_count,
                    'detection_confidence' => $entry->detection_confidence,
                ];
            });

        return response()->json($activities);
    }

    /**
     * GET /api/v1/dashboard/chart?location_id=1&period=daily
     */
    public function chart(Request $request)
    {
        $request->validate([
            'location_id' => 'required|integer',
            'period' => 'required|in:hourly,daily,weekly,monthly',
        ]);

        $locationId = $request->location_id;
        $period = $request->period;
        $chartData = [];

        if ($period === 'hourly') {
            for ($i = 0; $i < 24; $i++) {
                $start = now()->startOfDay()->addHours($i);
                $end = $start->copy()->addHour();
                $count = VehicleEntry::where('location_id', $locationId)
                    ->whereBetween('entry_time', [$start, $end])
                    ->sum('vehicle_count');
                $chartData[] = [
                    'hour' => $start->format('H:00'),
                    'total_vehicle' => $count,
                ];
            }
        } elseif ($period === 'daily') {
            for ($i = 0; $i < 7; $i++) {
                $date = now()->subDays($i);
                $count = VehicleEntry::where('location_id', $locationId)
                    ->whereDate('entry_time', $date)
                    ->sum('vehicle_count');
                $chartData[] = [
                    'date' => $date->format('Y-m-d'),
                    'total_vehicle' => $count,
                ];
            }
        } elseif ($period === 'weekly') {
            for ($i = 0; $i < 4; $i++) {
                $weekEnd = now()->subWeeks($i)->endOfWeek();
                $weekStart = $weekEnd->copy()->startOfWeek();
                $count = VehicleEntry::where('location_id', $locationId)
                    ->whereBetween('entry_time', [$weekStart, $weekEnd])
                    ->sum('vehicle_count');
                $chartData[] = [
                    'week' => "Week of " . $weekStart->format('Y-m-d'),
                    'total_vehicle' => $count,
                ];
            }
        } elseif ($period === 'monthly') {
            for ($i = 0; $i < 12; $i++) {
                $month = now()->subMonths($i);
                $count = VehicleEntry::where('location_id', $locationId)
                    ->whereYear('entry_time', $month->year)
                    ->whereMonth('entry_time', $month->month)
                    ->sum('vehicle_count');
                $chartData[] = [
                    'month' => $month->format('Y-m'),
                    'total_vehicle' => $count,
                ];
            }
        }

        return response()->json(['data' => $chartData]);
    }
}
