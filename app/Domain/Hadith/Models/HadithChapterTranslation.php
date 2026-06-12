<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HadithChapterTranslation extends Model
{
    protected $fillable = ['hadith_chapter_id', 'locale', 'name', 'description', 'is_ready'];

    protected $casts = ['is_ready' => 'boolean'];

    public function hadithChapter(): BelongsTo
    {
        return $this->belongsTo(HadithChapter::class);
    }
}
