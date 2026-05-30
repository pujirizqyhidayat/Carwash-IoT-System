<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\AuditLog;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if (!Auth::attempt($credentials)) {
            AuditLog::create([
                'user_id' => null,
                'action' => 'login',
                'module' => 'auth',
                'description' => 'Login failed.',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'status' => 'failed',
                'metadata' => ['email' => $credentials['email']],
            ]);

            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $user = Auth::user();

        if ($user->status !== 'active') {
            Auth::logout();

            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'login',
                'module' => 'auth',
                'description' => 'Inactive user attempted to login.',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'status' => 'failed',
            ]);

            return response()->json(['message' => 'Account inactive'], 403);
        }

        $user->update(['last_login_at' => now()]);
        $token = $user->createToken('api-token')->plainTextToken;

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'login',
            'module' => 'auth',
            'description' => 'Login success.',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'status' => 'success',
        ]);

        return response()->json(['message' => 'Login success', 'user' => $user, 'token' => $token]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out']);
    }

    public function me(Request $request)
    {
        return response()->json($request->user());
    }
}
