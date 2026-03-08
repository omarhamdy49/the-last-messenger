<?php

declare(strict_types=1);

namespace App\Domain\Blog\Models;

use App\Models\Article;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Str;
use Spatie\Translatable\HasTranslations;

/**
 * Tag Model
 * Generic tagging system for all content types
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
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'slug',
        'name',
        'description',
        'type',
        'color',
        'usage_count',
    ];

    /**
     * Translatable attributes
     * Handled by Spatie\Translatable\HasTranslations trait
     * Do NOT add these to $casts as it conflicts with HasTranslations
     *
     * @var array<string>
     */
    public array $translatable = ['name', 'description'];

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
                $name = $nameTranslations['en'] ?? $nameTranslations['ar'] ?? 'tag';
                $model->slug = Str::slug($name);
            }
        });

        // Update slug if name changes
        static::updating(function ($model) {
            if ($model->isDirty('name') && empty($model->slug)) {
                $nameTranslations = json_decode($model->getAttributes()['name'] ?? '{}', true);
                $name = $nameTranslations['en'] ?? $nameTranslations['ar'] ?? 'tag';
                $model->slug = Str::slug($name);
            }
        });
    }

    /**
     * Get articles with this tag
     */
    public function articles(): MorphToMany
    {
        return $this->morphedByMany(Article::class, 'taggable', 'taggables');
    }

    /**
     * Scope: Filter by type
     */
    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
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
     * Get name in specified language
     */
    public function getNameInLanguage(string $language): ?string
    {
        $translations = $this->getNameTranslations();
        return $translations[$language] ?? $translations['ar'] ?? null;
    }
}
