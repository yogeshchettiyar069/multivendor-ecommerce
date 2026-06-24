<?php

declare(strict_types=1);

use App\Enums\ProductStatus;
use App\Enums\VendorStatus;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

function publishedProduct(int $stock = 10, ?string $categoryId = null, ?string $name = null): Product
{
    $vendor = Vendor::factory()->create();

    if ($categoryId === null) {
        $root = Category::factory()->create();
        $categoryId = (string) Category::factory()->childOf($root)->create()->_id;
    }

    $name ??= 'Item '.Str::random(6);

    $product = Product::create([
        'vendor_id' => (string) $vendor->_id,
        'category_id' => $categoryId,
        'name' => $name,
        'slug' => Str::slug($name).'-'.Str::lower(Str::random(5)),
        'base_price_cents' => 2000,
        'status' => ProductStatus::Published,
    ]);

    $product->variants()->create([
        'sku' => 'SKU-'.strtoupper(Str::random(8)),
        'price_cents' => 2000,
        'stock' => $stock,
        'attributes' => ['size' => 'M', 'color' => 'Black'],
    ]);

    return $product->refresh();
}

it('lists only published products on the catalog', function () {
    publishedProduct();
    publishedProduct();
    Product::factory()->create(['status' => ProductStatus::Draft]);

    $this->get(route('catalog'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Storefront/Catalog')
            ->has('products.data', 2));
});

it('filters by in-stock', function () {
    publishedProduct(stock: 5);
    publishedProduct(stock: 0);

    $this->get(route('catalog', ['in_stock' => 1]))
        ->assertInertia(fn (Assert $page) => $page->has('products.data', 1));
});

it('filters by search term', function () {
    publishedProduct(name: 'Aurora Lamp');
    publishedProduct(name: 'Basalt Mug');

    $this->get(route('catalog', ['search' => 'aurora']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('products.data', 1)
            ->where('products.data.0.name', 'Aurora Lamp'));
});

it('shows a published product detail page', function () {
    $product = publishedProduct();

    $this->get(route('products.show', $product->slug))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Storefront/Show')
            ->where('product.name', $product->name)
            ->has('product.variants', 1));
});

it('returns 404 for a draft product detail page', function () {
    $product = Product::factory()->draft()->create();

    $this->get(route('products.show', $product->slug))->assertNotFound();
});

it('shows an approved vendor store with their published products', function () {
    $owner = User::factory()->vendor()->create();
    $vendor = Vendor::factory()->create([
        'user_id' => (string) $owner->_id,
        'status' => VendorStatus::Approved,
    ]);
    vendorProduct($vendor, 2000, 5);
    vendorProduct($vendor, 3000, 5);

    $this->get(route('stores.show', $vendor->slug))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Storefront/Store')
            ->where('vendor.name', $vendor->store_name)
            ->has('products.data', 2));
});

it('returns 404 for a non-approved vendor store', function () {
    $vendor = Vendor::factory()->pending()->create();

    $this->get(route('stores.show', $vendor->slug))->assertNotFound();
});
