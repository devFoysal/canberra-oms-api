<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

use App\Helpers\{
    ResponseHelper
};
use App\Models\{
    Order,
    Invoice,
    Payment
};

class SRDashboardController extends Controller
{
    public function index(){
        $today = Carbon::today();
        $yesterday = Carbon::yesterday();
        $salesRepId = auth()->user()->id;

        // -------------------------
        // Orders for this Sales Rep
        // -------------------------
        $orders = Order::where('sales_rep_id', $salesRepId);

        $todayOrders = Order::where('sales_rep_id', $salesRepId)
            ->whereDate('created_at', $today);

        $yesterdayOrders = Order::where('sales_rep_id', $salesRepId)
            ->whereDate('created_at', $yesterday);

        $totalTodayOrders = $todayOrders->count();
        $totalOrders = $orders->count();
        $pendingOrders = (clone $orders)->where('status', 'pending')->count();

        // -------------------------
        // Today's confirmed order sales
        // -------------------------
        $todaySales = (clone $orders)
            // ->where('status', 'confirmed')
            ->whereDate('created_at', $today)
            ->sum('total');

        // -------------------------
        // Yesterday's confirmed order sales
        // -------------------------
        $yesterdaySales = (clone $orders)
            // ->where('status', 'confirmed')
            ->whereDate('created_at', $yesterday)
            ->sum('total');

        // -------------------------
        // Sales % change vs yesterday
        // -------------------------
        $salesChange = $yesterdaySales > 0
            ? round((($todaySales - $yesterdaySales) / $yesterdaySales) * 100, 2)
            : 0;

        // -------------------------
        // Build response
        // -------------------------
        $data = [
            'sales_rep_id'         => $salesRepId,
            'today_sales'          => round($todaySales, 2),
            'sales_change_percent' => $salesChange,      // +15% etc
            'total_orders'         => $totalOrders,
            'today_orders'         => $totalTodayOrders,
            'pending_orders'       => $pendingOrders,
        ];

        return ResponseHelper::success($data, 'Today Sales Summary for Sales Representative');
    }
}
