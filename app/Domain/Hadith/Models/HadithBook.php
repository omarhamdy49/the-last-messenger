<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

/**
 * `book_number` is a string: the source carries `35b`, `introduction` and
 * `letter`. Real ordering lives in `sort_order`, never in the number.
 *
 * `is_synthetic` marks the hidden placeholder each bookless collection needs —
 * all three forties, not just nawawi40 (research.md §2).
 *
 * @property int $id
 * @property int $collection_id
 * @property string $book_number
 * @property int $sort_order
 * @property string|null $first_hadith_number
 * @property string|null $last_hadith_number
 * @property bool $is_synthetic
 * @property bool $is_published
 */
class HadithBook extends Model
{
    use HasFactory, HasTranslations;

    protected $table = 'hadith_books';

    /** @var array<string> */
    public array $translatable = ['title', 'intro'];

    /** @var array<string> */
    protected $fillable = [
        'collection_id',
        'book_number',
        'sort_order',
        'title',
        'intro',
        'first_hadith_number',
        'last_hadith_number',
        'is_synthetic',
        'is_published',
        'source_url',
        'source_metadata',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'collection_id' => 'integer',
            'sort_order' => 'integer',
            'is_synthetic' => 'boolean',
            'is_published' => 'boolean',
            'source_metadata' => 'array',
        ];
    }

    public function collection(): BelongsTo
    {
        return $this->belongsTo(HadithCollection::class, 'collection_id');
    }

    public function chapters(): HasMany
    {
        return $this->hasMany(HadithChapter::class, 'book_id');
    }

    public function hadiths(): HasMany
    {
        return $this->hasMany(Hadith::class, 'book_id');
    }

    /**
     * Factories live under Database\Factories\Hadith, which Laravel cannot
     * derive from this model's namespace.
     */
    protected static function newFactory(): \Database\Factories\Hadith\HadithBookFactory
    {
        return \Database\Factories\Hadith\HadithBookFactory::new();
    }
}
