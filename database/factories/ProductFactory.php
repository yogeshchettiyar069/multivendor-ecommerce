<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Product;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = Str::title(fake()->unique()->words(3, true));

        return [
            'vendor_id' => Vendor::factory(),
            'category_id' => Category::factory(),
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(10000, 99999),
            'description' => fake()->paragraphs(2, true),
            'base_price_cents' => fake()->numberBetween(500, 25000),
            'status' => ProductStatus::Published,
            'thumbnail_path' => null,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes): array => ['status' => ProductStatus::Draft]);
    }

    /**
     * Attach 2–4 embedded variants once the product document exists.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (Product $product): void {
            $sizes = ['XS', 'S', 'M', 'L', 'XL'];
            $colors = ['Black', 'White', 'Navy', 'Olive', 'Burgundy'];

            foreach (range(1, fake()->numberBetween(2, 4)) as $i) {
                $product->variants()->create([
                    'sku' => strtoupper(fake()->unique()->bothify('SKU-####-????')),
                    'price_cents' => $product->base_price_cents + fake()->numberBetween(0, 3000),
                    'stock' => fake()->numberBetween(0, 60),
                    'attributes' => [
                        'size' => fake()->randomElement($sizes),
                        'color' => fake()->randomElement($colors),
                    ],
                ]);
            }
        });
    }
}
