<?php

declare(strict_types=1);

namespace App\Domain\Quran\Models;

use App\Domain\Quran\Enums\CharType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A word's position in one mushaf layout.
 *
 * @property int $id QF record id
 * @property int $mushaf_id
 * @property int $word_id
 * @property int $verse_id
 * @property string|null $text glyph or text for this mushaf's font
 * @property CharType $char_type
 * @property int $page_number
 * @property int $line_number
 * @property int $position_in_verse
 * @property int $position_in_line
 * @property int $position_in_page
 * @property string|null $css_class
 * @property string|null $css_style
 */
class MushafWord extends Model
{
    protected $table = 'quran_mushaf_words';

    public $incrementing = false;

    protected $keyType = 'int';

    protected $fillable = [
        'id', 'mushaf_id', 'word_id', 'verse_id', 'text', 'char_type', 'page_number', 'line_number',
        'position_in_verse', 'position_in_line', 'position_in_page', 'css_class', 'css_style',
    ];

    protected $casts = [
        'id' => 'integer',
        'mushaf_id' => 'integer',
        'word_id' => 'integer',
        'verse_id' => 'integer',
        'char_type' => CharType::class,
        'page_number' => 'integer',
        'line_number' => 'integer',
        'position_in_verse' => 'integer',
        'position_in_line' => 'integer',
        'position_in_page' => 'integer',
    ];

    public function mushaf(): BelongsTo
    {
        return $this->belongsTo(Mushaf::class, 'mushaf_id');
    }

    public function word(): BelongsTo
    {
        return $this->belongsTo(Word::class, 'word_id');
    }
}
