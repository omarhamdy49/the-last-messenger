<?php

namespace App\Domain\Hadith\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

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
 *
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
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'death_year_hijri' => 'integer',
    ];

    /**
     * Get all isnad (chain of narration) entries for this narrator.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
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
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $term
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->whereRaw(
            'MATCH(name_ar, full_name_ar, notes) AGAINST(? IN NATURAL LANGUAGE MODE)',
            [$term]
        );
    }

    /**
     * Scope to filter narrators by death year.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $year
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByDeathYear(Builder $query, int $year): Builder
    {
        return $query->where('death_year_hijri', $year);
    }

    /**
     * Scope to filter narrators who have a kunya.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithKunya(Builder $query): Builder
    {
        return $query->whereNotNull('kunya');
    }

    /**
     * Scope to filter narrators by death year range.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $startYear
     * @param int $endYear
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByDeathYearRange(Builder $query, int $startYear, int $endYear): Builder
    {
        return $query->whereBetween('death_year_hijri', [$startYear, $endYear]);
    }

    /**
     * Scope to get narrators with their isnad relationships.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithIsnads(Builder $query): Builder
    {
        return $query->with('isnads');
    }

    /**
     * Get the narrator's display name.
     * Returns kunya if available, otherwise name_ar.
     *
     * @return string
     */
    public function getDisplayName(): string
    {
        return $this->kunya ?? $this->name_ar;
    }

    /**
     * Get the narrator's full display name with title.
     *
     * @return string
     */
    public function getFullDisplayName(): string
    {
        $name = $this->full_name_ar ?? $this->name_ar;

        if ($this->title) {
            return $this->title . ' ' . $name;
        }

        return $name;
    }

    /**
     * Get the count of hadiths narrated by this narrator.
     *
     * @return int
     */
    public function getHadithsCount(): int
    {
        return $this->isnads()->distinct('hadith_id')->count('hadith_id');
    }
}
