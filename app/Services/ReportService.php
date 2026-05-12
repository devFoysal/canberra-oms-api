<?php

namespace App\Services;

use App\Models\IdleEvent;
use App\Models\Order;
use App\Models\OutletVisit;
use App\Models\QuarterlyTarget;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReportService
{
    // ══════════════════════════════════════════════════════════════════════
    // SALES REPORT
    // ══════════════════════════════════════════════════════════════════════

    public function getSalesReport(
        string $period,
        ?int $salesRepId = null,
        ?string $startDate = null,
        ?string $endDate = null
    ): array {
        $start = $startDate
            ? Carbon::parse($startDate)->startOfDay()
            : Carbon::now()->subMonths(3)->startOfMonth();

        $end = $endDate
            ? Carbon::parse($endDate)->endOfDay()
            : Carbon::now()->endOfDay();

        return match ($period) {
            'daily'   => $this->getDailyReport($salesRepId, $start, $end),
            'weekly'  => $this->getWeeklyReport($salesRepId, $start, $end),
            'monthly' => $this->getMonthlyReport($salesRepId, $start, $end),
            default   => $this->getMonthlyReport($salesRepId, $start, $end),
        };
    }

    // ══════════════════════════════════════════════════════════════════════
    // DAILY REPORT
    // ══════════════════════════════════════════════════════════════════════

    private function getDailyReport(?int $salesRepId, Carbon $start, Carbon $end): array
    {
        $items = [];
        $current = $start->copy();

        while ($current->lte($end)) {

            $dayStart = $current->copy()->startOfDay();
            $dayEnd   = $current->copy()->endOfDay();

            $ordersQuery = Order::query()
                ->whereBetween('created_at', [$dayStart, $dayEnd])
                ->where('status', '!=', 'cancelled');

            if ($salesRepId) {
                $ordersQuery->where('sales_rep_id', $salesRepId);
            }

            $visitsQuery = OutletVisit::query()
                ->whereBetween('visited_at', [$dayStart, $dayEnd]);

            if ($salesRepId) {
                $visitsQuery->where('sales_rep_id', $salesRepId);
            }

            $items[] = [
                'label'            => $current->format('d M'),
                'targetAmount'     => $this->getTargetForRange($salesRepId, $dayStart, $dayEnd),
                'achievedAmount'   => (float) (clone $ordersQuery)->sum('total'),
                'ordersCount'      => (clone $ordersQuery)->count(),
                'outletVisits'     => (clone $visitsQuery)->count(),
            ];

            $current->addDay();
        }

        return $items;
    }

    // ══════════════════════════════════════════════════════════════════════
    // WEEKLY REPORT
    // ══════════════════════════════════════════════════════════════════════

    private function getWeeklyReport(?int $salesRepId, Carbon $start, Carbon $end): array
    {
        $items = [];

        $current = $start->copy()->startOfWeek(Carbon::SATURDAY);

        while ($current->lte($end)) {

            $weekStart = $current->copy()->startOfDay();

            $weekEnd = $current
                ->copy()
                ->endOfWeek(Carbon::FRIDAY)
                ->endOfDay();

            if ($weekEnd->gt($end)) {
                $weekEnd = $end->copy();
            }

            $ordersQuery = Order::query()
                ->whereBetween('created_at', [$weekStart, $weekEnd])
                ->where('status', '!=', 'cancelled');

            if ($salesRepId) {
                $ordersQuery->where('sales_rep_id', $salesRepId);
            }

            $visitsQuery = OutletVisit::query()
                ->whereBetween('visited_at', [$weekStart, $weekEnd]);

            if ($salesRepId) {
                $visitsQuery->where('sales_rep_id', $salesRepId);
            }

            $items[] = [
                'label'            => 'W' . $weekStart->format('W') . ' ' . $weekStart->format('M'),
                'targetAmount'     => $this->getTargetForRange($salesRepId, $weekStart, $weekEnd),
                'achievedAmount'   => (float) (clone $ordersQuery)->sum('total'),
                'ordersCount'      => (clone $ordersQuery)->count(),
                'outletVisits'     => (clone $visitsQuery)->count(),
            ];

            $current->addWeek();
        }

        return $items;
    }

    // ══════════════════════════════════════════════════════════════════════
    // MONTHLY REPORT
    // ══════════════════════════════════════════════════════════════════════

    private function getMonthlyReport(?int $salesRepId, Carbon $start, Carbon $end): array
    {
        $items = [];

        $current = $start->copy()->startOfMonth();

        while ($current->lte($end)) {

            $monthStart = $current->copy()->startOfMonth()->startOfDay();
            $monthEnd   = $current->copy()->endOfMonth()->endOfDay();

            if ($monthEnd->gt($end)) {
                $monthEnd = $end->copy();
            }

            $ordersQuery = Order::query()
                ->whereBetween('created_at', [$monthStart, $monthEnd])
                ->where('status', '!=', 'cancelled');

            if ($salesRepId) {
                $ordersQuery->where('sales_rep_id', $salesRepId);
            }

            $visitsQuery = OutletVisit::query()
                ->whereBetween('visited_at', [$monthStart, $monthEnd]);

            if ($salesRepId) {
                $visitsQuery->where('sales_rep_id', $salesRepId);
            }

            $items[] = [
                'label'            => $current->format('M Y'),
                'targetAmount'     => $this->getTargetForRange($salesRepId, $monthStart, $monthEnd),
                'achievedAmount'   => (float) (clone $ordersQuery)->sum('total'),
                'ordersCount'      => (clone $ordersQuery)->count(),
                'outletVisits'     => (clone $visitsQuery)->count(),
            ];

            $current->addMonth();
        }

        return $items;
    }

    // ══════════════════════════════════════════════════════════════════════
    // SALES REP REPORT
    // ══════════════════════════════════════════════════════════════════════

    public function getSalesRepReport(
        string $period,
        ?string $startDate,
        ?string $endDate
    ): array {

        $start = $startDate
            ? Carbon::parse($startDate)->startOfDay()
            : Carbon::now()->startOfMonth();

        $end = $endDate
            ? Carbon::parse($endDate)->endOfDay()
            : Carbon::now()->endOfMonth();

        $salesReps = User::role('sales_representative')
            ->where('status', 'active')
            ->get();

        return $salesReps
            ->map(function (User $sr) use ($start, $end) {

                $ordersQuery = Order::query()
                    ->where('sales_rep_id', $sr->id)
                    ->whereBetween('created_at', [$start, $end])
                    ->where('status', '!=', 'cancelled');

                $sales = (float) (clone $ordersQuery)->sum('total');

                $target = $this->getTargetForRange($sr->id, $start, $end);

                return [
                    'salesRepId'         => $sr->id,
                    'salesRepName'       => $sr->name,
                    'totalSales'         => $sales,
                    'targetAmount'       => $target,
                    'achievedPercentage' => $target > 0
                        ? round(($sales / $target) * 100, 2)
                        : 0,
                    'ordersCount'        => (clone $ordersQuery)->count(),

                    'outletVisits' => OutletVisit::where('sales_rep_id', $sr->id)
                        ->whereBetween('visited_at', [$start, $end])
                        ->count(),

                    'idleCount' => IdleEvent::where('sales_rep_id', $sr->id)
                        ->whereBetween('start_time', [$start, $end])
                        ->count(),
                ];
            })
            ->sortByDesc('totalSales')
            ->values()
            ->toArray();
    }

    // ══════════════════════════════════════════════════════════════════════
    // AREA REPORT
    // ══════════════════════════════════════════════════════════════════════

    public function getAreaReport(?string $startDate, ?string $endDate): array
    {
        $start = $startDate
            ? Carbon::parse($startDate)->startOfDay()
            : Carbon::now()->startOfMonth();

        $end = $endDate
            ? Carbon::parse($endDate)->endOfDay()
            : Carbon::now()->endOfDay();

        $areas = OutletVisit::query()
            ->whereBetween('visited_at', [$start, $end])
            ->selectRaw('
                area,
                COUNT(*) as outlet_count,
                COUNT(DISTINCT sales_rep_id) as active_srs
            ')
            ->groupBy('area')
            ->get();

        return $areas
            ->map(function ($areaData) use ($start, $end) {

                $ordersQuery = Order::query()
                    ->whereHas('customer', function ($q) use ($areaData) {
                        $q->where('area', $areaData->area);
                    })
                    ->whereBetween('created_at', [$start, $end])
                    ->where('status', '!=', 'cancelled');

                return [
                    'area'         => $areaData->area,
                    'totalSales'   => (float) (clone $ordersQuery)->sum('total'),
                    'ordersCount'  => (clone $ordersQuery)->count(),
                    'activeSrs'    => (int) $areaData->active_srs,
                    'outletCount'  => (int) $areaData->outlet_count,
                ];
            })
            ->sortByDesc('totalSales')
            ->values()
            ->toArray();
    }

    // ══════════════════════════════════════════════════════════════════════
    // COMPANY REPORT
    // ══════════════════════════════════════════════════════════════════════

    public function getCompanyReport(?string $startDate, ?string $endDate): array
    {
        $start = $startDate
            ? Carbon::parse($startDate)->startOfDay()
            : Carbon::now()->startOfMonth();

        $end = $endDate
            ? Carbon::parse($endDate)->endOfDay()
            : Carbon::now()->endOfDay();

        $companyData = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->whereBetween('orders.created_at', [$start, $end])
            ->where('orders.status', '!=', 'cancelled')
            ->selectRaw('
                products.company as company_name,
                SUM(order_items.total) as totalSales,
                COUNT(DISTINCT orders.id) as ordersCount
            ')
            ->groupBy('products.company')
            ->orderByDesc('totalSales')
            ->get();

        $grandTotal = (float) $companyData->sum('totalSales');

        return $companyData
            ->map(function ($row) use ($grandTotal) {

                return [
                    'companyName'  => $row->company_name ?? 'Unknown',
                    'totalSales'   => (float) $row->totalSales,
                    'ordersCount'  => (int) $row->ordersCount,
                    'revenueShare' => $grandTotal > 0
                        ? round(($row->totalSales / $grandTotal) * 100, 2)
                        : 0,
                ];
            })
            ->values()
            ->toArray();
    }

    // ══════════════════════════════════════════════════════════════════════
    // HELPERS
    // ══════════════════════════════════════════════════════════════════════

    private function getTargetForRange(
        ?int $salesRepId,
        Carbon $start,
        Carbon $end
    ): float {

        $query = QuarterlyTarget::query()
            ->where('target_type', 'sales')
            ->whereDate('quarter_start_date', '<=', $end)
            ->whereDate('quarter_end_date', '>=', $start)
            ->with([
                'monthlyTargets.weeklyTargets.dailyTargets' => function ($q) use ($start, $end) {
                    $q->whereBetween('date', [
                        $start->toDateString(),
                        $end->toDateString(),
                    ]);
                }
            ]);

        if ($salesRepId) {
            $query->where('sales_rep_id', $salesRepId);
        }

        return (float) $query->get()->sum(
            fn($quarterlyTarget) => $quarterlyTarget->monthlyTargets->sum(
                fn($monthlyTarget) => $monthlyTarget->weeklyTargets->sum(
                    fn($weeklyTarget) => $weeklyTarget->dailyTargets->sum('target_amount')
                )
            )
        );
    }
}
