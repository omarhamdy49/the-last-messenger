<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

/**
 * `intro` is the preamble before the chapter's hadiths; `ending` is its closing
 * passage. Deletion is RESTRICT at the database level: orphaning every hadith in
 * a chapter because an editor deleted it is not acceptable (FR-010).
 *
 * @property int $id
 * @property int $book_id
 * @property string $chapter_number
 * @property int $sort_order
 * @property bool $is_published
 */
class HadithChapter extends Model
{
    use HasFactory, HasTranslations;

    protected $table = 'hadith_chapters';

    /** @var array<string> */
    public array $translatable = ['title', 'intro', 'ending'];

    /** @var array<string> */
    protected $fillable = [
        'book_id',
        'chapter_number',
        'sort_order',
        'title',
        'intro',
        'ending',
        'is_published',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'book_id' => 'integer',
            'sort_order' => 'integer',
            'is_published' => 'boolean',
        ];
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(HadithBook::class, 'book_id');
    }

    public function hadiths(): HasMany
    {
        return $this->hasMany(Hadith::class, 'chapter_id');
    }

    /**
     * Factories live under Database\Factories\Hadith, which Laravel cannot
     * derive from this model's namespace.
     */
    protected static function newFactory(): \Database\Factories\Hadith\HadithChapterFactory
    {
        return \Database\Factories\Hadith\HadithChapterFactory::new();
    }
}
