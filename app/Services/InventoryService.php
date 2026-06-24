<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\InsufficientStockException;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use MongoDB\BSON\ObjectId;
use MongoDB\Driver\Session;
use MongoDB\Laravel\Connection;

class InventoryService
{
    /**
     * Atomically deduct stock for each line within the active transaction.
     *
     * Each deduction is a single conditional update: it only matches when the
     * SAME embedded variant ($elemMatch) still has enough stock, then decrements
     * it with the positional operator. If no document matches, stock ran out and
     * we abort — which rolls back the whole transaction. Two concurrent buyers of
     * the last unit therefore can never both succeed: exactly one wins.
     *
     * @param  array<int, array{product_id: string, variant_id: string, quantity: int, name?: string}>  $lines
     */
    public function deduct(array $lines, Session $session): void
    {
        /** @var Connection $connection */
        $connection = DB::connection('mongodb');
        $products = $connection->getCollection('products');

        foreach ($lines as $line) {
            $result = $products->findOneAndUpdate(
                [
                    '_id' => new ObjectId($line['product_id']),
                    'variants' => [
                        '$elemMatch' => [
                            '_id' => new ObjectId($line['variant_id']),
                            'stock' => ['$gte' => $line['quantity']],
                        ],
                    ],
                ],
                ['$inc' => ['variants.$.stock' => -$line['quantity']]],
                ['session' => $session],
            );

            if ($result === null) {
                throw new InsufficientStockException($line['name'] ?? 'an item');
            }
        }
    }

    /**
     * Return stock to inventory (used when a payment fails or is cancelled).
     */
    public function restore(Order $order): void
    {
        /** @var Connection $connection */
        $connection = DB::connection('mongodb');
        $products = $connection->getCollection('products');

        foreach ($order->items as $item) {
            $products->updateOne(
                [
                    '_id' => new ObjectId((string) $item->product_id),
                    'variants._id' => new ObjectId((string) $item->variant_id),
                ],
                ['$inc' => ['variants.$.stock' => (int) $item->quantity]],
            );
        }
    }
}
