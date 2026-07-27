<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Models;

use App\Domain\Sahaba\Models\Sahabi;
use App\Domain\Sahaba\Services\SahabiIdentityMatcher;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Companion extends Model
{
    protected $table = 'hadith_companions';

    protected $fillable = [
        'sahaba_id',
        'name_ar', 'name_en', 'full_name_ar', 'full_name_en',
        'title_ar', 'title_en', 'birth_year_hijri', 'death_year_hijri',
        'conversion_year', 'bio_ar', 'bio_en', 'virtues', 'verified',
    ];

    protected $casts = [
        'sahaba_id' => 'integer',
        'birth_year_hijri' => 'integer',
        'death_year_hijri' => 'integer',
        'verified' => 'boolean',
    ];

    public function sahabi(): BelongsTo
    {
        return $this->belongsTo(Sahabi::class, 'sahaba_id');
    }

    public function hadiths(): HasMany
    {
        return $this->hasMany(Hadith::class, 'companion_id');
    }

    public static function findOrCreateByName(array $data): self
    {
        $sahabi = app(SahabiIdentityMatcher::class)->match([
            $data['name_ar'] ?? null,
            $data['name_en'] ?? null,
            $data['full_name_ar'] ?? null,
            $data['full_name_en'] ?? null,
        ]);

        if ($sahabi !== null) {
            return static::firstOrCreate(
                ['sahaba_id' => $sahabi->id],
                ['verified' => true],
            );
        }

        if (! empty($data['name_ar'])) {
            $companion = static::where('name_ar', $data['name_ar'])->first();
            if ($companion) {
                return $companion;
            }
        }

        if (! empty($data['name_en'])) {
            $companion = static::where('name_en', $data['name_en'])->first();
            if ($companion) {
                return $companion;
            }
        }

        return static::create($data);
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

    public function getTitleArAttribute(?string $value): ?string
    {
        return $value ?? $this->sahabi?->getTranslation('title', 'ar');
    }

    public function getTitleEnAttribute(?string $value): ?string
    {
        return $value ?? $this->sahabi?->getTranslation('title', 'en');
    }

    public function getBioArAttribute(?string $value): ?string
    {
        return $value ?? $this->sahabi?->getTranslation('biography', 'ar');
    }

    public function getBioEnAttribute(?string $value): ?string
    {
        return $value ?? $this->sahabi?->getTranslation('biography', 'en');
    }

    public function getVirtuesAttribute(?string $value): ?string
    {
        return $value
            ?? $this->sahabi?->getTranslation('virtues', 'ar')
            ?? $this->sahabi?->getTranslation('virtues', 'en');
    }
}
