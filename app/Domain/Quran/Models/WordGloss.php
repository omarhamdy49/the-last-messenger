<?php

declare(strict_types=1);

namespace App\Domain\Quran\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A word-by-word translation or transliteration of one word, owned by a
 * word_translation / word_transliteration resource.
 *
 * @property int $id
 * @property int $word_id
 * @property int $resource_id
 * @property string $kind translation|transliteration
 * @property string $language_iso
 * @property string $text
 * @property int|null $priority
 * @property int|null $qf_id
 */
class WordGloss extends Model
{
    public const KIND_TRANSLATION = 'translation';

    public const KIND_TRANSLITERATION = 'transliteration';

    protected $table = 'quran_word_glosses';

    protected $fillable = ['word_id', 'resource_id', 'kind', 'language_iso', 'text', 'priority', 'qf_id'];

    protected $casts = [
        'word_id' => 'integer',
        'resource_id' => 'integer',
        'priority' => 'integer',
        'qf_id' => 'integer',
    ];

    public function word(): BelongsTo
    {
        return $this->belongsTo(Word::class, 'word_id');
    }

    public function resource(): BelongsTo
    {
        return $this->belongsTo(QuranResource::class, 'resource_id');
    }
}
