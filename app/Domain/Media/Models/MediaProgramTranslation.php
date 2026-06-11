<?php

declare(strict_types=1);

namespace App\Domain\Media\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * MediaProgramTranslation Model
 *
 * Per-locale translations for MediaProgram.
 *
 * @property int $id
 * @property int $media_program_id
 * @property string $locale
 * @property string|null $title
 * @property string|null $description
 * @property string|null $host
 * @property string|null $seo_title
 * @property string|null $seo_description
 * @property string|null $seo_keywords
 * @property bool $is_ready
 */
class MediaProgramTranslation extends Model
{
    protected $table = 'media_program_translations';

    protected $fillable = [
        'media_program_id',
        'locale',
        'title',
        'description',
        'host',
        'seo_title',
        'seo_description',
        'seo_keywords',
        'is_ready',
    ];

    protected $casts = [
        'media_program_id' => 'integer',
        'is_ready' => 'boolean',
    ];

    public function program(): BelongsTo
    {
        return $this->belongsTo(MediaProgram::class, 'media_program_id');
    }

    public function scopeByLocale($query, string $locale)
    {
        return $query->where('locale', $locale);
    }

    public function isRtl(): bool
    {
        return in_array($this->locale, ['ar', 'ur']);
    }
}
