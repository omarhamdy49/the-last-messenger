<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class HadithBook extends Model
{
    use HasFactory;

    protected $table = 'hadith_books';

    protected $fillable = [
        'collection_id',
        'book_number',
        'title',
        'description',
    ];

    protected $casts = [
        'collection_id' => 'integer',
        'book_number' => 'string',
        'title' => 'array',
        'description' => 'array',
    ];

    public function collection(): BelongsTo
    {
        return $this->belongsTo(HadithCollection::class, 'collection_id');
    }

    public function hadiths(): HasManyThrough
    {
        return $this->hasManyThrough(
            Hadith::class,
            HadithChapter::class,
            'book_id',
            'chapter_id',
        );
    }

    public function chapters(): HasMany
    {
        return $this->hasMany(HadithChapter::class, 'book_id');
    }

    public function getTitleInLocale(string $locale = 'ar'): string
    {
        return (string) ($this->title[$locale] ?? $this->title['ar'] ?? '');
    }

    public function getTitleArAttribute(): string
    {
        return $this->getTitleInLocale('ar');
    }

    public function getTitleEnAttribute(): ?string
    {
        return $this->title['en'] ?? null;
    }

    public function getChapterTitleInLocale(string $locale = 'ar'): ?string
    {
        return $this->description[$locale] ?? $this->description['ar'] ?? null;
    }

    public function scopeByCollection($query, int $collectionId)
    {
        return $query->where('collection_id', $collectionId);
    }

    public function scopeWithHadiths($query)
    {
        return $query->with('hadiths');
    }

    public function scopeWithCollection($query)
    {
        return $query->with('collection');
    }

    public function getTotalHadithsCount(): int
    {
        return $this->hadiths()->count();
    }
}
