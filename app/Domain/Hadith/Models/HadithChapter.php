<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HadithChapter extends Model
{
    use HasFactory;

    protected $table = 'hadith_chapters';

    protected $fillable = [
        'book_id',
        'chapter_number',
        'name',
        'description',
        'display_order',
    ];

    protected $casts = [
        'book_id' => 'integer',
        'chapter_number' => 'string',
        'name' => 'array',
        'description' => 'array',
        'display_order' => 'integer',
    ];

    public function book(): BelongsTo
    {
        return $this->belongsTo(HadithBook::class, 'book_id');
    }

    public function hadiths(): HasMany
    {
        return $this->hasMany(Hadith::class, 'chapter_id');
    }

    public function scopeForBook(Builder $query, int $bookId): Builder
    {
        return $query->where('book_id', $bookId);
    }

    public function scopeByChapterNumber(Builder $query, string|int $chapterNumber): Builder
    {
        return $query->where('chapter_number', (string) $chapterNumber);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('display_order')->orderBy('chapter_number');
    }

    public function getNameInLocale(string $locale = 'ar'): string
    {
        return (string) ($this->name[$locale] ?? $this->name['ar'] ?? '');
    }

    public function getDescriptionInLocale(string $locale = 'ar'): ?string
    {
        return $this->description[$locale] ?? $this->description['ar'] ?? null;
    }

    public function getHadithsCount(): int
    {
        return $this->hadiths()->count();
    }

    public function getNextChapter(): ?self
    {
        return static::where('book_id', $this->book_id)
            ->where('display_order', '>', $this->display_order)
            ->ordered()
            ->first();
    }

    public function getPreviousChapter(): ?self
    {
        return static::where('book_id', $this->book_id)
            ->where('display_order', '<', $this->display_order)
            ->orderByDesc('display_order')
            ->orderByDesc('chapter_number')
            ->first();
    }
}
