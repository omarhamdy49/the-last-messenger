<?php

declare(strict_types=1);

namespace App\Domain\Blog\Models;

use App\Domain\Shared\Traits\HasLocalizedContent;
use App\Models\Article;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Series extends Model
{
    use HasFactory, HasLocalizedContent;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'slug',
        'thumbnail',
        'total_articles',
    ];

    protected function casts(): array
    {
        return [
            'total_articles' => 'integer',
        ];
    }

    /**
     * Get the FQCN of the translation model.
     */
    public function getTranslationModelClass(): string
    {
        return SeriesTranslation::class;
    }

    /**
     * Get the list of translatable field names.
     */
    public function getTranslatableFields(): array
    {
        return ['title', 'description'];
    }

    /**
     * Get articles in this series (many-to-many with position ordering)
     */
    public function articles(): BelongsToMany
    {
        return $this->belongsToMany(Article::class, 'article_series')
            ->withPivot('position')
            ->withTimestamps()
            ->orderBy('article_series.position');
    }

    /**
     * Update total articles count from pivot
     */
    public function updateArticlesCount(): void
    {
        $this->updateQuietly(['total_articles' => $this->articles()->count()]);
    }
}
