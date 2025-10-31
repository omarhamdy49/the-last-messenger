<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

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
    use HasFactory, HasTranslations;

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
            'chapter_number' => 'integer',
            'display_order' => 'integer',
        ];
    }

    /**
     * Translatable attributes
     *
     * @var array<string>
     */
    public array $translatable = ['name', 'description'];

    /**
     * Get the book this chapter belongs to
     */
    public function book(): BelongsTo
    {
        return $this->belongsTo(HadithBook::class, 'book_id');
    }

    /**
     * Get all hadith in this chapter
     */
    public function hadiths(): HasMany
    {
        return $this->hasMany(Hadith::class, 'chapter_id');
    }

    /**
     * Scope: Ordered by display order
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('display_order');
    }

    /**
     * Get name in specified language
     */
    public function getNameInLanguage(string $language): ?string
    {
        return $this->getTranslation('name', $language, false) ?? $this->getTranslation('name', 'ar', false);
    }

    /**
     * Get description in specified language
     */
    public function getDescriptionInLanguage(string $language): ?string
    {
        return $this->getTranslation('description', $language, false) ?? $this->getTranslation('description', 'ar', false);
    }
}
