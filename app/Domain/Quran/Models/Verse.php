<?php

declare(strict_types=1);

namespace App\Domain\Quran\Models;

use App\Domain\Quran\Enums\SajdaType;
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
 * @property int $verse_key
 * @property string $text_uthmani
 * @property string $text_simple
 * @property string|null $text_imlaei
 * @property array $translations
 * @property string|null $transliteration
 * @property int $juz_number
 * @property int|null $hizb_number
 * @property int $page_number
 * @property int|null $rub_number
 * @property bool $is_sajda
 * @property SajdaType|null $sajda_type
 * @property array|null $audio_urls
 * @property int|null $words_count
 * @property int|null $letters_count
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Verse extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
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
        'text_uthmani',
        'text_simple',
        'text_imlaei',
        'translations',
        'transliteration',
        'juz_number',
        'hizb_number',
        'page_number',
        'rub_number',
        'is_sajda',
        'sajda_type',
        'audio_urls',
        'words_count',
        'letters_count',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'surah_id' => 'integer',
        'verse_number' => 'integer',
        'verse_key' => 'integer',
        'translations' => 'array',
        'juz_number' => 'integer',
        'hizb_number' => 'integer',
        'page_number' => 'integer',
        'rub_number' => 'integer',
        'is_sajda' => 'boolean',
        'sajda_type' => SajdaType::class,
        'audio_urls' => 'array',
        'words_count' => 'integer',
        'letters_count' => 'integer',
    ];

    /**
     * Get the surah this verse belongs to
     *
     * @return BelongsTo
     */
    public function surah(): BelongsTo
    {
        return $this->belongsTo(Surah::class, 'surah_id');
    }

    /**
     * Get tafseer for this verse
     *
     * @return HasMany
     */
    public function tafseer(): HasMany
    {
        return $this->hasMany(Tafseer::class, 'verse_id');
    }

    /**
     * Get translation in specific language
     *
     * @param string $language
     * @return string|null
     */
    public function getTranslation(string $language = 'en'): ?string
    {
        return $this->translations[$language] ?? null;
    }

    /**
     * Get verse reference (e.g., "2:255" for Ayat Al-Kursi)
     *
     * @return string
     */
    public function getReference(): string
    {
        return "{$this->surah->number}:{$this->verse_number}";
    }

    /**
     * Scope: Filter by Juz
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $juz
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByJuz($query, int $juz)
    {
        return $query->where('juz_number', $juz);
    }

    /**
     * Scope: Filter by page
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $page
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByPage($query, int $page)
    {
        return $query->where('page_number', $page);
    }

    /**
     * Scope: Only Sajda verses
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSajda($query)
    {
        return $query->where('is_sajda', true);
    }

    /**
     * Scope: Search in Arabic text
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $searchTerm
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSearchArabic($query, string $searchTerm)
    {
        return $query->where(function ($q) use ($searchTerm) {
            $q->where('text_simple', 'like', "%{$searchTerm}%")
              ->orWhere('text_uthmani', 'like', "%{$searchTerm}%");
        });
    }

    /**
     * Scope: Search in translations
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $searchTerm
     * @param string $language
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSearchTranslation($query, string $searchTerm, string $language = 'en')
    {
        return $query->whereRaw(
            "JSON_EXTRACT(translations, '$.{$language}') LIKE ?",
            ["%{$searchTerm}%"]
        );
    }
}

