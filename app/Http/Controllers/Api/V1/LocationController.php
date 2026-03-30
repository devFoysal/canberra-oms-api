<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Helpers\{
    ResponseHelper,
};

use App\Http\Resources\Api\V1\Location\{
    LocationResource,
    LocationCollectionResource
};

use App\Models\{
    User,
    Location
};

class LocationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
         $userId = $request->userId
        ? explode(',', $request->userId)
        : [];

        $locations = Location::query()
            ->select([
                'user_id',
                'latitude',
                'longitude',
                'timestamp'
            ])

            ->when(!empty($userId), fn ($q) =>
                $q->where('user_id', $userId)
            )

            ->when($request->fromDate && $request->toDate, fn ($q) =>
                $q->whereBetween('timestamp', [
                    $request->fromDate,
                    $request->toDate
                ])
            )

            ->orderBy('user_id')
            ->orderBy('timestamp')
            ->get()
            ->groupBy('user_id'); // group in memory (fast enough if limited)

        $users = User::whereIn('id', $locations->keys())
            ->pluck('full_name', 'id');

        $result = $locations->map(function ($items, $userId) use ($users) {
            return [
                'user_id' => $userId,
                'user_name' => $users[$userId] ?? 'Unknown',
                'locations' => $items->map(fn ($loc) => [
                    'lat' => (float) $loc->latitude,
                    'lng' => (float) $loc->longitude,
                    'time' => $loc->timestamp,
                ])->values()
            ];
        })->values();

        return ResponseHelper::success($result, 'Location get successfully', 200);
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
