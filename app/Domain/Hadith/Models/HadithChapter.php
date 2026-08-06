<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Models;

use Database\Factories\Hadith\HadithChapterFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

/**
 * A division of a book.
 *
 * @property int $id
 * @property int $book_id
 * @property string $chapter_number NOT unique — real books repeat these
 * @property bool $is_published
 */
class HadithChapter extends Model
{
    use HasFactory, HasTranslations;

    protected $table = 'hadith_chapters';

    protected $fillable = [
        'book_id', 'chapter_number', 'title_ar', 'title_en',
        'intro', 'ending', 'sort_order', 'is_published',
    ];

    public array $translatable = ['intro', 'ending'];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'sort_order' => 'integer',
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

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    public function title(?string $locale = null): string
    {
        return ($locale ?? app()->getLocale()) === 'ar'
            ? $this->title_ar
            : ($this->title_en ?? $this->title_ar);
    }

    protected static function newFactory(): HadithChapterFactory
    {
        return HadithChapterFactory::new();
    }
}
