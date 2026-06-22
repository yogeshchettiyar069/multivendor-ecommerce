<?php

declare(strict_types=1);

use App\Models\Cart;
use App\Models\User;

it('requires authentication to add to cart', function () {
    $product = publishedProduct();
    $variant = $product->variants->first();

    $this->post(route('cart.store'), [
        'product_id' => (string) $product->_id,
        'variant_id' => (string) $variant->_id,
        'quantity' => 1,
    ])->assertRedirect(route('login'));
});

it('lets a customer add an item to their cart', function () {
    $user = User::factory()->customer()->create();
    $product = publishedProduct(stock: 5);
    $variant = $product->variants->first();

    $this->actingAs($user)->post(route('cart.store'), [
        'product_id' => (string) $product->_id,
        'variant_id' => (string) $variant->_id,
        'quantity' => 2,
    ])->assertRedirect();

    $cart = Cart::where('user_id', (string) $user->_id)->first();

    expect($cart->items)->toHaveCount(1)
        ->and($cart->items->first()->quantity)->toBe(2);
});

it('merges quantity when the same variant is added again', function () {
    $user = User::factory()->customer()->create();
    $product = publishedProduct(stock: 10);
    $variant = $product->variants->first();
    $payload = [
        'product_id' => (string) $product->_id,
        'variant_id' => (string) $variant->_id,
        'quantity' => 2,
    ];

    $this->actingAs($user)->post(route('cart.store'), $payload);
    $this->actingAs($user)->post(route('cart.store'), [...$payload, 'quantity' => 3]);

    $cart = Cart::where('user_id', (string) $user->_id)->first();
    expect($cart->items)->toHaveCount(1)
        ->and($cart->items->first()->quantity)->toBe(5);
});

it('caps cart quantity at available stock', function () {
    $user = User::factory()->customer()->create();
    $product = publishedProduct(stock: 3);
    $variant = $product->variants->first();

    $this->actingAs($user)->post(route('cart.store'), [
        'product_id' => (string) $product->_id,
        'variant_id' => (string) $variant->_id,
        'quantity' => 9,
    ]);

    $cart = Cart::where('user_id', (string) $user->_id)->first();
    expect($cart->items->first()->quantity)->toBe(3);
});

it('rejects adding an out-of-stock variant', function () {
    $user = User::factory()->customer()->create();
    $product = publishedProduct(stock: 0);
    $variant = $product->variants->first();

    $this->actingAs($user)->post(route('cart.store'), [
        'product_id' => (string) $product->_id,
        'variant_id' => (string) $variant->_id,
        'quantity' => 1,
    ])->assertSessionHas('error');

    expect(Cart::where('user_id', (string) $user->_id)->first()?->items ?? collect())->toHaveCount(0);
});

it('updates and removes cart items', function () {
    $user = User::factory()->customer()->create();
    $product = publishedProduct(stock: 10);
    $variant = $product->variants->first();
    $this->actingAs($user)->post(route('cart.store'), [
        'product_id' => (string) $product->_id,
        'variant_id' => (string) $variant->_id,
        'quantity' => 1,
    ]);

    $item = Cart::where('user_id', (string) $user->_id)->first()->items->first();

    $this->actingAs($user)->patch(route('cart.update', (string) $item->_id), ['quantity' => 4]);
    expect(Cart::where('user_id', (string) $user->_id)->first()->items->first()->quantity)->toBe(4);

    $this->actingAs($user)->delete(route('cart.destroy', (string) $item->_id));
    expect(Cart::where('user_id', (string) $user->_id)->first()->items)->toHaveCount(0);
});

it('keeps carts isolated per user', function () {
    $alice = User::factory()->customer()->create();
    $bob = User::factory()->customer()->create();
    $product = publishedProduct(stock: 5);
    $variant = $product->variants->first();

    $this->actingAs($alice)->post(route('cart.store'), [
        'product_id' => (string) $product->_id,
        'variant_id' => (string) $variant->_id,
        'quantity' => 1,
    ]);

    expect(Cart::where('user_id', (string) $bob->_id)->first()?->items ?? collect())->toHaveCount(0);
});
