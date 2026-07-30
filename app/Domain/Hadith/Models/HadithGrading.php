<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Models;

use App\Domain\Hadith\Enums\GradeScope;
use App\Domain\Hadith\Enums\HadithGrade;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One row per CLAIM, not per source string. "Sanad Da'if wal-Hadith Hasan"
 * becomes two rows sharing one `raw_grade` (FR-002, FR-003, FR-004).
 *
 * `raw_grade` is never discarded and never normalised in place: it is the
 * verbatim source string, and it is the audit trail for every `grade` value.
 *
 * The source's `authenticated_by` field must never create a row here — it names
 * the compiler of the collection the hadith already belongs to, which is
 * membership rather than a verdict (FR-006).
 *
 * @property int $id
 * @property int $hadith_id
 * @property int|null $scholar_id
 * @property HadithGrade $grade
 * @property GradeScope $scope
 * @property string|null $part_label
 * @property int $authority_rank
 * @property string $raw_grade
 * @property string|null $locale
 */
class HadithGrading extends Model
{
    use HasFactory;

    protected $table = 'hadith_gradings';

    /** @var array<string> */
    protected $fillable = [
        'hadith_id',
        'scholar_id',
        'grade',
        'scope',
        'part_label',
        'authority_rank',
        'raw_grade',
        'locale',
        'notes',
        'source_reference',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'hadith_id' => 'integer',
            'scholar_id' => 'integer',
            'grade' => HadithGrade::class,
            'scope' => GradeScope::class,
            'authority_rank' => 'integer',
        ];
    }

    public function hadith(): BelongsTo
    {
        return $this->belongsTo(Hadith::class, 'hadith_id');
    }

    public function scholar(): BelongsTo
    {
        return $this->belongsTo(HadithScholar::class, 'scholar_id');
    }

    /**
     * Factories live under Database\Factories\Hadith, which Laravel cannot
     * derive from this model's namespace.
     */
    protected static function newFactory(): \Database\Factories\Hadith\HadithGradingFactory
    {
        return \Database\Factories\Hadith\HadithGradingFactory::new();
    }
}
