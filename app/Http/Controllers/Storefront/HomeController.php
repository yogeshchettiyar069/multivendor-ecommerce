<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Enums\ProductStatus;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Support\ProductPresenter;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function index(): Response
    {
        $featured = Product::with(['vendor', 'category'])
            ->where('status', ProductStatus::Published->value)
            ->orderBy('created_at', 'desc')
            ->limit(8)
            ->get();

        $categories = Category::whereNull('parent_id')
            ->orderBy('name')
            ->get();

        return Inertia::render('Storefront/Home', [
            'featured' => $featured->map(ProductPresenter::card(...))->all(),
            'categories' => $categories->map(fn (Category $c): array => [
                'id' => (string) $c->_id,
                'name' => $c->name,
            ])->all(),
        ]);
    }
}
