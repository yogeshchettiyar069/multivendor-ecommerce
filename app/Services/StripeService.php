<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Order;
use Stripe\Event;
use Stripe\PaymentIntent;
use Stripe\StripeClient;
use Stripe\Webhook;

class StripeService
{
    private StripeClient $client;

    public function __construct()
    {
        $this->client = new StripeClient((string) config('services.stripe.secret'));
    }

    public function publishableKey(): ?string
    {
        return config('services.stripe.key');
    }

    /**
     * Create a PaymentIntent for the order total, tagged with the order id so the
     * webhook can correlate it back.
     */
    public function createPaymentIntent(Order $order): PaymentIntent
    {
        return $this->client->paymentIntents->create([
            'amount' => $order->total_cents,
            'currency' => (string) config('services.stripe.currency', 'usd'),
            'metadata' => ['order_id' => (string) $order->_id],
            'automatic_payment_methods' => ['enabled' => true],
        ]);
    }

    /**
     * Verify and parse a webhook payload using the signing secret.
     */
    public function constructEvent(string $payload, string $signature): Event
    {
        return Webhook::constructEvent(
            $payload,
            $signature,
            (string) config('services.stripe.webhook_secret'),
        );
    }
}
