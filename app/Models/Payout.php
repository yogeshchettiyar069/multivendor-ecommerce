<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PayoutStatus;
use Database\Factories\PayoutFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use MongoDB\Laravel\Eloquent\Model;
use MongoDB\Laravel\Relations\BelongsTo;

/**
 * A vendor payout for the vendor's portion of an order, net of commission.
 * Stored in its own collection because it is queried independently per vendor.
 *
 * @property string $_id
 * @property string $vendor_id
 * @property string $order_id
 * @property int $amount_cents
 * @property PayoutStatus $status
 */
class Payout extends Model
{
    /** @use HasFactory<PayoutFactory> */
    use HasFactory;

    protected $connection = 'mongodb';

    protected $collection = 'payouts';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'vendor_id',
        'order_id',
        'amount_cents',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount_cents' => 'integer',
            'status' => PayoutStatus::class,
        ];
    }

    /**
     * @return BelongsTo<Vendor, $this>
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
