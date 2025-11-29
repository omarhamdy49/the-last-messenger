<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HadithRelatedVerse extends Model
{
    protected $table = 'hadith_related_verses';

    protected $fillable = [
        'hadith_id', 'surah', 'surah_name_ar', 'surah_name_en',
        'ayah', 'text_ar', 'text_en', 'relevance',
    ];

    protected $casts = [
        'hadith_id' => 'integer',
        'surah' => 'integer',
        'ayah' => 'integer',
    ];

    public function hadith(): BelongsTo
    {
        return $this->belongsTo(Hadith::class);
    }
}
