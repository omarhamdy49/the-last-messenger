<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class HadithTheme extends Model
{
    protected $table = 'hadith_themes';

    public $timestamps = false;

    protected $fillable = [
        'slug',
        'name',
        'review_status',
        'source_reference',
    ];

    protected $casts = [
        'name' => 'array',
    ];

    public function hadiths(): BelongsToMany
    {
        return $this->belongsToMany(
            Hadith::class,
            'hadith_hadith_themes',
            'hadith_theme_id',
            'hadith_id',
        );
    }

    public function getNameInLocale(string $locale = 'ar'): string
    {
        return (string) ($this->name[$locale] ?? $this->name['ar'] ?? '');
    }

    public function getTranslation(string $field, string $locale): ?string
    {
        if ($field !== 'name') {
            return null;
        }

        return $this->name[$locale] ?? null;
    }

    public function getThemeArAttribute(): ?string
    {
        return $this->name['ar'] ?? null;
    }

    public function getThemeEnAttribute(): ?string
    {
        return $this->name['en'] ?? null;
    }
}
