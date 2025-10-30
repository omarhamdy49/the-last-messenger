<?php

declare(strict_types=1);

namespace App\Domain\Quran\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Tafseer Model (Quran Interpretation)
 *
 * @property int $id
 * @property string $verse_reference
 * @property int|null $surah_id
 * @property string|null $ayah_number
 * @property string $source
 * @property array $text
 * @property string|null $author
 * @property array|null $footnotes
 * @property string $status
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class Tafseer extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'quran_tafseer';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'verse_reference',
        'surah_id',
        'ayah_number',
        'source',
        'text',
        'author',
        'footnotes',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'surah_id' => 'integer',
        'text' => 'array',
        'footnotes' => 'array',
    ];

    /**
     * Get the surah this tafseer belongs to
     */
    public function surah(): BelongsTo
    {
        return $this->belongsTo(Surah::class, 'surah_id');
    }

    /**
     * Get the verse this tafseer belongs to
     *
     * Note: This dynamically finds the verse based on verse_reference
     */
    public function verse(): ?Verse
    {
        if (! $this->verse_reference) {
            return null;
        }

        [$surahNumber, $ayahNumber] = explode(':', $this->verse_reference);

        return Verse::whereHas('surah', function ($query) use ($surahNumber) {
            $query->where('number', (int) $surahNumber);
        })->where('verse_number', (int) $ayahNumber)->first();
    }

    /**
     * Scope: Filter by source
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeBySource($query, string $source)
    {
        return $query->where('source', $source);
    }

    /**
     * Scope: Filter by status
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: Filter by surah
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeBySurah($query, int $surahId)
    {
        return $query->where('surah_id', $surahId);
    }

    /**
     * Scope: Published only
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    /**
     * Scope: Search by verse reference or content
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSearch($query, string $searchTerm)
    {
        return $query->where(function ($q) use ($searchTerm) {
            $q->where('verse_reference', 'like', "%{$searchTerm}%")
                ->orWhereRaw("JSON_SEARCH(text, 'one', ?) IS NOT NULL", ["%{$searchTerm}%"]);
        });
    }

    /**
     * Parse and set verse reference (auto-extract surah_id and ayah_number)
     */
    public function setVerseReferenceAttribute(string $value): void
    {
        $this->attributes['verse_reference'] = $value;

        // Extract surah and ayah
        if (preg_match('/^(\d{1,3}):(\d{1,3})$/', $value, $matches)) {
            $surahNumber = (int) $matches[1];
            $ayahNumber = $matches[2];

            // Find surah_id
            $surah = Surah::where('number', $surahNumber)->first();
            if ($surah) {
                $this->attributes['surah_id'] = $surah->id;
            }

            $this->attributes['ayah_number'] = $ayahNumber;
        }
    }
}
