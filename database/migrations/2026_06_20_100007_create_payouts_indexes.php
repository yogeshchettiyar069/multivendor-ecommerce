<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use MongoDB\Laravel\Schema\Blueprint;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('mongodb')->table('payouts', function (Blueprint $collection): void {
            $collection->index('vendor_id');
            $collection->index('order_id');
            $collection->index('status');
        });
    }

    public function down(): void
    {
        Schema::connection('mongodb')->table('payouts', function (Blueprint $collection): void {
            $collection->dropIndex('vendor_id_1');
            $collection->dropIndex('order_id_1');
            $collection->dropIndex('status_1');
        });
    }
};
