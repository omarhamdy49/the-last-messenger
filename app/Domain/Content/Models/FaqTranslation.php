<?php

declare(strict_types=1);

namespace App\Domain\Content\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * FaqTranslation Model
 *
 * @property int $id
 * @property int $faq_id
 * @property string $locale
 * @property string|null $question
 * @property string|null $answer
 * @property bool $is_ready
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read Faq $faq
 */
class FaqTranslation extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'faq_translations';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'faq_id',
        'locale',
        'question',
        'answer',
        'is_ready',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'faq_id' => 'integer',
        'is_ready' => 'boolean',
    ];

    /**
     * Get the parent FAQ.
     */
    public function faq(): BelongsTo
    {
        return $this->belongsTo(Faq::class, 'faq_id');
    }

    /**
     * Scope: Filter by locale.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByLocale($query, string $locale)
    {
        return $query->where('locale', $locale);
    }
}
