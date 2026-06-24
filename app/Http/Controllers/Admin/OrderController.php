<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use App\Support\OrderPresenter;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request): Response
    {
        $status = $request->query('status');

        $query = Order::orderBy('created_at', 'desc');
        if (in_array($status, OrderStatus::values(), true)) {
            $query->where('status', $status);
        }

        $orders = $query->paginate(15)->withQueryString();

        $userIds = collect($orders->items())->map(fn (Order $o): string => (string) $o->user_id)->unique()->all();
        $users = User::whereIn('_id', $userIds)->get()->keyBy(fn (User $u): string => (string) $u->_id);

        return Inertia::render('Admin/Orders/Index', [
            'orders' => [
                'data' => collect($orders->items())->map(fn (Order $o): array => [
                    'id' => (string) $o->_id,
                    'customer' => $users->get((string) $o->user_id)->name ?? '—',
                    'status' => $o->status->value,
                    'payment_method' => $o->payment_method,
                    'total_cents' => $o->total_cents,
                    'item_count' => $o->items->count(),
                    'placed_at' => $o->placed_at?->toIso8601String(),
                    'created_at' => $o->created_at?->toIso8601String(),
                ])->all(),
                'links' => $orders->linkCollection()->all(),
                'meta' => [
                    'total' => $orders->total(),
                    'from' => $orders->firstItem(),
                    'to' => $orders->lastItem(),
                ],
            ],
            'filters' => ['status' => $status],
            'statuses' => OrderStatus::values(),
        ]);
    }

    public function show(Order $order): Response
    {
        $this->authorize('view', $order);

        $customer = User::where('_id', (string) $order->user_id)->first();

        return Inertia::render('Admin/Orders/Show', [
            'order' => OrderPresenter::detail($order),
            'customer' => [
                'name' => $customer?->name,
                'email' => $customer?->email,
            ],
        ]);
    }
}
