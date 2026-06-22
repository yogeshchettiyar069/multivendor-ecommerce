<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\VendorStatus;
use App\Models\Category;
use App\Models\Product;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Create ~40 published products spread across the approved vendors and the
     * leaf (child) categories, each with embedded variants and realistic stock.
     */
    public function run(): void
    {
        $vendorIds = Vendor::where('status', VendorStatus::Approved->value)
            ->pluck('_id')
            ->all();

        // Leaf categories are those that have a parent.
        $leafIds = Category::whereNotNull('parent_id')
            ->pluck('_id')
            ->all();

        if ($vendorIds === [] || $leafIds === []) {
            return;
        }

        Product::factory()
            ->count(40)
            ->state(new Sequence(fn (): array => [
                'vendor_id' => fake()->randomElement($vendorIds),
                'category_id' => fake()->randomElement($leafIds),
            ]))
            ->create();
    }
}
