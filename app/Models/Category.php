<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use MongoDB\Laravel\Eloquent\Model;
use MongoDB\Laravel\Relations\BelongsTo;
use MongoDB\Laravel\Relations\HasMany;

/**
 * Product category arranged as a tree. A materialized-path `ancestors` array
 * stores every ancestor id (root-first) for fast subtree queries without
 * recursive lookups.
 *
 * @property string $_id
 * @property string|null $parent_id
 * @property string $name
 * @property string $slug
 * @property array<int, string> $ancestors
 */
class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
    use HasFactory;

    protected $connection = 'mongodb';

    protected $collection = 'categories';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'parent_id',
        'name',
        'slug',
        'ancestors',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'ancestors' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    /**
     * @return HasMany<Category, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    /**
     * @return HasMany<Product, $this>
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function isRoot(): bool
    {
        return $this->parent_id === null;
    }
}
