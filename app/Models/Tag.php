<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Shared\Traits\HasLocalizedContent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Tag Model
 *
 * Tags for categorizing content across multiple models.
 *
 * @property int $id
 * @property string $slug
 * @property array $name
 * @property array|null $description
 * @property string $type
 * @property string|null $color
 * @property int $usage_count
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class Tag extends Model
{
    use HasFactory, HasLocalizedContent, SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected $table = 'tags';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'slug',
        'type',
        'color',
        'usage_count',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'usage_count' => 'integer',
    ];

    /**
     * Get the FQCN of the translation model.
     */
    public function getTranslationModelClass(): string
    {
        return \App\Domain\Blog\Models\TagTranslation::class;
    }

    /**
     * Get the list of translatable field names.
     */
    public function getTranslatableFields(): array
    {
        return ['name', 'description'];
    }

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Tag $tag) {
            if (empty($tag->slug)) {
                $name = $tag->getTranslation('name', 'en') ?? $tag->getTranslation('name', 'ar') ?? '';
                $tag->slug = Str::slug($name);
            }
        });
    }

    /**
     * Get all articles that have this tag.
     */
    public function articles(): MorphToMany
    {
        return $this->morphedByMany(Article::class, 'taggable');
    }

    /**
     * Get all media that have this tag.
     */
    public function media(): MorphToMany
    {
        return $this->morphedByMany(Media::class, 'taggable');
    }

    /**
     * Scope: Filter by type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope: Search by name.
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name->en', 'LIKE', "%{$search}%")
                ->orWhere('name->ar', 'LIKE', "%{$search}%")
                ->orWhere('slug', 'LIKE', "%{$search}%");
        });
    }

    /**
     * Scope: Popular tags (by usage_count).
     */
    public function scopePopular($query, int $limit = 10)
    {
        return $query->orderByDesc('usage_count')
            ->limit($limit);
    }

    /**
     * Scope: Recent tags (recently created).
     */
    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days))
            ->orderByDesc('created_at');
    }

    /**
     * Scope: Unused tags (zero usage_count).
     */
    public function scopeUnused($query)
    {
        return $query->where('usage_count', 0);
    }

    /**
     * Increment usage count when tag is attached
     */
    public function incrementUsage(): void
    {
        $this->increment('usage_count');
    }

    /**
     * Decrement usage count when tag is detached
     */
    public function decrementUsage(): void
    {
        $this->decrement('usage_count');
    }

    /**
     * Recalculate usage count from actual relationships
     */
    public function recalculateUsage(): void
    {
        $count = $this->articles()->count() + $this->media()->count();
        $this->update(['usage_count' => $count]);
    }

    /**
     * Get description in specified language
     */
    public function getDescriptionInLanguage(string $language): ?string
    {
        if (! $this->description) {
            return null;
        }

        $translations = is_string($this->description)
            ? json_decode($this->description, true)
            : $this->description;

        return $translations[$language] ?? $translations['ar'] ?? null;
    }

    /**
     * Get name in specified language
     */
    public function getNameInLanguage(string $language): ?string
    {
        $translations = is_string($this->name)
            ? json_decode($this->name, true)
            : $this->name;

        return $translations[$language] ?? $translations['ar'] ?? null;
    }
}
