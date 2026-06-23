<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    public function index(): Response
    {
        $all = Category::orderBy('name')->get();

        $tree = $all->whereNull('parent_id')->map(fn (Category $root): array => [
            'id' => (string) $root->_id,
            'name' => $root->name,
            'product_count' => Product::where('category_id', (string) $root->_id)->count(),
            'children' => $all->where('parent_id', (string) $root->_id)->map(fn (Category $c): array => [
                'id' => (string) $c->_id,
                'name' => $c->name,
                'product_count' => Product::where('category_id', (string) $c->_id)->count(),
            ])->values()->all(),
        ])->values()->all();

        $parents = $all->whereNull('parent_id')
            ->map(fn (Category $r): array => ['id' => (string) $r->_id, 'name' => $r->name])
            ->values()
            ->all();

        return Inertia::render('Admin/Categories/Index', [
            'tree' => $tree,
            'parents' => $parents,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'parent_id' => ['nullable', 'string', Rule::exists('categories', '_id')],
        ]);

        $ancestors = [];
        if (! empty($data['parent_id'])) {
            $parent = Category::find($data['parent_id']);
            $ancestors = [...($parent->ancestors ?? []), (string) $parent->_id];
        }

        Category::create([
            'name' => $data['name'],
            'slug' => $this->uniqueSlug($data['name']),
            'parent_id' => $data['parent_id'] ?? null,
            'ancestors' => $ancestors,
        ]);

        return back()->with('success', 'Category created.');
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
        ]);

        $category->update([
            'name' => $data['name'],
            'slug' => $this->uniqueSlug($data['name'], (string) $category->_id),
        ]);

        return back()->with('success', 'Category updated.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        $hasChildren = Category::where('parent_id', (string) $category->_id)->exists();
        $hasProducts = Product::where('category_id', (string) $category->_id)->exists();

        if ($hasChildren || $hasProducts) {
            return back()->with('error', 'Cannot delete a category that has subcategories or products.');
        }

        $category->delete();

        return back()->with('success', 'Category deleted.');
    }

    private function uniqueSlug(string $name, ?string $ignoreId = null): string
    {
        $base = Str::slug($name);
        $slug = $base;

        while (
            Category::where('slug', $slug)
                ->when($ignoreId !== null, fn ($q) => $q->where('_id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $base.'-'.Str::lower(Str::random(4));
        }

        return $slug;
    }
}
