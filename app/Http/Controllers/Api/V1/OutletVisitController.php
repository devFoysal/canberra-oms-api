<?php
// ══════════════════════════════════════════════════════════════════════════════
// OutletVisitController.php
// Route: /api/outlet-visits
// ══════════════════════════════════════════════════════════════════════════════

namespace App\Http\Controllers\Api\V1;
use App\Http\Controllers\Controller;
use App\Services\TargetService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class OutletVisitController extends Controller
{
    // GET /api/outlet-visits
    public function index(Request $request): JsonResponse
    {
        $query = \App\Models\OutletVisit::latest('visited_at');

        // SR শুধু নিজেরটা দেখবে
        if (auth()->user()->hasRole('sales_representative')) {
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
            'dailyTarget'       => $service->getAchievement($salesRepId, 'daily',     'outlet_visit')['targetAmount'],
            'weeklyTarget'      => $service->getAchievement($salesRepId, 'weekly',    'outlet_visit')['targetAmount'],
            'monthlyTarget'     => $service->getAchievement($salesRepId, 'monthly',   'outlet_visit')['targetAmount'],
            'quarterlyTarget'   => $service->getAchievement($salesRepId, 'quarterly', 'outlet_visit')['targetAmount'],
            'dailyAchieved'     => $service->getAchievement($salesRepId, 'daily',     'outlet_visit')['achievedAmount'],
            'weeklyAchieved'    => $service->getAchievement($salesRepId, 'weekly',    'outlet_visit')['achievedAmount'],
            'monthlyAchieved'   => $service->getAchievement($salesRepId, 'monthly',   'outlet_visit')['achievedAmount'],
            'quarterlyAchieved' => $service->getAchievement($salesRepId, 'quarterly', 'outlet_visit')['achievedAmount'],
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
