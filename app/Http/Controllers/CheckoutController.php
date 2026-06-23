<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Exceptions\InsufficientStockException;
use App\Models\Order;
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
        $summary = $this->cart->summary($request->user());

        if ($summary['count'] === 0) {
            return redirect()->route('catalog');
        }

        return Inertia::render('Checkout/Index', [
            'cart' => $summary,
            'stripeKey' => $this->stripe->publishableKey(),
            'customer' => [
                'name' => $request->user()->name,
                'email' => $request->user()->email,
            ],
            'savedAddress' => $request->user()->default_address,
        ]);
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
        ]);

        $shipping = Arr::only($validated, ['name', 'email', 'address', 'city', 'postal_code', 'country']);
        $method = $validated['payment_method'];

        try {
            $order = $this->checkout->placeOrder($request->user(), $shipping, $method);
        } catch (InsufficientStockException $e) {
            return response()->json(['message' => $e->getMessage()], 409);
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        // Remember the address for next time.
        $request->user()->update(['default_address' => $shipping]);

        // Card → real Stripe payment; everything else is placed as pending payment.
        if ($method !== 'card') {
            $this->checkout->confirmOffline($request->user());

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
