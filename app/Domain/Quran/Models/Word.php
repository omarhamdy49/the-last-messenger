<?php

declare(strict_types=1);

namespace App\Domain\Quran\Models;

use App\Domain\Quran\Enums\CharType;
use Database\Factories\Quran\WordFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A word (or verse-end / pause mark) keyed on the QF word id. Line and page are for
 * the default mushaf; see MushafWord for other layouts.
 *
 * @property int $id
 * @property int $verse_id
 * @property int $position
 * @property string $location "chapter:verse:position"
 * @property CharType $char_type
 * @property string|null $text_uthmani
 * @property string|null $text_uthmani_simple
 * @property string|null $text_indopak
 * @property string|null $text_imlaei
 * @property string|null $text_qpc_hafs
 * @property string|null $code_v1
 * @property string|null $code_v2
 * @property int|null $v1_page
 * @property int|null $v2_page
 * @property int $page_number
 * @property int $line_number
 * @property string|null $audio_url
 */
class Word extends Model
{
    /** @use HasFactory<WordFactory> */
    use HasFactory;

    protected $table = 'quran_words';

    public $incrementing = false;

    protected $keyType = 'int';

    protected $fillable = [
        'id', 'verse_id', 'position', 'location', 'char_type',
        'text_uthmani', 'text_uthmani_simple', 'text_indopak', 'text_imlaei', 'text_qpc_hafs',
        'code_v1', 'code_v2', 'v1_page', 'v2_page', 'page_number', 'line_number', 'audio_url',
    ];

    protected $casts = [
        'id' => 'integer',
        'verse_id' => 'integer',
        'position' => 'integer',
        'char_type' => CharType::class,
        'v1_page' => 'integer',
        'v2_page' => 'integer',
        'page_number' => 'integer',
        'line_number' => 'integer',
    ];

    protected static function newFactory(): WordFactory
    {
        return WordFactory::new();
    }

    public function verse(): BelongsTo
    {
        return $this->belongsTo(Verse::class, 'verse_id');
    }

    public function glosses(): HasMany
    {
        return $this->hasMany(WordGloss::class, 'word_id');
    }
}
