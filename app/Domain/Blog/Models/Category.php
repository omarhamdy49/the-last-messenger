<?php

declare(strict_types=1);

namespace App\Domain\Blog\Models;

use App\Domain\Blog\Enums\CategoryType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

/**
 * Category Model
 * Hierarchical categories for blog, media, and timeline content
 *
 * @property int $id
 * @property string $slug
 * @property CategoryType $type
 * @property array $name
 * @property array|null $description
 * @property int|null $parent_id
 * @property string|null $icon
 * @property string|null $color
 * @property int $display_order
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Category extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'slug',
        'type',
        'name',
        'description',
        'parent_id',
        'icon',
        'color',
        'display_order',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => CategoryType::class,
            'name' => 'array',
            'description' => 'array',
            'display_order' => 'integer',
        ];
    }

    /**
     * Get parent category
     *
     * @return BelongsTo
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    /**
     * Get child categories
     *
     * @return HasMany
     */
    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id')->orderBy('display_order');
    }

    /**
     * Get articles in this category
     *
     * @return HasMany
     */
    public function articles(): HasMany
    {
        return $this->hasMany(Article::class);
    }

    /**
     * Scope: Root categories only (no parent)
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeRoot(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Scope: Filter by type
     *
     * @param Builder $query
     * @param CategoryType $type
     * @return Builder
     */
    public function scopeOfType(Builder $query, CategoryType $type): Builder
    {
        return $query->where('type', $type);
    }

    /**
     * Scope: Ordered by display order
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('display_order');
    }

    /**
     * Check if category has children
     *
     * @return bool
     */
    public function hasChildren(): bool
    {
        return $this->children()->exists();
    }

    /**
     * Check if category is root (no parent)
     *
     * @return bool
     */
    public function isRoot(): bool
    {
        return $this->parent_id === null;
    }

    /**
     * Get name in specified language
     *
     * @param string $language
     * @return string|null
     */
    public function getNameInLanguage(string $language): ?string
    {
        return $this->name[$language] ?? $this->name['ar'] ?? null;
    }

    /**
     * Get description in specified language
     *
     * @param string $language
     * @return string|null
     */
    public function getDescriptionInLanguage(string $language): ?string
    {
        return $this->description[$language] ?? $this->description['ar'] ?? null;
    }
}

