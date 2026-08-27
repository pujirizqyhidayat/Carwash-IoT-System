<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\CarwashTransaction;
use App\Models\UltrasonicSensor;
use App\Models\VehicleEntry;
use App\Models\WashService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MonitoringController extends Controller
{
    public function today(Request $request)
    {
        $locationId = $this->locationId($request);
        $date = $request->query('date', now()->toDateString());

        $vehiclesToday = VehicleEntry::where('location_id', $locationId)
            ->whereDate('entry_time', $date)
            ->sum('vehicle_count');

        $transactionsToday = CarwashTransaction::where('location_id', $locationId)
            ->whereDate('transacted_at', $date)
            ->count();

        $totalRevenue = CarwashTransaction::where('location_id', $locationId)
            ->whereDate('transacted_at', $date)
            ->sum('price');

        $pendingTransactions = VehicleEntry::where('location_id', $locationId)
            ->whereDate('entry_time', $date)
            ->whereDoesntHave('transaction')
            ->count();

        $sensorStatus = UltrasonicSensor::aggregateStatus(
            UltrasonicSensor::where('location_id', $locationId)->get()
        );

        return response()->json([
            'date' => $date,
            'vehicles_today' => $vehiclesToday,
            'transactions_today' => $transactionsToday,
            'pending_transactions' => $pendingTransactions,
            'total_revenue' => (int) $totalRevenue,
            'sensor_status' => $sensorStatus,
        ]);
    }

    public function hourly(Request $request)
    {
        $locationId = $this->locationId($request);
        $date = $request->query('date', now()->toDateString());

        $hours = [];
        for ($h = 0; $h < 24; $h++) {
            $start = date('Y-m-d H:i:s', strtotime("$date $h:00:00"));
            $end = date('Y-m-d H:i:s', strtotime("$date $h:59:59"));

            $count = VehicleEntry::where('location_id', $locationId)
                ->whereBetween('entry_time', [$start, $end])
                ->sum('vehicle_count');

            $revenue = CarwashTransaction::where('location_id', $locationId)
                ->whereBetween('transacted_at', [$start, $end])
                ->sum('price');

            $hours[] = [
                'hour' => sprintf('%02d:00', $h),
                'total_vehicle' => $count,
                'total_revenue' => (int) $revenue,
            ];
        }

        return response()->json($hours);
    }

    public function services()
    {
        return response()->json(
            WashService::where('is_active', true)
                ->orderBy('service_name')
                ->get(['id', 'service_name', 'price'])
        );
    }

    public function entries(Request $request)
    {
        $locationId = $this->locationId($request);
        $date = $request->query('date', now()->toDateString());

        $entries = VehicleEntry::with(['transaction.cashier', 'transaction.washService'])
            ->where('location_id', $locationId)
            ->whereDate('entry_time', $date)
            ->orderByDesc('entry_time')
            ->get()
            ->map(fn ($entry) => [
                'id' => $entry->id,
                'location_id' => $entry->location_id,
                'sensor_id' => $entry->sensor_id,
                'entry_time' => $entry->entry_time?->toDateTimeString(),
                'vehicle_count' => $entry->vehicle_count,
                'transaction' => $entry->transaction ? [
                    'id' => $entry->transaction->id,
                    'service_name' => $entry->transaction->service_name,
                    'price' => $entry->transaction->price,
                    'payment_status' => $entry->transaction->payment_status,
                    'cashier_name' => $entry->transaction->cashier?->full_name,
                    'transacted_at' => $entry->transaction->transacted_at?->toDateTimeString(),
                ] : null,
            ]);

        return response()->json($entries);
    }

    public function storeTransaction(Request $request, VehicleEntry $vehicleEntry)
    {
        $this->authorizeLocation($request, $vehicleEntry->location_id);

        $data = $request->validate([
            'wash_service_id' => 'nullable|exists:wash_services,id',
            'service_name' => 'nullable|string|max:100',
            'price' => 'nullable|integer|min:0',
            'payment_status' => 'nullable|in:paid,unpaid',
            'notes' => 'nullable|string|max:500',
        ]);

        $service = null;
        if (!empty($data['wash_service_id'])) {
            $service = WashService::where('is_active', true)->findOrFail($data['wash_service_id']);
        }

        if (!$service && empty($data['service_name'])) {
            return response()->json(['message' => 'Service is required'], 422);
        }

        $serviceName = $service?->service_name ?? $data['service_name'];
        $price = $service
            ? (int) $service->price
            : (int) ($data['price'] ?? 0);

        $transaction = DB::transaction(function () use ($request, $vehicleEntry, $service, $serviceName, $price, $data) {
            $transaction = CarwashTransaction::updateOrCreate(
                ['vehicle_entry_id' => $vehicleEntry->id],
                [
                    'location_id' => $vehicleEntry->location_id,
                    'wash_service_id' => $service?->id,
                    'cashier_id' => $request->user()?->id,
                    'service_name' => $serviceName,
                    'price' => $price,
                    'payment_status' => $data['payment_status'] ?? 'paid',
                    'notes' => $data['notes'] ?? null,
                    'transacted_at' => now(),
                ]
            );

            AuditLog::create([
                'user_id' => $request->user()?->id,
                'action' => 'create',
                'module' => 'transaction',
                'description' => "Recorded transaction for vehicle entry {$vehicleEntry->id}.",
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'status' => 'success',
                'metadata' => [
                    'vehicle_entry_id' => $vehicleEntry->id,
                    'location_id' => $vehicleEntry->location_id,
                    'service_name' => $serviceName,
                    'price' => $price,
                ],
            ]);

            return $transaction;
        });

        return response()->json([
            'message' => 'Transaction saved',
            'transaction' => $transaction->fresh(['cashier', 'washService']),
        ]);
    }

    private function locationId(Request $request): ?int
    {
        return $request->user()?->assigned_location_id ?? $request->query('location_id');
    }

    private function authorizeLocation(Request $request, int $locationId): void
    {
        $assignedLocationId = $request->user()?->assigned_location_id;

        if ($assignedLocationId && (int) $assignedLocationId !== $locationId) {
            abort(403, 'Access denied');
        }
    }
}
