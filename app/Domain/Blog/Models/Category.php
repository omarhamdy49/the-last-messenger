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
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Translatable\HasTranslations;

/**
 * Category Model
 * Hierarchical categories for blog, media, and timeline content
 *
 * @property int $id
 * @property string $slug
 * @property CategoryType $type
 * @property string $status
 * @property array $name
 * @property array|null $description
 * @property int|null $parent_id
 * @property int $lft Nested set left value
 * @property int $rgt Nested set right value
 * @property int $depth Nested set depth/level
 * @property string|null $icon
 * @property string|null $color
 * @property int $display_order
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class Category extends Model
{
    use HasFactory, HasTranslations, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'slug',
        'type',
        'status',
        'name',
        'description',
        'parent_id',
        'lft',
        'rgt',
        'depth',
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
        'lft' => 'integer',
        'rgt' => 'integer',
        'depth' => 'integer',
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

    /**
     * Get all media (polymorphic)
     */
    public function media(): MorphMany
    {
        return $this->morphMany(\Spatie\MediaLibrary\MediaCollections\Models\Media::class, 'model');
    }

    /**
     * Scope: Active categories only
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope: Categories with specific depth level
     */
    public function scopeWithDepth(Builder $query, int $depth): Builder
    {
        return $query->where('depth', $depth);
    }

    /**
     * Get all descendants (children, grandchildren, etc.)
     */
    public function descendants(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id')->with('descendants');
    }

    /**
     * Get all ancestors (parent, grandparent, etc.)
     */
    public function ancestors(): Builder
    {
        return static::query()
            ->where('lft', '<', $this->lft)
            ->where('rgt', '>', $this->rgt)
            ->orderBy('lft');
    }

    /**
     * Get siblings (categories with same parent)
     */
    public function siblings(): Builder
    {
        return static::query()
            ->where('parent_id', $this->parent_id)
            ->where('id', '!=', $this->id)
            ->orderBy('display_order');
    }

    /**
     * Check if category is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if category is a leaf node (no children)
     */
    public function isLeaf(): bool
    {
        return $this->rgt - $this->lft === 1;
    }

    /**
     * Get breadcrumb path (array of ancestor names)
     */
    public function getBreadcrumb(string $language = 'ar'): array
    {
        $breadcrumb = [];
        $ancestors = $this->ancestors()->get();

        foreach ($ancestors as $ancestor) {
            $breadcrumb[] = $ancestor->getNameInLanguage($language);
        }

        $breadcrumb[] = $this->getNameInLanguage($language);

        return $breadcrumb;
    }

    /**
     * Get category tree as nested array
     */
    public static function getTree(?int $parentId = null, string $language = 'ar'): array
    {
        $categories = static::where('parent_id', $parentId)
            ->orderBy('display_order')
            ->get();

        $tree = [];
        foreach ($categories as $category) {
            $tree[] = [
                'id' => $category->id,
                'name' => $category->getNameInLanguage($language),
                'slug' => $category->slug,
                'icon' => $category->icon,
                'color' => $category->color,
                'depth' => $category->depth,
                'has_children' => $category->hasChildren(),
                'children' => static::getTree($category->id, $language),
            ];
        }

        return $tree;
    }
}
