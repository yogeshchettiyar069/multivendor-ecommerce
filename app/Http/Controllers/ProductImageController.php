<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductImageController extends Controller
{
    /**
     * Stream a product's thumbnail from the private disk. The file lives outside
     * the web root with a random name, so this controlled route is the only way
     * to reach it — there is no direct, guessable URL.
     */
    public function show(Product $product): StreamedResponse
    {
        abort_if(
            $product->thumbnail_path === null
                || ! Storage::disk('local')->exists($product->thumbnail_path),
            404,
        );

        return Storage::disk('local')->response(
            $product->thumbnail_path,
            null,
            ['Cache-Control' => 'public, max-age=86400'],
        );
    }
}
