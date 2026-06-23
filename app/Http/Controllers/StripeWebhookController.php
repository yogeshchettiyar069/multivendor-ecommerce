<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\CheckoutService;
use App\Services\StripeService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Stripe\Exception\SignatureVerificationException;
use UnexpectedValueException;

class StripeWebhookController extends Controller
{
    public function __construct(
        private readonly CheckoutService $checkout,
        private readonly StripeService $stripe,
    ) {}

    /**
     * Receive Stripe webhooks. The signature is verified before anything else,
     * and finalize/fail are idempotent, so retries are safe.
     */
    public function handle(Request $request): Response
    {
        try {
            $event = $this->stripe->constructEvent(
                $request->getContent(),
                (string) $request->header('Stripe-Signature'),
            );
        } catch (UnexpectedValueException|SignatureVerificationException $e) {
            return response('Invalid payload or signature', 400);
        }

        $paymentIntentId = $event->data->object->id ?? null;
        $order = $paymentIntentId !== null
            ? Order::where('stripe_payment_intent_id', $paymentIntentId)->first()
            : null;

        if ($order === null) {
            return response('No matching order', 200);
        }

        match ($event->type) {
            'payment_intent.succeeded' => $this->checkout->finalize($order),
            'payment_intent.payment_failed', 'payment_intent.canceled' => $this->checkout->fail($order),
            default => null,
        };

        return response('Webhook handled', 200);
    }
}
