<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Exceptions\InsufficientStockException;
use App\Models\Order;
use App\Models\Product;
use App\Services\CartService;
use App\Services\CheckoutService;
use App\Services\StripeService;
use App\Support\OrderPresenter;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class CheckoutController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly CartService $cart,
        private readonly CheckoutService $checkout,
        private readonly StripeService $stripe,
    ) {}

    public function index(Request $request): Response|RedirectResponse
    {
        $direct = null;
        $summary = null;

        if ($request->boolean('buy_now')) {
            $built = $this->directSummary($request);
            if ($built === null) {
                return redirect()->route('catalog')->with('error', 'That product is unavailable.');
            }
            $summary = $built['summary'];
            $direct = $built['direct'];
        } else {
            $summary = $this->cart->summary($request->user());
            if ($summary['count'] === 0) {
                return redirect()->route('catalog');
            }
        }

        return Inertia::render('Checkout/Index', [
            'cart' => $summary,
            'direct' => $direct,
            'stripeKey' => $this->stripe->publishableKey(),
            'customer' => [
                'name' => $request->user()->name,
                'email' => $request->user()->email,
            ],
            'savedAddress' => $request->user()->default_address,
        ]);
    }

    /**
     * Build a one-item summary + direct payload for a Buy Now checkout.
     *
     * @return array{summary: array<string, mixed>, direct: array<string, mixed>}|null
     */
    private function directSummary(Request $request): ?array
    {
        $product = Product::where('_id', (string) $request->query('product', ''))
            ->where('status', 'published')
            ->first();
        if ($product === null) {
            return null;
        }

        $variant = $product->variants->first(
            fn ($v): bool => (string) $v->_id === (string) $request->query('variant', '')
        );
        if ($variant === null) {
            return null;
        }

        $qty = max(1, min(99, (int) $request->query('qty', 1)));
        $unit = (int) $variant->price_cents;
        $attributes = $variant->attributes ?? [];
        $label = implode(' / ', array_filter([$attributes['size'] ?? null, $attributes['color'] ?? null])) ?: 'Default';

        return [
            'summary' => [
                'count' => $qty,
                'subtotalCents' => $unit * $qty,
                'items' => [[
                    'item_id' => 'direct',
                    'product_id' => (string) $product->_id,
                    'slug' => $product->slug,
                    'name' => $product->name,
                    'variant_label' => $label,
                    'unit_price_cents' => $unit,
                    'quantity' => $qty,
                    'line_total_cents' => $unit * $qty,
                    'stock' => (int) $variant->stock,
                    'thumbnail_url' => $product->thumbnail_path ? route('products.image', $product->_id) : null,
                ]],
            ],
            'direct' => [
                'product_id' => (string) $product->_id,
                'variant_id' => (string) $variant->_id,
                'quantity' => $qty,
            ],
        ];
    }

    /**
     * Create the pending order (atomic stock deduction) and a Stripe
     * PaymentIntent; returns the client secret for the front-end to confirm.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'payment_method' => ['required', Rule::in(['card', 'upi', 'netbanking', 'cod'])],
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:160'],
            'address' => ['required', 'string', 'max:200'],
            'city' => ['required', 'string', 'max:100'],
            'postal_code' => ['required', 'string', 'max:20'],
            'country' => ['required', 'string', 'max:100'],
            'buy_now' => ['nullable', 'boolean'],
            'product_id' => ['required_if:buy_now,true', 'string'],
            'variant_id' => ['required_if:buy_now,true', 'string'],
            'quantity' => ['required_if:buy_now,true', 'integer', 'min:1', 'max:99'],
        ]);

        $shipping = Arr::only($validated, ['name', 'email', 'address', 'city', 'postal_code', 'country']);
        $method = $validated['payment_method'];
        $buyNow = $request->boolean('buy_now');
        $directItem = $buyNow ? [
            'product_id' => $validated['product_id'],
            'variant_id' => $validated['variant_id'],
            'quantity' => (int) $validated['quantity'],
        ] : null;

        try {
            $order = $this->checkout->placeOrder($request->user(), $shipping, $method, $directItem);
        } catch (InsufficientStockException $e) {
            return response()->json(['message' => $e->getMessage()], 409);
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        // Remember the address for next time.
        $request->user()->update(['default_address' => $shipping]);

        // Card → real Stripe payment; everything else is placed as pending payment.
        if ($method !== 'card') {
            // Only clear the cart for cart checkouts (not Buy Now).
            if (! $buyNow) {
                $this->checkout->confirmOffline($request->user());
            }

            return response()->json([
                'mode' => 'offline',
                'orderId' => (string) $order->_id,
            ]);
        }

        try {
            $intent = $this->stripe->createPaymentIntent($order);
            $order->stripe_payment_intent_id = $intent->id;
            $order->save();
        } catch (Throwable $e) {
            $this->checkout->fail($order);
            report($e);

            return response()->json(['message' => 'Could not start payment. Please try again.'], 502);
        }

        return response()->json([
            'mode' => 'card',
            'clientSecret' => $intent->client_secret,
            'orderId' => (string) $order->_id,
        ]);
    }

    public function success(Request $request, Order $order): Response
    {
        $this->authorize('view', $order);

        return Inertia::render('Checkout/Success', [
            'order' => OrderPresenter::detail($order),
        ]);
    }
}
