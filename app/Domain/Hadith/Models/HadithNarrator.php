<?php

namespace App\Domain\Hadith\Models;

use App\Domain\Sahaba\Models\Sahabi;
use App\Domain\Sahaba\Services\SahabiIdentityMatcher;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Arr;

/**
 * Class HadithNarrator
 *
 * Represents a narrator (راوي) in the chain of Hadith narration.
 * Narrators are scholars who transmitted Hadiths through the chain (isnad).
 *
 * @property int $id
 * @property string $name_ar Narrator name in Arabic
 * @property string|null $full_name_ar Full name in Arabic
 * @property string|null $kunya Kunya (e.g., Abu...)
 * @property string|null $title Title/honorific
 * @property int|null $death_year_hijri Death year in Hijri calendar
 * @property string|null $notes Biographical notes
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Domain\Hadith\Models\HadithIsnad[] $isnads
 * @property-read int|null $isnads_count
 *
 * @method static Builder search(string $term)
 * @method static Builder byDeathYear(int $year)
 * @method static Builder withKunya()
 */
class HadithNarrator extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'hadith_narrators';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'sahaba_id',
        'name_ar',
        'name_en',
        'full_name_ar',
        'full_name_en',
        'kunya',
        'birth_year_hijri',
        'death_year_hijri',
        'reliability_grade',
        'biography',
        'school',
        'region',
        'source_system',
        'source_narrator_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'sahaba_id' => 'integer',
        'death_year_hijri' => 'integer',
    ];

    public function sahabi(): BelongsTo
    {
        return $this->belongsTo(Sahabi::class, 'sahaba_id');
    }

    /**
     * Create or update a narrator while reusing a canonical Sahabi identity.
     *
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $uniqueKey
     */
    public static function resolveOrCreate(array $data, array $uniqueKey = []): self
    {
        $sahabi = app(SahabiIdentityMatcher::class)->match([
            $data['name_ar'] ?? null,
            $data['name_en'] ?? null,
            $data['full_name_ar'] ?? null,
            $data['full_name_en'] ?? null,
        ]);

        if ($sahabi !== null) {
            $roleData = Arr::except($data, [
                'name_ar',
                'name_en',
                'full_name_ar',
                'full_name_en',
                'kunya',
                'birth_year_hijri',
                'death_year_hijri',
                'biography',
            ]);

            return static::updateOrCreate(
                ['sahaba_id' => $sahabi->id],
                array_merge($roleData, ['sahaba_id' => $sahabi->id]),
            );
        }

        $uniqueKey = $uniqueKey ?: [
            'name_ar' => $data['name_ar'] ?? $data['name_en'] ?? null,
        ];

        return static::updateOrCreate($uniqueKey, $data);
    }

    /**
     * Update role metadata and write linked identity changes to Sahaba only.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateIdentityAndRole(array $data): bool
    {
        if ($this->sahabi === null) {
            return $this->update($data);
        }

        $this->sahabi->update(array_filter([
            'birth_date_hijri' => isset($data['birth_year_hijri']) ? $data['birth_year_hijri'].' AH' : null,
            'death_date_hijri' => isset($data['death_year_hijri']) ? $data['death_year_hijri'].' AH' : null,
        ], static fn (mixed $value): bool => $value !== null));

        $this->sahabi->syncTranslations([
            'name' => [
                'ar' => $data['name_ar'] ?? null,
                'en' => $data['name_en'] ?? null,
            ],
            'full_name' => [
                'ar' => $data['full_name_ar'] ?? null,
                'en' => $data['full_name_en'] ?? null,
            ],
            'kunya' => [
                'ar' => $data['kunya'] ?? null,
            ],
            'biography' => [
                app()->getLocale() => $data['biography'] ?? null,
            ],
        ]);

        return $this->update(Arr::only($data, [
            'reliability_grade',
            'school',
            'region',
            'source_system',
            'source_narrator_id',
        ]));
    }

    /**
     * Get all isnad (chain of narration) entries for this narrator.
     */
    public function isnads(): HasMany
    {
        return $this->hasMany(HadithIsnad::class, 'narrator_id');
    }

    /**
     * Get all hadiths narrated by this narrator through isnad.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function hadiths()
    {
        return Hadith::whereHas('isnads', function ($query) {
            $query->where('narrator_id', $this->id);
        })->get();
    }

    /**
     * Scope to search narrators by name, full name, or notes.
     * Uses MySQL FULLTEXT search on indexed columns.
     */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->where(function (Builder $query) use ($term): void {
            if ($query->getConnection()->getDriverName() === 'mysql') {
                $query->whereRaw(
                    'MATCH(name_ar, name_en, full_name_ar, full_name_en, kunya) AGAINST(? IN NATURAL LANGUAGE MODE)',
                    [$term]
                );
            } else {
                $query->where('name_ar', 'like', "%{$term}%")
                    ->orWhere('name_en', 'like', "%{$term}%");
            }

            $query->orWhereHas('sahabi.translations', function (Builder $translation) use ($term): void {
                $translation->where('name', 'like', "%{$term}%")
                    ->orWhere('full_name', 'like', "%{$term}%")
                    ->orWhere('kunya', 'like', "%{$term}%");
            });
        });
    }

    /**
     * Scope to filter narrators by death year.
     */
    public function scopeByDeathYear(Builder $query, int $year): Builder
    {
        return $query->where(function (Builder $query) use ($year): void {
            $query->where('death_year_hijri', $year)
                ->orWhereHas('sahabi', fn (Builder $sahabi) => $sahabi->where('death_date_hijri', 'like', "%{$year}%"));
        });
    }

    /**
     * Scope to filter narrators who have a kunya.
     */
    public function scopeWithKunya(Builder $query): Builder
    {
        return $query->where(function (Builder $query): void {
            $query->whereNotNull('kunya')
                ->orWhereHas('sahabi.translations', fn (Builder $translation) => $translation->whereNotNull('kunya'));
        });
    }

    /**
     * Scope to filter narrators by death year range.
     */
    public function scopeByDeathYearRange(Builder $query, int $startYear, int $endYear): Builder
    {
        return $query->whereBetween('death_year_hijri', [$startYear, $endYear]);
    }

    /**
     * Scope to get narrators with their isnad relationships.
     */
    public function scopeWithIsnads(Builder $query): Builder
    {
        return $query->with('isnads');
    }

    /**
     * Get the narrator's display name.
     * Returns kunya if available, otherwise name_ar.
     */
    public function getDisplayName(): string
    {
        return $this->kunya ?? $this->name_ar;
    }

    /**
     * Get the narrator's full display name with title.
     */
    public function getFullDisplayName(): string
    {
        $name = $this->full_name_ar ?? $this->name_ar;

        if ($this->title) {
            return $this->title.' '.$name;
        }

        return $name;
    }

    /**
     * Get the count of hadiths narrated by this narrator.
     */
    public function getHadithsCount(): int
    {
        return $this->isnads()->distinct('hadith_id')->count('hadith_id');
    }

    public function getNameArAttribute(?string $value): ?string
    {
        return $value ?? $this->sahabi?->getTranslation('name', 'ar');
    }

    public function getNameEnAttribute(?string $value): ?string
    {
        return $value ?? $this->sahabi?->getTranslation('name', 'en');
    }

    public function getFullNameArAttribute(?string $value): ?string
    {
        return $value ?? $this->sahabi?->getTranslation('full_name', 'ar');
    }

    public function getFullNameEnAttribute(?string $value): ?string
    {
        return $value ?? $this->sahabi?->getTranslation('full_name', 'en');
    }

    public function getKunyaAttribute(?string $value): ?string
    {
        return $value
            ?? $this->sahabi?->getTranslation('kunya', 'ar')
            ?? $this->sahabi?->getTranslation('kunya', 'en');
    }

    public function getBiographyAttribute(?string $value): ?string
    {
        return $value
            ?? $this->sahabi?->getTranslation('biography', app()->getLocale())
            ?? $this->sahabi?->getTranslation('biography', 'ar');
    }

    public function getBirthYearHijriAttribute(mixed $value): ?int
    {
        return $value !== null ? (int) $value : $this->extractHijriYear($this->sahabi?->birth_date_hijri);
    }

    public function getDeathYearHijriAttribute(mixed $value): ?int
    {
        return $value !== null ? (int) $value : $this->extractHijriYear($this->sahabi?->death_date_hijri);
    }

    private function extractHijriYear(?string $date): ?int
    {
        return $date !== null && preg_match('/-?\d+/', $date, $match)
            ? (int) $match[0]
            : null;
    }
}
