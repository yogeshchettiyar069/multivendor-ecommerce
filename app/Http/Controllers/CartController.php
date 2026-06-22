<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Services\CartService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(private readonly CartService $cart) {}

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'string'],
            'variant_id' => ['required', 'string'],
            'quantity' => ['required', 'integer', 'min:1', 'max:99'],
        ]);

        $product = Product::where('_id', $data['product_id'])
            ->where('status', ProductStatus::Published->value)
            ->first();

        abort_if($product === null, 404);

        $variant = $product->variants->first(
            fn ($v): bool => (string) $v->_id === $data['variant_id']
        );

        abort_if($variant === null, 404);

        if ($variant->stock < 1) {
            return back()->with('error', 'That option is out of stock.');
        }

        $cart = $this->cart->for($request->user());
        $this->cart->add($cart, (string) $product->_id, (string) $variant->_id, $data['quantity'], (int) $variant->stock);

        return back()->with('success', 'Added to cart.');
    }

    public function update(Request $request, string $item): RedirectResponse
    {
        $data = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:99'],
        ]);

        $cart = $this->cart->for($request->user());
        $cartItem = $cart->items->first(fn ($i): bool => (string) $i->_id === $item);

        abort_if($cartItem === null, 404);

        // Never exceed the variant's current stock.
        $product = Product::where('_id', (string) $cartItem->product_id)->first();
        $variant = $product?->variants->first(fn ($v): bool => (string) $v->_id === (string) $cartItem->variant_id);
        $stock = $variant !== null ? (int) $variant->stock : $data['quantity'];

        $cartItem->quantity = max(1, min($data['quantity'], $stock));
        $cart->items()->save($cartItem);

        return back();
    }

    public function destroy(Request $request, string $item): RedirectResponse
    {
        $cart = $this->cart->for($request->user());

        if ($cart->items->contains(fn ($i): bool => (string) $i->_id === $item)) {
            $cart->items()->destroy($item);
        }

        return back()->with('success', 'Item removed from cart.');
    }
}
