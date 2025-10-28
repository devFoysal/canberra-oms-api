<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Helpers\{
    ResponseHelper
};
use App\Models\{
    Order,
    Invoice,
    Customer,
    Product,
    Category,
    SalesRepresentative,
};
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

// Total orders, pending orders, total income, due payment, ready to ship, total delivered,
class AdminDashboardController extends Controller
{
    public function index()
    {
        $today = Carbon::today();
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth   = Carbon::now()->endOfMonth();
        $startLastMonth = Carbon::now()->subMonth()->startOfMonth();
        $endLastMonth   = Carbon::now()->subMonth()->endOfMonth();

        // -------------------------
        // Orders
        // -------------------------
        $totalOrders   = Order::count();
        $pendingOrders = Order::where('status', 'pending')->count();
        $delivered     = Order::where('status', 'delivered')->count();
        $readyToShip   = Order::where('status', 'ready_to_ship')->count();
        $todayOrders   = Order::whereDate('created_at', $today)->count();

        // Last month orders
        $totalOrdersLastMonth = Order::whereBetween('created_at', [$startLastMonth, $endLastMonth])->count();
        $ordersChange = $totalOrdersLastMonth > 0
            ? round((($totalOrders - $totalOrdersLastMonth) / $totalOrdersLastMonth) * 100, 2)
            : 0;

        // -------------------------
        // Sales Representatives
        // -------------------------
        $activeReps = SalesRepresentative::whereHas('user', fn($q) => $q->where('status', 'active'))->count();
        $inactiveReps = SalesRepresentative::whereHas('user', fn($q) => $q->where('status', 'inactive'))->count();

        // -------------------------
        // Customers
        // -------------------------
        $totalCustomers = Customer::count();
        $newCustomers   = Customer::whereDate('created_at', $today)->count();

        // -------------------------
        // Products & Categories
        // -------------------------
        $totalProducts   = Product::count();
        $totalCategories = Category::count();

        // -------------------------
        // Payments
        // -------------------------
        $totalIncome = DB::table('payments')->sum('amount_paid');

        $totalIncomeThisMonth = DB::table('payments')
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->sum('amount_paid');

        // Last month income for comparison
        $totalIncomeLastMonth = DB::table('payments')
            ->whereBetween('created_at', [$startLastMonth, $endLastMonth])
            ->sum('amount_paid');

        $incomeChange = $totalIncomeLastMonth > 0
            ? round((($totalIncome - $totalIncomeLastMonth) / $totalIncomeLastMonth) * 100, 2)
            : 0;

        // Payment collected today
        $paymentCollectedToday = DB::table('payments')
            ->whereDate('created_at', $today)
            ->sum('amount_paid');

        // Payment pending (invoice total - payments)
        $paymentPending = Invoice::select(
            DB::raw('SUM(i.total - COALESCE(p.total_paid, 0)) as total_due')
        )
            ->from('invoices as i')
            ->leftJoin(
                DB::raw('(SELECT invoice_id, SUM(amount_paid) as total_paid FROM payments GROUP BY invoice_id) as p'),
                'i.id',
                '=',
                'p.invoice_id'
            )
            ->value('total_due');

        // Collection rate today
        $todayInvoiceTotal = Invoice::whereDate('created_at', $today)->sum('total');
        $collectionRateToday = $todayInvoiceTotal > 0
            ? round(($paymentCollectedToday / $todayInvoiceTotal) * 100, 2)
            : 0;

        // -------------------------
        // Pending Collections Orders
        // -------------------------
        $pendingCollectionsOrders = Invoice::select('id')
            ->leftJoin(
                DB::raw('(SELECT invoice_id, SUM(amount_paid) as total_paid FROM payments GROUP BY invoice_id) as p'),
                'invoices.id',
                '=',
                'p.invoice_id'
            )
            ->whereRaw('invoices.total - COALESCE(p.total_paid,0) > 0')
            ->count();

        // -------------------------
        // Payment Collected Today Orders
        // -------------------------
        $paymentCollectedTodayOrders = DB::table('payments')
            ->whereDate('created_at', $today)
            ->distinct('invoice_id')
            ->count('invoice_id');

        // -------------------------
        // Build dashboard array
        // -------------------------
        $data = [
            'total_orders'                    => $totalOrders,
            'pending_orders'                  => $pendingOrders,
            'ready_to_ship'                   => $readyToShip,
            'delivered_orders'                => $delivered,
            'today_orders'                    => $todayOrders,
            'total_orders_change'             => $ordersChange,    // % vs last month

            'active_sales_reps'               => $activeReps,
            'inactive_sales_reps'             => $inactiveReps,

            'total_customers'                 => $totalCustomers,
            'new_customers'                   => $newCustomers,

            'total_products'                  => $totalProducts,
            'total_categories'                => $totalCategories,

            'total_income'                    => round($totalIncome, 2),
            'total_income_this_month'         => round($totalIncomeThisMonth, 2),
            'income_change'                   => $incomeChange,    // % vs last month

            'payment_collected_today'         => round($paymentCollectedToday, 2),
            'payment_pending'                 => round($paymentPending, 2),
            'collection_rate_today'           => $collectionRateToday, // %

            'pending_collections_orders'      => $pendingCollectionsOrders,
            'payment_collected_today_orders'  => $paymentCollectedTodayOrders,
        ];

        return ResponseHelper::success($data, 'Dashboard Summary');
    }
}
