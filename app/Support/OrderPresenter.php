<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Order;
use App\Models\Product;
use App\Models\Variant;

class OrderPresenter
{
    /**
     * Full order detail with line items resolved against current products
     * (names/images), while prices remain the purchase-time snapshot.
     *
     * @return array<string, mixed>
     */
    public static function detail(Order $order): array
    {
        $productIds = collect($order->items)
            ->map(fn ($i): string => (string) $i->product_id)
            ->unique()
            ->all();

        $products = Product::whereIn('_id', $productIds)
            ->get()
            ->keyBy(fn (Product $p): string => (string) $p->_id);

        $items = collect($order->items)->map(function ($item) use ($products): array {
            $product = $products->get((string) $item->product_id);
            $variant = $product?->variants->first(
                fn (Variant $v): bool => (string) $v->_id === (string) $item->variant_id
            );

            return [
                'product_name' => $product?->name ?? 'Unavailable product',
                'slug' => $product?->slug,
                'variant_label' => $variant !== null ? self::variantLabel($variant) : null,
                'unit_price_cents' => (int) $item->unit_price_cents,
                'quantity' => (int) $item->quantity,
                'line_total_cents' => (int) $item->unit_price_cents * (int) $item->quantity,
                'thumbnail_url' => $product?->thumbnail_path ? route('products.image', $product->_id) : null,
            ];
        })->all();

        return [
            'id' => (string) $order->_id,
            'status' => $order->status->value,
            'tracking_status' => $order->tracking_status?->value ?? 'placed',
            'payment_method' => $order->payment_method,
            'subtotal_cents' => $order->subtotal_cents,
            'total_cents' => $order->total_cents,
            'shipping' => $order->shipping,
            'placed_at' => $order->placed_at?->toIso8601String(),
            'created_at' => $order->created_at?->toIso8601String(),
            'items' => $items,
        ];
    }

    private static function variantLabel(Variant $variant): string
    {
        $attributes = $variant->attributes ?? [];
        $parts = array_filter([$attributes['size'] ?? null, $attributes['color'] ?? null]);

        return $parts === [] ? 'Default' : implode(' / ', $parts);
    }
}
