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
        ]);
    }

    /**
     * Create the pending order (atomic stock deduction) and a Stripe
     * PaymentIntent; returns the client secret for the front-end to confirm.
     */
    public function store(Request $request): JsonResponse
    {
        $shipping = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:160'],
            'address' => ['required', 'string', 'max:200'],
            'city' => ['required', 'string', 'max:100'],
            'postal_code' => ['required', 'string', 'max:20'],
            'country' => ['required', 'string', 'max:100'],
        ]);

        try {
            $order = $this->checkout->placeOrder($request->user(), $shipping);
        } catch (InsufficientStockException $e) {
            return response()->json(['message' => $e->getMessage()], 409);
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        try {
            $intent = $this->stripe->createPaymentIntent($order);
            $order->stripe_payment_intent_id = $intent->id;
            $order->save();
        } catch (Throwable $e) {
            // Payment setup failed — release the reserved stock and cancel.
            $this->checkout->fail($order);
            report($e);

            return response()->json(['message' => 'Could not start payment. Please try again.'], 502);
        }

        return response()->json([
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
