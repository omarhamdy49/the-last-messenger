<?php

declare(strict_types=1);

namespace App\Domain\Quran\Models;

use App\Domain\Shared\Traits\HasLocalizedContent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

/**
 * RevelationContext Model (Asbab al-Nuzul — revelation context of Quranic verses)
 *
 * @property int $id
 * @property int $surah_id
 * @property int $verse_from
 * @property int|null $verse_to
 * @property string $period 'makki' | 'madani'
 * @property string|null $event_key
 * @property int $display_order
 * @property bool $is_active
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class RevelationContext extends Model
{
    use HasLocalizedContent;

    public const PERIOD_MAKKI = 'makki';

    public const PERIOD_MADANI = 'madani';

    /**
     * The table associated with the model.
     */
    protected $table = 'quran_revelation_contexts';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'surah_id',
        'verse_from',
        'verse_to',
        'period',
        'event_key',
        'display_order',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'surah_id' => 'integer',
        'verse_from' => 'integer',
        'verse_to' => 'integer',
        'display_order' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Get the FQCN of the translation model.
     */
    public function getTranslationModelClass(): string
    {
        return RevelationContextTranslation::class;
    }

    /**
     * Get the list of translatable field names.
     *
     * @return array<string>
     */
    public function getTranslatableFields(): array
    {
        return ['title', 'narrative', 'source_reference'];
    }

    /**
     * Get the surah this revelation context belongs to.
     */
    public function surah(): BelongsTo
    {
        return $this->belongsTo(Surah::class, 'surah_id');
    }

    /**
     * Verse-range label, e.g. "5" or "11-20".
     */
    public function getVerseRangeAttribute(): string
    {
        if ($this->verse_to !== null && $this->verse_to !== $this->verse_from) {
            return $this->verse_from.'-'.$this->verse_to;
        }

        return (string) $this->verse_from;
    }

    /**
     * Arabic excerpt of the first verse in the range.
     *
     * WARNING: this issues a query per call — never use it inside list loops.
     * List flows must bulk-fetch excerpts via
     * RevelationContextRepositoryInterface::getArabicExcerptsForContexts().
     */
    public function getVerseExcerptAttribute(): ?string
    {
        return VerseTranslation::query()
            ->select(['quran_verse_translations.text'])
            ->join('quran_verses', 'quran_verses.id', '=', 'quran_verse_translations.verse_id')
            ->where('quran_verses.surah_id', $this->surah_id)
            ->where('quran_verses.verse_number', $this->verse_from)
            ->where('quran_verse_translations.lang', 'ar')
            ->value('text');
    }

    /**
     * Scope: only active contexts.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: filter by revelation period (makki/madani).
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByPeriod($query, string $period)
    {
        return $query->where('period', $period);
    }

    /**
     * Valid period values.
     *
     * @return array<string>
     */
    public static function periods(): array
    {
        return [self::PERIOD_MAKKI, self::PERIOD_MADANI];
    }

    /**
     * Translations keyed by locale (requires eager-loaded translations).
     *
     * @return Collection<string, RevelationContextTranslation>
     */
    public function translationsByLocale(): Collection
    {
        return $this->translations->keyBy('locale');
    }
}
