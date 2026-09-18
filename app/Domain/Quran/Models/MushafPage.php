<?php

declare(strict_types=1);

namespace App\Domain\Quran\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One page of a mushaf and the verse range it holds.
 *
 * @property int $id QF page record id
 * @property int $mushaf_id
 * @property int $page_number
 * @property int $first_verse_id
 * @property int $last_verse_id
 * @property int|null $first_word_id
 * @property int|null $last_word_id
 * @property int $verses_count
 * @property array<string, string> $verse_mapping
 */
class MushafPage extends Model
{
    protected $table = 'quran_mushaf_pages';

    public $incrementing = false;

    protected $keyType = 'int';

    protected $fillable = [
        'id', 'mushaf_id', 'page_number', 'first_verse_id', 'last_verse_id', 'first_word_id', 'last_word_id',
        'verses_count', 'verse_mapping',
    ];

    protected $casts = [
        'id' => 'integer',
        'mushaf_id' => 'integer',
        'page_number' => 'integer',
        'first_verse_id' => 'integer',
        'last_verse_id' => 'integer',
        'first_word_id' => 'integer',
        'last_word_id' => 'integer',
        'verses_count' => 'integer',
        'verse_mapping' => 'array',
    ];

    public function mushaf(): BelongsTo
    {
        return $this->belongsTo(Mushaf::class, 'mushaf_id');
    }

    /**
     * Words on this page in reading order. A query, not a relation: the link is the
     * composite (mushaf_id, page_number).
     *
     * @return Builder<MushafWord>
     */
    public function wordsQuery(): Builder
    {
        return MushafWord::query()
            ->where('mushaf_id', $this->mushaf_id)
            ->where('page_number', $this->page_number)
            // Page order, not the per-line index: see EloquentMushafRepository.
            ->orderBy('position_in_page');
    }
}
