<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HadithScholar extends Model
{
    use HasFactory;

    protected $table = 'hadith_scholars';

    public $timestamps = false;

    protected $fillable = [
        'name',
        'full_name',
        'kunya',
        'biography',
        'school',
        'birth_year_hijri',
        'death_year_hijri',
        'region',
    ];

    protected $casts = [
        'name' => 'array',
        'full_name' => 'array',
        'kunya' => 'array',
        'biography' => 'array',
        'birth_year_hijri' => 'integer',
        'death_year_hijri' => 'integer',
    ];

    public function gradings(): HasMany
    {
        return $this->hasMany(HadithGrading::class, 'scholar_id');
    }

    public function commentaries(): HasMany
    {
        return $this->hasMany(HadithCommentary::class, 'scholar_id');
    }

    public function getDisplayName(string $locale = 'ar'): string
    {
        $name = $this->name[$locale] ?? $this->name['ar'] ?? '';
        $kunya = $this->kunya[$locale] ?? $this->kunya['ar'] ?? null;

        return $kunya ? "{$name} ({$kunya})" : $name;
    }

    public function getNameArAttribute(): string
    {
        return $this->name['ar'] ?? '';
    }

    public function getNameEnAttribute(): ?string
    {
        return $this->name['en'] ?? null;
    }

    public function scopeBySchool($query, string $school)
    {
        return $query->where('school', 'like', "%{$school}%");
    }

    public function scopeByRegion($query, string $region)
    {
        return $query->where('region', 'like', "%{$region}%");
    }
}
