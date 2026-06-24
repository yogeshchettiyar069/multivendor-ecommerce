<?php

declare(strict_types=1);

use App\Enums\OrderStatus;
use App\Enums\PayoutStatus;
use App\Models\Order;
use App\Models\Payout;
use App\Models\User;
use App\Models\Vendor;
use App\Services\CheckoutService;
use Inertia\Testing\AssertableInertia as Assert;

function paidOrderForVendor(Vendor $vendor, User $customer): Order
{
    $product = vendorProduct($vendor, 5000, 5);
    cartAdd($customer, $product, 1);
    $order = app(CheckoutService::class)->placeOrder($customer, shippingStub());
    app(CheckoutService::class)->finalize($order);

    return $order->fresh();
}

it('shows a vendor only the orders containing their items', function () {
    $owner = User::factory()->vendor()->create();
    $vendor = Vendor::factory()->create(['user_id' => (string) $owner->_id]);
    paidOrderForVendor($vendor, User::factory()->customer()->create());

    // An unrelated order for another vendor.
    paidOrderForVendor(Vendor::factory()->create(), User::factory()->customer()->create());

    $this->actingAs($owner)
        ->get(route('vendor.orders.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Vendor/Orders/Index')
            ->has('orders.data', 1));
});

it('lets a vendor advance order tracking without completing it', function () {
    $owner = User::factory()->vendor()->create();
    $vendor = Vendor::factory()->create(['user_id' => (string) $owner->_id]);
    $order = paidOrderForVendor($vendor, User::factory()->customer()->create());

    $this->actingAs($owner)
        ->patch(route('vendor.orders.tracking', $order), ['tracking_status' => 'shipped'])
        ->assertRedirect();

    $order->refresh();
    expect($order->tracking_status->value)->toBe('shipped')
        ->and($order->status)->toBe(OrderStatus::Paid);
});

it('completes the order and pays out when marked delivered', function () {
    $owner = User::factory()->vendor()->create();
    $vendor = Vendor::factory()->create(['user_id' => (string) $owner->_id, 'commission_rate' => 0.10]);
    $order = paidOrderForVendor($vendor, User::factory()->customer()->create());

    $this->actingAs($owner)
        ->patch(route('vendor.orders.tracking', $order), ['tracking_status' => 'delivered'])
        ->assertRedirect();

    $order->refresh();
    $payout = Payout::where('order_id', (string) $order->_id)
        ->where('vendor_id', (string) $vendor->_id)
        ->first();

    expect($order->status)->toBe(OrderStatus::Fulfilled)
        ->and($order->tracking_status->value)->toBe('delivered')
        ->and($payout->status)->toBe(PayoutStatus::Paid);
});

it('forbids a vendor from viewing or updating an order without their items', function () {
    $owner = User::factory()->vendor()->create();
    Vendor::factory()->create(['user_id' => (string) $owner->_id]);
    $order = paidOrderForVendor(Vendor::factory()->create(), User::factory()->customer()->create());

    $this->actingAs($owner)->get(route('vendor.orders.show', $order))->assertForbidden();
    $this->actingAs($owner)
        ->patch(route('vendor.orders.tracking', $order), ['tracking_status' => 'shipped'])
        ->assertForbidden();
});
