<?php

declare(strict_types=1);

namespace App\Domain\Quran\Models;

use App\Domain\Quran\Enums\RevelationPlace;
use App\Domain\Shared\Models\Location;
use App\Domain\Shared\Traits\HasLocalizedContent;
use Database\Factories\Quran\ChapterFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A chapter (surah). The primary key is the chapter number, 1–114.
 *
 * @property int $id
 * @property RevelationPlace $revelation_place
 * @property int $revelation_order
 * @property bool $bismillah_pre
 * @property string $name_simple
 * @property string $name_complex
 * @property string $name_arabic
 * @property int $verses_count
 * @property int $first_page
 * @property int $last_page
 * @property int|null $revelation_location_id
 * @property string|null $cover_image
 * @property bool $is_published
 */
class Chapter extends Model
{
    /** @use HasFactory<ChapterFactory> */
    use HasFactory, HasLocalizedContent;

    public const COUNT = 114;

    protected $table = 'quran_chapters';

    public $incrementing = false;

    protected $keyType = 'int';

    protected $fillable = [
        'id',
        'revelation_place',
        'revelation_order',
        'bismillah_pre',
        'name_simple',
        'name_complex',
        'name_arabic',
        'verses_count',
        'first_page',
        'last_page',
        'revelation_location_id',
        'cover_image',
        'is_published',
    ];

    protected $casts = [
        'id' => 'integer',
        'revelation_place' => RevelationPlace::class,
        'revelation_order' => 'integer',
        'bismillah_pre' => 'boolean',
        'verses_count' => 'integer',
        'first_page' => 'integer',
        'last_page' => 'integer',
        'revelation_location_id' => 'integer',
        'is_published' => 'boolean',
    ];

    protected static function newFactory(): ChapterFactory
    {
        return ChapterFactory::new();
    }

    public function getTranslationModelClass(): string
    {
        return ChapterTranslation::class;
    }

    public function getTranslatableFields(): array
    {
        return ['name'];
    }

    public function getTranslationForeignKey(): string
    {
        return 'chapter_id';
    }

    public function verses(): HasMany
    {
        return $this->hasMany(Verse::class, 'chapter_id')->orderBy('verse_number');
    }

    public function infos(): HasMany
    {
        return $this->hasMany(ChapterInfo::class, 'chapter_id');
    }

    public function audioFiles(): HasMany
    {
        return $this->hasMany(ChapterAudioFile::class, 'chapter_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'revelation_location_id');
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    /**
     * Localized name, falling back to the Arabic name QF supplies.
     */
    public function displayName(?string $locale = null): string
    {
        $locale ??= app()->getLocale();

        if ($locale === 'ar') {
            return $this->name_arabic;
        }

        return $this->getTranslation('name', $locale, false)
            ?? ($locale === 'en' ? $this->name_simple : null)
            ?? $this->name_arabic;
    }
}
