<?php

declare(strict_types=1);

namespace App\Domain\Quran\Models;

use App\Domain\Quran\Enums\RevelationPlace;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

/**
 * Surah Model (Quran Chapter)
 *
 * @property int $id
 * @property int $number
 * @property array $name
 * @property array|null $meaning
 * @property RevelationPlace $revelation_place
 * @property int|null $revelation_order
 * @property int $verses_count
 * @property int|null $words_count
 * @property int|null $letters_count
 * @property array|null $juz
 * @property array|null $description
 * @property array|null $theme
 * @property array|null $audio_urls
 * @property int|null $page_number
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Surah extends Model
{
    use HasFactory, HasTranslations;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'quran_surahs';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'number',
        'name',
        'meaning',
        'revelation_place',
        'revelation_order',
        'verses_count',
        'words_count',
        'letters_count',
        'juz',
        'description',
        'theme',
        'audio_urls',
        'page_number',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'number' => 'integer',
        'name' => 'array',
        'meaning' => 'array',
        'revelation_place' => RevelationPlace::class,
        'revelation_order' => 'integer',
        'verses_count' => 'integer',
        'words_count' => 'integer',
        'letters_count' => 'integer',
        'juz' => 'array',
        'description' => 'array',
        'theme' => 'array',
        'audio_urls' => 'array',
        'page_number' => 'integer',
    ];

    /**
     * Translatable attributes
     *
     * @var array<string>
     */
    public array $translatable = [
        'name',
        'meaning',
        'description',
        'theme',
    ];

    /**
     * Get all verses in this surah
     *
     * @return HasMany
     */
    public function verses(): HasMany
    {
        return $this->hasMany(Verse::class, 'surah_id')->orderBy('verse_number');
    }

    /**
     * Check if surah is Makkan
     *
     * @return bool
     */
    public function isMakkan(): bool
    {
        return $this->revelation_place === RevelationPlace::MAKKAH;
    }

    /**
     * Check if surah is Madinan
     *
     * @return bool
     */
    public function isMadinan(): bool
    {
        return $this->revelation_place === RevelationPlace::MADINAH;
    }

    /**
     * Get display name
     *
     * @param string $language
     * @return string
     */
    public function getDisplayName(string $language = 'ar'): string
    {
        return $this->name[$language] ?? $this->name['ar'] ?? '';
    }

    /**
     * Scope: Filter by revelation place
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param RevelationPlace|string $place
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByRevelationPlace($query, RevelationPlace|string $place)
    {
        $placeValue = $place instanceof RevelationPlace ? $place->value : $place;
        return $query->where('revelation_place', $placeValue);
    }

    /**
     * Scope: Order by revelation order
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByRevelationOrder($query)
    {
        return $query->orderBy('revelation_order');
    }

    /**
     * Scope: Filter by Juz
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $juz
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByJuz($query, int $juz)
    {
        return $query->whereJsonContains('juz', (string) $juz);
    }
}

