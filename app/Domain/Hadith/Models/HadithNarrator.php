<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Models;

use App\Domain\Hadith\Enums\NarratorGrade;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Builder;

/**
 * Hadith Narrator Model
 * Represents hadith narrators/transmitters
 *
 * @property int $id
 * @property string $slug
 * @property string $name_arabic
 * @property string|null $name_english
 * @property string|null $biography
 * @property NarratorGrade $reliability_grade
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class HadithNarrator extends Model
{
    use HasFactory;

    protected $table = 'hadith_narrators';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'slug',
        'name_arabic',
        'name_english',
        'biography',
        'reliability_grade',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'reliability_grade' => NarratorGrade::class,
        ];
    }

    /**
     * Get all hadith narrated by this person
     *
     * @return BelongsToMany
     */
    public function hadiths(): BelongsToMany
    {
        return $this->belongsToMany(Hadith::class, 'hadith_narrator_chain')
            ->withPivot('position')
            ->orderByPivot('position');
    }

    /**
     * Scope: Filter by reliability grade
     *
     * @param Builder $query
     * @param NarratorGrade $grade
     * @return Builder
     */
    public function scopeByGrade(Builder $query, NarratorGrade $grade): Builder
    {
        return $query->where('reliability_grade', $grade);
    }

    /**
     * Scope: Trustworthy narrators only
     *
     * @param Builder $query
     * @return Builder
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
     *
     * @return bool
     */
    public function isTrustworthy(): bool
    {
        return in_array($this->reliability_grade, [
            NarratorGrade::THIQAH,
            NarratorGrade::SADUQ,
        ]);
    }
}

