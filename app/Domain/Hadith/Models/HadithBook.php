<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

/**
 * Hadith Book Model
 * Represents hadith collections (e.g., Sahih al-Bukhari, Sahih Muslim)
 *
 * @property int $id
 * @property string $slug
 * @property array $name
 * @property array|null $description
 * @property string|null $author_name
 * @property int $total_hadith_count
 * @property int $display_order
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class HadithBook extends Model
{
    use HasFactory;

    protected $table = 'hadith_books';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'slug',
        'name',
        'description',
        'author_name',
        'total_hadith_count',
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
            'total_hadith_count' => 'integer',
            'display_order' => 'integer',
        ];
    }

    /**
     * Get all chapters in this book
     *
     * @return HasMany
     */
    public function chapters(): HasMany
    {
        return $this->hasMany(HadithChapter::class, 'book_id')->orderBy('display_order');
    }

    /**
     * Get all hadith in this book
     *
     * @return HasMany
     */
    public function hadiths(): HasMany
    {
        return $this->hasMany(Hadith::class, 'book_id');
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

    /**
     * Update hadith count
     *
     * @return void
     */
    public function updateHadithCount(): void
    {
        $this->update(['total_hadith_count' => $this->hadiths()->count()]);
    }
}

