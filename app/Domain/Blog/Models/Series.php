<?php

declare(strict_types=1);

namespace App\Domain\Blog\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

/**
 * Series Model
 * Groups related articles into series
 *
 * @property int $id
 * @property string $slug
 * @property array $title
 * @property array|null $description
 * @property string|null $thumbnail
 * @property int $total_articles
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Series extends Model
{
    use HasFactory, HasTranslations;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'slug',
        'title',
        'description',
        'thumbnail',
        'total_articles',
    ];

    /**
     * Translatable attributes
     * Handled by Spatie\Translatable\HasTranslations trait
     * Do NOT add these to $casts as it conflicts with HasTranslations
     *
     * @var array<string>
     */
    public array $translatable = ['title', 'description'];

    protected function casts(): array
    {
        return [
            'total_articles' => 'integer',
        ];
    }

    /**
     * Get articles in this series
     */
    public function articles(): HasMany
    {
        return $this->hasMany(Article::class)->orderBy('published_at');
    }

    /**
     * Update total articles count
     */
    public function updateArticlesCount(): void
    {
        $this->update(['total_articles' => $this->articles()->count()]);
    }

    /**
     * Get all translations for title attribute
     * Returns the raw JSON decoded to array
     */
    public function getTitleTranslations(): array
    {
        $raw = $this->getAttributes()['title'] ?? '{}';
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
     * Get title in specified language
     */
    public function getTitleInLanguage(string $language): ?string
    {
        $translations = $this->getTitleTranslations();
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
