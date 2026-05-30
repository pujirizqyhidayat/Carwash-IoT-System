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
        $q = AuditLog::query();
        if ($request->has('module')) $q->where('module', $request->query('module'));
        if ($request->has('action')) $q->where('action', $request->query('action'));
        if ($request->has('start_date')) $q->where('created_at', '>=', $request->query('start_date'));
        if ($request->has('end_date')) $q->where('created_at', '<=', $request->query('end_date'));
        return response()->json($q->orderBy('created_at', 'desc')->paginate(50));
    }

    public function show($id)
    {
        return response()->json(AuditLog::findOrFail($id));
    }

    public function export(Request $request)
    {
        $data = $request->validate([
            'module' => 'nullable|string',
            'action' => 'nullable|string',
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
                $data['action'] ?? null,
                $data['start_date'] ?? null,
                $data['end_date'] ?? null
            ),
            'audit-logs-'.now()->format('Ymd-His').'.xlsx'
        );
    }
}
