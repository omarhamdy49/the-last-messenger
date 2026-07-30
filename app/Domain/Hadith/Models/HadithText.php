<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One row per locale THAT HAS TEXT. English is absent for ~7% of records, and
 * `body` is NOT NULL, so an Arabic-only hadith has exactly one row — a row is
 * omitted rather than written empty (FR-015).
 *
 * `search_body` is the only FULLTEXT-indexed column: normalised Arabic for `ar`
 * (diacritics and tatweel stripped, letter variants folded) and the original
 * body for Latin-script locales. Never queried directly for display.
 *
 * @property int $id
 * @property int $hadith_id
 * @property string $locale
 * @property string $body
 * @property string $search_body
 * @property string|null $narrated_by
 * @property string|null $sanad
 * @property string|null $matn
 * @property string|null $translator
 * @property string|null $source_url
 */
class HadithText extends Model
{
    use HasFactory;

    protected $table = 'hadith_texts';

    /** @var array<string> */
    protected $fillable = [
        'hadith_id',
        'locale',
        'body',
        'search_body',
        'narrated_by',
        'sanad',
        'matn',
        'translator',
        'source_url',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['hadith_id' => 'integer'];
    }

    public function hadith(): BelongsTo
    {
        return $this->belongsTo(Hadith::class, 'hadith_id');
    }

    /**
     * Factories live under Database\Factories\Hadith, which Laravel cannot
     * derive from this model's namespace.
     */
    protected static function newFactory(): \Database\Factories\Hadith\HadithTextFactory
    {
        return \Database\Factories\Hadith\HadithTextFactory::new();
    }
}
