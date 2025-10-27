<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

/**
 * Hadith Chapter Model
 * Represents chapters within hadith books
 *
 * @property int $id
 * @property int $book_id
 * @property int $chapter_number
 * @property array $name
 * @property array|null $description
 * @property int $display_order
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class HadithChapter extends Model
{
    use HasFactory;

    protected $table = 'hadith_chapters';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'book_id',
        'chapter_number',
        'name',
        'description',
        'display_order',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'name' => 'array',
            'description' => 'array',
            'chapter_number' => 'integer',
            'display_order' => 'integer',
        ];
    }

    /**
     * Get the book this chapter belongs to
     *
     * @return BelongsTo
     */
    public function book(): BelongsTo
    {
        return $this->belongsTo(HadithBook::class, 'book_id');
    }

    /**
     * Get all hadith in this chapter
     *
     * @return HasMany
     */
    public function hadiths(): HasMany
    {
        return $this->hasMany(Hadith::class, 'chapter_id');
    }

    /**
     * Scope: Ordered by display order
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('display_order');
    }

    /**
     * Get name in specified language
     *
     * @param string $language
     * @return string|null
     */
    public function getNameInLanguage(string $language): ?string
    {
        return $this->name[$language] ?? $this->name['ar'] ?? null;
    }

    /**
     * Get description in specified language
     *
     * @param string $language
     * @return string|null
     */
    public function getDescriptionInLanguage(string $language): ?string
    {
        return $this->description[$language] ?? $this->description['ar'] ?? null;
    }
}

