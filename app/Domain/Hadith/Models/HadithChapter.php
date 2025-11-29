<?php

namespace App\Domain\Hadith\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Translatable\HasTranslations;

/**
 * Class HadithChapter
 *
 * Represents a chapter (باب) within a Hadith book.
 * Chapters organize hadiths by topic within a book (e.g., "Chapter on Prayer", "Chapter on Fasting").
 *
 * Note: This model assumes a hadith_chapters table exists. If migration doesn't exist, create it:
 * - id, book_id, chapter_number, name (JSON), description (JSON), display_order
 *
 * @property int $id
 * @property int $book_id
 * @property int|null $chapter_number
 * @property array $name Multi-language chapter name (ar, en, etc.)
 * @property array|null $description Multi-language chapter description
 * @property int|null $display_order Order for display purposes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @property-read \App\Domain\Hadith\Models\HadithBook $book
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Domain\Hadith\Models\Hadith[] $hadiths
 * @property-read int|null $hadiths_count
 *
 * @method static Builder forBook(int $bookId)
 * @method static Builder byChapterNumber(int $chapterNumber)
 * @method static Builder ordered()
 */
class HadithChapter extends Model
{
    use HasFactory;
    use HasTranslations;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'hadith_chapters';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'book_id',
        'chapter_number',
        'name',
        'description',
        'display_order',
    ];

    /**
     * The attributes that should be translatable.
     *
     * @var array<int, string>
     */
    public array $translatable = [
        'name',
        'description',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'book_id' => 'integer',
        'chapter_number' => 'integer',
        'display_order' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the book that this chapter belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function book(): BelongsTo
    {
        return $this->belongsTo(HadithBook::class, 'book_id');
    }

    /**
     * Get all hadiths in this chapter.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function hadiths(): HasMany
    {
        return $this->hasMany(Hadith::class, 'chapter_id');
    }

    /**
     * Scope to filter chapters by book.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $bookId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForBook(Builder $query, int $bookId): Builder
    {
        return $query->where('book_id', $bookId);
    }

    /**
     * Scope to filter chapters by chapter number.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $chapterNumber
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByChapterNumber(Builder $query, int $chapterNumber): Builder
    {
        return $query->where('chapter_number', $chapterNumber);
    }

    /**
     * Scope to order chapters by display order, then chapter number.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('display_order', 'asc')
            ->orderBy('chapter_number', 'asc');
    }

    /**
     * Scope to get chapters with their book relationship.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithBook(Builder $query): Builder
    {
        return $query->with('book');
    }

    /**
     * Scope to get chapters with their hadiths.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithHadiths(Builder $query): Builder
    {
        return $query->with('hadiths');
    }

    /**
     * Scope to get chapters with hadith count.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithHadithCount(Builder $query): Builder
    {
        return $query->withCount('hadiths');
    }

    /**
     * Get the chapter name in the specified language.
     *
     * @param string $locale
     * @return string
     */
    public function getNameInLocale(string $locale = 'ar'): string
    {
        return $this->getTranslation('name', $locale);
    }

    /**
     * Get the chapter description in the specified language.
     *
     * @param string $locale
     * @return string|null
     */
    public function getDescriptionInLocale(string $locale = 'ar'): ?string
    {
        return $this->getTranslation('description', $locale);
    }

    /**
     * Get the total count of hadiths in this chapter.
     *
     * @return int
     */
    public function getHadithsCount(): int
    {
        return $this->hadiths()->count();
    }

    /**
     * Get the next chapter in the same book.
     *
     * @return \App\Domain\Hadith\Models\HadithChapter|null
     */
    public function getNextChapter(): ?HadithChapter
    {
        return static::where('book_id', $this->book_id)
            ->where('display_order', '>', $this->display_order ?? 0)
            ->ordered()
            ->first();
    }

    /**
     * Get the previous chapter in the same book.
     *
     * @return \App\Domain\Hadith\Models\HadithChapter|null
     */
    public function getPreviousChapter(): ?HadithChapter
    {
        return static::where('book_id', $this->book_id)
            ->where('display_order', '<', $this->display_order ?? PHP_INT_MAX)
            ->orderBy('display_order', 'desc')
            ->orderBy('chapter_number', 'desc')
            ->first();
    }

    /**
     * Get the chapter's full display title with book name.
     *
     * @param string $locale
     * @return string
     */
    public function getFullTitle(string $locale = 'ar'): string
    {
        $bookTitle = $this->book ? $this->book->getTitleInLocale($locale) : '';
        $chapterName = $this->getNameInLocale($locale);

        if ($this->chapter_number) {
            return sprintf('%s - باب %d: %s', $bookTitle, $this->chapter_number, $chapterName);
        }

        return sprintf('%s - %s', $bookTitle, $chapterName);
    }
}
