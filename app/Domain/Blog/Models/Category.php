<?php

declare(strict_types=1);

namespace App\Domain\Blog\Models;

use App\Domain\Blog\Enums\CategoryType;
use App\Domain\Shared\Traits\HasLocalizedContent;
use App\Models\Article;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

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
    use HasFactory, HasLocalizedContent, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'slug',
        'type',
        'status',
        'parent_id',
        'lft',
        'rgt',
        'depth',
        'icon',
        'color',
        'display_order',
    ];

    protected $casts = [
        'type' => CategoryType::class,
        'display_order' => 'integer',
        'lft' => 'integer',
        'rgt' => 'integer',
        'depth' => 'integer',
    ];

    /**
     * Get the FQCN of the translation model.
     */
    public function getTranslationModelClass(): string
    {
        return CategoryTranslation::class;
    }

    /**
     * Get the list of translatable field names.
     */
    public function getTranslatableFields(): array
    {
        return ['name', 'description'];
    }

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
                $name = $model->getTranslation('name', 'en') ?? $model->getTranslation('name', 'ar') ?? 'category';
                $model->slug = Str::slug($name);
            }
        });

        // Update slug if name changes
        static::updating(function ($model) {
            if ($model->isDirty('translations') && empty($model->slug)) {
                $name = $model->getTranslation('name', 'en') ?? $model->getTranslation('name', 'ar') ?? 'category';
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
     * Get name in specified language (backward-compatible shorthand)
     */
    public function getNameInLanguage(string $language): ?string
    {
        return $this->getTranslation('name', $language);
    }

    /**
     * Get description in specified language (backward-compatible shorthand)
     */
    public function getDescriptionInLanguage(string $language): ?string
    {
        return $this->getTranslation('description', $language);
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
