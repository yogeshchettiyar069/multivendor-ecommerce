<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\VendorStatus;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Vendor>
 */
class VendorFactory extends Factory
{
    protected $model = Vendor::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $storeName = fake()->unique()->company();

        return [
            'user_id' => User::factory()->vendor(),
            'store_name' => $storeName,
            'slug' => Str::slug($storeName).'-'.fake()->unique()->numberBetween(1000, 9999),
            'status' => VendorStatus::Approved,
            'bio' => fake()->paragraph(),
            'logo_path' => null,
            // Platform commission between 8% and 20%.
            'commission_rate' => fake()->randomElement([0.08, 0.10, 0.12, 0.15, 0.20]),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes): array => ['status' => VendorStatus::Pending]);
    }

    public function suspended(): static
    {
        return $this->state(fn (array $attributes): array => ['status' => VendorStatus::Suspended]);
    }
}
