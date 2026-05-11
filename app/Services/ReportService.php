<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OutletVisit;
use App\Models\IdleEvent;
use App\Models\QuarterlyTarget;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ReportService
{
    // ══════════════════════════════════════════════════════════════════════
    // SALES REPORT — period wise bar chart data
    // ══════════════════════════════════════════════════════════════════════

    /**
     * Bar chart এর জন্য data — label, target, achieved, orders, visits।
     *
     * @param string $period  'daily'|'weekly'|'monthly'
     * @param int|null $salesRepId  null = সব SR মিলিয়ে
     * @param string|null $startDate
     * @param string|null $endDate
     */
    public function getSalesReport(
        string $period,
        ?int $salesRepId = null,
        ?string $startDate = null,
        ?string $endDate = null
    ): array {
        $start = $startDate ? Carbon::parse($startDate) : Carbon::now()->subMonths(3);
        $end   = $endDate   ? Carbon::parse($endDate)   : Carbon::now();

        return match ($period) {
            'daily'   => $this->getDailyReport($salesRepId, $start, $end),
            'weekly'  => $this->getWeeklyReport($salesRepId, $start, $end),
            'monthly' => $this->getMonthlyReport($salesRepId, $start, $end),
            default   => $this->getMonthlyReport($salesRepId, $start, $end),
        };
    }

    private function getDailyReport(?int $salesRepId, Carbon $start, Carbon $end): array
    {
        $items = [];
        $current = $start->copy();

        while ($current->lte($end)) {
            $dayStart = $current->copy()->startOfDay();
            $dayEnd   = $current->copy()->endOfDay();

            $ordersQuery = Order::whereBetween('created_at', [$dayStart, $dayEnd])
                ->where('status', '!=', 'cancelled');
            if ($salesRepId) $ordersQuery->where('sales_rep_id', $salesRepId);

            $visitsQuery = OutletVisit::whereBetween('visited_at', [$dayStart, $dayEnd]);
            if ($salesRepId) $visitsQuery->where('sales_rep_id', $salesRepId);

            $items[] = [
                'label'          => $current->format('d M'),
                'target_amount'  => $this->getTargetForRange($salesRepId, $dayStart, $dayEnd),
                'achieved_amount'=> (float) $ordersQuery->sum('total_amount'),
                'orders_count'   => $ordersQuery->count(),
                'outlet_visits'  => $visitsQuery->count(),
            ];

            $current->addDay();
        }

        return $items;
    }

    private function getWeeklyReport(?int $salesRepId, Carbon $start, Carbon $end): array
    {
        $items = [];
        $current = $start->copy()->startOfWeek();

        while ($current->lte($end)) {
            $weekStart = $current->copy();
            $weekEnd   = $current->copy()->endOfWeek();
            if ($weekEnd->gt($end)) $weekEnd = $end->copy();

            $ordersQuery = Order::whereBetween('created_at', [$weekStart, $weekEnd])
                ->where('status', '!=', 'cancelled');
            if ($salesRepId) $ordersQuery->where('sales_rep_id', $salesRepId);

            $visitsQuery = OutletVisit::whereBetween('visited_at', [$weekStart, $weekEnd]);
            if ($salesRepId) $visitsQuery->where('sales_rep_id', $salesRepId);

            $items[] = [
                'label'          => 'W' . $current->weekOfYear . ' ' . $current->format('M'),
                'target_amount'  => $this->getTargetForRange($salesRepId, $weekStart, $weekEnd),
                'achieved_amount'=> (float) $ordersQuery->sum('total_amount'),
                'orders_count'   => $ordersQuery->count(),
                'outlet_visits'  => $visitsQuery->count(),
            ];

            $current->addWeek();
        }

        return $items;
    }

    private function getMonthlyReport(?int $salesRepId, Carbon $start, Carbon $end): array
    {
        $items = [];
        $current = $start->copy()->startOfMonth();

        while ($current->lte($end)) {
            $monthStart = $current->copy()->startOfMonth();
            $monthEnd   = $current->copy()->endOfMonth();

            $ordersQuery = Order::whereBetween('created_at', [$monthStart, $monthEnd])
                ->where('status', '!=', 'cancelled');
            if ($salesRepId) $ordersQuery->where('sales_rep_id', $salesRepId);

            $visitsQuery = OutletVisit::whereBetween('visited_at', [$monthStart, $monthEnd]);
            if ($salesRepId) $visitsQuery->where('sales_rep_id', $salesRepId);

            $items[] = [
                'label'          => $current->format('M Y'),
                'target_amount'  => $this->getTargetForRange($salesRepId, $monthStart, $monthEnd),
                'achieved_amount'=> (float) $ordersQuery->sum('total_amount'),
                'orders_count'   => $ordersQuery->count(),
                'outlet_visits'  => $visitsQuery->count(),
            ];

            $current->addMonth();
        }

        return $items;
    }

    // ══════════════════════════════════════════════════════════════════════
    // SR-WISE REPORT
    // ══════════════════════════════════════════════════════════════════════

    public function getSalesRepReport(string $period, ?string $startDate, ?string $endDate): array
    {
        $start = $startDate ? Carbon::parse($startDate)->startOfDay() : Carbon::now()->startOfMonth();
        $end   = $endDate   ? Carbon::parse($endDate)->endOfDay()     : Carbon::now()->endOfMonth();

        $salesReps = User::where('role', 'sales_rep')->where('is_active', true)->get();

        return $salesReps->map(function (User $sr) use ($start, $end) {
            $sales = (float) Order::where('sales_rep_id', $sr->id)
                ->whereBetween('created_at', [$start, $end])
                ->where('status', '!=', 'cancelled')
                ->sum('total_amount');

            $orders = Order::where('sales_rep_id', $sr->id)
                ->whereBetween('created_at', [$start, $end])
                ->where('status', '!=', 'cancelled')
                ->count();

            $visits = OutletVisit::where('sales_rep_id', $sr->id)
                ->whereBetween('visited_at', [$start, $end])
                ->count();

            $idles = IdleEvent::where('sales_rep_id', $sr->id)
                ->whereBetween('start_time', [$start, $end])
                ->count();

            $target = $this->getTargetForRange($sr->id, $start, $end);
            $pct = $target > 0 ? round(($sales / $target) * 100, 2) : 0;

            return [
                'sales_rep_id'         => $sr->id,
                'sales_rep_name'       => $sr->name,
                'total_sales'          => $sales,
                'target_amount'        => $target,
                'achieved_percentage'  => $pct,
                'orders_count'         => $orders,
                'outlet_visits'        => $visits,
                'idle_count'           => $idles,
            ];
        })->sortByDesc('total_sales')->values()->toArray();
    }

    // ══════════════════════════════════════════════════════════════════════
    // AREA-WISE REPORT
    // ══════════════════════════════════════════════════════════════════════

    public function getAreaReport(?string $startDate, ?string $endDate): array
    {
        $start = $startDate ? Carbon::parse($startDate) : Carbon::now()->startOfMonth();
        $end   = $endDate   ? Carbon::parse($endDate)   : Carbon::now();

        // Orders এ area field থাকলে সেখান থেকে, না হলে outlet_visits থেকে
        $areas = OutletVisit::whereBetween('visited_at', [$start, $end])
            ->selectRaw('area, COUNT(*) as outlet_count, COUNT(DISTINCT sales_rep_id) as active_srs')
            ->groupBy('area')
            ->get();

        return $areas->map(function ($areaData) use ($start, $end) {
            $sales = (float) Order::whereHas('customer', function ($q) use ($areaData) {
                    $q->where('area', $areaData->area);
                })
                ->whereBetween('created_at', [$start, $end])
                ->where('status', '!=', 'cancelled')
                ->sum('total_amount');

            $orders = Order::whereHas('customer', function ($q) use ($areaData) {
                    $q->where('area', $areaData->area);
                })
                ->whereBetween('created_at', [$start, $end])
                ->where('status', '!=', 'cancelled')
                ->count();

            return [
                'area'         => $areaData->area,
                'total_sales'  => $sales,
                'orders_count' => $orders,
                'active_srs'   => $areaData->active_srs,
                'outlet_count' => $areaData->outlet_count,
            ];
        })->sortByDesc('total_sales')->values()->toArray();
    }

    // ══════════════════════════════════════════════════════════════════════
    // COMPANY-WISE REPORT
    // ══════════════════════════════════════════════════════════════════════

    public function getCompanyReport(?string $startDate, ?string $endDate): array
    {
        $start = $startDate ? Carbon::parse($startDate) : Carbon::now()->startOfMonth();
        $end   = $endDate   ? Carbon::parse($endDate)   : Carbon::now();

        // Products এর company field দিয়ে group করা
        // Order → OrderItem → Product → company
        $companyData = \DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->whereBetween('orders.created_at', [$start, $end])
            ->where('orders.status', '!=', 'cancelled')
            ->selectRaw('products.company as company_name, SUM(order_items.total_price) as total_sales, COUNT(DISTINCT orders.id) as orders_count')
            ->groupBy('products.company')
            ->orderByDesc('total_sales')
            ->get();

        $grandTotal = $companyData->sum('total_sales');

        return $companyData->map(function ($row) use ($grandTotal) {
            return [
                'company_name'  => $row->company_name ?? 'Unknown',
                'total_sales'   => (float) $row->total_sales,
                'orders_count'  => (int) $row->orders_count,
                'revenue_share' => $grandTotal > 0
                    ? round(($row->total_sales / $grandTotal) * 100, 2)
                    : 0,
            ];
        })->toArray();
    }

    // ══════════════════════════════════════════════════════════════════════
    // HELPERS
    // ══════════════════════════════════════════════════════════════════════

    private function getTargetForRange(?int $salesRepId, Carbon $start, Carbon $end): float
    {
        $query = QuarterlyTarget::where('target_type', 'sales')
            ->where('quarter_start_date', '<=', $end->toDateString())
            ->where('quarter_end_date', '>=', $start->toDateString())
            ->with(['monthlyTargets.weeklyTargets.dailyTargets' => function ($q) use ($start, $end) {
                $q->whereBetween('date', [$start->toDateString(), $end->toDateString()]);
            }]);

        if ($salesRepId) {
            $query->where('sales_rep_id', $salesRepId);
        }

        return (float) $query->get()->sum(
            fn($qt) => $qt->monthlyTargets->sum(
                fn($m) => $m->weeklyTargets->sum(
                    fn($w) => $w->dailyTargets->sum('target_amount')
                )
            )
        );
    }
}
