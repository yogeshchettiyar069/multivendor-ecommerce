<?php

declare(strict_types=1);

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

/**
 * A product variant, embedded inside the parent product document (it is always
 * loaded with the product). Each variant carries its own ObjectId so stock can
 * be decremented atomically via a positional update on `variants.$`.
 *
 * Money is stored in integer minor units (cents) — never floats.
 *
 * @property string $_id
 * @property string $sku
 * @property int $price_cents
 * @property int $stock
 * @property array{size?: string, color?: string}|null $attributes
 */
class Variant extends Model
{
    protected $connection = 'mongodb';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'sku',
        'price_cents',
        'stock',
        'attributes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price_cents' => 'integer',
            'stock' => 'integer',
            'attributes' => 'array',
        ];
    }

    public function inStock(): bool
    {
        return $this->stock > 0;
    }
}
