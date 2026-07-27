<?php

namespace App\Domain\Hadith\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class Hadith
 *
 * Represents a single Hadith (prophetic narration).
 * This is the core model that contains the hadith number, grading, and relationships
 * to translations, isnad (chain of narrators), gradings, and themes.
 *
 * @property int $id
 * @property int $book_id
 * @property int|null $chapter_id Chapter (باب) within the book, when the source provides one
 * @property string $hadith_number Hadith number within the book
 * @property string $grading_status Overall grading status (sahih, hasan, daif, etc.)
 * @property bool $grading_agreement Whether scholars agree on the grading
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read \App\Domain\Hadith\Models\HadithBook $book
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Domain\Hadith\Models\HadithTranslation[] $translations
 * @property-read int|null $translations_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Domain\Hadith\Models\HadithIsnad[] $isnads
 * @property-read int|null $isnads_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Domain\Hadith\Models\HadithGrading[] $gradings
 * @property-read int|null $gradings_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Domain\Hadith\Models\HadithTheme[] $themes
 * @property-read int|null $themes_count
 */
class Hadith extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'hadith_hadiths';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'book_id',
        'chapter_id',
        'companion_id',
        'local_source_id',
        'source_file',
        'source_url',
        'sunnah_urn_ar',
        'sunnah_urn_en',
        'hadith_number',
        'grading_status',
        'grading_agreement',
        'significance_ar',
        'significance_en',
        'historical_context_ar',
        'historical_context_en',
        'verification_status',
        'source_verified_at',
        'source_verification_method',
        'uncertainty_notes',
        'text_hash',
        'merged_into_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'book_id' => 'integer',
        'chapter_id' => 'integer',
        'companion_id' => 'integer',
        // hadith_number is a string column; sqlite (test driver) returns
        // numeric-looking values as ints, which breaks the typed DTOs.
        'hadith_number' => 'string',
        'sunnah_urn_ar' => 'integer',
        'sunnah_urn_en' => 'integer',
        'grading_agreement' => 'boolean',
        'source_verified_at' => 'datetime',
        'merged_into_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Grading status constants.
     */
    public const GRADING_SAHIH = 'sahih';           // Authentic

    public const GRADING_HASAN = 'hasan';           // Good

    public const GRADING_DAIF = 'daif';             // Weak

    public const GRADING_MAWDU = 'mawdu';           // Fabricated

    public const GRADING_MUTAWATIR = 'mutawatir';   // Mass transmitted

    public const GRADING_MURSAL = 'mursal';         // Missing companion in chain

    /**
     * Get all available grading statuses.
     *
     * @return array<string>
     */
    public static function getGradingStatuses(): array
    {
        return [
            self::GRADING_SAHIH,
            self::GRADING_HASAN,
            self::GRADING_DAIF,
            self::GRADING_MAWDU,
            self::GRADING_MUTAWATIR,
            self::GRADING_MURSAL,
        ];
    }

    /**
     * Get the book that owns this hadith.
     */
    public function book(): BelongsTo
    {
        return $this->belongsTo(HadithBook::class, 'book_id');
    }

    /**
     * Get the chapter (باب) this hadith belongs to, when the source provides one.
     */
    public function chapter(): BelongsTo
    {
        return $this->belongsTo(HadithChapter::class, 'chapter_id');
    }

    /**
     * Get the companion who narrated this hadith.
     */
    public function companion(): BelongsTo
    {
        return $this->belongsTo(Companion::class, 'companion_id');
    }

    /**
     * Get the hadith this one was merged into (for deduplication).
     */
    public function mergedInto(): BelongsTo
    {
        return $this->belongsTo(Hadith::class, 'merged_into_id');
    }

    /**
     * Get hadiths that were merged into this one.
     */
    public function mergedDuplicates(): HasMany
    {
        return $this->hasMany(Hadith::class, 'merged_into_id');
    }

    /**
     * Get all translations of this hadith.
     */
    public function translations(): HasMany
    {
        return $this->hasMany(HadithTranslation::class, 'hadith_id');
    }

    /**
     * Get all isnads (chain of narrators) for this hadith.
     */
    public function isnads(): HasMany
    {
        return $this->hasMany(HadithIsnad::class, 'hadith_id');
    }

    /**
     * Get all gradings for this hadith.
     */
    public function gradings(): HasMany
    {
        return $this->hasMany(HadithGrading::class, 'hadith_id');
    }

    /**
     * Get all scholarly commentaries (shuruh) for this hadith.
     */
    public function commentaries(): HasMany
    {
        return $this->hasMany(HadithCommentary::class, 'hadith_id');
    }

    /**
     * Get all themes associated with this hadith.
     */
    public function themes(): BelongsToMany
    {
        return $this->belongsToMany(
            HadithTheme::class,
            'hadith_hadith_themes',
            'hadith_id',
            'hadith_theme_id'
        );
    }

    /**
     * Get translation in specific language.
     */
    public function getTranslation(string $languageCode): ?HadithTranslation
    {
        return $this->translations()
            ->where('language_code', $languageCode)
            ->first();
    }

    /**
     * Scope to filter by book.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByBook($query, int $bookId)
    {
        return $query->where('book_id', $bookId);
    }

    /**
     * Scope to filter by hadith number.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByNumber($query, string $hadithNumber)
    {
        return $query->where('hadith_number', $hadithNumber);
    }

    /**
     * Scope to filter by grading status.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByGrading($query, string $gradingStatus)
    {
        return $query->where('grading_status', $gradingStatus);
    }

    /**
     * Scope to get only authentic hadiths (sahih).
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeAuthentic($query)
    {
        return $query->where('grading_status', self::GRADING_SAHIH);
    }

    /**
     * Scope to get hadiths with scholarly agreement.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithAgreement($query)
    {
        return $query->where('grading_agreement', true);
    }

    /**
     * Scope to get hadiths with their translations.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithTranslations($query, ?string $languageCode = null)
    {
        return $query->with(['translations' => function ($q) use ($languageCode) {
            if ($languageCode) {
                $q->where('language_code', $languageCode);
            }
        }]);
    }

    /**
     * Scope to get hadiths with full relations.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithFullRelations($query)
    {
        return $query->with([
            'book.collection',
            'translations',
            'isnads.narrator',
            'gradings',
            'themes',
        ]);
    }

    /**
     * Check if this hadith is authentic (sahih).
     */
    public function isAuthentic(): bool
    {
        return $this->grading_status === self::GRADING_SAHIH;
    }

    /**
     * Check if scholars agree on the grading.
     */
    public function hasScholarlyAgreement(): bool
    {
        return $this->grading_agreement === true;
    }

    /**
     * Get the full hadith reference (Collection > Book > Number).
     */
    public function getFullReference(): string
    {
        $collection = $this->book->collection->getNameInLocale('ar');
        $book = $this->book->getTitleInLocale('ar');

        return "{$collection} > {$book} > حديث رقم {$this->hadith_number}";
    }
}
