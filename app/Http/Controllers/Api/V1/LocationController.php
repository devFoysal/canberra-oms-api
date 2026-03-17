<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Helpers\{
    ResponseHelper,
};

use App\Models\Location;

class LocationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Log incoming request for debugging
        Log::info('Storing location data', ['request' => $request->all()]);

        $validated = $request->validate([
            '*.lat' => 'required|numeric',
            '*.lng' => 'required|numeric',
            '*.timestamp' => 'required',
            '*.accuracy' => 'nullable|numeric',
            '*.altitude' => 'nullable|numeric',
            '*.speed' => 'nullable|numeric',
            '*.heading' => 'nullable|numeric',
            '*.battery_level' => 'nullable|integer',
            '*.network_type' => 'nullable|string',
        ]);

        try {
            $userId = auth()->id();
            $now = now();

            $locations = array_map(function ($item) use ($userId, $now) {
                return [
                    'user_id' => $userId,
                    'latitude' => $item['lat'],
                    'longitude' => $item['lng'],
                    'accuracy' => $item['accuracy'] ?? null,
                    'altitude' => $item['altitude'] ?? null,
                    'speed' => $item['speed'] ?? null,
                    'heading' => $item['heading'] ?? null,
                    'battery_level' => $item['battery_level'] ?? null,
                    'network_type' => $item['network_type'] ?? null,
                    'timestamp' => Carbon::createFromTimestampMs($item['timestamp']),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }, $validated);

            // Log the prepared locations before inserting
            Log::info('Prepared locations for insert', ['locations' => $locations]);

            Location::insert($locations);

            Log::info('Locations successfully stored', ['count' => count($locations), 'user_id' => $userId]);

            return ResponseHelper::success([], 'Location stored', 201);

        } catch (\Throwable $e) {
            // Log the error
            Log::error('Error storing locations', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return ResponseHelper::error($e->getMessage(), 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
