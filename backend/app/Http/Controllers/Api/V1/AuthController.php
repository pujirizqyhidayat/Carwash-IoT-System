<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
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

    public function forgotPassword(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email',
            'new_password' => 'required|string|min:6',
        ]);

        $user = User::where('email', $data['email'])->first();

        if (!$user) {
            AuditLog::create([
                'user_id' => null,
                'action' => 'forgot_password',
                'module' => 'auth',
                'description' => 'Forgot password failed. Email not found.',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'status' => 'failed',
                'metadata' => ['email' => $data['email']],
            ]);

            return response()->json(['message' => 'Email not found'], 404);
        }

        $user->password = Hash::make($data['new_password']);
        $user->tokens()->delete();
        $user->save();

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'forgot_password',
            'module' => 'auth',
            'description' => 'Password changed from forgot password.',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'status' => 'success',
        ]);

        return response()->json(['message' => 'Password changed successfully']);
    }

    public function changePassword(Request $request)
    {
        $data = $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:6|confirmed',
        ]);

        $user = $request->user();

        if (!Hash::check($data['current_password'], $user->password)) {
            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'change_password',
                'module' => 'auth',
                'description' => 'Change password failed. Current password is incorrect.',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'status' => 'failed',
            ]);

            return response()->json(['message' => 'Current password is incorrect'], 422);
        }

        $user->password = Hash::make($data['new_password']);
        $user->tokens()->where('id', '!=', $request->user()->currentAccessToken()->id)->delete();
        $user->save();

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'change_password',
            'module' => 'auth',
            'description' => 'Changed own password from profile.',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'status' => 'success',
        ]);

        return response()->json(['message' => 'Password changed successfully']);
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

