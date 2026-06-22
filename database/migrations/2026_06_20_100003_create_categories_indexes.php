<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use MongoDB\Laravel\Schema\Blueprint;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('mongodb')->table('categories', function (Blueprint $collection): void {
            $collection->unique('slug');
            $collection->index('parent_id');
            // Multikey index over the materialized path for fast subtree queries.
            $collection->index('ancestors');
        });
    }

    public function down(): void
    {
        Schema::connection('mongodb')->table('categories', function (Blueprint $collection): void {
            $collection->dropIndex('slug_1');
            $collection->dropIndex('parent_id_1');
            $collection->dropIndex('ancestors_1');
        });
    }
};
