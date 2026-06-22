<?php

declare(strict_types=1);

use App\Models\User;

it('redirects guests from role-gated routes to login', function () {
    $this->get(route('vendor.apply'))->assertRedirect(route('login'));
});

it('allows customers to reach the customer-only vendor application', function () {
    $customer = User::factory()->customer()->create();

    $this->actingAs($customer)->get(route('vendor.apply'))->assertOk();
});

it('forbids vendors from the customer-only vendor application', function () {
    $vendor = User::factory()->vendor()->create();

    $this->actingAs($vendor)->get(route('vendor.apply'))->assertForbidden();
});

it('forbids admins from the customer-only vendor application', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->get(route('vendor.apply'))->assertForbidden();
});
