<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AuditLog;

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
        return response()->json(['message' => 'Export audit logs not implemented']);
    }
}
