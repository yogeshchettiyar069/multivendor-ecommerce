<?php

declare(strict_types=1);

namespace App\Http\Controllers\Vendor;

use App\Enums\OrderStatus;
use App\Enums\PayoutStatus;
use App\Enums\TrackingStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payout;
use App\Models\Product;
use App\Models\Vendor;
use App\Services\CheckoutService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    use AuthorizesRequests;

    public function __construct(private readonly CheckoutService $checkout) {}

    public function index(Request $request): Response
    {
        $vendorId = (string) $this->vendor($request)->_id;

        $orders = Order::where('items.vendor_id', $vendorId)
            ->where(function ($q) {
                $q->whereIn('status', [OrderStatus::Paid->value, OrderStatus::Fulfilled->value])
                    ->orWhere(function ($q2) {
                        $q2->where('status', OrderStatus::Pending->value)
                            ->whereIn('payment_method', ['cod', 'upi', 'netbanking']);
                    });
            })
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return Inertia::render('Vendor/Orders/Index', [
            'orders' => [
                'data' => collect($orders->items())->map(fn (Order $o): array => $this->row($o, $vendorId))->all(),
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
        $vendorId = (string) $this->vendor($request)->_id;

        $mine = $order->items->filter(fn ($i): bool => (string) $i->vendor_id === $vendorId)->values();
        abort_if($mine->isEmpty(), 403);

        $products = Product::whereIn('_id', $mine->map(fn ($i): string => (string) $i->product_id)->unique()->all())
            ->get()
            ->keyBy(fn (Product $p): string => (string) $p->_id);

        $items = $mine->map(function ($item) use ($products): array {
            $product = $products->get((string) $item->product_id);
            $variant = $product?->variants->first(fn ($v): bool => (string) $v->_id === (string) $item->variant_id);
            $attributes = $variant->attributes ?? [];

            return [
                'product_name' => $product->name ?? 'Unavailable product',
                'variant_label' => $variant !== null
                    ? (implode(' / ', array_filter([$attributes['size'] ?? null, $attributes['color'] ?? null])) ?: 'Default')
                    : null,
                'unit_price_cents' => (int) $item->unit_price_cents,
                'quantity' => (int) $item->quantity,
                'line_total_cents' => (int) $item->unit_price_cents * (int) $item->quantity,
                'thumbnail_url' => $product?->thumbnail_path ? route('products.image', $product->_id) : null,
                'fulfilled' => (bool) $item->fulfilled,
            ];
        })->all();

        return Inertia::render('Vendor/Orders/Show', [
            'order' => [
                'id' => (string) $order->_id,
                'status' => $order->status->value,
                'payment_method' => $order->payment_method,
                'placed_at' => $order->placed_at?->toIso8601String(),
                'created_at' => $order->created_at?->toIso8601String(),
                'shipping' => $order->shipping,
                'items' => $items,
                'my_revenue_cents' => array_sum(array_map(fn (array $x): int => $x['line_total_cents'], $items)),
                'tracking_status' => $order->tracking_status->value,
            ],
        ]);
    }

    /**
     * Advance the order's shipment tracking stage. Reaching "Delivered" completes
     * the order: items fulfilled, payouts ensured and paid, status -> fulfilled.
     */
    public function updateTracking(Request $request, Order $order): RedirectResponse
    {
        $vendorId = (string) $this->vendor($request)->_id;
        abort_unless($order->items->contains(fn ($i): bool => (string) $i->vendor_id === $vendorId), 403);

        $data = $request->validate([
            'tracking_status' => ['required', Rule::enum(TrackingStatus::class)],
        ]);
        $new = TrackingStatus::from($data['tracking_status']);

        $order->tracking_status = $new;

        if ($new === TrackingStatus::Delivered) {
            foreach ($order->items as $item) {
                if ($item->fulfilled !== true) {
                    $item->fulfilled = true;
                    $order->items()->save($item);
                }
            }
            $this->checkout->ensurePayouts($order->fresh());
            Payout::where('order_id', (string) $order->_id)->update(['status' => PayoutStatus::Paid->value]);
            $order->status = OrderStatus::Fulfilled;
        }

        $order->save();

        return back()->with('success', "Order tracking updated to {$new->label()}.");
    }

    private function vendor(Request $request): Vendor
    {
        $vendor = $request->user()?->vendor;
        abort_unless($vendor instanceof Vendor, 403);

        return $vendor;
    }

    /**
     * @return array<string, mixed>
     */
    private function row(Order $order, string $vendorId): array
    {
        $mine = $order->items->filter(fn ($i): bool => (string) $i->vendor_id === $vendorId);

        return [
            'id' => (string) $order->_id,
            'status' => $order->status->value,
            'tracking_status' => $order->tracking_status->value,
            'payment_method' => $order->payment_method,
            'my_items' => $mine->count(),
            'my_revenue_cents' => (int) $mine->sum(fn ($i): int => (int) $i->unit_price_cents * (int) $i->quantity),
            'placed_at' => $order->placed_at?->toIso8601String(),
            'created_at' => $order->created_at?->toIso8601String(),
        ];
    }
}
