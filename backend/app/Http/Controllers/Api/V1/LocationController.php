<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ParkingLocation;

class LocationController extends Controller
{
    public function index()
    {
        return response()->json(ParkingLocation::all());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'owner_id' => 'required|integer',
            'location_name' => 'required|string',
            'address' => 'required|string',
            'capacity' => 'nullable|integer',
        ]);
        $loc = ParkingLocation::create($data);
        return response()->json($loc, 201);
    }

    public function update(Request $request, $id)
    {
        $loc = ParkingLocation::findOrFail($id);
        $data = $request->validate([
            'location_name' => 'sometimes|string',
            'address' => 'sometimes|string',
            'capacity' => 'nullable|integer',
        ]);
        $loc->update($data);
        return response()->json($loc);
    }

    public function show($id)
    {
        return response()->json(ParkingLocation::findOrFail($id));
    }

    public function destroy($id)
    {
        $loc = ParkingLocation::findOrFail($id);
        $loc->delete();
        return response()->json(['message' => 'Location deleted']);
    }
}
