<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * Build a two-level category tree with a materialized `ancestors` path.
     */
    public function run(): void
    {
        /** @var array<string, array<int, string>> $tree */
        $tree = [
            'Apparel' => ["Men's Clothing", "Women's Clothing", 'Footwear', 'Accessories'],
            'Electronics' => ['Phones', 'Laptops', 'Audio', 'Wearables'],
            'Home & Kitchen' => ['Cookware', 'Furniture', 'Decor', 'Appliances'],
            'Books' => ['Fiction', 'Non-Fiction', "Children's"],
            'Sports & Outdoors' => ['Fitness', 'Camping', 'Cycling'],
        ];

        foreach ($tree as $rootName => $children) {
            $root = Category::create([
                'parent_id' => null,
                'name' => $rootName,
                'slug' => Str::slug($rootName),
                'ancestors' => [],
            ]);

            foreach ($children as $childName) {
                Category::create([
                    'parent_id' => $root->_id,
                    'name' => $childName,
                    'slug' => Str::slug($rootName.' '.$childName),
                    'ancestors' => [$root->_id],
                ]);
            }
        }
    }
}
