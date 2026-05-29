<?php

namespace App\Http\Controllers\Api;

use Illuminate\Auth\Access\AuthorizationException;

trait AuthorizesRequests
{
    /**
     * Check if user is Admin
     */
    public function authorize($ability, $arguments = [])
    {
        if ($ability === 'isAdmin') {
            if (!auth()->check() || auth()->user()->role !== 'admin') {
                throw new AuthorizationException('Unauthorized - Admin only');
            }
        }
    }
}
