<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Models;

use App\Domain\Hadith\Enums\HadithGrade;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Builder;

/**
 * Hadith Model
 * Represents individual hadith with full text and narration chain
 *
 * @property int $id
 * @property int $book_id
 * @property int|null $chapter_id
 * @property int $hadith_number
 * @property string $arabic_text
 * @property string|null $narrator_chain
 * @property array|null $translations
 * @property HadithGrade $grade
 * @property string|null $reference_url
 * @property int $views_count
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Hadith extends Model
{
    use HasFactory;

    protected $table = 'hadith';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'book_id',
        'chapter_id',
        'hadith_number',
        'arabic_text',
        'narrator_chain',
        'translations',
        'grade',
        'reference_url',
        'views_count',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'translations' => 'array',
            'grade' => HadithGrade::class,
            'hadith_number' => 'integer',
            'views_count' => 'integer',
        ];
    }

    /**
     * Get the book this hadith belongs to
     *
     * @return BelongsTo
     */
    public function book(): BelongsTo
    {
        return $this->belongsTo(HadithBook::class, 'book_id');
    }

    /**
     * Get the chapter this hadith belongs to
     *
     * @return BelongsTo
     */
    public function chapter(): BelongsTo
    {
        return $this->belongsTo(HadithChapter::class, 'chapter_id');
    }

    /**
     * Get all narrators in the chain
     *
     * @return BelongsToMany
     */
    public function narrators(): BelongsToMany
    {
        return $this->belongsToMany(HadithNarrator::class, 'hadith_narrator_chain')
            ->withPivot('position')
            ->orderByPivot('position');
    }

    /**
     * Get all favorites
     *
     * @return MorphMany
     */
    public function favorites(): MorphMany
    {
        return $this->morphMany(\App\Domain\Blog\Models\Favorite::class, 'favorable');
    }

    /**
     * Get user interactions
     *
     * @return MorphMany
     */
    public function interactions(): MorphMany
    {
        return $this->morphMany(\App\Domain\Blog\Models\UserInteraction::class, 'interactable');
    }

    /**
     * Scope: Filter by book
     *
     * @param Builder $query
     * @param int $bookId
     * @return Builder
     */
    public function scopeByBook(Builder $query, int $bookId): Builder
    {
        return $query->where('book_id', $bookId);
    }

    /**
     * Scope: Filter by chapter
     *
     * @param Builder $query
     * @param int $chapterId
     * @return Builder
     */
    public function scopeByChapter(Builder $query, int $chapterId): Builder
    {
        return $query->where('chapter_id', $chapterId);
    }

    /**
     * Scope: Filter by grade
     *
     * @param Builder $query
     * @param HadithGrade $grade
     * @return Builder
     */
    public function scopeByGrade(Builder $query, HadithGrade $grade): Builder
    {
        return $query->where('grade', $grade);
    }

    /**
     * Scope: Authentic hadith only (sahih)
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeSahih(Builder $query): Builder
    {
        return $query->where('grade', HadithGrade::SAHIH);
    }

    /**
     * Scope: Reliable hadith (sahih or hasan)
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeReliable(Builder $query): Builder
    {
        return $query->whereIn('grade', [HadithGrade::SAHIH, HadithGrade::HASAN]);
    }

    /**
     * Scope: Full-text search in Arabic text
     *
     * @param Builder $query
     * @param string $search
     * @return Builder
     */
    public function scopeSearch(Builder $query, string $search): Builder
    {
        if (config('database.default') === 'mysql') {
            return $query->whereRaw(
                "MATCH(arabic_text) AGAINST(? IN BOOLEAN MODE)",
                [$search]
            );
        }
        
        // Fallback for SQLite
        return $query->where('arabic_text', 'LIKE', "%{$search}%");
    }

    /**
     * Increment views count
     *
     * @return void
     */
    public function incrementViews(): void
    {
        $this->increment('views_count');
    }

    /**
     * Get translation in specified language
     *
     * @param string $language
     * @return string|null
     */
    public function getTranslation(string $language): ?string
    {
        return $this->translations[$language] ?? null;
    }

    /**
     * Check if hadith is authentic (sahih)
     *
     * @return bool
     */
    public function isAuthentic(): bool
    {
        return $this->grade === HadithGrade::SAHIH;
    }

    /**
     * Check if hadith is reliable (sahih or hasan)
     *
     * @return bool
     */
    public function isReliable(): bool
    {
        return $this->grade->isReliable();
    }
}

