<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Translatable\HasTranslations;

/**
 * Tag Model
 *
 * Tags for categorizing content across multiple models.
 *
 * @property int $id
 * @property string $slug
 * @property array $name
 * @property string $type
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Tag extends Model
{
    use HasFactory, HasTranslations;

    /**
     * The table associated with the model.
     */
    protected $table = 'tags';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'slug',
        'name',
        'type',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'name' => 'array',
    ];

    /**
     * Translatable attributes.
     */
    public array $translatable = ['name'];

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Tag $tag) {
            if (empty($tag->slug)) {
                $tag->slug = Str::slug($tag->name['en'] ?? $tag->name['ar'] ?? '');
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
     * Scope: Popular tags.
     */
    public function scopePopular($query, int $limit = 10)
    {
        return $query->withCount('media')
            ->orderByDesc('media_count')
            ->limit($limit);
    }
}
