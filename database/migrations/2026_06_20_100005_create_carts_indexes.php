<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use MongoDB\Laravel\Schema\Blueprint;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('mongodb')->table('carts', function (Blueprint $collection): void {
            // One cart per user.
            $collection->unique('user_id');
        });
    }

    public function down(): void
    {
        Schema::connection('mongodb')->table('carts', function (Blueprint $collection): void {
            $collection->dropIndex('user_id_1');
        });
    }
};
