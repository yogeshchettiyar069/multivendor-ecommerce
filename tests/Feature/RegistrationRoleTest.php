<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Models\User;

it('registers new users with the customer role', function () {
    $response = $this->post('/register', [
        'name' => 'New Shopper',
        'email' => 'shopper@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));

    $user = User::where('email', 'shopper@example.com')->first();

    expect($user)->not->toBeNull()
        ->and($user->role)->toBe(Role::Customer);
});
