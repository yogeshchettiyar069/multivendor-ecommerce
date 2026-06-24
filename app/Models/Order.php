<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\TrackingStatus;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Carbon;
use MongoDB\Laravel\Eloquent\Model;
use MongoDB\Laravel\Relations\BelongsTo;
use MongoDB\Laravel\Relations\EmbedsMany;
use MongoDB\Laravel\Relations\HasMany;

/**
 * A customer order. Line items are embedded with snapshotted prices. Money is
 * stored in integer cents.
 *
 * @property string $_id
 * @property string $user_id
 * @property OrderStatus $status
 * @property int $subtotal_cents
 * @property int $total_cents
 * @property string|null $stripe_payment_intent_id
 * @property string|null $payment_method
 * @property array<string, mixed>|null $shipping
 * @property bool $from_cart
 * @property TrackingStatus|null $tracking_status
 * @property Carbon|null $placed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Collection<int, OrderItem> $items
 */
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    protected $connection = 'mongodb';

    protected $collection = 'orders';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'status',
        'subtotal_cents',
        'total_cents',
        'stripe_payment_intent_id',
        'payment_method',
        'shipping',
        'from_cart',
        'tracking_status',
        'placed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'subtotal_cents' => 'integer',
            'total_cents' => 'integer',
            'shipping' => 'array',
            'from_cart' => 'boolean',
            'tracking_status' => TrackingStatus::class,
            'placed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return EmbedsMany<OrderItem, $this>
     */
    public function items(): EmbedsMany
    {
        return $this->embedsMany(OrderItem::class);
    }

    /**
     * @return HasMany<Payout, $this>
     */
    public function payouts(): HasMany
    {
        return $this->hasMany(Payout::class);
    }
}
