<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use MongoDB\Laravel\Schema\Blueprint;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('mongodb')->table('products', function (Blueprint $collection): void {
            $collection->unique('slug');
            $collection->index('vendor_id');
            $collection->index('category_id');
            $collection->index('status');
            $collection->index('name');
            // Embedded variant indexes. SKU is unique across variants (sparse so
            // products without variants don't collide on a missing field).
            $collection->unique('variants.sku', null, null, ['sparse' => true]);
            $collection->index('variants.stock');
        });
    }

    public function down(): void
    {
        Schema::connection('mongodb')->table('products', function (Blueprint $collection): void {
            $collection->dropIndex('slug_1');
            $collection->dropIndex('vendor_id_1');
            $collection->dropIndex('category_id_1');
            $collection->dropIndex('status_1');
            $collection->dropIndex('name_1');
            $collection->dropIndex('variants.sku_1');
            $collection->dropIndex('variants.stock_1');
        });
    }
};
