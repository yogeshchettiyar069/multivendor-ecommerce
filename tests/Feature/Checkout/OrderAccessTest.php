<?php

declare(strict_types=1);

use App\Models\User;
use App\Services\CheckoutService;

it('lets a customer view their own order but not another customer\'s', function () {
    $alice = User::factory()->customer()->create();
    $bob = User::factory()->customer()->create();
    $product = publishedProduct(stock: 5);
    cartAdd($alice, $product, 1);
    $order = app(CheckoutService::class)->placeOrder($alice, shippingStub());

    $this->actingAs($alice)->get(route('orders.show', $order))->assertOk();
    $this->actingAs($bob)->get(route('orders.show', $order))->assertForbidden();
});

it('requires authentication for checkout', function () {
    $this->get(route('checkout'))->assertRedirect(route('login'));
});
