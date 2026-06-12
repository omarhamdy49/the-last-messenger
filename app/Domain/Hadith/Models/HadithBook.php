<?php

namespace App\Domain\Hadith\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class HadithBook
 *
 * Represents a book within a Hadith collection (e.g., Book of Faith, Book of Prayer).
 * Each book belongs to a collection and contains multiple hadiths and chapters.
 *
 * @property int $id
 * @property int $collection_id
 * @property string|null $title_ar Arabic book title
 * @property string $title_en English book title
 * @property string|null $chapter_title_ar Arabic chapter title
 * @property string|null $chapter_title_en English chapter title
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read \App\Domain\Hadith\Models\HadithCollection $collection
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Domain\Hadith\Models\Hadith[] $hadiths
 * @property-read int|null $hadiths_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Domain\Hadith\Models\HadithChapter[] $chapters
 * @property-read int|null $chapters_count
 */
class HadithBook extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'hadith_books';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'collection_id',
        'book_number',
        'title_ar',
        'title_en',
        'slug_ar',
        'slug_en',
        'chapter_title_ar',
        'chapter_title_en',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'collection_id' => 'integer',
        'book_number' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the collection that owns this book.
     */
    public function collection(): BelongsTo
    {
        return $this->belongsTo(HadithCollection::class, 'collection_id');
    }

    /**
     * Get all hadiths in this book.
     */
    public function hadiths(): HasMany
    {
        return $this->hasMany(Hadith::class, 'book_id');
    }

    /**
     * Get all chapters in this book.
     */
    public function chapters(): HasMany
    {
        return $this->hasMany(HadithChapter::class, 'book_id');
    }

    /**
     * Get the book title in the specified language.
     */
    public function getTitleInLocale(string $locale = 'ar'): string
    {
        $field = 'title_'.$locale;

        return $this->$field ?? $this->title_en ?? '';
    }

    /**
     * Get the chapter title in the specified language.
     */
    public function getChapterTitleInLocale(string $locale = 'ar'): ?string
    {
        $field = 'chapter_title_'.$locale;

        return $this->$field ?? $this->chapter_title_en;
    }

    /**
     * Scope to filter books by collection.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByCollection($query, int $collectionId)
    {
        return $query->where('collection_id', $collectionId);
    }

    /**
     * Scope to get books with their hadiths.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithHadiths($query)
    {
        return $query->with('hadiths');
    }

    /**
     * Scope to get books with their collection.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithCollection($query)
    {
        return $query->with('collection');
    }

    /**
     * Get the total count of hadiths in this book.
     */
    public function getTotalHadithsCount(): int
    {
        return $this->hadiths()->count();
    }
}
