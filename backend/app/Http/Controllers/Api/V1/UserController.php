<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index()
    {
        return response()->json(User::all());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'full_name' => 'required|string',
            'username' => 'required|string|unique:users,username',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'role' => 'required|in:owner,cashier,admin',
        ]);

        $data['password'] = Hash::make($data['password']);
        $user = User::create($data);
        return response()->json($user, 201);
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $data = $request->validate([
            'full_name' => 'sometimes|string',
            'role' => 'sometimes|in:owner,cashier,admin',
            'status' => 'sometimes|in:active,inactive',
        ]);
        $user->update($data);
        return response()->json($user);
    }

    public function show($id)
    {
        return response()->json(User::findOrFail($id));
    }

    public function resetPassword(Request $request, $id)
    {
        $data = $request->validate(['new_password' => 'required|string|min:6']);
        $user = User::findOrFail($id);
        $user->password = Hash::make($data['new_password']);
        $user->save();
        return response()->json(['message' => 'Password reset']);
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->update(['status' => 'inactive']);
        return response()->json(['message' => 'User deactivated']);
    }
}
