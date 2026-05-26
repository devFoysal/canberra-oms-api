<?php

namespace App\Http\Controllers\Api\V1;

// File: app/Http/Controllers/Api/V1/PaymentWarningController.php

use App\Http\Controllers\Controller;
use App\Services\PaymentWarningService;
use App\Helpers\ResponseHelper;
use Illuminate\Http\Request;

class PaymentWarningController extends Controller
{
    public function __construct(private PaymentWarningService $warningService) {}

    // ── GET /api/v1/payment-warnings ──────────────────────────────────────
    // Admin + Super Admin: সব warnings
    // SR: নিজের customer দের warnings শুধু

    public function index(Request $request)
    {
        $user = auth()->user();

        $filters = [
            'warning_type' => $request->warningType,   // '15_days' | '30_days'
            'is_resolved'  => $request->isResolved,
            'search'       => $request->search,
            'per_page'     => $request->perPage ?? 15,
            'page'         => $request->page ?? 1,
        ];

        // SR হলে শুধু নিজের orders এর warnings
        if ($user->hasRole('sales_representative')) {
            $filters['sales_rep_id'] = $user->id;
        }

        $warnings = $this->warningService->getWarnings($filters);

        return ResponseHelper::success(
            $warnings,
            'Payment warnings retrieved successfully'
        );
    }

    // ── GET /api/v1/payment-warnings/summary ─────────────────────────────
    // Tab badge counts এবং total due amount

    public function summary(Request $request)
    {
        $user = auth()->user();
        $salesRepId = $user->hasRole('sales_representative') ? $user->id : null;

        $summary = $this->warningService->getSummary($salesRepId);

        return ResponseHelper::success($summary, 'Summary retrieved');
    }

    // ── POST /api/v1/payment-warnings/{id}/note ───────────────────────────
    // Admin + SR: customer এর সাথে কথার পর note রাখা

    public function addNote(Request $request, int $id)
    {
        $request->validate([
            'note' => 'required|string|min:3|max:1000',
        ]);

        $warning = $this->warningService->addNote($id, $request->note, auth()->id());

        return ResponseHelper::success($warning, 'Note added successfully');
    }

    // ── POST /api/v1/payment-warnings/{id}/resolve ────────────────────────
    // Admin only: warning resolve করা (payment collected / cancelled)

    public function resolve(int $id)
    {
        // Only admin / super_admin can resolve
        if (!auth()->user()->hasAnyRole(['admin', 'super_admin'])) {
            return ResponseHelper::error('Unauthorized', 403);
        }

        $warning = $this->warningService->resolve($id, auth()->id());

        return ResponseHelper::success($warning, 'Warning resolved successfully');
    }

    // ── POST /api/v1/payment-warnings/generate ────────────────────────────
    // Cron job থেকে call হয় (manual trigger for admin too)

    public function generate()
    {
        if (!auth()->user()->hasAnyRole(['admin', 'super_admin'])) {
            return ResponseHelper::error('Unauthorized', 403);
        }

        $result = $this->warningService->generateWarnings();

        return ResponseHelper::success($result, 'Warnings generated');
    }
}
