<?php

namespace App\Domain\Hadith\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class HadithCollection
 *
 * Represents a Hadith collection (e.g., Sahih Bukhari, Sahih Muslim).
 * A collection is compiled by a specific scholar and contains multiple books.
 *
 * @property int $id
 * @property array $name Multi-language collection name (ar, en, etc.)
 * @property string|null $description Collection description
 * @property string $compiler Name of the compiler/scholar
 * @property int|null $death_year_hijri Death year of the compiler in Hijri calendar
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 *
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Domain\Hadith\Models\HadithBook[] $books
 * @property-read int|null $books_count
 */
class HadithCollection extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'hadith_collections';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name_ar',
        'name_en',
        'slug_ar',
        'slug_en',
        'description_ar',
        'description_en',
        'abbreviation',
        'total_hadiths',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'death_year_hijri' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get all books belonging to this collection.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function books(): HasMany
    {
        return $this->hasMany(HadithBook::class, 'collection_id');
    }

    /**
     * Get the collection name in the specified language.
     *
     * @param string $locale
     * @return string
     */
    public function getNameInLocale(string $locale = 'ar'): string
    {
        return $this->getTranslation('name', $locale);
    }

    /**
     * Get the collection description in the specified language.
     *
     * @param string $locale
     * @return string|null
     */
    public function getDescriptionInLocale(string $locale = 'ar'): ?string
    {
        return $this->getTranslation('description', $locale);
    }

    /**
     * Scope to get collections by compiler name.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $compiler
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByCompiler($query, string $compiler)
    {
        return $query->where('compiler', 'like', "%{$compiler}%");
    }

    /**
     * Scope to get collections with their books.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithBooks($query)
    {
        return $query->with('books');
    }
}
