<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Models;

use App\Domain\Hadith\Enums\HadithGrade;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use App\Domain\Shared\Traits\HasInteractions;
use Spatie\Translatable\HasTranslations;

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
    use HasFactory, HasTranslations, HasInteractions;

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
     * Translatable attributes
     *
     * @var array<string>
     */
    public array $translatable = ['translations'];

    /**
     * Get the book this hadith belongs to
     */
    public function book(): BelongsTo
    {
        return $this->belongsTo(HadithBook::class, 'book_id');
    }

    /**
     * Get the chapter this hadith belongs to
     */
    public function chapter(): BelongsTo
    {
        return $this->belongsTo(HadithChapter::class, 'chapter_id');
    }

    /**
     * Get all narrators in the chain
     */
    public function narrators(): BelongsToMany
    {
        return $this->belongsToMany(HadithNarrator::class, 'hadith_narrator_chain')
            ->withPivot('position')
            ->orderByPivot('position');
    }

    /**
     * Get all favorites
     */
    public function favorites(): MorphMany
    {
        return $this->morphMany(\App\Domain\Blog\Models\Favorite::class, 'favorable');
    }

    /**
     * Get user interactions
     */
    public function interactions(): MorphMany
    {
        return $this->morphMany(\App\Domain\Blog\Models\UserInteraction::class, 'interactable');
    }

    /**
     * Scope: Filter by book
     */
    public function scopeByBook(Builder $query, int $bookId): Builder
    {
        return $query->where('book_id', $bookId);
    }

    /**
     * Scope: Filter by chapter
     */
    public function scopeByChapter(Builder $query, int $chapterId): Builder
    {
        return $query->where('chapter_id', $chapterId);
    }

    /**
     * Scope: Filter by grade
     */
    public function scopeByGrade(Builder $query, HadithGrade $grade): Builder
    {
        return $query->where('grade', $grade);
    }

    /**
     * Scope: Authentic hadith only (sahih)
     */
    public function scopeSahih(Builder $query): Builder
    {
        return $query->where('grade', HadithGrade::SAHIH);
    }

    /**
     * Scope: Reliable hadith (sahih or hasan)
     */
    public function scopeReliable(Builder $query): Builder
    {
        return $query->whereIn('grade', [HadithGrade::SAHIH, HadithGrade::HASAN]);
    }

    /**
     * Scope: Full-text search in Arabic text
     */
    public function scopeSearch(Builder $query, string $search): Builder
    {
        if (config('database.default') === 'mysql') {
            return $query->whereRaw(
                'MATCH(arabic_text) AGAINST(? IN BOOLEAN MODE)',
                [$search]
            );
        }

        // Fallback for SQLite
        return $query->where('arabic_text', 'LIKE', "%{$search}%");
    }

    /**
     * Increment views count
     */
    public function incrementViews(): void
    {
        $this->increment('views_count');
    }

    /**
     * Get translation in specified language
     */
    public function getTranslation(string $language): ?string
    {
        return $this->translations[$language] ?? null;
    }

    /**
     * Check if hadith is authentic (sahih)
     */
    public function isAuthentic(): bool
    {
        return $this->grade === HadithGrade::SAHIH;
    }

    /**
     * Check if hadith is reliable (sahih or hasan)
     */
    public function isReliable(): bool
    {
        return $this->grade->isReliable();
    }
}
