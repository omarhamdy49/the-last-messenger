<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ArticleMetadata Model
 *
 * Stores searchable fields per locale for performance
 *
 * @property int $id
 * @property int $article_id
 * @property string $locale
 * @property string $searchable_title
 * @property string $searchable_excerpt
 */
class ArticleMetadata extends Model
{
    protected $table = 'article_metadata';

    protected $fillable = [
        'article_id',
        'locale',
        'searchable_title',
        'searchable_excerpt',
    ];

    /**
     * Get the article this metadata belongs to
     */
    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }
}
