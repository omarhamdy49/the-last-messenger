<?php

declare(strict_types=1);

namespace App\Domain\Quran\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Per-locale chapter name ("The Opener", "الفاتحة", …).
 *
 * @property int $id
 * @property int $chapter_id
 * @property string $locale
 * @property string $name
 * @property bool $is_ready
 */
class ChapterTranslation extends Model
{
    protected $table = 'quran_chapter_translations';

    protected $fillable = ['chapter_id', 'locale', 'name', 'is_ready'];

    protected $casts = [
        'chapter_id' => 'integer',
        'is_ready' => 'boolean',
    ];

    public function chapter(): BelongsTo
    {
        return $this->belongsTo(Chapter::class, 'chapter_id');
    }
}
