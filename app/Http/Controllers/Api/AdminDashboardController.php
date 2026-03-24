<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminDashboardController extends Controller
{
    private const REVENUE_STATUSES = ['confirmed', 'processing', 'shipped', 'delivered'];

    /**
     * Get dashboard stats and chart data.
     */
    public function index(Request $request)
    {
        $days = (int) $request->get('days', 7);
        $days = in_array($days, [7, 30]) ? $days : 7;

        $today = Carbon::today();
        $monthStart = Carbon::now()->startOfMonth();
        $rangeStart = Carbon::today()->subDays($days);

        $revenueQuery = fn () => Order::whereIn('status', self::REVENUE_STATUSES);

        $totalRevenueToday = (float) (clone $revenueQuery())
            ->whereDate('created_at', $today)
            ->sum('total');

        $totalRevenueMonth = (float) (clone $revenueQuery())
            ->where('created_at', '>=', $monthStart)
            ->sum('total');

        $totalOrders = Order::where('status', '!=', 'cancelled')->count();

        $pendingOrders = Order::whereIn('status', ['pending', 'confirmed', 'processing'])->count();

        $cancelledRefundedOrders = Order::where('status', 'cancelled')->count();

        $totalCustomers = User::count();

        $lowStockCount = Product::where('track_inventory', true)
            ->whereColumn('quantity', '<=', 'low_stock_threshold')
            ->count();

        $revenueTrend = Order::whereIn('status', self::REVENUE_STATUSES)
            ->where('created_at', '>=', $rangeStart)
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('SUM(total) as revenue'))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(fn ($row) => [
                'date' => $row->date,
                'revenue' => (float) $row->revenue,
            ])
            ->values()
            ->all();

        $ordersTrend = Order::where('status', '!=', 'cancelled')
            ->where('created_at', '>=', $rangeStart)
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(fn ($row) => [
                'date' => $row->date,
                'count' => (int) $row->count,
            ])
            ->values()
            ->all();

        $topProducts = OrderItem::query()
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.status', '!=', 'cancelled')
            ->whereNotNull('order_items.product_id')
            ->select('order_items.product_id', 'order_items.product_name', DB::raw('SUM(order_items.quantity) as quantity_sold'))
            ->groupBy('order_items.product_id', 'order_items.product_name')
            ->orderByDesc('quantity_sold')
            ->limit(10)
            ->get()
            ->map(fn ($row) => [
                'product_id' => (int) $row->product_id,
                'product_name' => $row->product_name,
                'quantity_sold' => (int) $row->quantity_sold,
            ])
            ->values()
            ->all();

        $topCategories = OrderItem::query()
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->where('orders.status', '!=', 'cancelled')
            ->whereNotNull('products.category_id')
            ->select('categories.id as category_id', 'categories.name as category_name', DB::raw('COUNT(DISTINCT orders.id) as order_count'))
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('order_count')
            ->limit(10)
            ->get()
            ->map(fn ($row) => [
                'category_id' => (int) $row->category_id,
                'category_name' => $row->category_name,
                'order_count' => (int) $row->order_count,
            ])
            ->values()
            ->all();

        $dates = collect();
        for ($i = 0; $i < $days; $i++) {
            $dates->push($rangeStart->copy()->addDays($i)->format('Y-m-d'));
        }

        $revenueTrendFilled = $dates->map(fn ($d) => [
            'date' => $d,
            'revenue' => collect($revenueTrend)->firstWhere('date', $d)['revenue'] ?? 0,
        ])->values()->all();

        $ordersTrendFilled = $dates->map(fn ($d) => [
            'date' => $d,
            'count' => collect($ordersTrend)->firstWhere('date', $d)['count'] ?? 0,
        ])->values()->all();

        return response()->json([
            'total_revenue_today' => $totalRevenueToday,
            'total_revenue_month' => $totalRevenueMonth,
            'total_orders' => $totalOrders,
            'pending_orders' => $pendingOrders,
            'cancelled_refunded_orders' => $cancelledRefundedOrders,
            'total_customers' => $totalCustomers,
            'low_stock_count' => $lowStockCount,
            'revenue_trend' => $revenueTrendFilled,
            'orders_trend' => $ordersTrendFilled,
            'top_products' => $topProducts,
            'top_categories' => $topCategories,
        ]);
    }
}
