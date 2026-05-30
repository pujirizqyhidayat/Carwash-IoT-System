<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DeviceKeyMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $expectedKey = env('IOT_DEVICE_KEY', 'sensor_device_secret_key');

        if (!$request->header('X-DEVICE-KEY') || !hash_equals($expectedKey, $request->header('X-DEVICE-KEY'))) {
            return response()->json(['message' => 'Invalid device key'], 401);
        }

        return $next($request);
    }
}
