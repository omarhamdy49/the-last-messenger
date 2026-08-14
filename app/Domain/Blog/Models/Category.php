<?php

declare(strict_types=1);

namespace App\Domain\Blog\Models;

use App\Domain\Blog\Enums\CategoryType;
use App\Domain\Shared\Traits\HasLocalizedContent;
use App\Models\Article;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

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
 * @property int $depth Nesting depth (0 = root), derived from parent_id on save
 * @property string|null $icon
 * @property string|null $color
 * @property int $display_order
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon|null $deleted_at
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
        'depth',
        'icon',
        'color',
        'display_order',
    ];

    protected $casts = [
        'type' => CategoryType::class,
        'display_order' => 'integer',
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

            // Derive depth from parent chain so the column is always correct.
            $model->depth = static::computeDepth($model->parent_id);
        });

        // Update slug if name changes; also refresh depth when parent changes.
        static::updating(function ($model) {
            if ($model->isDirty('translations') && empty($model->slug)) {
                $name = $model->getTranslation('name', 'en') ?? $model->getTranslation('name', 'ar') ?? 'category';
                $model->slug = Str::slug($name);
            }

            if ($model->isDirty('parent_id')) {
                $model->depth = static::computeDepth($model->parent_id);
            }
        });
    }

    /**
     * Walk up the parent chain to compute the correct depth for a given parent_id.
     * Depth is 0 for root categories (parent_id = null).
     */
    protected static function computeDepth(?int $parentId): int
    {
        $depth = 0;
        $currentId = $parentId;
        $visited = [];

        while ($currentId !== null) {
            if (in_array($currentId, $visited, true)) {
                break; // Circular reference guard
            }
            $visited[] = $currentId;
            $depth++;
            $parent = static::find($currentId);
            $currentId = $parent?->parent_id;
        }

        return $depth;
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
     * Check if category has children.
     *
     * Prefers already-materialised data (an eager-loaded `children` relation or
     * a `children_exists` column from withExists()) so that rendering a list of
     * categories does not fire one EXISTS query per row.
     */
    public function hasChildren(): bool
    {
        if ($this->relationLoaded('children')) {
            return $this->getRelation('children')->isNotEmpty();
        }

        if (array_key_exists('children_exists', $this->attributes)) {
            return (bool) $this->attributes['children_exists'];
        }

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
     * Resolve `name` in the active locale, falling back to Arabic.
     *
     * The column was moved to category_translations, which left every
     * `$category->name` read in the views resolving to null. This keeps those
     * reads working; writes go through syncTranslations, so there is no setter.
     */
    public function getNameAttribute(): ?string
    {
        return $this->getTranslation('name', app()->getLocale())
            ?? $this->getTranslation('name', 'ar');
    }

    /**
     * Resolve `description` in the active locale, falling back to Arabic.
     */
    public function getDescriptionAttribute(): ?string
    {
        return $this->getTranslation('description', app()->getLocale())
            ?? $this->getTranslation('description', 'ar');
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
        return $this->morphMany(Media::class, 'model');
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
     * Get all ancestors (parent, grandparent, etc.) ordered from root down.
     *
     * Previously this used lft/rgt nested-set range queries, but those columns
     * were never populated (no nested-set package was installed).  This
     * implementation walks the parent_id chain instead, which is reliable.
     *
     * @return Collection<int, static>
     */
    public function ancestors(): Collection
    {
        $ancestors = collect();
        $currentId = $this->parent_id;
        $visited = [];

        while ($currentId !== null) {
            if (in_array($currentId, $visited, true)) {
                break; // Circular reference guard
            }
            $visited[] = $currentId;
            $ancestor = static::find($currentId);
            if ($ancestor === null) {
                break;
            }
            $ancestors->prepend($ancestor); // prepend keeps root-first order
            $currentId = $ancestor->parent_id;
        }

        return $ancestors;
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
     * Check if category is a leaf node (no children).
     *
     * Previously relied on rgt - lft === 1 (nested-set convention) but those
     * columns were never populated, so this always returned false.  Uses a
     * children existence check instead.
     */
    public function isLeaf(): bool
    {
        return ! $this->hasChildren();
    }

    /**
     * Get breadcrumb path (array of ancestor names)
     */
    public function getBreadcrumb(string $language = 'ar'): array
    {
        $breadcrumb = [];

        foreach ($this->ancestors() as $ancestor) {
            $breadcrumb[] = $ancestor->getNameInLanguage($language);
        }

        $breadcrumb[] = $this->getNameInLanguage($language);

        return $breadcrumb;
    }

    /**
     * Get category tree as nested array.
     *
     * Loads every category (and its translations) in two queries and assembles
     * the nesting in memory; the previous recursive implementation issued a
     * query per node plus an EXISTS check and a lazy translation load.
     */
    public static function getTree(?int $parentId = null, string $language = 'ar'): array
    {
        $categories = static::query()
            ->with('translations')
            ->orderBy('display_order')
            ->get();

        // Bucket by parent, using 0 for roots (ids are auto-increment, never 0).
        $byParent = [];
        foreach ($categories as $category) {
            $byParent[$category->parent_id ?? 0][] = $category;
        }

        return static::buildTreeNodes($byParent, $parentId, $language);
    }

    /**
     * Build the nested node array for one level of the pre-bucketed tree.
     *
     * @param  array<int, array<int, static>>  $byParent
     * @return array<int, array<string, mixed>>
     */
    protected static function buildTreeNodes(array $byParent, ?int $parentId, string $language): array
    {
        $nodes = [];

        foreach ($byParent[$parentId ?? 0] ?? [] as $category) {
            $children = static::buildTreeNodes($byParent, $category->id, $language);

            $nodes[] = [
                'id' => $category->id,
                'name' => $category->getNameInLanguage($language),
                'slug' => $category->slug,
                'type' => $category->type?->value,
                'icon' => $category->icon,
                'color' => $category->color,
                'depth' => $category->depth,
                'has_children' => $children !== [],
                'children' => $children,
            ];
        }

        return $nodes;
    }
}
