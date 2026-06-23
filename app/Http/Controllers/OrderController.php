<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Order;
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
        $this->authorize('viewAny', Order::class);

        $orders = Order::where('user_id', (string) $request->user()->_id)
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return Inertia::render('Orders/Index', [
            'orders' => [
                'data' => collect($orders->items())->map(fn (Order $o): array => [
                    'id' => (string) $o->_id,
                    'status' => $o->status->value,
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
        ]);
    }

    public function show(Request $request, Order $order): Response
    {
        $this->authorize('view', $order);

        return Inertia::render('Orders/Show', [
            'order' => OrderPresenter::detail($order),
        ]);
    }
}
