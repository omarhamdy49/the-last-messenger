<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * HadithGradingLevel Model
 *
 * Represents the different levels/grades of hadith authenticity (Sahih, Hasan, Daif, etc.)
 *
 * @property int $id
 * @property string $grade_ar Grade in Arabic (e.g., صحيح، حسن، ضعيف)
 * @property string $grade_en Grade in English (e.g., Sahih, Hasan, Da'if)
 * @property string|null $description Grade description
 * @property int|null $rank Numeric ranking (1 = highest/most authentic)
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class HadithGradingLevel extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'hadith_grading_levels';

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
        'grade_ar',
        'grade_en',
        'description',
        'rank',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'rank' => 'integer',
    ];

    /**
     * Grading level constants (English codes)
     */
    public const SAHIH = 'sahih'; // Authentic

    public const HASAN = 'hasan'; // Good

    public const DAIF = 'daif'; // Weak

    public const MAWDU = 'mawdu'; // Fabricated

    public const MUTTASIL = 'muttasil'; // Connected chain

    public const MURSAL = 'mursal'; // Missing companion

    public const MUNQATI = 'munqati'; // Broken chain

    public const MUDALLAS = 'mudallas'; // Concealed defect

    public const SHADH = 'shadh'; // Irregular

    public const MUNKAR = 'munkar'; // Denounced

    public const SAHIH_LIGHAIRIHI = 'sahih_lighairihi'; // Authentic due to corroborating evidence

    public const HASAN_LIGHAIRIHI = 'hasan_lighairihi'; // Good due to corroborating evidence

    /**
     * Get all gradings with this level
     */
    public function gradings(): HasMany
    {
        return $this->hasMany(HadithGrading::class, 'grading_level_id');
    }

    /**
     * Scope: Order by severity/authenticity rank
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrderBySeverity($query, string $direction = 'asc')
    {
        return $query->orderBy('rank', $direction);
    }

    /**
     * Scope: Get authentic levels only (Sahih and Hasan)
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeAuthentic($query)
    {
        return $query->whereIn('grade_en', ['sahih', 'hasan', 'sahih_lighairihi', 'hasan_lighairihi']);
    }

    /**
     * Scope: Get weak levels only
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWeak($query)
    {
        return $query->whereIn('grade_en', ['daif', 'munqati', 'mursal', 'mudallas', 'shadh', 'munkar']);
    }

    /**
     * Scope: Get fabricated levels only
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFabricated($query)
    {
        return $query->where('grade_en', 'mawdu');
    }

    /**
     * Scope: Filter by grade code
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByCode($query, string $code)
    {
        return $query->where('grade_en', strtolower($code));
    }

    /**
     * Get grade display name
     */
    public function getDisplayName(string $language = 'ar'): string
    {
        return $language === 'ar' ? $this->grade_ar : $this->grade_en;
    }

    /**
     * Check if this is an authentic grade
     */
    public function isAuthentic(): bool
    {
        return in_array(strtolower($this->grade_en), [
            self::SAHIH,
            self::HASAN,
            self::SAHIH_LIGHAIRIHI,
            self::HASAN_LIGHAIRIHI,
        ], true);
    }

    /**
     * Check if this is a weak grade
     */
    public function isWeak(): bool
    {
        return in_array(strtolower($this->grade_en), [
            self::DAIF,
            self::MUNQATI,
            self::MURSAL,
            self::MUDALLAS,
            self::SHADH,
            self::MUNKAR,
        ], true);
    }

    /**
     * Check if this is a fabricated grade
     */
    public function isFabricated(): bool
    {
        return strtolower($this->grade_en) === self::MAWDU;
    }

    /**
     * Get CSS color class based on grade severity
     */
    public function getColorClass(): string
    {
        if ($this->isAuthentic()) {
            return 'text-green-600 bg-green-50';
        }

        if ($this->isFabricated()) {
            return 'text-red-600 bg-red-50';
        }

        if ($this->isWeak()) {
            return 'text-yellow-600 bg-yellow-50';
        }

        return 'text-gray-600 bg-gray-50';
    }

    /**
     * Get badge color based on grade severity
     */
    public function getBadgeColor(): string
    {
        if ($this->isAuthentic()) {
            return 'success';
        }

        if ($this->isFabricated()) {
            return 'danger';
        }

        if ($this->isWeak()) {
            return 'warning';
        }

        return 'secondary';
    }
}
