<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\Vendor;
use Inertia\Testing\AssertableInertia as Assert;

it('shows the admin dashboard to admins', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page->component('Dashboard/Admin'));
});

it('shows the vendor dashboard to vendors', function () {
    $user = User::factory()->vendor()->create();
    Vendor::factory()->create(['user_id' => (string) $user->_id]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page->component('Dashboard/Vendor'));
});

it('shows the customer dashboard to customers', function () {
    $customer = User::factory()->customer()->create();

    $this->actingAs($customer)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page->component('Dashboard/Customer'));
});
