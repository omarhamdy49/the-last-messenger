<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Models;

use App\Domain\Hadith\Enums\HadithGrade;
use App\Domain\Shared\Traits\HasAuditLog;
use App\Domain\Shared\Traits\HasVersions;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
 * @property string $hadith_number
 * @property int|null $in_book_number
 * @property string|null $volume_number
 * @property int|null $sunnah_urn
 * @property int|null $sunnah_urn_en
 * @property HadithGrade $display_grade
 * @property bool $is_published
 * @property-read HadithCollection $collection
 * @property-read HadithBook $book
 * @property-read HadithChapter|null $chapter
 * @property-read \Illuminate\Database\Eloquent\Collection<int, HadithText> $texts
 * @property-read \Illuminate\Database\Eloquent\Collection<int, HadithGrading> $gradings
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
        'hadith_number',
        'in_book_number',
        'volume_number',
        'sunnah_urn',
        'sunnah_urn_en',
        'is_published',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'collection_id' => 'integer',
            'book_id' => 'integer',
            'chapter_id' => 'integer',
            'in_book_number' => 'integer',
            'sunnah_urn' => 'integer',
            'sunnah_urn_en' => 'integer',
            'display_grade' => HadithGrade::class,
            'is_published' => 'boolean',
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

    public function texts(): HasMany
    {
        return $this->hasMany(HadithText::class, 'hadith_id');
    }

    public function gradings(): HasMany
    {
        return $this->hasMany(HadithGrading::class, 'hadith_id');
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
    protected static function newFactory(): \Database\Factories\Hadith\HadithFactory
    {
        return \Database\Factories\Hadith\HadithFactory::new();
    }
}
