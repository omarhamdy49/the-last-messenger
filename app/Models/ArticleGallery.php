<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ArticleGallery Model
 *
 * Stores gallery images for articles
 *
 * @property int $id
 * @property int $article_id
 * @property string $image
 * @property array|null $caption
 * @property int $order
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class ArticleGallery extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'articles_gallery';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'article_id',
        'media_id',
        'image',
        'caption',
        'order',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'caption' => 'array',
        ];
    }

    /**
     * Get the article this gallery item belongs to
     */
    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    /**
     * Get caption for specific locale
     */
    public function getCaption(string $locale): ?string
    {
        return $this->caption[$locale] ?? null;
    }
}
