<?php

namespace App\Domain\Hadith\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class HadithTranslation
 *
 * Represents a translation of a Hadith in a specific language.
 * Contains the matn (text), translator information, and notes.
 * The matn field is fulltext searchable.
 *
 * @property int $id
 * @property int $hadith_id
 * @property string $language_code Language code (ar, en, tr, fr, ur, etc.)
 * @property string $matn The hadith text/translation
 * @property string|null $translator_name Name of the translator
 * @property string|null $notes Additional notes or commentary
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read \App\Domain\Hadith\Models\Hadith $hadith
 */
class HadithTranslation extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'hadith_translations';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'hadith_id',
        'language_code',
        'text',
        'translator_name',
        'source_url',
        'urn',
        'verified',
        'is_primary',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'hadith_id' => 'integer',
        'urn' => 'integer',
        'verified' => 'boolean',
        'is_primary' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Supported language codes.
     */
    public const LANG_ARABIC = 'ar';

    public const LANG_ENGLISH = 'en';

    public const LANG_TURKISH = 'tr';

    public const LANG_FRENCH = 'fr';

    public const LANG_URDU = 'ur';

    /**
     * Get all supported language codes.
     *
     * @return array<string>
     */
    public static function getSupportedLanguages(): array
    {
        return [
            self::LANG_ARABIC,
            self::LANG_ENGLISH,
            self::LANG_TURKISH,
            self::LANG_FRENCH,
            self::LANG_URDU,
        ];
    }

    /**
     * Get language names mapped to codes.
     *
     * @return array<string, string>
     */
    public static function getLanguageNames(): array
    {
        return [
            self::LANG_ARABIC => 'العربية',
            self::LANG_ENGLISH => 'English',
            self::LANG_TURKISH => 'Türkçe',
            self::LANG_FRENCH => 'Français',
            self::LANG_URDU => 'اردو',
        ];
    }

    /**
     * Get the hadith that owns this translation.
     */
    public function hadith(): BelongsTo
    {
        return $this->belongsTo(Hadith::class, 'hadith_id');
    }

    /**
     * Scope to filter by language.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByLanguage($query, string $languageCode)
    {
        return $query->where('language_code', $languageCode);
    }

    /**
     * Scope to filter Arabic translations.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeArabic($query)
    {
        return $query->where('language_code', self::LANG_ARABIC);
    }

    /**
     * Scope to filter English translations.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeEnglish($query)
    {
        return $query->where('language_code', self::LANG_ENGLISH);
    }

    /**
     * Scope to search in text using fulltext search.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSearchMatn($query, string $searchTerm)
    {
        return $query->whereRaw(
            'MATCH(text) AGAINST(? IN NATURAL LANGUAGE MODE)',
            [$searchTerm]
        );
    }

    /**
     * Scope to search in text using boolean mode for advanced queries.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSearchMatnBoolean($query, string $searchTerm)
    {
        return $query->whereRaw(
            'MATCH(text) AGAINST(? IN BOOLEAN MODE)',
            [$searchTerm]
        );
    }

    /**
     * Scope to search in text with basic LIKE (fallback for non-fulltext).
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSearchMatnLike($query, string $searchTerm)
    {
        return $query->where('text', 'like', "%{$searchTerm}%");
    }

    /**
     * Scope to filter by translator.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByTranslator($query, string $translatorName)
    {
        return $query->where('translator_name', 'like', "%{$translatorName}%");
    }

    /**
     * Scope to get translations with their hadith.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithHadith($query)
    {
        return $query->with('hadith');
    }

    /**
     * Scope to get translations with full hadith relations.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithFullHadith($query)
    {
        return $query->with([
            'hadith.book.collection',
            'hadith.gradings',
            'hadith.themes',
        ]);
    }

    /**
     * Get the language name for this translation.
     */
    public function getLanguageName(): string
    {
        $names = self::getLanguageNames();

        return $names[$this->language_code] ?? $this->language_code;
    }

    /**
     * Check if this translation is in Arabic.
     */
    public function isArabic(): bool
    {
        return $this->language_code === self::LANG_ARABIC;
    }

    /**
     * Check if this translation is in English.
     */
    public function isEnglish(): bool
    {
        return $this->language_code === self::LANG_ENGLISH;
    }

    /**
     * Check if this translation is RTL (Right-to-Left) language.
     */
    public function isRtl(): bool
    {
        return in_array($this->language_code, [self::LANG_ARABIC, self::LANG_URDU]);
    }

    /**
     * Get excerpt of text (first N characters).
     */
    public function getExcerpt(int $length = 150): string
    {
        return mb_substr($this->text, 0, $length).(mb_strlen($this->text) > $length ? '...' : '');
    }

    /**
     * Get word count of text.
     */
    public function getWordCount(): int
    {
        return str_word_count(strip_tags($this->text));
    }
}
