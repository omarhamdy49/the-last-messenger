<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Models;

use App\Domain\Sahaba\Models\Sahabi;
use App\Domain\Sahaba\Services\SahabiIdentityMatcher;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Arr;

class HadithNarrator extends Model
{
    use HasFactory;

    protected $table = 'hadith_narrators';

    protected $fillable = [
        'sahaba_id',
        'name',
        'full_name',
        'kunya',
        'biography',
        'birth_year_hijri',
        'death_year_hijri',
        'reliability_grade',
        'school',
        'region',
    ];

    protected $casts = [
        'sahaba_id' => 'integer',
        'name' => 'array',
        'full_name' => 'array',
        'kunya' => 'array',
        'biography' => 'array',
        'birth_year_hijri' => 'integer',
        'death_year_hijri' => 'integer',
    ];

    public function sahabi(): BelongsTo
    {
        return $this->belongsTo(Sahabi::class, 'sahaba_id');
    }

    public function isnads(): HasMany
    {
        return $this->hasMany(HadithIsnad::class, 'narrator_id');
    }

    public function hadiths(): BelongsToMany
    {
        return $this->belongsToMany(
            Hadith::class,
            'hadith_isnad',
            'narrator_id',
            'hadith_id',
        )->withPivot(['position_in_chain', 'transmission_method']);
    }

    public static function resolveOrCreate(array $data, array $uniqueKey = []): self
    {
        $localized = self::localizedData($data);
        $sahabi = app(SahabiIdentityMatcher::class)->match([
            $localized['name']['ar'] ?? null,
            $localized['name']['en'] ?? null,
            $localized['full_name']['ar'] ?? null,
            $localized['full_name']['en'] ?? null,
        ]);

        if ($sahabi !== null) {
            return static::updateOrCreate(
                ['sahaba_id' => $sahabi->id],
                $localized + ['sahaba_id' => $sahabi->id],
            );
        }

        if ($uniqueKey === []) {
            $query = static::query();
            foreach (['ar', 'en'] as $locale) {
                if (! empty($localized['name'][$locale])) {
                    $query->orWhere("name->{$locale}", $localized['name'][$locale]);
                }
            }

            if ($existing = $query->first()) {
                $existing->update($localized);

                return $existing;
            }
        }

        return static::create($localized);
    }

    public function updateIdentityAndRole(array $data): bool
    {
        $localized = self::localizedData($data);

        if ($this->sahabi === null) {
            return $this->update($localized);
        }

        $this->sahabi->syncTranslations([
            'name' => $localized['name'] ?? [],
            'full_name' => $localized['full_name'] ?? [],
            'kunya' => $localized['kunya'] ?? [],
            'biography' => $localized['biography'] ?? [],
        ]);

        return $this->update(Arr::only($localized, [
            'reliability_grade',
            'school',
            'region',
            'birth_year_hijri',
            'death_year_hijri',
        ]));
    }

    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->where(function (Builder $query) use ($term): void {
            foreach (['ar', 'en', 'fr', 'tr', 'ur'] as $locale) {
                $query->orWhere("name->{$locale}", 'like', "%{$term}%")
                    ->orWhere("full_name->{$locale}", 'like', "%{$term}%");
            }

            $query->orWhereHas('sahabi.translations', fn (Builder $translation) => $translation
                ->where('name', 'like', "%{$term}%")
                ->orWhere('full_name', 'like', "%{$term}%"));
        });
    }

    public function scopeByDeathYear(Builder $query, int $year): Builder
    {
        return $query->where('death_year_hijri', $year);
    }

    public function scopeWithKunya(Builder $query): Builder
    {
        return $query->whereNotNull('kunya');
    }

    public function getDisplayName(string $locale = 'ar'): string
    {
        return $this->localized('kunya', $locale)
            ?? $this->localized('name', $locale)
            ?? '';
    }

    public function getFullDisplayName(string $locale = 'ar'): string
    {
        return $this->localized('full_name', $locale) ?? $this->getDisplayName($locale);
    }

    public function getHadithsCount(): int
    {
        return $this->isnads()->distinct('hadith_id')->count('hadith_id');
    }

    public function getNameArAttribute(): ?string
    {
        return $this->localized('name', 'ar') ?? $this->sahabi?->getTranslation('name', 'ar');
    }

    public function getNameEnAttribute(): ?string
    {
        return $this->localized('name', 'en') ?? $this->sahabi?->getTranslation('name', 'en');
    }

    public function getFullNameArAttribute(): ?string
    {
        return $this->localized('full_name', 'ar') ?? $this->sahabi?->getTranslation('full_name', 'ar');
    }

    public function getFullNameEnAttribute(): ?string
    {
        return $this->localized('full_name', 'en') ?? $this->sahabi?->getTranslation('full_name', 'en');
    }

    public function getBiographyArAttribute(): ?string
    {
        return $this->localized('biography', 'ar') ?? $this->sahabi?->getTranslation('biography', 'ar');
    }

    public function getBiographyEnAttribute(): ?string
    {
        return $this->localized('biography', 'en') ?? $this->sahabi?->getTranslation('biography', 'en');
    }

    public function getKunyaArAttribute(): ?string
    {
        return $this->localized('kunya', 'ar') ?? $this->sahabi?->getTranslation('kunya', 'ar');
    }

    private function localized(string $field, string $locale): ?string
    {
        $values = $this->getAttribute($field);

        return is_array($values) ? ($values[$locale] ?? $values['ar'] ?? null) : null;
    }

    private static function localizedData(array $data): array
    {
        if (isset($data['name']) && is_array($data['name'])) {
            return Arr::only($data, (new static)->getFillable());
        }

        return array_filter([
            'sahaba_id' => $data['sahaba_id'] ?? null,
            'name' => array_filter(['ar' => $data['name_ar'] ?? null, 'en' => $data['name_en'] ?? null]),
            'full_name' => array_filter(['ar' => $data['full_name_ar'] ?? null, 'en' => $data['full_name_en'] ?? null]),
            'kunya' => array_filter(['ar' => $data['kunya'] ?? null]),
            'biography' => array_filter([
                'ar' => $data['biography_ar'] ?? $data['biography'] ?? null,
                'en' => $data['biography_en'] ?? null,
            ]),
            'birth_year_hijri' => $data['birth_year_hijri'] ?? null,
            'death_year_hijri' => $data['death_year_hijri'] ?? null,
            'reliability_grade' => $data['reliability_grade'] ?? null,
            'school' => $data['school'] ?? null,
            'region' => $data['region'] ?? null,
        ], static fn (mixed $value): bool => $value !== null && $value !== []);
    }
}
