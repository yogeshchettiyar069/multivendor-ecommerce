<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PayoutStatus;
use App\Models\Order;
use App\Models\Payout;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payout>
 */
class PayoutFactory extends Factory
{
    protected $model = Payout::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'vendor_id' => Vendor::factory(),
            'order_id' => Order::factory(),
            'amount_cents' => fake()->numberBetween(1000, 40000),
            'status' => PayoutStatus::Pending,
        ];
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes): array => ['status' => PayoutStatus::Paid]);
    }
}
