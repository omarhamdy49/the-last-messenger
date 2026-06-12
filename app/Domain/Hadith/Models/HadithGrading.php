<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * HadithGrading Model
 *
 * Represents a scholar's grading/authentication of a specific hadith.
 * Links hadiths, scholars, and grading levels together.
 *
 * @property int $id
 * @property int $hadith_id Reference to hadith
 * @property int $scholar_id Reference to scholar
 * @property int $grading_level_id Reference to grading level
 * @property string|null $grading_notes Grading notes/reasoning
 * @property string|null $source_reference Source reference (e.g., Silsilat al-Ahadith al-Sahihah 1/23)
 * @property \Carbon\Carbon|null $date_evaluated Date of evaluation
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class HadithGrading extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'hadith_gradings';

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
        'hadith_id',
        'scholar_id',
        'grading_level_id',
        'grading_notes',
        'source_reference',
        'source_url',
        'source_name',
        'review_status',
        'date_evaluated',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'hadith_id' => 'integer',
        'scholar_id' => 'integer',
        'grading_level_id' => 'integer',
        'date_evaluated' => 'date',
    ];

    /**
     * Get the hadith that was graded
     */
    public function hadith(): BelongsTo
    {
        return $this->belongsTo(Hadith::class, 'hadith_id');
    }

    /**
     * Get the scholar who graded this hadith
     */
    public function scholar(): BelongsTo
    {
        return $this->belongsTo(HadithScholar::class, 'scholar_id');
    }

    /**
     * Get the grading level
     */
    public function gradingLevel(): BelongsTo
    {
        return $this->belongsTo(HadithGradingLevel::class, 'grading_level_id');
    }

    /**
     * Scope: Filter by scholar
     */
    public function scopeByScholar(Builder $query, int $scholarId): Builder
    {
        return $query->where('scholar_id', $scholarId);
    }

    /**
     * Scope: Filter by grading level
     */
    public function scopeByLevel(Builder $query, int $levelId): Builder
    {
        return $query->where('grading_level_id', $levelId);
    }

    /**
     * Scope: Filter by hadith
     */
    public function scopeByHadith(Builder $query, int $hadithId): Builder
    {
        return $query->where('hadith_id', $hadithId);
    }

    /**
     * Scope: Filter by date range
     *
     * @param  \Carbon\Carbon|string  $startDate
     * @param  \Carbon\Carbon|string|null  $endDate
     */
    public function scopeByDateRange(Builder $query, $startDate, $endDate = null): Builder
    {
        $query->where('date_evaluated', '>=', $startDate);

        if ($endDate) {
            $query->where('date_evaluated', '<=', $endDate);
        }

        return $query;
    }

    /**
     * Scope: Order by evaluation date
     */
    public function scopeOrderByDate(Builder $query, string $direction = 'desc'): Builder
    {
        return $query->orderBy('date_evaluated', $direction);
    }

    /**
     * Scope: Get recent gradings
     */
    public function scopeRecent(Builder $query, int $days = 30): Builder
    {
        return $query->where('date_evaluated', '>=', now()->subDays($days))
            ->orderBy('date_evaluated', 'desc');
    }

    /**
     * Scope: Get authentic gradings (Sahih and Hasan)
     */
    public function scopeAuthentic(Builder $query): Builder
    {
        return $query->whereHas('gradingLevel', function ($q) {
            $q->authentic();
        });
    }

    /**
     * Scope: Get weak gradings
     */
    public function scopeWeak(Builder $query): Builder
    {
        return $query->whereHas('gradingLevel', function ($q) {
            $q->weak();
        });
    }

    /**
     * Scope: Get fabricated gradings
     */
    public function scopeFabricated(Builder $query): Builder
    {
        return $query->whereHas('gradingLevel', function ($q) {
            $q->fabricated();
        });
    }

    /**
     * Scope: With eager loaded relationships
     */
    public function scopeWithRelations(Builder $query): Builder
    {
        return $query->with(['hadith', 'scholar', 'gradingLevel']);
    }

    /**
     * Get grading summary for display
     */
    public function getGradingSummary(string $language = 'ar'): string
    {
        $scholarName = $this->scholar?->getDisplayName($language) ?? 'Unknown Scholar';
        $gradeName = $this->gradingLevel?->getDisplayName($language) ?? 'Unknown Grade';

        if ($language === 'ar') {
            return "{$gradeName} - {$scholarName}";
        }

        return "{$gradeName} by {$scholarName}";
    }

    /**
     * Check if grading has notes
     */
    public function hasNotes(): bool
    {
        return ! empty($this->grading_notes);
    }

    /**
     * Check if grading has source reference
     */
    public function hasSourceReference(): bool
    {
        return ! empty($this->source_reference);
    }

    /**
     * Check if grading is authentic
     */
    public function isAuthentic(): bool
    {
        return $this->gradingLevel?->isAuthentic() ?? false;
    }

    /**
     * Check if grading is weak
     */
    public function isWeak(): bool
    {
        return $this->gradingLevel?->isWeak() ?? false;
    }

    /**
     * Check if grading is fabricated
     */
    public function isFabricated(): bool
    {
        return $this->gradingLevel?->isFabricated() ?? false;
    }

    /**
     * Get the grading age (time since evaluation)
     */
    public function getAgeAttribute(): ?string
    {
        if (! $this->date_evaluated) {
            return null;
        }

        return $this->date_evaluated->diffForHumans();
    }

    /**
     * Get formatted source reference with notes
     */
    public function getFullReferenceAttribute(): ?string
    {
        if (! $this->source_reference && ! $this->grading_notes) {
            return null;
        }

        $parts = [];

        if ($this->source_reference) {
            $parts[] = $this->source_reference;
        }

        if ($this->grading_notes) {
            $parts[] = "({$this->grading_notes})";
        }

        return implode(' ', $parts);
    }
}
