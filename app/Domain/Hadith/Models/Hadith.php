<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Hadith extends Model
{
    use HasFactory;

    protected $table = 'hadith_hadiths';

    protected $fillable = [
        'chapter_id',
        'primary_narrator_id',
        'hadith_number',
        'text_hash',
    ];

    protected $casts = [
        'chapter_id' => 'integer',
        'primary_narrator_id' => 'integer',
        'hadith_number' => 'string',
    ];

    protected $appends = [
        'book_id',
        'grading_status',
        'grading_agreement',
    ];

    public const GRADING_SAHIH = 'sahih';

    public const GRADING_HASAN = 'hasan';

    public const GRADING_DAIF = 'daif';

    public const GRADING_MAWDU = 'mawdu';

    public const GRADING_UNKNOWN = 'unknown';

    public static function getGradingStatuses(): array
    {
        return [
            self::GRADING_SAHIH,
            self::GRADING_HASAN,
            self::GRADING_DAIF,
            self::GRADING_MAWDU,
            self::GRADING_UNKNOWN,
        ];
    }

    public function chapter(): BelongsTo
    {
        return $this->belongsTo(HadithChapter::class, 'chapter_id');
    }

    public function primaryNarrator(): BelongsTo
    {
        return $this->belongsTo(HadithNarrator::class, 'primary_narrator_id');
    }

    public function translations(): HasMany
    {
        return $this->hasMany(HadithTranslation::class, 'hadith_id');
    }

    public function isnads(): HasMany
    {
        return $this->hasMany(HadithIsnad::class, 'hadith_id');
    }

    public function narrators(): BelongsToMany
    {
        return $this->belongsToMany(
            HadithNarrator::class,
            'hadith_isnad',
            'hadith_id',
            'narrator_id',
        )->withPivot(['position_in_chain', 'transmission_method']);
    }

    public function gradings(): HasMany
    {
        return $this->hasMany(HadithGrading::class, 'hadith_id');
    }

    public function commentaries(): HasMany
    {
        return $this->hasMany(HadithCommentary::class, 'hadith_id');
    }

    public function themes(): BelongsToMany
    {
        return $this->belongsToMany(
            HadithTheme::class,
            'hadith_hadith_themes',
            'hadith_id',
            'hadith_theme_id',
        );
    }

    public function sources(): HasMany
    {
        return $this->hasMany(HadithSource::class, 'hadith_id');
    }

    public function keywords(): HasMany
    {
        return $this->hasMany(HadithKeyword::class, 'hadith_id');
    }

    public function relatedVerses(): HasMany
    {
        return $this->hasMany(HadithRelatedVerse::class, 'hadith_id');
    }

    public function applications(): HasMany
    {
        return $this->hasMany(HadithApplication::class, 'hadith_id');
    }

    public function fiqhRulings(): HasMany
    {
        return $this->hasMany(HadithFiqhRuling::class, 'hadith_id');
    }

    public function getTranslation(string $locale): ?HadithTranslation
    {
        return $this->translations()->where('locale', $locale)->first();
    }

    public function getBookAttribute(): ?HadithBook
    {
        return $this->chapter?->book;
    }

    public function getBookIdAttribute(): ?int
    {
        return $this->chapter?->book_id;
    }

    public function getCompanionAttribute(): ?HadithNarrator
    {
        return $this->primaryNarrator;
    }

    public function getCompanionIdAttribute(): ?int
    {
        return $this->primary_narrator_id;
    }

    public function getGradingStatusAttribute(): string
    {
        $grading = $this->relationLoaded('gradings')
            ? $this->gradings->first()
            : $this->gradings()->first();

        return $grading?->grade_key ?? self::GRADING_UNKNOWN;
    }

    public function getGradingAgreementAttribute(): bool
    {
        $keys = $this->relationLoaded('gradings')
            ? $this->gradings->pluck('grade_key')
            : $this->gradings()->pluck('grade_key');

        return $keys->count() > 1 && $keys->unique()->count() === 1;
    }

    public function scopeByBook(Builder $query, int $bookId): Builder
    {
        return $query->whereHas('chapter', fn (Builder $chapter) => $chapter->where('book_id', $bookId));
    }

    public function scopeByNumber(Builder $query, string $hadithNumber): Builder
    {
        return $query->where('hadith_number', $hadithNumber);
    }

    public function scopeByGrading(Builder $query, string $gradeKey): Builder
    {
        return $query->whereHas('gradings', fn (Builder $grading) => $grading->where('grade_key', $gradeKey));
    }

    public function scopeAuthentic(Builder $query): Builder
    {
        return $query->byGrading(self::GRADING_SAHIH);
    }

    public function scopeWithAgreement(Builder $query): Builder
    {
        return $query->whereIn('id', HadithGrading::query()
            ->select('hadith_id')
            ->groupBy('hadith_id')
            ->havingRaw('COUNT(*) > 1')
            ->havingRaw('COUNT(DISTINCT grade_key) = 1'));
    }

    public function scopeWithTranslations(Builder $query, ?string $locale = null): Builder
    {
        return $query->with(['translations' => function ($translations) use ($locale): void {
            if ($locale !== null) {
                $translations->where('locale', $locale);
            }
        }]);
    }

    public function scopeWithFullRelations(Builder $query): Builder
    {
        return $query->with([
            'chapter.book.collection',
            'primaryNarrator',
            'translations',
            'isnads.narrator',
            'gradings.scholar',
            'commentaries.scholar',
            'themes',
            'sources',
            'keywords',
            'relatedVerses',
            'applications',
            'fiqhRulings.scholar',
        ]);
    }

    public function isAuthentic(): bool
    {
        return $this->grading_status === self::GRADING_SAHIH;
    }

    public function hasScholarlyAgreement(): bool
    {
        return $this->grading_agreement;
    }

    public function getFullReference(): string
    {
        $collection = $this->chapter->book->collection->getNameInLocale('ar');
        $book = $this->chapter->book->getTitleInLocale('ar');

        return "{$collection} > {$book} > حديث رقم {$this->hadith_number}";
    }
}
