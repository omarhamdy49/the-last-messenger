<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Models;

use Database\Factories\Hadith\HadithCollectionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

/**
 * One of the 17 collections. `code` is the URL segment, the API path segment,
 * and the sunnah.com path segment all at once.
 *
 * No `total_hadiths` attribute: use `withCount('hadiths')`. A stored counter
 * drifts the moment admin CRUD exists, and there are only 17 rows.
 *
 * @property int $id
 * @property string $code
 * @property bool $has_books
 * @property bool $has_chapters
 * @property bool $has_volumes
 * @property string|null $reference_template
 * @property bool $is_published
 * @property bool $featured
 * @property int $display_order
 */
class HadithCollection extends Model
{
    use HasFactory, HasTranslations;

    protected $table = 'hadith_collections';

    /** @var array<string> */
    public array $translatable = ['name', 'author', 'short_intro', 'about', 'introduction'];

    /** @var array<string> */
    protected $fillable = [
        'code',
        'name',
        'author',
        'short_intro',
        'about',
        'introduction',
        'has_books',
        'has_chapters',
        'has_volumes',
        'reference_template',
        'is_published',
        'featured',
        'display_order',
        'source_url',
        'about_url',
        'introduction_url',
        'source_category',
        'source_capture_complete',
        'source_metadata',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'has_books' => 'boolean',
            'has_chapters' => 'boolean',
            'has_volumes' => 'boolean',
            'is_published' => 'boolean',
            'featured' => 'boolean',
            'display_order' => 'integer',
            'source_metadata' => 'array',
            'source_capture_complete' => 'boolean',
        ];
    }

    public function books(): HasMany
    {
        return $this->hasMany(HadithBook::class, 'collection_id');
    }

    /**
     * Reachable directly because `collection_id` is denormalised onto the
     * hadith row — the canonical lookup must not join through books.
     */
    public function hadiths(): HasMany
    {
        return $this->hasMany(Hadith::class, 'collection_id');
    }

    /**
     * Factories live under Database\Factories\Hadith, which Laravel cannot
     * derive from this model's namespace.
     */
    protected static function newFactory(): HadithCollectionFactory
    {
        return HadithCollectionFactory::new();
    }
}
