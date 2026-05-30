<?php

namespace App\Exports;

use App\Models\AuditLog;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class AuditLogExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    public function __construct(
        private readonly ?string $module,
        private readonly ?string $action,
        private readonly ?string $startDate,
        private readonly ?string $endDate
    ) {
    }

    public function collection(): Collection
    {
        return AuditLog::with('user')
            ->when($this->module, fn ($query) => $query->where('module', $this->module))
            ->when($this->action, fn ($query) => $query->where('action', $this->action))
            ->when($this->startDate, fn ($query) => $query->where('created_at', '>=', $this->startDate))
            ->when($this->endDate, fn ($query) => $query->where('created_at', '<=', $this->endDate))
            ->orderBy('created_at')
            ->get();
    }

    public function headings(): array
    {
        return [
            'Created At',
            'User',
            'Action',
            'Module',
            'Status',
            'Description',
            'IP Address',
        ];
    }

    public function map($log): array
    {
        return [
            $log->created_at?->toDateTimeString(),
            $log->user?->username ?? 'system',
            $log->action,
            $log->module,
            $log->status,
            $log->description,
            $log->ip_address,
        ];
    }
}
