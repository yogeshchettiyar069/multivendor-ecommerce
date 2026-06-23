<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Cart;
use App\Models\Product;
use App\Models\User;
use App\Models\Variant;
use Illuminate\Support\Collection;

class CartService
{
    public function for(User $user): Cart
    {
        return Cart::firstOrCreate(['user_id' => (string) $user->_id]);
    }

    /**
     * Empty the user's cart (the document is recreated lazily on next access).
     */
    public function clear(User $user): void
    {
        Cart::where('user_id', (string) $user->_id)->delete();
    }

    /**
     * Add a variant to the cart, merging with an existing line (capped at stock).
     */
    public function add(Cart $cart, string $productId, string $variantId, int $quantity, int $stock): void
    {
        $existing = $cart->items->first(
            fn ($item): bool => (string) $item->product_id === $productId
                && (string) $item->variant_id === $variantId
        );

        if ($existing !== null) {
            $existing->quantity = min($existing->quantity + $quantity, $stock);
            $cart->items()->save($existing);

            return;
        }

        $cart->items()->create([
            'product_id' => $productId,
            'variant_id' => $variantId,
            'quantity' => min($quantity, $stock),
        ]);
    }

    /**
     * Build a display summary of the user's cart (live prices, not snapshots).
     *
     * @return array{count: int, items: array<int, array<string, mixed>>, subtotalCents: int}
     */
    public function summary(User $user): array
    {
        $cart = $this->for($user);

        if ($cart->items->isEmpty()) {
            return ['count' => 0, 'items' => [], 'subtotalCents' => 0];
        }

        $productIds = $cart->items->pluck('product_id')->map(fn ($id): string => (string) $id)->unique()->all();

        /** @var Collection<string, Product> $products */
        $products = Product::whereIn('_id', $productIds)->get()->keyBy(fn (Product $p): string => (string) $p->_id);

        $lines = [];
        $subtotal = 0;
        $count = 0;

        foreach ($cart->items as $item) {
            $product = $products->get((string) $item->product_id);
            $variant = $product?->variants->first(
                fn (Variant $v): bool => (string) $v->_id === (string) $item->variant_id
            );

            if ($product === null || $variant === null) {
                continue;
            }

            $unit = (int) $variant->price_cents;
            $lineTotal = $unit * $item->quantity;
            $subtotal += $lineTotal;
            $count += $item->quantity;

            $lines[] = [
                'item_id' => (string) $item->_id,
                'product_id' => (string) $product->_id,
                'slug' => $product->slug,
                'name' => $product->name,
                'variant_label' => $this->variantLabel($variant),
                'unit_price_cents' => $unit,
                'quantity' => $item->quantity,
                'line_total_cents' => $lineTotal,
                'stock' => (int) $variant->stock,
                'thumbnail_url' => $product->thumbnail_path ? route('products.image', $product->_id) : null,
            ];
        }

        return ['count' => $count, 'items' => $lines, 'subtotalCents' => $subtotal];
    }

    public function variantLabel(Variant $variant): string
    {
        $attributes = $variant->attributes ?? [];
        $parts = array_filter([$attributes['size'] ?? null, $attributes['color'] ?? null]);

        return $parts === [] ? 'Default' : implode(' / ', $parts);
    }
}
