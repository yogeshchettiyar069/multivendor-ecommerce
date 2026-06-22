<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ProductStatus;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use MongoDB\Laravel\Eloquent\Model;
use MongoDB\Laravel\Relations\BelongsTo;
use MongoDB\Laravel\Relations\EmbedsMany;

/**
 * A catalogue product owned by a vendor. Variants are EMBEDDED because they are
 * always read together with the product. Money is stored in integer cents.
 *
 * @property string $_id
 * @property string $vendor_id
 * @property string $category_id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property int $base_price_cents
 * @property ProductStatus $status
 * @property string|null $thumbnail_path
 * @property Collection<int, Variant> $variants
 */
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    protected $connection = 'mongodb';

    protected $collection = 'products';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'vendor_id',
        'category_id',
        'name',
        'slug',
        'description',
        'base_price_cents',
        'status',
        'thumbnail_path',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ProductStatus::class,
            'base_price_cents' => 'integer',
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
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Embedded product variants (size/colour, price, stock).
     *
     * @return EmbedsMany<Variant, $this>
     */
    public function variants(): EmbedsMany
    {
        return $this->embedsMany(Variant::class);
    }

    public function isPublished(): bool
    {
        return $this->status === ProductStatus::Published;
    }

    /**
     * Total stock across all variants.
     */
    public function totalStock(): int
    {
        return (int) $this->variants->sum('stock');
    }
}
