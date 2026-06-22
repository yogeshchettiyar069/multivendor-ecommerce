<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Product;

class ProductPresenter
{
    /**
     * Serialize a product for a storefront card/grid. Price shown is the lowest
     * variant price ("from $X"); falls back to the base price.
     *
     * @return array<string, mixed>
     */
    public static function card(Product $product): array
    {
        $minVariantPrice = $product->variants->min('price_cents');

        return [
            'id' => (string) $product->_id,
            'slug' => $product->slug,
            'name' => $product->name,
            'price_cents' => (int) ($minVariantPrice ?? $product->base_price_cents),
            'in_stock' => (int) $product->variants->sum('stock') > 0,
            'vendor' => $product->vendor?->store_name,
            'category' => $product->category?->name,
            'thumbnail_url' => $product->thumbnail_path ? route('products.image', $product->_id) : null,
        ];
    }
}
