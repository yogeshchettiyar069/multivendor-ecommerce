<?php

declare(strict_types=1);

namespace App\Http\Controllers\Vendor;

use App\Enums\ProductStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Vendor\StoreProductRequest;
use App\Http\Requests\Vendor\UpdateProductRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\Vendor;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use MongoDB\BSON\Regex;

class ProductController extends Controller
{
    use AuthorizesRequests;

    private const SORTS = ['name', 'base_price_cents', 'created_at'];

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Product::class);

        $vendor = $this->vendor($request);
        $search = trim((string) $request->query('search', ''));
        $sort = in_array($request->query('sort'), self::SORTS, true) ? $request->query('sort') : 'created_at';
        $direction = $request->query('direction') === 'asc' ? 'asc' : 'desc';

        $query = Product::with('category')->where('vendor_id', $vendor->_id);

        if ($search !== '') {
            // preg_quote neutralises regex metacharacters → no NoSQL/regex injection.
            $query->where('name', new Regex(preg_quote($search), 'i'));
        }

        $products = $query->orderBy($sort, $direction)->paginate(10)->withQueryString();

        return Inertia::render('Vendor/Products/Index', [
            'products' => [
                'data' => collect($products->items())->map($this->serialize(...))->all(),
                'links' => $products->linkCollection()->all(),
                'meta' => [
                    'current_page' => $products->currentPage(),
                    'last_page' => $products->lastPage(),
                    'total' => $products->total(),
                    'from' => $products->firstItem(),
                    'to' => $products->lastItem(),
                ],
            ],
            'filters' => ['search' => $search, 'sort' => $sort, 'direction' => $direction],
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', Product::class);

        return Inertia::render('Vendor/Products/Create', [
            'categories' => $this->categoryOptions(),
            'statuses' => $this->statusOptions(),
        ]);
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $vendor = $this->vendor($request);
        $data = $request->validated();

        $product = new Product([
            'vendor_id' => (string) $vendor->_id,
            'category_id' => $data['category_id'],
            'name' => $data['name'],
            'slug' => $this->uniqueSlug($data['name']),
            'description' => $data['description'] ?? null,
            'base_price_cents' => $this->toCents($data['base_price']),
            'status' => $data['status'],
        ]);

        if ($request->hasFile('thumbnail')) {
            $product->thumbnail_path = $request->file('thumbnail')->store('products', 'local');
        }

        $product->save();
        $this->syncVariants($product, $data['variants']);

        return redirect()->route('vendor.products.index')
            ->with('success', 'Product created.');
    }

    public function edit(Request $request, Product $product): Response
    {
        $this->authorize('update', $product);

        return Inertia::render('Vendor/Products/Edit', [
            'product' => [
                'id' => (string) $product->_id,
                'name' => $product->name,
                'category_id' => (string) $product->category_id,
                'description' => $product->description,
                'base_price' => $this->toDollars($product->base_price_cents),
                'status' => $product->status->value,
                'thumbnail_url' => $product->thumbnail_path ? route('products.image', $product->_id) : null,
                'variants' => $product->variants->map(fn ($v): array => [
                    'id' => (string) $v->_id,
                    'sku' => $v->sku,
                    'size' => $v->attributes['size'] ?? '',
                    'color' => $v->attributes['color'] ?? '',
                    'price' => $this->toDollars($v->price_cents),
                    'stock' => $v->stock,
                ])->all(),
            ],
            'categories' => $this->categoryOptions(),
            'statuses' => $this->statusOptions(),
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $data = $request->validated();

        $product->fill([
            'category_id' => $data['category_id'],
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'base_price_cents' => $this->toCents($data['base_price']),
            'status' => $data['status'],
        ]);

        if ($request->hasFile('thumbnail')) {
            $this->deleteThumbnail($product);
            $product->thumbnail_path = $request->file('thumbnail')->store('products', 'local');
        }

        $product->save();
        $this->syncVariants($product, $data['variants']);

        return redirect()->route('vendor.products.index')
            ->with('success', 'Product updated.');
    }

    public function destroy(Request $request, Product $product): RedirectResponse
    {
        $this->authorize('delete', $product);

        $this->deleteThumbnail($product);
        $product->delete();

        return redirect()->route('vendor.products.index')
            ->with('success', 'Product deleted.');
    }

    /**
     * Reconcile embedded variants with the submitted set: update by id, create
     * new ones, and remove any that were dropped.
     *
     * @param  array<int, array<string, mixed>>  $variants
     */
    private function syncVariants(Product $product, array $variants): void
    {
        $existing = $product->variants->keyBy(fn ($v): string => (string) $v->_id);
        $kept = [];

        foreach ($variants as $row) {
            $payload = [
                'sku' => $row['sku'],
                'price_cents' => $this->toCents($row['price']),
                'stock' => (int) $row['stock'],
                'attributes' => array_filter([
                    'size' => $row['size'] ?? null,
                    'color' => $row['color'] ?? null,
                ], fn ($value): bool => $value !== null && $value !== ''),
            ];

            $id = $row['id'] ?? null;

            if ($id !== null && $existing->has($id)) {
                $variant = $existing->get($id);
                $variant->fill($payload);
                $product->variants()->save($variant);
                $kept[] = $id;
            } else {
                $created = $product->variants()->create($payload);
                $kept[] = (string) $created->_id;
            }
        }

        foreach ($existing->keys() as $id) {
            if (! in_array($id, $kept, true)) {
                $product->variants()->destroy($id);
            }
        }
    }

    private function deleteThumbnail(Product $product): void
    {
        if ($product->thumbnail_path && Storage::disk('local')->exists($product->thumbnail_path)) {
            Storage::disk('local')->delete($product->thumbnail_path);
        }
    }

    private function vendor(Request $request): Vendor
    {
        $vendor = $request->user()?->vendor;

        abort_unless($vendor instanceof Vendor, 403);

        return $vendor;
    }

    /**
     * @return array<string, mixed>
     */
    private function serialize(Product $product): array
    {
        return [
            'id' => (string) $product->_id,
            'name' => $product->name,
            'category' => $product->category?->name,
            'base_price_cents' => $product->base_price_cents,
            'stock' => $product->totalStock(),
            'variant_count' => $product->variants->count(),
            'status' => $product->status->value,
            'thumbnail_url' => $product->thumbnail_path ? route('products.image', $product->_id) : null,
        ];
    }

    /**
     * @return array<int, array{id: string, label: string}>
     */
    private function categoryOptions(): array
    {
        $all = Category::all();
        $byId = $all->keyBy(fn (Category $c): string => (string) $c->_id);

        return $all->filter(fn (Category $c): bool => $c->parent_id !== null)
            ->map(function (Category $c) use ($byId): array {
                $parent = $byId->get((string) $c->parent_id);

                return [
                    'id' => (string) $c->_id,
                    'label' => ($parent ? $parent->name.' / ' : '').$c->name,
                ];
            })
            ->sortBy('label')
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function statusOptions(): array
    {
        return array_map(
            fn (ProductStatus $status): array => ['value' => $status->value, 'label' => $status->label()],
            ProductStatus::cases(),
        );
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;

        while (Product::where('slug', $slug)->exists()) {
            $slug = $base.'-'.Str::lower(Str::random(5));
        }

        return $slug;
    }

    private function toCents(mixed $dollars): int
    {
        return (int) round(((float) $dollars) * 100);
    }

    private function toDollars(int $cents): string
    {
        return number_format($cents / 100, 2, '.', '');
    }
}
