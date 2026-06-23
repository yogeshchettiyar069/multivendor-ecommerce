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
     * Seed the genuine demo catalogue (see data/catalog.php): real product names,
     * descriptions, prices and photos sourced from open datasets, themed to a
     * matching vendor, with per-category variants.
     */
    public function run(): void
    {
        /** @var array<int, array<string, mixed>> $catalog */
        $catalog = require database_path('seeders/data/catalog.php');

        $vendors = Vendor::all()->keyBy('store_name');
        $categories = Category::whereNotNull('parent_id')->get()->keyBy('name');

        foreach ($catalog as $row) {
            $vendor = $vendors->get($row['vendor']);
            $category = $categories->get($row['category']);

            if ($vendor === null || $category === null) {
                continue;
            }

            $baseSlug = Str::slug($row['name']);

            $product = Product::create([
                'vendor_id' => (string) $vendor->_id,
                'category_id' => (string) $category->_id,
                'name' => $row['name'],
                'slug' => $this->uniqueSlug($baseSlug),
                'description' => $row['description'] ?? null,
                'base_price_cents' => (int) round(((float) $row['price']) * 100),
                'status' => ProductStatus::Published,
                'thumbnail_path' => $this->storeThumbnail($row['image'] ?? null),
            ]);

            foreach ($this->variantsFor($row, $baseSlug) as $variant) {
                $product->variants()->create($variant);
            }
        }
    }

    /**
     * Copy a pre-downloaded image into the private products disk, return its path.
     */
    private function storeThumbnail(?string $imageFile): ?string
    {
        if ($imageFile === null) {
            return null;
        }

        $source = database_path("seeders/product_images/{$imageFile}");
        if (! is_file($source)) {
            return null;
        }

        $path = "products/seed-{$imageFile}";
        Storage::disk('local')->put($path, (string) file_get_contents($source));

        return $path;
    }

    /**
     * Build variants for a catalogue row based on its declared variant type.
     *
     * @param  array<string, mixed>  $row
     * @return array<int, array<string, mixed>>
     */
    private function variantsFor(array $row, string $baseSlug): array
    {
        $cents = (int) round(((float) $row['price']) * 100);
        $stock = (int) ($row['stock'] ?? 0);
        $type = $row['variant'] ?? 'single';

        $options = match ($type) {
            'sizes' => ['size' => ['S', 'M', 'L', 'XL']],
            'shoes' => ['size' => ['8', '9', '10', '11']],
            default => null,
        };

        if ($options === null) {
            return [[
                'sku' => $this->sku($baseSlug, 0),
                'price_cents' => $cents,
                'stock' => $stock,
                'attributes' => [],
            ]];
        }

        $attribute = array_key_first($options);
        $variants = [];
        foreach ($options[$attribute] as $i => $value) {
            $variants[] = [
                'sku' => $this->sku($baseSlug, $i),
                'price_cents' => $cents,
                'stock' => fake()->numberBetween(0, max(4, (int) round($stock / 2))),
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
        $slug = $base !== '' ? $base : 'product';

        while (Product::where('slug', $slug)->exists()) {
            $slug = $base.'-'.Str::lower(Str::random(4));
        }

        return $slug;
    }
}
