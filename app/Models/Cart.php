<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use MongoDB\Laravel\Eloquent\Model;
use MongoDB\Laravel\Relations\BelongsTo;
use MongoDB\Laravel\Relations\EmbedsMany;

/**
 * A user's shopping cart. Items are embedded since they are owned by and always
 * read with the cart.
 *
 * @property string $_id
 * @property string $user_id
 * @property Collection<int, CartItem> $items
 */
class Cart extends Model
{
    protected $connection = 'mongodb';

    protected $collection = 'carts';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return EmbedsMany<CartItem, $this>
     */
    public function items(): EmbedsMany
    {
        return $this->embedsMany(CartItem::class);
    }
}
