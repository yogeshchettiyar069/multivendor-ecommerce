<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 *
 * Produces an order shell; embedded line items are added by the seeder or test
 * so they can snapshot real product prices.
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subtotal = fake()->numberBetween(2000, 50000);

        return [
            'user_id' => User::factory()->customer(),
            'status' => OrderStatus::Paid,
            'subtotal_cents' => $subtotal,
            'total_cents' => $subtotal,
            'stripe_payment_intent_id' => 'pi_'.fake()->unique()->bothify('############################'),
            'placed_at' => fake()->dateTimeBetween('-3 months', 'now'),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => OrderStatus::Pending,
            'stripe_payment_intent_id' => null,
        ]);
    }
}
