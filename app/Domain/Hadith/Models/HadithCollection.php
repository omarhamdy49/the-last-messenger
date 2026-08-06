<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Models;

use App\Domain\Shared\Contracts\PersonLookup;
use Database\Factories\Hadith\HadithCollectionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A hadith collection.
 *
 * @property int $id
 * @property string $slug
 * @property string|null $reference_template
 * @property int|null $compiler_id
 * @property int|null $total_hadith
 * @property bool $has_books
 * @property bool $has_chapters
 * @property bool $is_published
 */
class HadithCollection extends Model
{
    use HasFactory;

    protected $table = 'hadith_collections';

    protected $fillable = [
        'slug', 'thumb', 'reference_template', 'title_ar', 'title_en',
        'intro_ar', 'intro_en', 'compiler_id', 'total_hadith',
        'has_books', 'has_chapters', 'sort_order', 'is_featured', 'is_published',
    ];

    protected function casts(): array
    {
        return [
            'has_books' => 'boolean',
            'has_chapters' => 'boolean',
            'is_featured' => 'boolean',
            'is_published' => 'boolean',
            'total_hadith' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function books(): HasMany
    {
        return $this->hasMany(HadithBook::class, 'collection_id');
    }

    public function hadiths(): HasMany
    {
        return $this->hasMany(Hadith::class, 'collection_id');
    }

    /**
     * NOTE: there is deliberately no `compiler()` relation. `compiler_id` is a
     * database foreign key into `muslims`, which is allowed, but an Eloquent
     * relation would mean importing App\Domain\People — which constitution IV
     * forbids and deptrac rejects. Resolve it through
     * {@see PersonLookup} instead.
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    public function title(?string $locale = null): string
    {
        return ($locale ?? app()->getLocale()) === 'ar' ? $this->title_ar : $this->title_en;
    }

    public function intro(?string $locale = null): ?string
    {
        return ($locale ?? app()->getLocale()) === 'ar' ? $this->intro_ar : $this->intro_en;
    }

    /**
     * How many hadiths a reader can actually open right now.
     *
     * Distinct from `total_hadith`, which is what the BOOK declares and never
     * changes when an editor publishes or unpublishes a row. Exposing them
     * under different names is what stops a template claiming content the site
     * does not serve.
     */
    public function availableHadithsCount(): int
    {
        return (int) $this->hadiths()->where('is_published', true)->count();
    }

    protected static function newFactory(): HadithCollectionFactory
    {
        return HadithCollectionFactory::new();
    }
}
