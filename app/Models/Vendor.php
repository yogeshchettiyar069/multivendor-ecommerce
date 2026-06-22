<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\VendorStatus;
use Database\Factories\VendorFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use MongoDB\Laravel\Eloquent\Model;
use MongoDB\Laravel\Relations\BelongsTo;
use MongoDB\Laravel\Relations\HasMany;

/**
 * A vendor store, referenced by products, orders and payouts.
 *
 * @property string $_id
 * @property string $user_id
 * @property string $store_name
 * @property string $slug
 * @property VendorStatus $status
 * @property string|null $bio
 * @property string|null $logo_path
 * @property float $commission_rate Platform commission as a fraction (0.0–1.0).
 */
class Vendor extends Model
{
    /** @use HasFactory<VendorFactory> */
    use HasFactory;

    protected $connection = 'mongodb';

    protected $collection = 'vendors';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'store_name',
        'slug',
        'status',
        'bio',
        'logo_path',
        'commission_rate',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => VendorStatus::class,
            'commission_rate' => 'float',
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
     * @return HasMany<Product, $this>
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * @return HasMany<Payout, $this>
     */
    public function payouts(): HasMany
    {
        return $this->hasMany(Payout::class);
    }

    public function isApproved(): bool
    {
        return $this->status === VendorStatus::Approved;
    }
}
