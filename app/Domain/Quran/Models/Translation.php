<?php

declare(strict_types=1);

namespace App\Domain\Quran\Models;

use Database\Factories\Quran\TranslationFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One verse's text in one translation resource.
 *
 * @property int $id
 * @property int $resource_id
 * @property int $verse_id
 * @property int|null $qf_id
 * @property string $text HTML; footnote markers are <sup foot_note=ID>
 * @property array<string, string>|null $foot_notes footnote id => text
 * @property bool $is_locked
 */
class Translation extends Model
{
    /** @use HasFactory<TranslationFactory> */
    use HasFactory;

    protected $table = 'quran_translations';

    protected $fillable = ['resource_id', 'verse_id', 'qf_id', 'text', 'foot_notes', 'is_locked'];

    protected $casts = [
        'resource_id' => 'integer',
        'verse_id' => 'integer',
        'qf_id' => 'integer',
        'foot_notes' => 'array',
        'is_locked' => 'boolean',
    ];

    protected static function newFactory(): TranslationFactory
    {
        return TranslationFactory::new();
    }

    public function resource(): BelongsTo
    {
        return $this->belongsTo(QuranResource::class, 'resource_id');
    }

    public function verse(): BelongsTo
    {
        return $this->belongsTo(Verse::class, 'verse_id');
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeUnlocked(Builder $query): Builder
    {
        return $query->where('is_locked', false);
    }
}
