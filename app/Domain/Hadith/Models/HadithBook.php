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
 * Hadith Book Model
 * Represents hadith books (e.g., Sahih al-Bukhari, Sahih Muslim)
 *
 * @property int $id
 * @property string $slug
 * @property int|null $collection_id
 * @property array $name
 * @property array|null $description
 * @property string|null $author_name
 * @property int $total_hadith_count
 * @property int $display_order
 * @property bool $active
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class HadithBook extends Model
{
    use HasFactory, HasTranslations;

    protected $table = 'hadith_books';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'slug',
        'collection_id',
        'name',
        'description',
        'author_name',
        'total_hadith_count',
        'display_order',
        'active',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'total_hadith_count' => 'integer',
            'display_order' => 'integer',
            'active' => 'boolean',
        ];
    }

    /**
     * Translatable attributes
     *
     * @var array<string>
     */
    public array $translatable = ['name', 'description'];

    /**
     * Get the collection this book belongs to
     */
    public function collection(): BelongsTo
    {
        return $this->belongsTo(HadithCollection::class, 'collection_id');
    }

    /**
     * Get all chapters in this book
     */
    public function chapters(): HasMany
    {
        return $this->hasMany(HadithChapter::class, 'book_id')->orderBy('display_order');
    }

    /**
     * Get all hadith in this book
     */
    public function hadiths(): HasMany
    {
        return $this->hasMany(Hadith::class, 'book_id');
    }

    /**
     * Scope: Active books only
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
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

    /**
     * Update hadith count
     */
    public function updateHadithCount(): void
    {
        $this->update(['total_hadith_count' => $this->hadiths()->count()]);
    }
}
