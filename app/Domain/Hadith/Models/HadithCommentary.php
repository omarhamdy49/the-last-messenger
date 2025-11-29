<?php

namespace App\Domain\Hadith\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

/**
 * Class HadithCommentary
 *
 * Represents a scholarly commentary (شرح) on a Hadith.
 * Commentaries provide explanations, interpretations, and context for hadiths.
 *
 * @property int $id
 * @property int $hadith_id
 * @property string $language_code Language code (ar, en, ur, fr, tr)
 * @property string|null $author Commentary author name
 * @property string|null $source Source book (e.g., Fath al-Bari, Sharh al-Nawawi)
 * @property string|null $commentary_text Full commentary text
 * @property \Illuminate\Support\Carbon $created_at
 *
 * @property-read \App\Domain\Hadith\Models\Hadith $hadith
 *
 * @method static Builder forHadith(int $hadithId)
 * @method static Builder byLanguage(string $languageCode)
 * @method static Builder byAuthor(string $author)
 * @method static Builder bySource(string $source)
 * @method static Builder search(string $term)
 */
class HadithCommentary extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'hadith_commentaries';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The name of the "created at" column.
     *
     * @var string|null
     */
    const CREATED_AT = 'created_at';

    /**
     * The name of the "updated at" column.
     *
     * @var string|null
     */
    const UPDATED_AT = null;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'hadith_id',
        'language_code',
        'author',
        'source',
        'commentary_text',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'hadith_id' => 'integer',
        'created_at' => 'datetime',
    ];

    /**
     * Supported language codes for commentaries.
     *
     * @var array<int, string>
     */
    const SUPPORTED_LANGUAGES = ['ar', 'en', 'ur', 'fr', 'tr'];

    /**
     * Get the hadith that this commentary belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function hadith(): BelongsTo
    {
        return $this->belongsTo(Hadith::class, 'hadith_id');
    }

    /**
     * Scope to filter commentaries by hadith.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $hadithId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForHadith(Builder $query, int $hadithId): Builder
    {
        return $query->where('hadith_id', $hadithId);
    }

    /**
     * Scope to filter commentaries by language.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $languageCode
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByLanguage(Builder $query, string $languageCode): Builder
    {
        return $query->where('language_code', $languageCode);
    }

    /**
     * Scope to filter commentaries by author.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $author
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByAuthor(Builder $query, string $author): Builder
    {
        return $query->where('author', 'like', "%{$author}%");
    }

    /**
     * Scope to filter commentaries by source book.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $source
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeBySource(Builder $query, string $source): Builder
    {
        return $query->where('source', 'like', "%{$source}%");
    }

    /**
     * Scope to search commentaries using FULLTEXT search.
     * Searches commentary_text column using MySQL FULLTEXT index.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $term
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->whereRaw(
            'MATCH(commentary_text) AGAINST(? IN NATURAL LANGUAGE MODE)',
            [$term]
        );
    }

    /**
     * Scope to get Arabic commentaries.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeArabic(Builder $query): Builder
    {
        return $query->byLanguage('ar');
    }

    /**
     * Scope to get English commentaries.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeEnglish(Builder $query): Builder
    {
        return $query->byLanguage('en');
    }

    /**
     * Scope to get commentaries with hadith relationship.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithHadith(Builder $query): Builder
    {
        return $query->with('hadith');
    }

    /**
     * Scope to order by creation date (newest first).
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $direction
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRecent(Builder $query, string $direction = 'desc'): Builder
    {
        return $query->orderBy('created_at', $direction);
    }

    /**
     * Get the language display name.
     *
     * @return string
     */
    public function getLanguageName(): string
    {
        $languages = [
            'ar' => 'العربية',
            'en' => 'English',
            'ur' => 'اردو',
            'fr' => 'Français',
            'tr' => 'Türkçe',
        ];

        return $languages[$this->language_code] ?? $this->language_code;
    }

    /**
     * Get the author display name with fallback.
     *
     * @return string
     */
    public function getAuthorName(): string
    {
        return $this->author ?? 'Unknown Author';
    }

    /**
     * Get the source display name with fallback.
     *
     * @return string
     */
    public function getSourceName(): string
    {
        return $this->source ?? 'Unknown Source';
    }

    /**
     * Get a truncated version of the commentary text.
     *
     * @param int $length
     * @param string $suffix
     * @return string
     */
    public function getTruncatedText(int $length = 200, string $suffix = '...'): string
    {
        if (!$this->commentary_text) {
            return '';
        }

        if (mb_strlen($this->commentary_text) <= $length) {
            return $this->commentary_text;
        }

        return mb_substr($this->commentary_text, 0, $length) . $suffix;
    }

    /**
     * Check if the language code is supported.
     *
     * @param string $languageCode
     * @return bool
     */
    public static function isSupportedLanguage(string $languageCode): bool
    {
        return in_array($languageCode, self::SUPPORTED_LANGUAGES, true);
    }

    /**
     * Get all commentaries for a hadith grouped by language.
     *
     * @param int $hadithId
     * @return \Illuminate\Support\Collection
     */
    public static function getByLanguageForHadith(int $hadithId)
    {
        return static::forHadith($hadithId)
            ->get()
            ->groupBy('language_code');
    }
}
