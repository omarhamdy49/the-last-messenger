<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HadithRelatedVerse extends Model
{
    protected $table = 'hadith_related_verses';

    protected $fillable = [
        'hadith_id',
        'quran_verse_id',
        'relevance',
        'review_status',
        'source_reference',
        'source_url',
    ];

    protected $casts = [
        'hadith_id' => 'integer',
        'quran_verse_id' => 'integer',
        'relevance' => 'array',
    ];

    public function hadith(): BelongsTo
    {
        return $this->belongsTo(Hadith::class);
    }
}
