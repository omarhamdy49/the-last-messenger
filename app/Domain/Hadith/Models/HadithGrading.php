<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HadithGrading extends Model
{
    use HasFactory;

    protected $table = 'hadith_gradings';

    public $timestamps = false;

    protected $fillable = [
        'hadith_id',
        'scholar_id',
        'grade_key',
        'raw_grade',
        'notes',
        'source_reference',
        'source_url',
        'source_name',
        'review_status',
        'evaluated_at',
    ];

    protected $casts = [
        'hadith_id' => 'integer',
        'scholar_id' => 'integer',
        'evaluated_at' => 'date',
    ];

    public function hadith(): BelongsTo
    {
        return $this->belongsTo(Hadith::class, 'hadith_id');
    }

    public function scholar(): BelongsTo
    {
        return $this->belongsTo(HadithScholar::class, 'scholar_id');
    }

    public function scopeByScholar(Builder $query, int $scholarId): Builder
    {
        return $query->where('scholar_id', $scholarId);
    }

    public function scopeByLevel(Builder $query, string $gradeKey): Builder
    {
        return $query->where('grade_key', $gradeKey);
    }

    public function scopeByHadith(Builder $query, int $hadithId): Builder
    {
        return $query->where('hadith_id', $hadithId);
    }

    public function scopeAuthentic(Builder $query): Builder
    {
        return $query->whereIn('grade_key', [Hadith::GRADING_SAHIH, Hadith::GRADING_HASAN]);
    }

    public function scopeWeak(Builder $query): Builder
    {
        return $query->where('grade_key', Hadith::GRADING_DAIF);
    }

    public function scopeFabricated(Builder $query): Builder
    {
        return $query->where('grade_key', Hadith::GRADING_MAWDU);
    }

    public function hasNotes(): bool
    {
        return ! empty($this->notes);
    }
}
