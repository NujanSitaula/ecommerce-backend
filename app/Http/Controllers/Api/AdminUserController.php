<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Http\Resources\TransactionResource;
use App\Models\Order;
use App\Models\SearchHistory;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;

class AdminUserController extends Controller
{
    /**
     * List users with search, role filter, and aggregates.
     */
    public function index(Request $request)
    {
        $query = User::query()
            ->withCount(['orders' => fn ($q) => $q->where('status', '!=', 'cancelled')])
            ->withSum(['orders as total_spent' => fn ($q) => $q->where('status', '!=', 'cancelled')], 'total');

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->has('role') && $request->role) {
            $query->where('role', $request->role);
        }

        $users = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 20));

        $items = collect($users->items())->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'country' => $user->country,
                'role' => $user->role,
                'created_at' => $user->created_at?->toISOString(),
                'orders_count' => (int) ($user->orders_count ?? 0),
                'total_spent' => (float) ($user->total_spent ?? 0),
            ];
        });

        return response()->json([
            'data' => $items,
            'current_page' => $users->currentPage(),
            'last_page' => $users->lastPage(),
            'per_page' => $users->perPage(),
            'total' => $users->total(),
        ]);
    }

    /**
     * Get single user with stats, recent orders, transactions, search history, and activity timeline.
     */
    public function show($id)
    {
        $user = User::findOrFail($id);

        $ordersCount = Order::where('user_id', $user->id)->where('status', '!=', 'cancelled')->count();
        $totalSpent = (float) Order::where('user_id', $user->id)
            ->where('status', '!=', 'cancelled')
            ->sum('total');
        $transactionsCount = Transaction::whereHas('order', fn ($q) => $q->where('user_id', $user->id))->count();
        $searchCount = SearchHistory::where('user_id', $user->id)->count();

        $recentOrders = Order::where('user_id', $user->id)
            ->with(['items.product', 'address', 'contactNumber'])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        $recentTransactions = Transaction::whereHas('order', fn ($q) => $q->where('user_id', $user->id))
            ->with('order')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        $searchHistory = SearchHistory::where('user_id', $user->id)
            ->orderByDesc('searched_at')
            ->limit(20)
            ->get()
            ->map(fn ($s) => [
                'id' => $s->id,
                'query' => $s->query,
                'searched_at' => $s->searched_at?->toISOString(),
            ]);

        $activityTimeline = $this->buildActivityTimeline($user->id);

        return response()->json([
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'country' => $user->country,
                'role' => $user->role,
                'created_at' => $user->created_at?->toISOString(),
                'stats' => [
                    'orders_count' => $ordersCount,
                    'total_spent' => $totalSpent,
                    'transactions_count' => $transactionsCount,
                    'search_count' => $searchCount,
                ],
                'recent_orders' => OrderResource::collection($recentOrders),
                'recent_transactions' => TransactionResource::collection($recentTransactions),
                'search_history' => $searchHistory,
                'activity_timeline' => $activityTimeline,
            ],
        ]);
    }

    private function buildActivityTimeline(int $userId): array
    {
        $events = [];

        $orders = Order::where('user_id', $userId)
            ->orderByDesc('created_at')
            ->limit(30)
            ->get();

        foreach ($orders as $order) {
            $events[] = [
                'type' => 'order',
                'date' => $order->created_at->toISOString(),
                'title' => 'Order placed',
                'description' => "Order #{$order->id} - $" . number_format((float) $order->total, 2),
                'metadata' => [
                    'order_id' => $order->id,
                    'total' => (float) $order->total,
                    'status' => $order->status,
                ],
            ];
        }

        $searches = SearchHistory::where('user_id', $userId)
            ->orderByDesc('searched_at')
            ->limit(20)
            ->get();

        foreach ($searches as $search) {
            $events[] = [
                'type' => 'search',
                'date' => $search->searched_at->toISOString(),
                'title' => 'Search',
                'description' => "Searched for \"{$search->query}\"",
                'metadata' => ['query' => $search->query],
            ];
        }

        $events[] = [
            'type' => 'cart_placeholder',
            'date' => now()->toISOString(),
            'title' => 'Cart & product views',
            'description' => 'Coming soon — Track products added to cart, viewed, and abandoned',
            'metadata' => [],
        ];

        usort($events, fn ($a, $b) => strcmp($b['date'], $a['date']));

        return array_slice($events, 0, 50);
    }
}
