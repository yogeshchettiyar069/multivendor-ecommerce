<?php

declare(strict_types=1);

use App\Enums\VendorStatus;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Create an approved vendor user with a category to attach products to.
 *
 * @return array{0: User, 1: Vendor, 2: Category}
 */
function approvedVendor(): array
{
    $user = User::factory()->vendor()->create();
    $vendor = Vendor::factory()->create([
        'user_id' => (string) $user->_id,
        'status' => VendorStatus::Approved,
    ]);
    $root = Category::factory()->create();
    $category = Category::factory()->childOf($root)->create();

    return [$user, $vendor, $category];
}

function validProductPayload(Category $category): array
{
    return [
        'name' => 'Test Hoodie',
        'category_id' => (string) $category->_id,
        'description' => 'A cozy hoodie.',
        'base_price' => '49.99',
        'status' => 'published',
        'variants' => [
            ['id' => null, 'sku' => 'HOOD-RED-S', 'size' => 'S', 'color' => 'Red', 'price' => '49.99', 'stock' => '10'],
            ['id' => null, 'sku' => 'HOOD-RED-M', 'size' => 'M', 'color' => 'Red', 'price' => '54.99', 'stock' => '5'],
        ],
    ];
}

it('lists only the vendor\'s own products', function () {
    [$user, $vendor] = approvedVendor();
    Product::factory()->count(2)->create(['vendor_id' => (string) $vendor->_id]);

    $other = Vendor::factory()->create();
    Product::factory()->create(['vendor_id' => (string) $other->_id]);

    $this->actingAs($user)
        ->get(route('vendor.products.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Vendor/Products/Index')
            ->has('products.data', 2));
});

it('lets an approved vendor create a product with variants (price stored as cents)', function () {
    [$user, $vendor, $category] = approvedVendor();

    $this->actingAs($user)
        ->post(route('vendor.products.store'), validProductPayload($category))
        ->assertRedirect(route('vendor.products.index'));

    $product = Product::where('vendor_id', (string) $vendor->_id)->first();

    expect($product)->not->toBeNull()
        ->and($product->base_price_cents)->toBe(4999)
        ->and($product->variants)->toHaveCount(2)
        ->and($product->variants->first()->price_cents)->toBe(4999)
        ->and($product->variants->first()->attributes['size'])->toBe('S');
});

it('forbids a pending vendor from creating products', function () {
    $user = User::factory()->vendor()->create();
    Vendor::factory()->create(['user_id' => (string) $user->_id, 'status' => VendorStatus::Pending]);
    $root = Category::factory()->create();
    $category = Category::factory()->childOf($root)->create();

    $this->actingAs($user)
        ->post(route('vendor.products.store'), validProductPayload($category))
        ->assertForbidden();
});

it('forbids a vendor from editing another vendor\'s product', function () {
    [$user] = approvedVendor();
    $other = Vendor::factory()->create();
    $product = Product::factory()->create(['vendor_id' => (string) $other->_id]);

    $this->actingAs($user)->get(route('vendor.products.edit', $product))->assertForbidden();
    $this->actingAs($user)
        // Authorization fails before validation, so the payload is irrelevant.
        ->put(route('vendor.products.update', $product), ['name' => 'Hijacked'])
        ->assertForbidden();
});

it('lets a vendor update their own product and sync variants', function () {
    [$user, $vendor, $category] = approvedVendor();
    $this->actingAs($user)->post(route('vendor.products.store'), validProductPayload($category));
    $product = Product::where('vendor_id', (string) $vendor->_id)->first();
    $keepVariantId = (string) $product->variants->first()->_id;

    $this->actingAs($user)
        ->put(route('vendor.products.update', $product), [
            'name' => 'Updated Hoodie',
            'category_id' => (string) $category->_id,
            'base_price' => '59.99',
            'status' => 'draft',
            'variants' => [
                ['id' => $keepVariantId, 'sku' => 'HOOD-RED-S', 'size' => 'S', 'color' => 'Red', 'price' => '59.99', 'stock' => '3'],
            ],
        ])
        ->assertRedirect(route('vendor.products.index'));

    $product->refresh();
    expect($product->name)->toBe('Updated Hoodie')
        ->and($product->status->value)->toBe('draft')
        ->and($product->variants)->toHaveCount(1)
        ->and($product->variants->first()->stock)->toBe(3);
});

it('lets a vendor delete their own product', function () {
    [$user, $vendor, $category] = approvedVendor();
    $this->actingAs($user)->post(route('vendor.products.store'), validProductPayload($category));
    $product = Product::where('vendor_id', (string) $vendor->_id)->first();

    $this->actingAs($user)
        ->delete(route('vendor.products.destroy', $product))
        ->assertRedirect(route('vendor.products.index'));

    expect(Product::where('_id', (string) $product->_id)->exists())->toBeFalse();
});

it('rejects a non-image thumbnail upload', function () {
    [$user, , $category] = approvedVendor();

    $payload = validProductPayload($category);
    $payload['thumbnail'] = UploadedFile::fake()->create('notes.txt', 20, 'text/plain');

    $this->actingAs($user)
        ->post(route('vendor.products.store'), $payload)
        ->assertSessionHasErrors('thumbnail');
});

it('stores an uploaded image privately and serves it through the controlled route', function () {
    Storage::fake('local');
    [$user, $vendor, $category] = approvedVendor();

    $payload = validProductPayload($category);
    $payload['thumbnail'] = UploadedFile::fake()->image('hoodie.jpg', 400, 400);

    $this->actingAs($user)->post(route('vendor.products.store'), $payload);

    $product = Product::where('vendor_id', (string) $vendor->_id)->first();

    // Stored under the private products/ path with a random (non-user) name.
    expect($product->thumbnail_path)->toStartWith('products/');
    Storage::disk('local')->assertExists($product->thumbnail_path);

    $this->get(route('products.image', $product))->assertOk();
});
