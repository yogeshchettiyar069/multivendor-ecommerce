<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class ProductImageController extends Controller
{
    /**
     * Stream a product's thumbnail from the private disk. The file lives outside
     * the web root with a random name, so this controlled route is the only way
     * to reach it — there is no direct, guessable URL.
     */
    public function show(Product $product): Response
    {
        $path = $product->thumbnail_path;
        $headers = ['Cache-Control' => 'public, max-age=86400'];

        // Primary: the file stored on the (private) local disk.
        if ($path !== null && Storage::disk('local')->exists($path)) {
            return Storage::disk('local')->response($path, null, $headers);
        }

        // Fallback for ephemeral hosts (e.g. Render free tier): the guarded
        // seeder skips when data already exists in the database, so the seeded
        // image files are never written to this container's disk. The original
        // seed images ship inside the image, so serve them straight from there.
        if ($path !== null && str_starts_with($path, 'products/seed-')) {
            $original = substr($path, strlen('products/seed-'));
            $source = database_path("seeders/product_images/{$original}");

            if (is_file($source)) {
                return response()->file($source, $headers);
            }
        }

        abort(404);
    }
}
