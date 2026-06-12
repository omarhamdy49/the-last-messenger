<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * HadithScholar Model
 *
 * Represents Islamic scholars who graded hadiths throughout history.
 *
 * @property int $id
 * @property string $name_ar Scholar name in Arabic
 * @property string|null $name_en Scholar name in English
 * @property string|null $kunya Kunya (e.g., Abu...)
 * @property string|null $full_name Full name
 * @property string|null $school School of thought (e.g., Shafi'i, Hanbali)
 * @property int|null $birth_year_hijri Birth year (Hijri calendar)
 * @property int|null $death_year_hijri Death year (Hijri calendar)
 * @property string|null $region Region (e.g., Khorasan, Andalus)
 * @property string|null $biography Biographical information
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class HadithScholar extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'hadith_scholars';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'name_ar',
        'name_en',
        'kunya',
        'full_name',
        'full_name_ar',
        'full_name_en',
        'school',
        'birth_year_hijri',
        'death_year_hijri',
        'region',
        'biography',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'birth_year_hijri' => 'integer',
        'death_year_hijri' => 'integer',
    ];

    /**
     * Get all gradings by this scholar
     */
    public function gradings(): HasMany
    {
        return $this->hasMany(HadithGrading::class, 'scholar_id');
    }

    /**
     * Scope: Filter by specialty/school
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeBySchool($query, string $school)
    {
        return $query->where('school', 'like', "%{$school}%");
    }

    /**
     * Scope: Filter by region
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByRegion($query, string $region)
    {
        return $query->where('region', 'like', "%{$region}%");
    }

    /**
     * Scope: Filter scholars who died in a specific century
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByCentury($query, int $century)
    {
        $startYear = ($century - 1) * 100;
        $endYear = $century * 100;

        return $query->whereBetween('death_year_hijri', [$startYear, $endYear]);
    }

    /**
     * Scope: Order by death year
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrderByDeathYear($query, string $direction = 'asc')
    {
        return $query->orderBy('death_year_hijri', $direction);
    }

    /**
     * Get scholar's full display name
     */
    public function getDisplayName(string $language = 'ar'): string
    {
        if ($language === 'ar' || empty($this->name_en)) {
            return $this->kunya
                ? "{$this->name_ar} ({$this->kunya})"
                : $this->name_ar;
        }

        return $this->kunya
            ? "{$this->name_en} ({$this->kunya})"
            : $this->name_en;
    }

    /**
     * Get scholar's lifespan in Hijri years
     */
    public function getLifespanAttribute(): ?int
    {
        if ($this->birth_year_hijri && $this->death_year_hijri) {
            return $this->death_year_hijri - $this->birth_year_hijri;
        }

        return null;
    }

    /**
     * Get scholar's century (based on death year)
     */
    public function getCenturyAttribute(): ?int
    {
        if ($this->death_year_hijri) {
            return (int) ceil($this->death_year_hijri / 100);
        }

        return null;
    }

    /**
     * Check if scholar is from classical era (before year 600 AH)
     */
    public function isClassicalScholar(): bool
    {
        return $this->death_year_hijri && $this->death_year_hijri < 600;
    }

    /**
     * Check if scholar is contemporary (after year 1300 AH)
     */
    public function isContemporaryScholar(): bool
    {
        return $this->death_year_hijri && $this->death_year_hijri > 1300;
    }
}
