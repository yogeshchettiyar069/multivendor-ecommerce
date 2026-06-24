<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\PayoutStatus;
use App\Enums\ProductStatus;
use App\Enums\TrackingStatus;
use App\Models\Cart;
use App\Models\Order;
use App\Models\Payout;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Support\Facades\DB;
use MongoDB\Laravel\Connection;
use RuntimeException;

class CheckoutService
{
    public function __construct(
        private readonly CartService $cart,
        private readonly InventoryService $inventory,
    ) {}

    /**
     * Place a pending order: snapshot the cart, then atomically deduct stock and
     * create the order inside a single MongoDB transaction (all-or-nothing).
     *
     * @param  array<string, mixed>  $shipping
     * @param  array{product_id: string, variant_id: string, quantity: int}|null  $directItem  Buy-now item (bypasses the cart).
     */
    public function placeOrder(User $user, array $shipping, string $paymentMethod = 'card', ?array $directItem = null): Order
    {
        $lines = $directItem !== null ? $this->buildDirectLines($directItem) : $this->buildLines($user);
        $fromCart = $directItem === null;

        if ($lines === []) {
            throw new RuntimeException('Your cart is empty.');
        }

        /** @var Connection $connection */
        $connection = DB::connection('mongodb');

        /** @var Order $order */
        $order = $connection->transaction(function () use ($connection, $user, $lines, $shipping, $paymentMethod, $fromCart): Order {
            $this->inventory->deduct($lines, $connection->getSession());

            $subtotal = array_sum(array_map(
                fn (array $l): int => $l['unit_price_cents'] * $l['quantity'],
                $lines,
            ));

            $order = Order::create([
                'user_id' => (string) $user->_id,
                'status' => OrderStatus::Pending,
                'subtotal_cents' => $subtotal,
                'total_cents' => $subtotal,
                'payment_method' => $paymentMethod,
                'shipping' => $shipping,
                'from_cart' => $fromCart,
                'tracking_status' => TrackingStatus::Placed,
                'placed_at' => null,
            ]);

            foreach ($lines as $line) {
                $order->items()->create([
                    'product_id' => $line['product_id'],
                    'variant_id' => $line['variant_id'],
                    'vendor_id' => $line['vendor_id'],
                    'unit_price_cents' => $line['unit_price_cents'],
                    'quantity' => $line['quantity'],
                ]);
            }

            return $order;
        });

        return $order;
    }

    /**
     * Finalize a paid order: mark paid, split payouts per vendor, clear the cart.
     * Idempotent — only a pending order transitions, so webhook retries are safe.
     */
    public function finalize(Order $order): void
    {
        if ($order->status !== OrderStatus::Pending) {
            return;
        }

        $order->status = OrderStatus::Paid;
        $order->placed_at = now();
        $order->save();

        $this->createPayouts($order);

        // Empty the buyer's cart (only for cart checkouts, not Buy Now).
        if ($order->from_cart) {
            Cart::where('user_id', (string) $order->user_id)->delete();
        }
    }

    /**
     * Confirm an offline order (UPI / netbanking / cash on delivery): the order
     * is placed as pending payment, so we just clear the cart. No Stripe, and no
     * payout until the payment is later collected/confirmed.
     */
    public function confirmOffline(User $user): void
    {
        $this->cart->clear($user);
    }

    /**
     * Cancel a pending order and return its stock (payment failed/cancelled).
     */
    public function fail(Order $order): void
    {
        if ($order->status !== OrderStatus::Pending) {
            return;
        }

        $this->inventory->restore($order);

        $order->status = OrderStatus::Cancelled;
        $order->save();
    }

    /**
     * Create payouts for an order if it has none yet (e.g. an offline order being
     * fulfilled, where payment is collected on delivery rather than via Stripe).
     */
    public function ensurePayouts(Order $order): void
    {
        if (Payout::where('order_id', (string) $order->_id)->exists()) {
            return;
        }

        $this->createPayouts($order);
    }

    /**
     * Split the order per vendor and create a payout net of each commission rate.
     */
    private function createPayouts(Order $order): void
    {
        $grossByVendor = [];
        foreach ($order->items as $item) {
            $vendorId = (string) $item->vendor_id;
            $grossByVendor[$vendorId] = ($grossByVendor[$vendorId] ?? 0)
                + $item->unit_price_cents * $item->quantity;
        }

        $vendors = Vendor::whereIn('_id', array_keys($grossByVendor))
            ->get()
            ->keyBy(fn (Vendor $v): string => (string) $v->_id);

        foreach ($grossByVendor as $vendorId => $gross) {
            $vendor = $vendors->get($vendorId);
            $rate = $vendor !== null ? (float) $vendor->commission_rate : 0.10;

            Payout::create([
                'vendor_id' => $vendorId,
                'order_id' => (string) $order->_id,
                'amount_cents' => (int) round($gross * (1 - $rate)),
                'status' => PayoutStatus::Pending,
            ]);
        }
    }

    /**
     * Build a single order line for a Buy Now purchase (bypasses the cart).
     *
     * @param  array{product_id: string, variant_id: string, quantity: int}  $item
     * @return array<int, array{product_id: string, variant_id: string, vendor_id: string, unit_price_cents: int, quantity: int, name: string}>
     */
    private function buildDirectLines(array $item): array
    {
        $product = Product::where('_id', $item['product_id'])
            ->where('status', ProductStatus::Published->value)
            ->first();

        if ($product === null) {
            throw new RuntimeException('That product is not available.');
        }

        $variant = $product->variants->first(
            fn ($v): bool => (string) $v->_id === (string) $item['variant_id']
        );

        if ($variant === null) {
            throw new RuntimeException('That option is not available.');
        }

        return [[
            'product_id' => (string) $product->_id,
            'variant_id' => (string) $variant->_id,
            'vendor_id' => (string) $product->vendor_id,
            'unit_price_cents' => (int) $variant->price_cents,
            'quantity' => (int) $item['quantity'],
            'name' => $product->name,
        ]];
    }

    /**
     * Snapshot the cart into order lines with live prices (validated, published).
     *
     * @return array<int, array{product_id: string, variant_id: string, vendor_id: string, unit_price_cents: int, quantity: int, name: string}>
     */
    private function buildLines(User $user): array
    {
        $cart = $this->cart->for($user);

        if ($cart->items->isEmpty()) {
            return [];
        }

        $productIds = $cart->items->pluck('product_id')->map(fn ($id): string => (string) $id)->unique()->all();
        $products = Product::whereIn('_id', $productIds)->get()->keyBy(fn (Product $p): string => (string) $p->_id);

        $lines = [];
        foreach ($cart->items as $item) {
            $product = $products->get((string) $item->product_id);
            if ($product === null || $product->status !== ProductStatus::Published) {
                continue;
            }

            $variant = $product->variants->first(
                fn ($v): bool => (string) $v->_id === (string) $item->variant_id
            );
            if ($variant === null) {
                continue;
            }

            $lines[] = [
                'product_id' => (string) $product->_id,
                'variant_id' => (string) $variant->_id,
                'vendor_id' => (string) $product->vendor_id,
                'unit_price_cents' => (int) $variant->price_cents,
                'quantity' => (int) $item->quantity,
                'name' => $product->name,
            ];
        }

        return $lines;
    }
}
