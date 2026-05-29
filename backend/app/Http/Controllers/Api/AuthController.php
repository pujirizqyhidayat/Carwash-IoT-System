<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    /**
     * POST /api/v1/auth/login
     */
    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            // Log failed attempt
            AuditLog::create([
                'user_id' => null,
                'action' => 'login',
                'module' => 'auth',
                'description' => 'Failed login attempt for email: ' . $validated['email'],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'status' => 'failed',
            ]);

            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        // Update last login
        $user->update(['last_login_at' => now()]);

        // Create token
        $token = $user->createToken('auth-token')->plainTextToken;

        // Log success
        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'login',
            'module' => 'auth',
            'description' => 'User login success',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'status' => 'success',
        ]);

        return response()->json([
            'message' => 'Login success',
            'user' => [
                'id' => $user->id,
                'full_name' => $user->full_name,
                'email' => $user->email,
                'role' => $user->role,
                'status' => $user->status,
            ],
            'token' => $token,
        ]);
    }

    /**
     * POST /api/v1/auth/logout
     */
    public function logout(Request $request)
    {
        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'logout',
            'module' => 'auth',
            'description' => 'User logout',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'status' => 'success',
        ]);

        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logout success']);
    }

    /**
     * GET /api/v1/auth/me
     */
    public function me(Request $request)
    {
        return response()->json([
            'user' => [
                'id' => $request->user()->id,
                'full_name' => $request->user()->full_name,
                'email' => $request->user()->email,
                'username' => $request->user()->username,
                'role' => $request->user()->role,
                'status' => $request->user()->status,
                'last_login_at' => $request->user()->last_login_at,
            ],
        ]);
    }
}
