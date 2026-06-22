<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use MongoDB\Laravel\Schema\Blueprint;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('mongodb')->table('vendors', function (Blueprint $collection): void {
            $collection->unique('slug');
            $collection->index('user_id');
            $collection->index('status');
        });
    }

    public function down(): void
    {
        Schema::connection('mongodb')->table('vendors', function (Blueprint $collection): void {
            $collection->dropIndex('slug_1');
            $collection->dropIndex('user_id_1');
            $collection->dropIndex('status_1');
        });
    }
};
