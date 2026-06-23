<?php

declare(strict_types=1);

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

/**
 * An order line item, embedded inside the order document. The unit price is a
 * SNAPSHOT taken at purchase time — never read the live product price for a past
 * order. Carries vendor_id so a multi-vendor order can be split for payouts.
 *
 * @property string $_id
 * @property string $product_id
 * @property string $variant_id
 * @property string $vendor_id
 * @property int $unit_price_cents
 * @property int $quantity
 * @property bool $fulfilled
 */
class OrderItem extends Model
{
    protected $connection = 'mongodb';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'product_id',
        'variant_id',
        'vendor_id',
        'unit_price_cents',
        'quantity',
        'fulfilled',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'unit_price_cents' => 'integer',
            'quantity' => 'integer',
            'fulfilled' => 'boolean',
        ];
    }

    public function lineTotalCents(): int
    {
        return $this->unit_price_cents * $this->quantity;
    }
}
