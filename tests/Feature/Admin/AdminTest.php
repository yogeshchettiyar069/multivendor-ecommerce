<?php

declare(strict_types=1);

use App\Enums\VendorStatus;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use App\Services\CheckoutService;

it('lets an admin approve and suspend a vendor', function () {
    $admin = User::factory()->admin()->create();
    $vendor = Vendor::factory()->pending()->create();

    $this->actingAs($admin)->patch(route('admin.vendors.approve', $vendor))->assertRedirect();
    expect($vendor->fresh()->status)->toBe(VendorStatus::Approved);

    $this->actingAs($admin)->patch(route('admin.vendors.suspend', $vendor))->assertRedirect();
    expect($vendor->fresh()->status)->toBe(VendorStatus::Suspended);
});

it('forbids non-admins from the admin area', function () {
    $vendorUser = User::factory()->vendor()->create();
    $customer = User::factory()->customer()->create();
    $vendor = Vendor::factory()->pending()->create();

    $this->actingAs($customer)->get(route('admin.vendors.index'))->assertForbidden();
    $this->actingAs($vendorUser)->get(route('admin.vendors.index'))->assertForbidden();
    $this->actingAs($customer)->patch(route('admin.vendors.approve', $vendor))->assertForbidden();
    expect($vendor->fresh()->status)->toBe(VendorStatus::Pending);
});

it('lets an admin create, rename and delete categories', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.categories.store'), ['name' => 'Gadgets', 'parent_id' => null])
        ->assertRedirect();
    $root = Category::where('name', 'Gadgets')->first();
    expect($root)->not->toBeNull()->and($root->parent_id)->toBeNull();

    $this->actingAs($admin)
        ->post(route('admin.categories.store'), ['name' => 'Smart Phones', 'parent_id' => (string) $root->_id])
        ->assertRedirect();
    $child = Category::where('name', 'Smart Phones')->first();
    expect($child->parent_id)->toBe((string) $root->_id)
        ->and($child->ancestors)->toBe([(string) $root->_id]);

    $this->actingAs($admin)
        ->patch(route('admin.categories.update', $child), ['name' => 'Mobiles'])
        ->assertRedirect();
    expect($child->fresh()->name)->toBe('Mobiles');

    $this->actingAs($admin)->delete(route('admin.categories.destroy', $child))->assertRedirect();
    expect(Category::where('_id', (string) $child->_id)->exists())->toBeFalse();
});

it('prevents deleting a category that has products', function () {
    $admin = User::factory()->admin()->create();
    $root = Category::factory()->create();
    $category = Category::factory()->childOf($root)->create();
    $vendor = Vendor::factory()->create();
    Product::factory()->create(['vendor_id' => (string) $vendor->_id, 'category_id' => (string) $category->_id]);

    $this->actingAs($admin)
        ->delete(route('admin.categories.destroy', $category))
        ->assertSessionHas('error');

    expect(Category::where('_id', (string) $category->_id)->exists())->toBeTrue();
});

it('prevents deleting a category that has subcategories', function () {
    $admin = User::factory()->admin()->create();
    $root = Category::factory()->create();
    Category::factory()->childOf($root)->create();

    $this->actingAs($admin)
        ->delete(route('admin.categories.destroy', $root))
        ->assertSessionHas('error');

    expect(Category::where('_id', (string) $root->_id)->exists())->toBeTrue();
});

it('lets an admin view any order', function () {
    $admin = User::factory()->admin()->create();
    $customer = User::factory()->customer()->create();
    $product = publishedProduct(stock: 5);
    cartAdd($customer, $product, 1);
    $order = app(CheckoutService::class)->placeOrder($customer, shippingStub());

    $this->actingAs($admin)->get(route('admin.orders.index'))->assertOk();
    $this->actingAs($admin)->get(route('admin.orders.show', $order))->assertOk();
});
