<?php

declare(strict_types=1);

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Payout;
use App\Models\User;
use App\Services\CheckoutService;
use Illuminate\Testing\TestResponse;

function signWebhook(string $payload, string $secret): string
{
    $timestamp = time();
    $signature = hash_hmac('sha256', $timestamp.'.'.$payload, $secret);

    return "t={$timestamp},v1={$signature}";
}

function postWebhook(string $payload, string $signature): TestResponse
{
    return test()->call(
        'POST',
        route('stripe.webhook'),
        [],
        [],
        [],
        ['HTTP_STRIPE_SIGNATURE' => $signature, 'CONTENT_TYPE' => 'application/json'],
        $payload,
    );
}

function pendingOrderWithIntent(string $intentId): Order
{
    $user = User::factory()->customer()->create();
    $product = publishedProduct(stock: 5);
    cartAdd($user, $product, 1);
    $order = app(CheckoutService::class)->placeOrder($user, shippingStub());
    $order->stripe_payment_intent_id = $intentId;
    $order->save();

    return $order;
}

beforeEach(fn () => config(['services.stripe.webhook_secret' => 'whsec_testsecret']));

it('rejects a webhook with an invalid signature', function () {
    postWebhook('{"type":"payment_intent.succeeded"}', 't=1,v1=deadbeef')
        ->assertStatus(400);
});

it('finalizes the order on payment_intent.succeeded', function () {
    $order = pendingOrderWithIntent('pi_succeed_1');
    $payload = json_encode([
        'id' => 'evt_1',
        'type' => 'payment_intent.succeeded',
        'data' => ['object' => ['id' => 'pi_succeed_1']],
    ]);

    postWebhook($payload, signWebhook($payload, 'whsec_testsecret'))->assertOk();

    expect($order->fresh()->status)->toBe(OrderStatus::Paid)
        ->and(Payout::where('order_id', (string) $order->_id)->count())->toBe(1);
});

it('is idempotent against duplicate webhook deliveries', function () {
    $order = pendingOrderWithIntent('pi_succeed_2');
    $payload = json_encode([
        'type' => 'payment_intent.succeeded',
        'data' => ['object' => ['id' => 'pi_succeed_2']],
    ]);

    postWebhook($payload, signWebhook($payload, 'whsec_testsecret'))->assertOk();
    postWebhook($payload, signWebhook($payload, 'whsec_testsecret'))->assertOk();

    expect(Payout::where('order_id', (string) $order->_id)->count())->toBe(1)
        ->and($order->fresh()->status)->toBe(OrderStatus::Paid);
});

it('cancels the order and restores stock on payment_intent.payment_failed', function () {
    $order = pendingOrderWithIntent('pi_fail_1');
    $payload = json_encode([
        'type' => 'payment_intent.payment_failed',
        'data' => ['object' => ['id' => 'pi_fail_1']],
    ]);

    postWebhook($payload, signWebhook($payload, 'whsec_testsecret'))->assertOk();

    expect($order->fresh()->status)->toBe(OrderStatus::Cancelled);
});
