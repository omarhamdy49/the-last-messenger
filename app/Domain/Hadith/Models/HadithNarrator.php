<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Models;

use App\Domain\Hadith\Enums\NarratorGrade;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

/**
 * Hadith Narrator Model
 * Represents hadith narrators/transmitters
 *
 * @property int $id
 * @property string $slug
 * @property array $name
 * @property array|null $biography_json
 * @property NarratorGrade $reliability_grade
 * @property string|null $era
 * @property string|null $country
 * @property string|null $birth_hijri
 * @property string|null $death_hijri
 * @property array|null $teachers
 * @property array|null $students
 * @property string $status
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class HadithNarrator extends Model
{
    use HasFactory, HasTranslations, SoftDeletes;

    protected $table = 'hadith_narrators';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'slug',
        'name',
        'biography_json',
        'reliability_grade',
        'era',
        'country',
        'birth_hijri',
        'death_hijri',
        'teachers',
        'students',
        'status',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'name' => 'array',
            'biography_json' => 'array',
            'reliability_grade' => NarratorGrade::class,
            'teachers' => 'array',
            'students' => 'array',
        ];
    }

    /**
     * Translatable attributes
     *
     * @var array<string>
     */
    public array $translatable = ['name', 'biography_json'];

    /**
     * Get all hadith narrated by this person
     */
    public function hadiths(): BelongsToMany
    {
        return $this->belongsToMany(Hadith::class, 'hadith_narrator_chain')
            ->withPivot('position')
            ->orderByPivot('position');
    }

    /**
     * Scope: Filter by reliability grade
     */
    public function scopeByGrade(Builder $query, NarratorGrade $grade): Builder
    {
        return $query->where('reliability_grade', $grade);
    }

    /**
     * Scope: Filter by era/period
     */
    public function scopeByEra(Builder $query, string $era): Builder
    {
        return $query->where('era', $era);
    }

    /**
     * Scope: Filter by country
     */
    public function scopeByCountry(Builder $query, string $country): Builder
    {
        return $query->where('country', $country);
    }

    /**
     * Scope: Active narrators only
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope: Search by name
     */
    public function scopeSearch(Builder $query, string $searchTerm): Builder
    {
        return $query->where(function ($q) use ($searchTerm) {
            $q->whereRaw("MATCH(name_ar_generated, name_en_generated) AGAINST(? IN NATURAL LANGUAGE MODE)", [$searchTerm])
                ->orWhere('country', 'like', "%{$searchTerm}%");
        });
    }

    /**
     * Scope: Trustworthy narrators only
     */
    public function scopeTrustworthy(Builder $query): Builder
    {
        return $query->whereIn('reliability_grade', [
            NarratorGrade::THIQAH,
            NarratorGrade::SADUQ,
        ]);
    }

    /**
     * Check if narrator is trustworthy
     */
    public function isTrustworthy(): bool
    {
        return in_array($this->reliability_grade, [
            NarratorGrade::THIQAH,
            NarratorGrade::SADUQ,
        ]);
    }

    /**
     * Get hadith count for this narrator
     */
    public function getHadithCountAttribute(): int
    {
        return $this->hadiths()->count();
    }

    /**
     * Get teachers as narrator models
     */
    public function getTeachersAttribute(): array
    {
        if (empty($this->attributes['teachers'])) {
            return [];
        }

        $teacherIds = json_decode($this->attributes['teachers'], true);
        if (empty($teacherIds) || !is_array($teacherIds)) {
            return [];
        }

        return self::whereIn('id', $teacherIds)->get()->toArray();
    }

    /**
     * Get students as narrator models
     */
    public function getStudentsAttribute(): array
    {
        if (empty($this->attributes['students'])) {
            return [];
        }

        $studentIds = json_decode($this->attributes['students'], true);
        if (empty($studentIds) || !is_array($studentIds)) {
            return [];
        }

        return self::whereIn('id', $studentIds)->get()->toArray();
    }
}
