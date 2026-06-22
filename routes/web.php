<?php

declare(strict_types=1);

use App\Http\Controllers\CartController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProductImageController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Storefront\CatalogController;
use App\Http\Controllers\Storefront\HomeController;
use App\Http\Controllers\Vendor\ProductController as VendorProductController;
use App\Http\Controllers\VendorApplicationController;
use Illuminate\Support\Facades\Route;

// Public storefront.
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/products', [CatalogController::class, 'index'])->name('catalog');
Route::get('/products/{slug}', [CatalogController::class, 'show'])->name('products.show');

// Controlled product image streaming (no direct filesystem path, random names).
Route::get('/media/products/{product}', [ProductImageController::class, 'show'])
    ->name('products.image');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Shopping cart.
    Route::post('/cart', [CartController::class, 'store'])->name('cart.store');
    Route::patch('/cart/{item}', [CartController::class, 'update'])->name('cart.update');
    Route::delete('/cart/{item}', [CartController::class, 'destroy'])->name('cart.destroy');

    // Become a vendor — available to customers only.
    Route::middleware('role:customer')->group(function () {
        Route::get('/vendor/apply', [VendorApplicationController::class, 'create'])->name('vendor.apply');
        Route::post('/vendor/apply', [VendorApplicationController::class, 'store'])->name('vendor.apply.store');
    });

    // Vendor product management (authorization is enforced per-action by ProductPolicy).
    Route::middleware('role:vendor')->prefix('vendor')->name('vendor.')->group(function () {
        Route::get('/products', [VendorProductController::class, 'index'])->name('products.index');
        Route::get('/products/create', [VendorProductController::class, 'create'])->name('products.create');
        Route::post('/products', [VendorProductController::class, 'store'])->name('products.store');
        Route::get('/products/{product}/edit', [VendorProductController::class, 'edit'])->name('products.edit');
        Route::match(['put', 'patch'], '/products/{product}', [VendorProductController::class, 'update'])->name('products.update');
        Route::delete('/products/{product}', [VendorProductController::class, 'destroy'])->name('products.destroy');
    });
});

require __DIR__.'/auth.php';
