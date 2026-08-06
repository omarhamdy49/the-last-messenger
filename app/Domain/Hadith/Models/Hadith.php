<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Models;

use App\Domain\Hadith\Enums\HadithGrade;
use App\Domain\Shared\Traits\HasAuditLog;
use App\Domain\Shared\Traits\HasVersions;
use Database\Factories\Hadith\HadithFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Identity and structure only — no text. Bodies live in `hadith_texts`, one row
 * per locale that actually has text.
 *
 * Not translatable: nothing on this row is language-specific.
 *
 * `display_grade` is derived from the `hadith` scope gradings and is never
 * written directly; `HadithAdminService` owns the recomputation inside the same
 * transaction as any grading write (data-model.md §4). It is deliberately absent
 * from `$fillable` so mass assignment cannot set it.
 *
 * @property int $id
 * @property int $collection_id
 * @property int $book_id
 * @property int|null $chapter_id
 * @property int|null $primary_narrator_id
 * @property string $hadith_number
 * @property int|null $in_book_number
 * @property string|null $volume_number
 * @property int|null $sunnah_urn
 * @property int|null $sunnah_urn_en
 * @property HadithGrade|null $display_grade
 * @property string|null $source_reference
 * @property string|null $source_in_book_reference
 * @property string|null $source_translation_reference
 * @property bool $is_published
 * @property-read HadithCollection $collection
 * @property-read HadithBook $book
 * @property-read HadithChapter|null $chapter
 * @property-read HadithScholar|null $primaryNarrator
 * @property-read Collection<int, HadithText> $texts
 * @property-read Collection<int, HadithGrading> $gradings
 */
class Hadith extends Model
{
    use HasAuditLog, HasFactory, HasVersions;

    protected $table = 'hadith_hadiths';

    /**
     * `display_grade` is excluded on purpose — it is derived, never assigned.
     *
     * @var array<string>
     */
    protected $fillable = [
        'collection_id',
        'book_id',
        'chapter_id',
        'primary_narrator_id',
        'hadith_number',
        'in_book_number',
        'source_position',
        'volume_number',
        'sunnah_urn',
        'sunnah_urn_en',
        'is_published',
        'source_container_id',
        'source_reference',
        'source_in_book_reference',
        'source_translation_reference',
        'canonical_url',
        'source_text_sha256',
        'source_metadata',
        'source_unavailable_fields',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'collection_id' => 'integer',
            'book_id' => 'integer',
            'chapter_id' => 'integer',
            'primary_narrator_id' => 'integer',
            'in_book_number' => 'integer',
            'source_position' => 'integer',
            'sunnah_urn' => 'integer',
            'sunnah_urn_en' => 'integer',
            'display_grade' => HadithGrade::class,
            'is_published' => 'boolean',
            'source_metadata' => 'array',
            'source_unavailable_fields' => 'array',
        ];
    }

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

    public function primaryNarrator(): BelongsTo
    {
        return $this->belongsTo(HadithScholar::class, 'primary_narrator_id');
    }

    public function texts(): HasMany
    {
        return $this->hasMany(HadithText::class, 'hadith_id');
    }

    public function gradings(): HasMany
    {
        return $this->hasMany(HadithGrading::class, 'hadith_id');
    }

    public function narrators(): BelongsToMany
    {
        return $this->belongsToMany(HadithScholar::class, 'hadith_narrators', 'hadith_id', 'scholar_id')
            ->withPivot(['position', 'display_name_ar', 'source_metadata'])
            ->withTimestamps()
            ->orderByPivot('position');
    }

    /**
     * The body for a locale, falling back to Arabic — which is always present.
     */
    public function textFor(string $locale): ?HadithText
    {
        return $this->texts->firstWhere('locale', $locale)
            ?? $this->texts->firstWhere('locale', 'ar');
    }

    /**
     * Factories live under Database\Factories\Hadith, which Laravel cannot
     * derive from this model's namespace.
     */
    protected static function newFactory(): HadithFactory
    {
        return HadithFactory::new();
    }
}
