<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\LocationSession;
use App\Models\LocationPoint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class LocationController extends Controller
{
    /**
     * Save a complete location session
     */
    public function saveSession(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'sessionId' => 'required|string|max:255',
            'userId' => 'required|max:255',
            'startTime' => 'required|date',
            'endTime' => 'nullable|date',
            'totalDistance' => 'required|numeric|min:0',
            'maxSpeed' => 'required|numeric|min:0',
            'avgSpeed' => 'required|numeric|min:0',
            'duration' => 'required|integer|min:0',
            'pointsCount' => 'required|integer|min:0',
            'path' => 'required|array|min:1',
            'path.*.id' => 'required|string',
            'path.*.lat' => 'required|numeric|between:-90,90',
            'path.*.lng' => 'required|numeric|between:-180,180',
            'path.*.timestamp' => 'required|date',
            'path.*.accuracy' => 'nullable|numeric|min:0',
            'path.*.altitude' => 'nullable|numeric',
            'path.*.speed' => 'nullable|numeric|min:0',
            'path.*.heading' => 'nullable|numeric|between:0,360',
            'path.*.battery' => 'nullable|numeric|between:0,100',
            'path.*.network' => 'nullable|string',
            'deviceId' => 'nullable|string|max:255',
            'deviceInfo' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $user = Auth::user();

        try {
            DB::beginTransaction();

            // Check if session already exists
            $session = LocationSession::where('session_id', $request->sessionId)
                ->where('user_id', $user->id)
                ->first();

            if ($session) {
                return response()->json([
                    'success' => false,
                    'message' => 'Session already exists'
                ], 409);
            }

            // Create session
            $session = LocationSession::create([
                'session_id' => $request->sessionId,
                'user_id' => $user->id,
                'device_id' => $request->deviceId,
                'start_latitude' => $request->path[0]['lat'],
                'start_longitude' => $request->path[0]['lng'],
                'end_latitude' => $request->path[count($request->path) - 1]['lat'] ?? null,
                'end_longitude' => $request->path[count($request->path) - 1]['lng'] ?? null,
                'start_time' => $request->startTime,
                'end_time' => $request->endTime,
                'total_distance' => $request->totalDistance,
                'max_speed' => $request->maxSpeed,
                'avg_speed' => $request->avgSpeed,
                'duration' => $request->duration,
                'points_count' => $request->pointsCount,
                'device_info' => $request->deviceInfo,
            ]);

            // Prepare points for bulk insert
            $points = [];
            foreach ($request->path as $point) {
                $points[] = [
                    'session_id' => $session->id,
                    'user_id' => $user->id,
                    'point_id' => $point['id'],
                    'latitude' => $point['lat'],
                    'longitude' => $point['lng'],
                    'accuracy' => $point['accuracy'] ?? null,
                    'altitude' => $point['altitude'] ?? null,
                    'speed' => $point['speed'] ?? null,
                    'heading' => $point['heading'] ?? null,
                    'battery_level' => $point['battery'] ?? null,
                    'network_type' => $point['network'] ?? null,
                    'timestamp' => Carbon::parse($point['timestamp'])->format('Y-m-d H:i:s'),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            // Bulk insert points
            if (!empty($points)) {
                LocationPoint::insert($points);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Session saved successfully',
                'data' => [
                    'session_id' => $session->session_id,
                    'points_saved' => count($points),
                    'session_created_at' => $session->created_at,
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error('Error saving location session:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $user->id,
                'session_id' => $request->sessionId
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to save session',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Save individual location points in bulk
     */
    public function savePoints(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'points' => 'required|array|min:1',
            'points.*.id' => 'required|string|max:255',
            'points.*.lat' => 'required|numeric|between:-90,90',
            'points.*.lng' => 'required|numeric|between:-180,180',
            'points.*.timestamp' => 'required|date',
            'points.*.sessionId' => 'required|string|max:255',
            'points.*.accuracy' => 'nullable|numeric|min:0',
            'points.*.altitude' => 'nullable|numeric',
            'points.*.speed' => 'nullable|numeric|min:0',
            'points.*.heading' => 'nullable|numeric|between:0,360',
            'points.*.battery' => 'nullable|numeric|between:0,100',
            'points.*.network' => 'nullable|string|max:50',
            'points.*.userId' => 'nullable|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $user = Auth::user();
        $sessionIds = array_unique(array_column($request->points, 'sessionId'));

        try {
            // Get session IDs for the points
            $sessions = LocationSession::whereIn('session_id', $sessionIds)
                ->where('user_id', $user->id)
                ->pluck('id', 'session_id')
                ->toArray();

            // Prepare points for bulk insert
            $pointsToInsert = [];
            $duplicatePoints = 0;

            foreach ($request->points as $point) {
                // Check if session exists
                if (!isset($sessions[$point['sessionId']])) {
                    \Log::warning('Session not found for point', [
                        'session_id' => $point['sessionId'],
                        'point_id' => $point['id']
                    ]);
                    continue;
                }

                // Check if point already exists
                $exists = LocationPoint::where('point_id', $point['id'])
                    ->where('user_id', $user->id)
                    ->exists();

                if ($exists) {
                    $duplicatePoints++;
                    continue;
                }

                $pointsToInsert[] = [
                    'session_id' => $sessions[$point['sessionId']],
                    'user_id' => $user->id,
                    'point_id' => $point['id'],
                    'latitude' => $point['lat'],
                    'longitude' => $point['lng'],
                    'accuracy' => $point['accuracy'] ?? null,
                    'altitude' => $point['altitude'] ?? null,
                    'speed' => $point['speed'] ?? null,
                    'heading' => $point['heading'] ?? null,
                    'battery_level' => $point['battery'] ?? null,
                    'network_type' => $point['network'] ?? null,
                    'timestamp' => Carbon::parse($point['timestamp'])->format('Y-m-d H:i:s'),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            // Insert points in batches of 500
            $chunks = array_chunk($pointsToInsert, 500);
            $totalInserted = 0;

            foreach ($chunks as $chunk) {
                LocationPoint::insert($chunk);
                $totalInserted += count($chunk);
            }

            return response()->json([
                'success' => true,
                'message' => 'Points saved successfully',
                'data' => [
                    'points_inserted' => $totalInserted,
                    'points_duplicate' => $duplicatePoints,
                    'total_processed' => count($request->points)
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Error saving location points:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $user->id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to save points',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get all sessions for the authenticated user
     */
    public function getSessions(Request $request)
    {
        $user = Auth::user();

        $query = LocationSession::where('user_id', $user->id)
            ->withCount('points')
            ->orderBy('created_at', 'desc');

        // Add pagination
        $perPage = $request->get('per_page', 20);
        $sessions = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $sessions
        ]);
    }

    /**
     * Get a specific session by ID
     */
    public function getSession($sessionId, Request $request)
    {
        $user = Auth::user();

        $session = LocationSession::withCount('points')
            ->where('session_id', $sessionId)
            ->where('user_id', $user->id)
            ->first();

        if (!$session) {
            return response()->json([
                'success' => false,
                'message' => 'Session not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $session
        ]);
    }

    /**
     * Get all points for a specific session
     */
    public function getSessionPoints($sessionId, Request $request)
    {
        $user = Auth::user();

        // Find the session
        $session = LocationSession::where('session_id', $sessionId)
            ->where('user_id', $user->id)
            ->first();

        if (!$session) {
            return response()->json([
                'success' => false,
                'message' => 'Session not found'
            ], 404);
        }

        // Get points with pagination
        $perPage = $request->get('per_page', 1000);
        $points = LocationPoint::where('session_id', $session->id)
            ->orderBy('timestamp')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => [
                'session' => $session,
                'points' => $points
            ]
        ]);
    }

    /**
     * Get session statistics
     */
    public function getSessionStats($sessionId, Request $request)
    {
        $user = Auth::user();

        $session = LocationSession::where('session_id', $sessionId)
            ->where('user_id', $user->id)
            ->first();

        if (!$session) {
            return response()->json([
                'success' => false,
                'message' => 'Session not found'
            ], 404);
        }

        // Get additional statistics
        $stats = [
            'session' => $session,
            'point_statistics' => [
                'total_points' => $session->points_count,
                'average_accuracy' => $session->points()->avg('accuracy'),
                'max_altitude' => $session->points()->max('altitude'),
                'min_altitude' => $session->points()->min('altitude'),
                'average_speed' => $session->points()->avg('speed'),
            ],
            'time_statistics' => [
                'duration_hours' => $session->duration / 3600,
                'points_per_hour' => $session->duration > 0 ?
                    ($session->points_count / ($session->duration / 3600)) : 0,
                'average_time_between_points' => $session->points_count > 1 ?
                    ($session->duration / $session->points_count) : 0,
            ]
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Delete a session
     */
    public function deleteSession($sessionId, Request $request)
    {
        $user = Auth::user();

        $session = LocationSession::where('session_id', $sessionId)
            ->where('user_id', $user->id)
            ->first();

        if (!$session) {
            return response()->json([
                'success' => false,
                'message' => 'Session not found'
            ], 404);
        }

        try {
            // Delete session and all related points (cascade delete)
            $session->delete();

            return response()->json([
                'success' => true,
                'message' => 'Session deleted successfully'
            ]);

        } catch (\Exception $e) {
            \Log::error('Error deleting location session:', [
                'error' => $e->getMessage(),
                'session_id' => $sessionId,
                'user_id' => $user->id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete session'
            ], 500);
        }
    }

    /**
     * Get user's location statistics
     */
    public function getUserStats(Request $request)
    {
        $user = Auth::user();

        $stats = [
            'total_sessions' => LocationSession::where('user_id', $user->id)->count(),
            'total_points' => LocationPoint::where('user_id', $user->id)->count(),
            'total_distance' => LocationSession::where('user_id', $user->id)->sum('total_distance'),
            'total_duration' => LocationSession::where('user_id', $user->id)->sum('duration'),
            'avg_speed' => LocationSession::where('user_id', $user->id)->avg('avg_speed'),
            'max_speed' => LocationSession::where('user_id', $user->id)->max('max_speed'),
            'first_session_date' => LocationSession::where('user_id', $user->id)->min('created_at'),
            'last_session_date' => LocationSession::where('user_id', $user->id)->max('created_at'),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Sync endpoint for offline data
     */
    public function syncData(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'sessions' => 'nullable|array',
            'points' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $user = Auth::user();
        $results = [
            'sessions' => [
                'saved' => 0,
                'failed' => 0,
                'duplicate' => 0
            ],
            'points' => [
                'saved' => 0,
                'failed' => 0,
                'duplicate' => 0
            ]
        ];

        try {
            DB::beginTransaction();

            // Process sessions
            if ($request->has('sessions') && is_array($request->sessions)) {
                foreach ($request->sessions as $sessionData) {
                    try {
                        // Check if session exists
                        $exists = LocationSession::where('session_id', $sessionData['sessionId'])
                            ->where('user_id', $user->id)
                            ->exists();

                        if ($exists) {
                            $results['sessions']['duplicate']++;
                            continue;
                        }

                        // Create session
                        LocationSession::create([
                            'session_id' => $sessionData['sessionId'],
                            'user_id' => $user->id,
                            'device_id' => $sessionData['deviceId'] ?? null,
                            'start_latitude' => $sessionData['path'][0]['lat'] ?? 0,
                            'start_longitude' => $sessionData['path'][0]['lng'] ?? 0,
                            'end_latitude' => $sessionData['path'][count($sessionData['path']) - 1]['lat'] ?? null,
                            'end_longitude' => $sessionData['path'][count($sessionData['path']) - 1]['lng'] ?? null,
                            'start_time' => $sessionData['startTime'],
                            'end_time' => $sessionData['endTime'] ?? null,
                            'total_distance' => $sessionData['totalDistance'] ?? 0,
                            'max_speed' => $sessionData['maxSpeed'] ?? 0,
                            'avg_speed' => $sessionData['avgSpeed'] ?? 0,
                            'duration' => $sessionData['duration'] ?? 0,
                            'points_count' => $sessionData['pointsCount'] ?? 0,
                            'device_info' => $sessionData['deviceInfo'] ?? null,
                        ]);

                        $results['sessions']['saved']++;
                    } catch (\Exception $e) {
                        $results['sessions']['failed']++;
                        \Log::error('Error syncing session:', [
                            'error' => $e->getMessage(),
                            'session_id' => $sessionData['sessionId'] ?? 'unknown'
                        ]);
                    }
                }
            }

            // Process points
            if ($request->has('points') && is_array($request->points)) {
                // Get all session IDs for efficient lookup
                $sessionIds = array_unique(array_column($request->points, 'sessionId'));
                $sessions = LocationSession::whereIn('session_id', $sessionIds)
                    ->where('user_id', $user->id)
                    ->pluck('id', 'session_id')
                    ->toArray();

                $pointsToInsert = [];

                foreach ($request->points as $point) {
                    try {
                        if (!isset($sessions[$point['sessionId']])) {
                            $results['points']['failed']++;
                            continue;
                        }

                        // Check for duplicates
                        $exists = LocationPoint::where('point_id', $point['id'])
                            ->where('user_id', $user->id)
                            ->exists();

                        if ($exists) {
                            $results['points']['duplicate']++;
                            continue;
                        }

                        $pointsToInsert[] = [
                            'session_id' => $sessions[$point['sessionId']],
                            'user_id' => $user->id,
                            'point_id' => $point['id'],
                            'latitude' => $point['lat'],
                            'longitude' => $point['lng'],
                            'accuracy' => $point['accuracy'] ?? null,
                            'altitude' => $point['altitude'] ?? null,
                            'speed' => $point['speed'] ?? null,
                            'heading' => $point['heading'] ?? null,
                            'battery_level' => $point['battery'] ?? null,
                            'network_type' => $point['network'] ?? null,
                            'timestamp' => Carbon::parse($point['timestamp'])->format('Y-m-d H:i:s'),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];

                    } catch (\Exception $e) {
                        $results['points']['failed']++;
                        \Log::error('Error processing point for sync:', [
                            'error' => $e->getMessage(),
                            'point_id' => $point['id'] ?? 'unknown'
                        ]);
                    }
                }

                // Insert points in batches
                $chunks = array_chunk($pointsToInsert, 500);
                foreach ($chunks as $chunk) {
                    LocationPoint::insert($chunk);
                    $results['points']['saved'] += count($chunk);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Data synced successfully',
                'data' => $results
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error('Error in syncData:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $user->id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Sync failed',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }
}
