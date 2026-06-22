<?php

declare(strict_types=1);

use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;

it('lets a vendor update their own product', function () {
    $owner = User::factory()->vendor()->create();
    $vendor = Vendor::factory()->create(['user_id' => (string) $owner->_id]);
    $product = Product::factory()->create(['vendor_id' => (string) $vendor->_id]);

    expect($owner->can('update', $product))->toBeTrue();
});

it('prevents a vendor from updating another vendor\'s product', function () {
    $owner = User::factory()->vendor()->create();
    $vendor = Vendor::factory()->create(['user_id' => (string) $owner->_id]);
    $product = Product::factory()->create(['vendor_id' => (string) $vendor->_id]);

    $intruder = User::factory()->vendor()->create();
    Vendor::factory()->create(['user_id' => (string) $intruder->_id]);

    expect($intruder->can('update', $product))->toBeFalse()
        ->and($intruder->can('delete', $product))->toBeFalse()
        ->and($intruder->can('view', $product))->toBeFalse();
});

it('lets an admin manage any product', function () {
    $admin = User::factory()->admin()->create();
    $product = Product::factory()->create();

    expect($admin->can('update', $product))->toBeTrue()
        ->and($admin->can('delete', $product))->toBeTrue();
});

it('blocks customers from managing products', function () {
    $customer = User::factory()->customer()->create();
    $product = Product::factory()->create();

    expect($customer->can('update', $product))->toBeFalse();
});
