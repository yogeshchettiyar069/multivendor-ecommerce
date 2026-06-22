<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Enums\VendorStatus;
use App\Models\User;
use App\Models\Vendor;

it('creates a pending vendor and promotes the user to vendor', function () {
    $customer = User::factory()->customer()->create();

    $response = $this->actingAs($customer)->post(route('vendor.apply.store'), [
        'store_name' => 'My Test Store',
        'bio' => 'We sell quality goods.',
    ]);

    $response->assertRedirect(route('dashboard'));

    $vendor = Vendor::where('user_id', (string) $customer->_id)->first();

    expect($vendor)->not->toBeNull()
        ->and($vendor->status)->toBe(VendorStatus::Pending)
        ->and($customer->fresh()->role)->toBe(Role::Vendor);
});

it('validates the store name', function () {
    $customer = User::factory()->customer()->create();

    $this->actingAs($customer)
        ->post(route('vendor.apply.store'), ['store_name' => 'ab'])
        ->assertSessionHasErrors('store_name');
});

it('blocks an existing vendor from applying again (role middleware)', function () {
    $vendor = User::factory()->vendor()->create();
    Vendor::factory()->create(['user_id' => (string) $vendor->_id]);

    $this->actingAs($vendor)
        ->post(route('vendor.apply.store'), ['store_name' => 'Another Store'])
        ->assertForbidden();

    expect(Vendor::where('user_id', (string) $vendor->_id)->count())->toBe(1);
});
