<?php

namespace App\Domain\Hadith\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'death_year_hijri' => 'integer',
    ];

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
        return $query->whereRaw(
            'MATCH(name_ar, name_en, full_name_ar, full_name_en, kunya) AGAINST(? IN NATURAL LANGUAGE MODE)',
            [$term]
        );
    }

    /**
     * Scope to filter narrators by death year.
     */
    public function scopeByDeathYear(Builder $query, int $year): Builder
    {
        return $query->where('death_year_hijri', $year);
    }

    /**
     * Scope to filter narrators who have a kunya.
     */
    public function scopeWithKunya(Builder $query): Builder
    {
        return $query->whereNotNull('kunya');
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
}
