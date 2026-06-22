<?php

declare(strict_types=1);

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

/**
 * A line in a shopping cart, embedded inside the cart document.
 *
 * @property string $_id
 * @property string $product_id
 * @property string $variant_id
 * @property int $quantity
 */
class CartItem extends Model
{
    protected $connection = 'mongodb';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'product_id',
        'variant_id',
        'quantity',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
        ];
    }
}
