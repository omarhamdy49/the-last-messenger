<?php

declare(strict_types=1);

namespace App\Domain\Blog\Models;

use App\Domain\Blog\Enums\CategoryType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Spatie\Translatable\HasTranslations;

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
    use HasFactory, HasTranslations;

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
     * Translatable attributes
     * Handled by Spatie\Translatable\HasTranslations trait
     * Do NOT add these to $casts as it conflicts with HasTranslations
     *
     * @var array<string>
     */
    public array $translatable = ['name', 'description'];

    protected $casts = [
        'type' => CategoryType::class,
        'display_order' => 'integer',
    ];

    /**
     * Boot the model
     */
    protected static function boot(): void
    {
        parent::boot();

        // Auto-generate slug from English name if not provided
        static::creating(function ($model) {
            if (empty($model->slug)) {
                // Generate slug from English name, fallback to Arabic
                $nameTranslations = json_decode($model->getAttributes()['name'] ?? '{}', true);
                $name = $nameTranslations['en'] ?? $nameTranslations['ar'] ?? 'category';
                $model->slug = Str::slug($name);
            }
        });

        // Update slug if name changes
        static::updating(function ($model) {
            if ($model->isDirty('name') && empty($model->slug)) {
                $nameTranslations = json_decode($model->getAttributes()['name'] ?? '{}', true);
                $name = $nameTranslations['en'] ?? $nameTranslations['ar'] ?? 'category';
                $model->slug = Str::slug($name);
            }
        });
    }

    /**
     * Get parent category
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    /**
     * Get child categories
     */
    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id')->orderBy('display_order');
    }

    /**
     * Get articles in this category (primary category - old relationship)
     */
    public function articles(): HasMany
    {
        return $this->hasMany(Article::class);
    }

    /**
     * Get all articles that have this category (many-to-many)
     */
    public function articlesMany(): BelongsToMany
    {
        return $this->belongsToMany(Article::class, 'article_category');
    }

    /**
     * Scope: Root categories only (no parent)
     */
    public function scopeRoot(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Scope: Filter by type
     */
    public function scopeOfType(Builder $query, CategoryType $type): Builder
    {
        return $query->where('type', $type);
    }

    /**
     * Scope: Ordered by display order
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('display_order');
    }

    /**
     * Check if category has children
     */
    public function hasChildren(): bool
    {
        return $this->children()->exists();
    }

    /**
     * Check if category is root (no parent)
     */
    public function isRoot(): bool
    {
        return $this->parent_id === null;
    }

    /**
     * Get all translations for name attribute
     * Returns the raw JSON decoded to array
     */
    public function getNameTranslations(): array
    {
        $raw = $this->getAttributes()['name'] ?? '{}';
        return is_string($raw) ? json_decode($raw, true) : $raw;
    }

    /**
     * Get all translations for description attribute
     * Returns the raw JSON decoded to array
     */
    public function getDescriptionTranslations(): array
    {
        $raw = $this->getAttributes()['description'] ?? '{}';
        return is_string($raw) ? json_decode($raw, true) : $raw;
    }

    /**
     * Get name in specified language
     */
    public function getNameInLanguage(string $language): ?string
    {
        $translations = $this->getNameTranslations();
        return $translations[$language] ?? $translations['ar'] ?? null;
    }

    /**
     * Get description in specified language
     */
    public function getDescriptionInLanguage(string $language): ?string
    {
        $translations = $this->getDescriptionTranslations();
        return $translations[$language] ?? $translations['ar'] ?? null;
    }
}
