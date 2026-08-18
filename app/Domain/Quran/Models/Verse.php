<?php

declare(strict_types=1);

namespace App\Domain\Quran\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Verse Model (Quran Ayah)
 *
 * @property int $id
 * @property int $surah_id
 * @property int $verse_number
 * @property string $verse_key
 * @property int $juz_number
 * @property int $hizb_number
 * @property int $rub_el_hizb_number
 * @property int $ruku_number
 * @property int|null $manzil_number
 * @property int|null $sajdah_number
 * @property int $page_number
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Verse extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'quran_verses';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'surah_id',
        'verse_number',
        'verse_key',
        'juz_number',
        'hizb_number',
        'rub_el_hizb_number',
        'ruku_number',
        'manzil_number',
        'sajdah_number',
        'page_number',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'surah_id' => 'integer',
        'verse_number' => 'integer',
        'juz_number' => 'integer',
        'hizb_number' => 'integer',
        'rub_el_hizb_number' => 'integer',
        'ruku_number' => 'integer',
        'manzil_number' => 'integer',
        'sajdah_number' => 'integer',
        'page_number' => 'integer',
    ];

    /**
     * Get the surah this verse belongs to
     */
    public function surah(): BelongsTo
    {
        return $this->belongsTo(Surah::class, 'surah_id');
    }

    /**
     * Get translations for this verse
     */
    public function translations(): HasMany
    {
        return $this->hasMany(VerseTranslation::class, 'verse_id');
    }

    /**
     * Get tafsirs for this verse
     */
    public function tafsirs(): HasMany
    {
        return $this->hasMany(VerseTafsir::class, 'verse_id');
    }

    /**
     * Eager-load constraint for reader paths: load only the translation
     * editions a reader actually renders for $locale, not every locale/edition
     * in quran_verse_translations (112k rows). Usable with ->load() and ->with().
     *
     * `ar` (source Arabic text -> VerseDTO::textArabic, the fallback), `en`
     * (the inlined default translation) and `en-tl` (transliteration) are read
     * by the reader regardless of $locale (see Site\QuranController), so they
     * stay in the set to preserve output and the Arabic fallback.
     *
     * ponytail: reader lang set is fixed; widen it if the UI inlines another
     * edition.
     *
     * @return array<string, \Closure>
     */
    public static function readerTranslationsEagerLoad(string $locale = 'ar'): array
    {
        $langs = array_values(array_unique(['ar', 'en', 'en-tl', $locale]));

        return ['translations' => fn ($q) => $q
            ->select(['id', 'verse_id', 'resource_id', 'lang', 'text'])
            ->whereIn('lang', $langs)];
    }

    /**
     * Get translation for specific language
     */
    public function getTranslation(string $lang = 'ar'): ?VerseTranslation
    {
        $query = $this->translations()->where('lang', $lang);

        return (clone $query)
            ->whereHas('resource', fn ($resource) => $resource->where('is_default', true))
            ->first()
            ?? $query->first();
    }

    /**
     * Get translation text for specific language
     */
    public function getTranslationText(string $lang = 'ar'): ?string
    {
        $translation = $this->getTranslation($lang);

        return $translation?->text;
    }

    /**
     * Get tafsir for specific language
     */
    public function getTafsir(string $lang = 'ar'): ?VerseTafsir
    {
        return $this->tafsirs()->where('lang', $lang)->where('active', true)->first();
    }

    /**
     * Check if this verse has sajdah (prostration)
     */
    public function hasSajdah(): bool
    {
        return $this->sajdah_number !== null;
    }

    /**
     * Get verse reference (e.g., "2:255")
     */
    public function getVerseKey(): string
    {
        return $this->verse_key;
    }

    /**
     * Scope: Filter by surah
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeBySurah($query, int $surahId)
    {
        return $query->where('surah_id', $surahId);
    }

    /**
     * Scope: Filter by juz
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeByJuz($query, int $juz)
    {
        return $query->where('juz_number', $juz);
    }

    /**
     * Scope: Filter by page
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeByPage($query, int $page)
    {
        return $query->where('page_number', $page);
    }

    /**
     * Scope: Filter by hizb
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeByHizb($query, int $hizb)
    {
        return $query->where('hizb_number', $hizb);
    }

    /**
     * Scope: Filter sajdah verses only
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeSajdah($query)
    {
        return $query->whereNotNull('sajdah_number');
    }

    /**
     * Scope: Filter by ruku
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeByRuku($query, int $ruku)
    {
        return $query->where('ruku_number', $ruku);
    }

    /**
     * Scope: Filter by manzil
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeByManzil($query, int $manzil)
    {
        return $query->where('manzil_number', $manzil);
    }
}
