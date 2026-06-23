<?php

declare(strict_types=1);

use App\Enums\OrderStatus;
use App\Enums\PayoutStatus;
use App\Exceptions\InsufficientStockException;
use App\Models\Category;
use App\Models\Order;
use App\Models\Payout;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use App\Services\CartService;
use App\Services\CheckoutService;
use Illuminate\Support\Str;

function shippingStub(): array
{
    return [
        'name' => 'Test Buyer',
        'email' => 'buyer@example.com',
        'address' => '1 Test Street',
        'city' => 'Testville',
        'postal_code' => '12345',
        'country' => 'United States',
    ];
}

function cartAdd(User $user, Product $product, int $qty): void
{
    $cart = app(CartService::class)->for($user);
    $variant = $product->variants->first();
    app(CartService::class)->add($cart, (string) $product->_id, (string) $variant->_id, $qty, (int) $variant->stock);
}

function vendorProduct(Vendor $vendor, int $priceCents, int $stock): Product
{
    $root = Category::factory()->create();
    $category = Category::factory()->childOf($root)->create();

    $product = Product::create([
        'vendor_id' => (string) $vendor->_id,
        'category_id' => (string) $category->_id,
        'name' => 'Product '.Str::random(6),
        'slug' => 'product-'.Str::lower(Str::random(8)),
        'base_price_cents' => $priceCents,
        'status' => 'published',
    ]);
    $product->variants()->create([
        'sku' => 'SKU-'.Str::upper(Str::random(8)),
        'price_cents' => $priceCents,
        'stock' => $stock,
        'attributes' => [],
    ]);

    return $product->refresh();
}

it('places a pending order and deducts stock atomically', function () {
    $user = User::factory()->customer()->create();
    $product = publishedProduct(stock: 5);
    cartAdd($user, $product, 2);

    $order = app(CheckoutService::class)->placeOrder($user, shippingStub());

    expect($order->status)->toBe(OrderStatus::Pending)
        ->and($order->items)->toHaveCount(1)
        ->and($order->subtotal_cents)->toBe($product->variants->first()->price_cents * 2);

    $product->refresh();
    expect($product->variants->first()->stock)->toBe(3);
});

it('lets only one buyer take the last unit (atomic, exactly one succeeds)', function () {
    $alice = User::factory()->customer()->create();
    $bob = User::factory()->customer()->create();
    $product = publishedProduct(stock: 1);
    cartAdd($alice, $product, 1);
    cartAdd($bob, $product, 1);

    app(CheckoutService::class)->placeOrder($alice, shippingStub());

    expect(fn () => app(CheckoutService::class)->placeOrder($bob, shippingStub()))
        ->toThrow(InsufficientStockException::class);

    $product->refresh();
    expect($product->variants->first()->stock)->toBe(0)
        ->and(Order::count())->toBe(1);
});

it('finalizes a paid order with a payout and clears the cart', function () {
    $user = User::factory()->customer()->create();
    $product = publishedProduct(stock: 5);
    cartAdd($user, $product, 2);
    $order = app(CheckoutService::class)->placeOrder($user, shippingStub());

    app(CheckoutService::class)->finalize($order);
    $order->refresh();

    expect($order->status)->toBe(OrderStatus::Paid)
        ->and($order->placed_at)->not->toBeNull()
        ->and(Payout::where('order_id', (string) $order->_id)->count())->toBe(1)
        ->and(app(CartService::class)->for($user)->items)->toHaveCount(0);
});

it('finalize is idempotent against webhook retries', function () {
    $user = User::factory()->customer()->create();
    $product = publishedProduct(stock: 5);
    cartAdd($user, $product, 1);
    $order = app(CheckoutService::class)->placeOrder($user, shippingStub());

    app(CheckoutService::class)->finalize($order);
    app(CheckoutService::class)->finalize($order->fresh());

    expect(Payout::where('order_id', (string) $order->_id)->count())->toBe(1)
        ->and($order->fresh()->status)->toBe(OrderStatus::Paid);
});

it('restores stock and cancels the order on failure', function () {
    $user = User::factory()->customer()->create();
    $product = publishedProduct(stock: 5);
    cartAdd($user, $product, 3);
    $order = app(CheckoutService::class)->placeOrder($user, shippingStub());

    expect($product->fresh()->variants->first()->stock)->toBe(2);

    app(CheckoutService::class)->fail($order);

    expect($order->fresh()->status)->toBe(OrderStatus::Cancelled)
        ->and($product->fresh()->variants->first()->stock)->toBe(5);
});

it('places an offline (cash on delivery) order as pending, no payout, cart cleared', function () {
    $user = User::factory()->customer()->create();
    $product = publishedProduct(stock: 5);
    cartAdd($user, $product, 1);

    $this->actingAs($user)
        ->postJson(route('checkout.store'), array_merge(shippingStub(), ['payment_method' => 'cod']))
        ->assertOk()
        ->assertJson(['mode' => 'offline']);

    $order = Order::where('user_id', (string) $user->_id)->first();

    expect($order->status)->toBe(OrderStatus::Pending)
        ->and($order->payment_method)->toBe('cod')
        ->and(Payout::where('order_id', (string) $order->_id)->count())->toBe(0)
        ->and(app(CartService::class)->for($user)->items)->toHaveCount(0)
        ->and($user->fresh()->default_address)->not->toBeNull()
        ->and($user->fresh()->default_address['city'])->toBe('Testville')
        ->and($product->fresh()->variants->first()->stock)->toBe(4);
});

it('buy now places a direct order without using or clearing the cart', function () {
    $user = User::factory()->customer()->create();
    $cartProduct = publishedProduct(stock: 5);
    cartAdd($user, $cartProduct, 1); // a pre-existing cart item that must survive

    $buyProduct = publishedProduct(stock: 5);
    $variant = $buyProduct->variants->first();

    $order = app(CheckoutService::class)->placeOrder($user, shippingStub(), 'card', [
        'product_id' => (string) $buyProduct->_id,
        'variant_id' => (string) $variant->_id,
        'quantity' => 2,
    ]);

    expect($order->from_cart)->toBeFalse()
        ->and($order->items)->toHaveCount(1)
        ->and((string) $order->items->first()->product_id)->toBe((string) $buyProduct->_id)
        ->and($buyProduct->fresh()->variants->first()->stock)->toBe(3);

    app(CheckoutService::class)->finalize($order);

    // Buy Now must not touch the cart.
    expect(app(CartService::class)->for($user)->items)->toHaveCount(1);
});

it('splits payouts across vendors net of commission', function () {
    $user = User::factory()->customer()->create();
    $vendorA = Vendor::factory()->create(['commission_rate' => 0.10]);
    $vendorB = Vendor::factory()->create(['commission_rate' => 0.20]);
    $productA = vendorProduct($vendorA, 10000, 5); // $100
    $productB = vendorProduct($vendorB, 5000, 5);  // $50
    cartAdd($user, $productA, 1);
    cartAdd($user, $productB, 1);

    $order = app(CheckoutService::class)->placeOrder($user, shippingStub());
    app(CheckoutService::class)->finalize($order);

    $payoutA = Payout::where('vendor_id', (string) $vendorA->_id)->where('order_id', (string) $order->_id)->first();
    $payoutB = Payout::where('vendor_id', (string) $vendorB->_id)->where('order_id', (string) $order->_id)->first();

    expect($payoutA->amount_cents)->toBe(9000)   // 10000 * (1 - 0.10)
        ->and($payoutB->amount_cents)->toBe(4000) // 5000 * (1 - 0.20)
        ->and($payoutA->status)->toBe(PayoutStatus::Pending);
});
