<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\GeoLocation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class GeoLocationController extends Controller
{

 public function index()
{
        $locations = GeoLocation::with('user')
            ->latest()
            ->get()
            ->unique('user_id')
            ->values();

        return response()->json($locations);
}
  public function store(Request $request)
    {
        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'device_info' => 'nullable|string',
        ]);

        $userId = Auth::id();

        // Check if the user already has a location
        $geo = GeoLocation::updateOrCreate(
            ['user_id' => $userId], // condition to check
            [
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'device_info' => $request->device_info,
            ]
        );

        return response()->json([
            'message' => 'Location stored/updated successfully',
            'data' => $geo
        ]);
    }

  public function getUserLocationById($userId)
{
    // Optional: only admin can access
    if (Auth::user()->role !== 'admin') {
        return response()->json(['message' => 'Unauthorized'], 403);
    }

    $geo = GeoLocation::where('user_id', $userId)
        ->latest() // get the most recent
        ->first();

    if (!$geo) {
        return response()->json(['message' => 'No location found for this user'], 404);
    }

    return response()->json([
        'message' => 'Location fetched successfully',
        'data' => $geo
    ]);
}

public function reverseGeocode(Request $request)
    {
        $request->validate([
            'lat' => 'required',
            'lon' => 'required',
        ]);

        $response = Http::withHeaders([
            // REQUIRED by Nominatim policy
            'User-Agent' => 'LiveTracker/1.0 (admin@yourdomain.com)',
        ])
        ->timeout(10)
        ->get('https://nominatim.openstreetmap.org/reverse', [
            'lat' => $request->lat,
            'lon' => $request->lon,
            'format' => 'json',
        ]);

        if ($response->failed()) {
            return response()->json([
                'message' => 'Nominatim failed'
            ], 500);
        }

        return response()->json($response->json());
    }

}
