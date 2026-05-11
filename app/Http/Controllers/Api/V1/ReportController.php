<?php
// ══════════════════════════════════════════════════════════════════════════════
// ReportController.php
// Route: /api/reports
// ══════════════════════════════════════════════════════════════════════════════

namespace App\Http\Controllers\Api\V1;

class ReportController extends Controller
{
    public function __construct(private ReportService $reportService) {}

    // GET /api/reports/sales
    public function sales(Request $request): JsonResponse
    {
        $request->validate([
            'period'       => 'required|in:daily,weekly,monthly',
            'sales_rep_id' => 'nullable|exists:users,id',
            'start_date'   => 'nullable|date',
            'end_date'     => 'nullable|date',
        ]);

        $data = $this->reportService->getSalesReport(
            $request->period,
            $request->sales_rep_id,
            $request->start_date,
            $request->end_date,
        );

        return response()->json($data);
    }

    // GET /api/reports/sales-reps
    public function salesReps(Request $request): JsonResponse
    {
        $this->authorize('viewReports');

        $data = $this->reportService->getSalesRepReport(
            $request->input('period', 'monthly'),
            $request->start_date,
            $request->end_date,
        );

        return response()->json($data);
    }

    // GET /api/reports/areas
    public function areas(Request $request): JsonResponse
    {
        $this->authorize('viewReports');

        $data = $this->reportService->getAreaReport(
            $request->start_date,
            $request->end_date,
        );

        return response()->json($data);
    }

    // GET /api/reports/companies
    public function companies(Request $request): JsonResponse
    {
        $this->authorize('viewReports');

        $data = $this->reportService->getCompanyReport(
            $request->start_date,
            $request->end_date,
        );

        return response()->json($data);
    }
}
