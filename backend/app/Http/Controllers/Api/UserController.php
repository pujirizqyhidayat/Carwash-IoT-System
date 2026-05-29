<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    use AuthorizesRequests;
    /**
     * GET /api/v1/users
     */
    public function index(Request $request)
    {
        $this->authorize('isAdmin');

        $users = User::all()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'full_name' => $user->full_name,
                    'username' => $user->username,
                    'email' => $user->email,
                    'role' => $user->role,
                    'status' => $user->status,
                    'last_login_at' => $user->last_login_at,
                ];
            });

        return response()->json($users);
    }

    /**
     * POST /api/v1/users
     */
    public function store(Request $request)
    {
        $this->authorize('isAdmin');

        $validated = $request->validate([
            'full_name' => 'required|string|max:150',
            'username' => 'required|string|max:100|unique:users',
            'email' => 'required|email|max:150|unique:users',
            'password' => 'required|string|min:8',
            'role' => 'required|in:owner,cashier,admin',
        ]);

        $validated['password'] = Hash::make($validated['password']);
        $validated['status'] = 'active';

        $user = User::create($validated);

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'create',
            'module' => 'user',
            'description' => "Created user: {$user->full_name} ({$user->role})",
            'status' => 'success',
        ]);

        return response()->json([
            'message' => 'User created',
            'user' => [
                'id' => $user->id,
                'full_name' => $user->full_name,
                'email' => $user->email,
                'role' => $user->role,
            ],
        ], 201);
    }

    /**
     * PUT /api/v1/users/{id}
     */
    public function update(Request $request, $id)
    {
        $this->authorize('isAdmin');

        $user = User::findOrFail($id);

        $validated = $request->validate([
            'full_name' => 'string|max:150',
            'role' => 'in:owner,cashier,admin',
            'status' => 'in:active,inactive',
        ]);

        $user->update($validated);

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'update',
            'module' => 'user',
            'description' => "Updated user: {$user->full_name}",
            'status' => 'success',
        ]);

        return response()->json(['message' => 'User updated', 'user' => $user]);
    }

    /**
     * POST /api/v1/users/{id}/reset-password
     */
    public function resetPassword(Request $request, $id)
    {
        $this->authorize('isAdmin');

        $user = User::findOrFail($id);

        $validated = $request->validate([
            'new_password' => 'required|string|min:8',
        ]);

        $user->update(['password' => Hash::make($validated['new_password'])]);

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'update',
            'module' => 'user',
            'description' => "Reset password for user: {$user->full_name}",
            'status' => 'success',
        ]);

        return response()->json(['message' => 'Password reset successfully']);
    }

    /**
     * DELETE /api/v1/users/{id}
     */
    public function destroy(Request $request, $id)
    {
        $this->authorize('isAdmin');

        $user = User::findOrFail($id);
        $userName = $user->full_name;

        $user->update(['status' => 'inactive']);

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'delete',
            'module' => 'user',
            'description' => "Deactivated user: {$userName}",
            'status' => 'success',
        ]);

        return response()->json(['message' => 'User deactivated']);
    }
}
