<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Models;

use App\Domain\Hadith\Enums\Grade;
use Database\Factories\Hadith\HadithFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A single narration.
 *
 * Two identities, deliberately distinct:
 *   `reference_key` — the stable public address, unique per collection, derived
 *                     from source structural position. This is what URLs use.
 *   `number`        — the displayed scholarly number. MAY repeat within a
 *                     collection. Never use it to look a hadith up; assuming it
 *                     was unique cost this project 56 Tirmidhi records once.
 *
 * @property int $id
 * @property int $collection_id
 * @property int|null $book_id null for bookless collections
 * @property int|null $chapter_id
 * @property string $reference_key
 * @property string $number
 * @property Grade|null $grade
 * @property string|null $raw_grade
 * @property string|null $text_ar null for the 38 documented Arabic-less records
 * @property bool $is_published
 */
class Hadith extends Model
{
    use HasFactory;

    protected $table = 'hadiths';

    protected $fillable = [
        'collection_id', 'book_id', 'chapter_id', 'reference_key', 'number',
        'in_book_number', 'sort_order', 'grade', 'raw_grade',
        'text_ar', 'text_en', 'search_ar', 'search_en', 'narrated_by',
        'canonical_reference', 'in_book_reference', 'translation_reference',
        'is_published',
    ];

    protected function casts(): array
    {
        return [
            'grade' => Grade::class,
            'is_published' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    // ---------------------------------------------------------------------
    // Relations
    // ---------------------------------------------------------------------

    public function collection(): BelongsTo
    {
        return $this->belongsTo(HadithCollection::class, 'collection_id');
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(HadithBook::class, 'book_id');
    }

    public function chapter(): BelongsTo
    {
        return $this->belongsTo(HadithChapter::class, 'chapter_id');
    }

    /** The chain, in source order. */
    public function narrators(): HasMany
    {
        return $this->hasMany(HadithNarrator::class, 'hadith_id')->orderBy('position');
    }

    public function primaryNarrator(): HasMany
    {
        return $this->hasMany(HadithNarrator::class, 'hadith_id')->where('is_primary', true);
    }

    // ---------------------------------------------------------------------
    // Scopes
    // ---------------------------------------------------------------------

    /**
     * Public visibility: the hadith AND every ancestor must be published.
     *
     * This is the single place that rule lives. Applying it per-controller is
     * how it ends up inconsistent between detail, browse, search and random —
     * which FR-033 exists to prevent.
     */
    public function scopePubliclyVisible(Builder $query): Builder
    {
        return $query
            ->where('hadiths.is_published', true)
            ->whereHas('collection', fn (Builder $q) => $q->where('is_published', true))
            ->where(fn (Builder $q) => $q
                ->whereNull('book_id')
                ->orWhereHas('book', fn (Builder $b) => $b->where('is_published', true)))
            ->where(fn (Builder $q) => $q
                ->whereNull('chapter_id')
                ->orWhereHas('chapter', fn (Builder $c) => $c->where('is_published', true)));
    }

    public function scopeForReference(Builder $query, int $collectionId, string $referenceKey): Builder
    {
        return $query->where('collection_id', $collectionId)
            ->where('reference_key', $referenceKey);
    }

    // ---------------------------------------------------------------------
    // Presentation
    // ---------------------------------------------------------------------

    public function text(?string $locale = null): ?string
    {
        return ($locale ?? app()->getLocale()) === 'ar' ? $this->text_ar : $this->text_en;
    }

    /**
     * Locale-aware citation. The stored `canonical_reference` is English
     * ("Sahih al-Bukhari 1"); Arabic pages cite as "صحيح البخاري ١" instead.
     */
    public function displayReference(?string $locale = null): string
    {
        if (($locale ?? app()->getLocale()) === 'ar' && $this->collection?->title_ar) {
            return $this->collection->title_ar.' '.arabic_digits($this->number);
        }

        return (string) $this->canonical_reference;
    }

    /**
     * Locale-aware in-book citation. Stored as "Book 1, Hadith 1" (English);
     * translated structurally for Arabic pages.
     */
    public function displayInBookReference(?string $locale = null): ?string
    {
        $ref = $this->in_book_reference;

        if ($ref === null || ($locale ?? app()->getLocale()) !== 'ar') {
            return $ref;
        }

        $ref = str_replace(
            ['Book', 'Hadith', ','],
            [__('hadith.book'), __('hadith.hadith'), '،'],
            $ref
        );

        return preg_replace_callback('/\d+/', fn ($m) => arabic_digits($m[0]), $ref);
    }

    /**
     * The public address. A colon, not a slash: `/hadith/{slug}/{bookNumber}`
     * already occupies the slash form, so `/hadith/bukhari/12` would be
     * ambiguous between book 12 and reference key 12.
     */
    public function publicPath(): string
    {
        return '/hadith/'.$this->collection->slug.':'.$this->reference_key;
    }

    /**
     * Publishable only with Arabic text. Mirrors `chk_hadiths_publishable` so
     * the admin surface reports a validation error instead of a database one.
     */
    public function isPublishable(): bool
    {
        return $this->text_ar !== null && $this->search_ar !== null;
    }

    protected static function newFactory(): HadithFactory
    {
        return HadithFactory::new();
    }
}
