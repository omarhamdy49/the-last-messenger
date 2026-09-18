<?php

declare(strict_types=1);

namespace App\Domain\Quran\Models;

use App\Domain\Quran\Enums\SectionType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * One juz / hizb / rubʿ al-ḥizb / manzil / rukūʿ.
 *
 * @property int $id
 * @property SectionType $type
 * @property int $number
 * @property int|null $surah_ruku_number
 * @property int $first_verse_id
 * @property int $last_verse_id
 * @property int $verses_count
 * @property array<string, string> $verse_mapping chapter number => "from-to"
 */
class Section extends Model
{
    protected $table = 'quran_sections';

    protected $fillable = [
        'type',
        'number',
        'surah_ruku_number',
        'first_verse_id',
        'last_verse_id',
        'verses_count',
        'verse_mapping',
    ];

    protected $casts = [
        'type' => SectionType::class,
        'number' => 'integer',
        'surah_ruku_number' => 'integer',
        'first_verse_id' => 'integer',
        'last_verse_id' => 'integer',
        'verses_count' => 'integer',
        'verse_mapping' => 'array',
    ];

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeOfType(Builder $query, SectionType $type): Builder
    {
        return $query->where('type', $type->value);
    }
}
