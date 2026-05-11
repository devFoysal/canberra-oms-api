<?php

// ══════════════════════════════════════════════════════════════════════════════
// IdleEventController.php
// Route: /api/idle-events
// ══════════════════════════════════════════════════════════════════════════════

namespace App\Http\Controllers\Api\V1;

use App\Services\IdleDetectionService;

class IdleEventController extends Controller
{
    public function __construct(private IdleDetectionService $idleService) {}

    // GET /api/idle-events  (Admin only)
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', \App\Models\IdleEvent::class);

        $events = $this->idleService->getEventsForAdmin($request->only([
            'sales_rep_id',
            'date',
            'is_resolved',
        ]));

        return response()->json($events);
    }

    // GET /api/idle-events/my-status  (SR only)
    public function myStatus(): JsonResponse
    {
        $status = $this->idleService->getMyStatus(auth()->id());
        return response()->json($status);
    }

    // POST /api/idle-events/log  (SR logs their own idle reason)
    public function log(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'reason_type' => 'required|in:traveling,lunch_prayer,customer_meeting,market_closed,no_response,other',
            'reason_note' => 'nullable|string|max:500|required_if:reason_type,other',
        ]);

        $event = $this->idleService->logAndResolve(auth()->id(), $validated);

        return response()->json($event);
    }

    // POST /api/idle-events/{id}/resolve  (Admin can resolve)
    public function resolve(Request $request, \App\Models\IdleEvent $idleEvent): JsonResponse
    {
        $this->authorize('update', $idleEvent);

        $validated = $request->validate([
            'reason_type' => 'required|in:traveling,lunch_prayer,customer_meeting,market_closed,no_response,other',
            'reason_note' => 'nullable|string|max:500',
        ]);

        $idleEvent->update(array_merge($validated, [
            'resolved_time' => now(),
            'is_resolved'   => true,
        ]));

        return response()->json($idleEvent->fresh());
    }
}
