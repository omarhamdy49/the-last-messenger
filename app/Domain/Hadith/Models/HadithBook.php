<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Models;

use Database\Factories\Hadith\HadithBookFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

/**
 * A division of a collection.
 *
 * @property int $id
 * @property int $collection_id
 * @property string $book_number string, not int — '35b', 'introduction', 'letter'
 * @property bool $is_published
 */
class HadithBook extends Model
{
    use HasFactory, HasTranslations;

    protected $table = 'hadith_books';

    protected $fillable = [
        'collection_id', 'book_number', 'title_ar', 'title_en', 'intro',
        'first_hadith_number', 'last_hadith_number', 'sort_order', 'is_published',
    ];

    /** {ar,en} only (Decision D). */
    public array $translatable = ['intro'];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function collection(): BelongsTo
    {
        return $this->belongsTo(HadithCollection::class, 'collection_id');
    }

    public function chapters(): HasMany
    {
        return $this->hasMany(HadithChapter::class, 'book_id');
    }

    public function hadiths(): HasMany
    {
        return $this->hasMany(Hadith::class, 'book_id');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    /** English titles are optional in the sources; fall back rather than render blank. */
    public function title(?string $locale = null): string
    {
        return ($locale ?? app()->getLocale()) === 'ar'
            ? $this->title_ar
            : ($this->title_en ?? $this->title_ar);
    }

    /**
     * Whether `book_number` reads as a figure.
     *
     * The column is a string because the sources do not only number their
     * books: across the corpus it also holds "introduction", "35b" and "8b".
     * The short ones sit fine in a figure-sized slot; "introduction" overflows
     * the card's number badge and hides the icon drawn underneath it.
     */
    public function hasFigureNumber(): bool
    {
        return mb_strlen((string) $this->book_number) <= 4;
    }

    protected static function newFactory(): HadithBookFactory
    {
        return HadithBookFactory::new();
    }
}
