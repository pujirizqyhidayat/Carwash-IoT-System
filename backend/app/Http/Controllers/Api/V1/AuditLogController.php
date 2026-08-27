<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Exports\AuditLogExport;
use Illuminate\Http\Request;
use App\Models\AuditLog;
use Maatwebsite\Excel\Facades\Excel;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->validate([
            'module' => 'nullable|string',
            'user_id' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $q = AuditLog::with('user:id,username,role');
        if (!empty($data['module'])) $q->where('module', $data['module']);
        if (!empty($data['user_id'])) {
            $data['user_id'] === 'system'
                ? $q->whereNull('user_id')
                : $q->where('user_id', $data['user_id']);
        }
        if (!empty($data['start_date'])) $q->where('created_at', '>=', $data['start_date']);
        if (!empty($data['end_date'])) $q->where('created_at', '<=', $data['end_date']);
        return response()->json($q->orderBy('created_at', 'desc')->paginate(50));
    }

    public function show($id)
    {
        return response()->json(AuditLog::with('user:id,username,role')->findOrFail($id));
    }

    public function export(Request $request)
    {
        $data = $request->validate([
            'module' => 'nullable|string',
            'user_id' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'export',
            'module' => 'audit_log',
            'description' => 'Exported audit logs to Excel.',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'status' => 'success',
            'metadata' => $data,
        ]);

        return Excel::download(
            new AuditLogExport(
                $data['module'] ?? null,
                $data['user_id'] ?? null,
                $data['start_date'] ?? null,
                $data['end_date'] ?? null
            ),
            'audit-logs-'.now()->format('Ymd-His').'.xlsx'
        );
    }
}

