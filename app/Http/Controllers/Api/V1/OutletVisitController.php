<?php
// ══════════════════════════════════════════════════════════════════════════════
// OutletVisitController.php
// Route: /api/outlet-visits
// ══════════════════════════════════════════════════════════════════════════════

namespace App\Http\Controllers\Api\V1;

class OutletVisitController extends Controller
{
    // GET /api/outlet-visits
    public function index(Request $request): JsonResponse
    {
        $query = \App\Models\OutletVisit::latest('visited_at');

        // SR শুধু নিজেরটা দেখবে
        if (auth()->user()->role === 'sales_rep') {
            $query->where('sales_rep_id', auth()->id());
        } elseif ($request->filled('sales_rep_id')) {
            $query->where('sales_rep_id', $request->sales_rep_id);
        }

        if ($request->filled('date')) {
            $query->whereDate('visited_at', $request->date);
        }

        return response()->json($query->get());
    }

    // GET /api/outlet-visits/targets
    public function targets(Request $request): JsonResponse
    {
        $salesRepId = $request->sales_rep_id ?? auth()->id();
        $service    = app(TargetService::class);

        return response()->json([
            'daily_target'       => $service->getAchievement($salesRepId, 'daily',     'outlet_visit')['target_amount'],
            'weekly_target'      => $service->getAchievement($salesRepId, 'weekly',    'outlet_visit')['target_amount'],
            'monthly_target'     => $service->getAchievement($salesRepId, 'monthly',   'outlet_visit')['target_amount'],
            'quarterly_target'   => $service->getAchievement($salesRepId, 'quarterly', 'outlet_visit')['target_amount'],
            'daily_achieved'     => $service->getAchievement($salesRepId, 'daily',     'outlet_visit')['achieved_amount'],
            'weekly_achieved'    => $service->getAchievement($salesRepId, 'weekly',    'outlet_visit')['achieved_amount'],
            'monthly_achieved'   => $service->getAchievement($salesRepId, 'monthly',   'outlet_visit')['achieved_amount'],
            'quarterly_achieved' => $service->getAchievement($salesRepId, 'quarterly', 'outlet_visit')['achieved_amount'],
        ]);
    }

    // POST /api/outlet-visits
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'outlet_name'    => 'required|string|max:100',
            'area'           => 'required|string|max:100',
            'contact_person' => 'nullable|string|max:100',
            'contact_phone'  => 'nullable|string|max:20',
            'note'           => 'nullable|string|max:500',
            'latitude'       => 'required|numeric|between:-90,90',
            'longitude'      => 'required|numeric|between:-180,180',
            'visited_at'     => 'nullable|date',
        ]);

        // GPS validation — SR must be within 500m of claimed location (optional)
        // এটি advanced feature — পরে add করতে পারো

        $visit = \App\Models\OutletVisit::create(array_merge($validated, [
            'sales_rep_id' => auth()->id(),
            'visited_at'   => $validated['visited_at'] ?? now(),
        ]));

        return response()->json($visit, 201);
    }
}
