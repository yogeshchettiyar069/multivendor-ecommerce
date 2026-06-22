<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use MongoDB\Laravel\Schema\Blueprint;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('mongodb')->table('orders', function (Blueprint $collection): void {
            $collection->index('user_id');
            $collection->index('status');
            $collection->index('placed_at');
            // Unique (sparse) so webhook retries can't create duplicate orders for
            // the same PaymentIntent; sparse because pending orders have none yet.
            $collection->unique('stripe_payment_intent_id', null, null, ['sparse' => true]);
        });
    }

    public function down(): void
    {
        Schema::connection('mongodb')->table('orders', function (Blueprint $collection): void {
            $collection->dropIndex('user_id_1');
            $collection->dropIndex('status_1');
            $collection->dropIndex('placed_at_1');
            $collection->dropIndex('stripe_payment_intent_id_1');
        });
    }
};
