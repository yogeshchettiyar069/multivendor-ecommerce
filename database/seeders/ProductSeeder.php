<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Product;
use App\Models\Vendor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    /**
     * Seed the curated demo catalogue (see data/catalog.php): properly named
     * products themed to a matching vendor, with real downloaded thumbnails and
     * per-category variants.
     */
    public function run(): void
    {
        /** @var array<int, array{0:string,1:string,2:string,3:float,4:string}> $catalog */
        $catalog = require database_path('seeders/data/catalog.php');

        $vendors = Vendor::all()->keyBy('store_name');
        $categories = Category::whereNotNull('parent_id')->get()->keyBy('name');

        foreach ($catalog as [$vendorName, $categoryName, $name, $price, $imageKeyword]) {
            $vendor = $vendors->get($vendorName);
            $category = $categories->get($categoryName);

            if ($vendor === null || $category === null) {
                continue;
            }

            $baseSlug = Str::slug($name);

            $product = Product::create([
                'vendor_id' => (string) $vendor->_id,
                'category_id' => (string) $category->_id,
                'name' => $name,
                'slug' => $this->uniqueSlug($baseSlug),
                'description' => $this->description($name, $vendorName),
                'base_price_cents' => (int) round($price * 100),
                'status' => ProductStatus::Published,
                'thumbnail_path' => $this->storeThumbnail($baseSlug),
            ]);

            foreach ($this->variantsFor($categoryName, $price, $baseSlug) as $variant) {
                $product->variants()->create($variant);
            }
        }
    }

    /**
     * Copy a pre-downloaded image into the private products disk, return its path.
     */
    private function storeThumbnail(string $baseSlug): ?string
    {
        $source = database_path("seeders/product_images/{$baseSlug}.jpg");

        if (! is_file($source)) {
            return null;
        }

        $path = "products/seed-{$baseSlug}.jpg";
        Storage::disk('local')->put($path, (string) file_get_contents($source));

        return $path;
    }

    /**
     * Build variants appropriate to the category (sizes / colours / single).
     *
     * @return array<int, array<string, mixed>>
     */
    private function variantsFor(string $category, float $price, string $baseSlug): array
    {
        $cents = (int) round($price * 100);
        $clothing = ["Men's Clothing", "Women's Clothing"];
        $colourful = ['Accessories', 'Phones', 'Laptops', 'Audio', 'Wearables', 'Furniture', 'Decor', 'Appliances'];

        if (in_array($category, $clothing, true)) {
            return $this->fromOptions($baseSlug, $cents, 'size', ['S', 'M', 'L', 'XL']);
        }

        if ($category === 'Footwear') {
            return $this->fromOptions($baseSlug, $cents, 'size', ['8', '9', '10', '11']);
        }

        if ($category === 'Cycling') {
            return $this->fromOptions($baseSlug, $cents, 'size', ['S', 'M', 'L']);
        }

        if (in_array($category, $colourful, true)) {
            return $this->fromOptions($baseSlug, $cents, 'color', ['Black', 'Silver']);
        }

        // Cookware, Fitness, Camping, Books → single default variant.
        return [[
            'sku' => $this->sku($baseSlug, 0),
            'price_cents' => $cents,
            'stock' => fake()->numberBetween(0, 40),
            'attributes' => [],
        ]];
    }

    /**
     * @param  array<int, string>  $values
     * @return array<int, array<string, mixed>>
     */
    private function fromOptions(string $baseSlug, int $cents, string $attribute, array $values): array
    {
        $variants = [];

        foreach ($values as $i => $value) {
            $variants[] = [
                'sku' => $this->sku($baseSlug, $i),
                'price_cents' => $cents,
                'stock' => fake()->numberBetween(0, 40),
                'attributes' => [$attribute => $value],
            ];
        }

        return $variants;
    }

    private function sku(string $baseSlug, int $index): string
    {
        $prefix = Str::upper(Str::substr(preg_replace('/[^a-z0-9]/', '', $baseSlug) ?? 'sku', 0, 6));

        return sprintf('%s-%d-%s', $prefix, $index, Str::upper(Str::random(4)));
    }

    private function uniqueSlug(string $base): string
    {
        $slug = $base;

        while (Product::where('slug', $slug)->exists()) {
            $slug = $base.'-'.Str::lower(Str::random(4));
        }

        return $slug;
    }

    private function description(string $name, string $vendor): string
    {
        return "The {$name} from {$vendor}. ".fake()->paragraph(3);
    }
}
