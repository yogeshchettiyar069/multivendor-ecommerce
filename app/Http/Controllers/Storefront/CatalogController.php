<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Enums\ProductStatus;
use App\Enums\VendorStatus;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\Vendor;
use App\Support\ProductPresenter;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use MongoDB\BSON\Regex;

class CatalogController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));
        $categoryId = (string) $request->query('category', '');
        $inStock = $request->boolean('in_stock');
        $sort = (string) $request->query('sort', 'newest');

        $query = Product::with(['vendor', 'category'])
            ->where('status', ProductStatus::Published->value);

        if ($search !== '') {
            $query->where('name', new Regex(preg_quote($search), 'i'));
        }

        if ($categoryId !== '') {
            $query->whereIn('category_id', $this->categorySubtreeIds($categoryId));
        }

        if ($request->filled('min_price')) {
            $query->where('base_price_cents', '>=', (int) round((float) $request->query('min_price') * 100));
        }

        if ($request->filled('max_price')) {
            $query->where('base_price_cents', '<=', (int) round((float) $request->query('max_price') * 100));
        }

        if ($inStock) {
            $query->where('variants.stock', '>', 0);
        }

        match ($sort) {
            'price_asc' => $query->orderBy('base_price_cents', 'asc'),
            'price_desc' => $query->orderBy('base_price_cents', 'desc'),
            'name' => $query->orderBy('name', 'asc'),
            default => $query->orderBy('created_at', 'desc'),
        };

        $products = $query->paginate(12)->withQueryString();

        return Inertia::render('Storefront/Catalog', [
            'products' => [
                'data' => collect($products->items())->map(ProductPresenter::card(...))->all(),
                'links' => $products->linkCollection()->all(),
                'meta' => [
                    'total' => $products->total(),
                    'from' => $products->firstItem(),
                    'to' => $products->lastItem(),
                ],
            ],
            'categories' => $this->categoryTree(),
            'priceBounds' => $this->priceBounds(),
            'filters' => [
                'search' => $search,
                'category' => $categoryId,
                'min_price' => $request->query('min_price'),
                'max_price' => $request->query('max_price'),
                'in_stock' => $inStock,
                'sort' => $sort,
            ],
        ]);
    }

    public function show(string $slug): Response
    {
        $product = Product::with(['vendor', 'category'])
            ->where('slug', $slug)
            ->where('status', ProductStatus::Published->value)
            ->firstOrFail();

        return Inertia::render('Storefront/Show', [
            'product' => [
                'id' => (string) $product->_id,
                'slug' => $product->slug,
                'name' => $product->name,
                'description' => $product->description,
                'vendor' => $product->vendor === null ? null : [
                    'name' => $product->vendor->store_name,
                    'slug' => $product->vendor->slug,
                    'bio' => $product->vendor->bio,
                    'approved' => $product->vendor->isApproved(),
                ],
                'category' => $product->category?->name,
                'base_price_cents' => $product->base_price_cents,
                'thumbnail_url' => $product->thumbnail_path ? route('products.image', $product->_id) : null,
                'variants' => $product->variants->map(fn ($v): array => [
                    'id' => (string) $v->_id,
                    'sku' => $v->sku,
                    'size' => $v->attributes['size'] ?? null,
                    'color' => $v->attributes['color'] ?? null,
                    'price_cents' => (int) $v->price_cents,
                    'stock' => (int) $v->stock,
                ])->all(),
            ],
        ]);
    }

    /**
     * Public vendor storefront: the vendor's published products + their profile.
     */
    public function store(string $slug): Response
    {
        $vendor = Vendor::where('slug', $slug)
            ->where('status', VendorStatus::Approved->value)
            ->firstOrFail();

        $products = Product::with(['vendor', 'category'])
            ->where('vendor_id', (string) $vendor->_id)
            ->where('status', ProductStatus::Published->value)
            ->orderBy('created_at', 'desc')
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('Storefront/Store', [
            'vendor' => [
                'name' => $vendor->store_name,
                'slug' => $vendor->slug,
                'bio' => $vendor->bio,
            ],
            'products' => [
                'data' => collect($products->items())->map(ProductPresenter::card(...))->all(),
                'links' => $products->linkCollection()->all(),
                'meta' => [
                    'total' => $products->total(),
                    'from' => $products->firstItem(),
                    'to' => $products->lastItem(),
                ],
            ],
        ]);
    }

    /**
     * Ids of a category and all its descendants (via the materialized path).
     *
     * @return array<int, string>
     */
    private function categorySubtreeIds(string $categoryId): array
    {
        $descendants = Category::where('ancestors', $categoryId)
            ->pluck('_id')
            ->map(fn ($id): string => (string) $id)
            ->all();

        return [$categoryId, ...$descendants];
    }

    /**
     * @return array<int, array{id: string, name: string, children: array<int, array{id: string, name: string}>}>
     */
    private function categoryTree(): array
    {
        $all = Category::orderBy('name')->get();

        return $all->whereNull('parent_id')->map(fn (Category $root): array => [
            'id' => (string) $root->_id,
            'name' => $root->name,
            'children' => $all->where('parent_id', (string) $root->_id)->map(fn (Category $c): array => [
                'id' => (string) $c->_id,
                'name' => $c->name,
            ])->values()->all(),
        ])->values()->all();
    }

    /**
     * @return array{min: int, max: int}
     */
    private function priceBounds(): array
    {
        $published = Product::where('status', ProductStatus::Published->value);

        return [
            'min' => (int) ($published->min('base_price_cents') ?? 0),
            'max' => (int) ($published->max('base_price_cents') ?? 0),
        ];
    }
}
