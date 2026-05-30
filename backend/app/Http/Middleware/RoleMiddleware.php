<?php

namespace App\Http\Middleware;

use App\Models\AuditLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user || $user->status !== 'active' || !in_array($user->role, $roles, true)) {
            AuditLog::create([
                'user_id' => $user?->id,
                'action' => 'failed_access',
                'module' => 'authorization',
                'description' => 'Access denied for restricted endpoint.',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'status' => 'failed',
                'metadata' => [
                    'path' => $request->path(),
                    'required_roles' => $roles,
                    'user_role' => $user?->role,
                ],
            ]);

            return response()->json(['message' => 'Access denied'], 403);
        }

        return $next($request);
    }
}
