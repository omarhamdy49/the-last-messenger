<?php

declare(strict_types=1);

namespace App\Domain\Blog\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
    use HasFactory;

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
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'title' => 'array',
            'description' => 'array',
            'total_articles' => 'integer',
        ];
    }

    /**
     * Get articles in this series
     *
     * @return HasMany
     */
    public function articles(): HasMany
    {
        return $this->hasMany(Article::class)->orderBy('published_at');
    }

    /**
     * Update total articles count
     *
     * @return void
     */
    public function updateArticlesCount(): void
    {
        $this->update(['total_articles' => $this->articles()->count()]);
    }

    /**
     * Get title in specified language
     *
     * @param string $language
     * @return string|null
     */
    public function getTitleInLanguage(string $language): ?string
    {
        return $this->title[$language] ?? $this->title['ar'] ?? null;
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

