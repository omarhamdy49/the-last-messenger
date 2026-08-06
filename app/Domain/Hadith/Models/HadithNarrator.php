<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Models;

use App\Domain\Shared\Contracts\PersonLookup;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One link in a hadith's chain of narration.
 *
 * `narrator_id` is a foreign key into `muslims`; there is deliberately no
 * Eloquent relation to that model — see {@see HadithCollection} and
 * {@see PersonLookup}.
 *
 * `source_name_ar` / `source_name_en` keep the source's own spelling next to
 * the resolved link. That is the evidence by which a questionable match can be
 * audited later; without it the link is unfalsifiable.
 *
 * @property int $hadith_id
 * @property int $narrator_id
 * @property int $position
 * @property bool $is_primary
 */
class HadithNarrator extends Model
{
    protected $table = 'hadith_narrators';

    /** Composite primary key (hadith_id, position) — not an auto-increment id. */
    public $incrementing = false;

    protected $primaryKey = null;

    protected $keyType = 'string';

    protected $fillable = [
        'hadith_id', 'narrator_id', 'position', 'is_primary',
        'source_name_ar', 'source_name_en',
    ];

    /**
     * `primary_hadith_id` is a STORED generated column enforcing one primary
     * narrator per hadith. It must never be written to.
     */
    protected $guarded = ['primary_hadith_id'];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'position' => 'integer',
        ];
    }

    public function hadith(): BelongsTo
    {
        return $this->belongsTo(Hadith::class, 'hadith_id');
    }

    public function sourceName(?string $locale = null): ?string
    {
        return ($locale ?? app()->getLocale()) === 'ar'
            ? ($this->source_name_ar ?? $this->source_name_en)
            : ($this->source_name_en ?? $this->source_name_ar);
    }
}
