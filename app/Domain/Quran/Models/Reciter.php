<?php

declare(strict_types=1);

namespace App\Domain\Quran\Models;

use App\Domain\Quran\Services\Audio\RecordingSourceService;
use App\Domain\Quran\Services\QuranCacheKeys;
use App\Domain\Shared\Traits\HasLocalizedContent;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Reciter Model (Quran Reciter/Qari)
 *
 * @property int $id
 * @property string $slug
 * @property array $name
 * @property array|null $description
 * @property string|null $style
 * @property string|null $riwayah
 * @property string|null $country
 * @property string|null $profile_image
 * @property bool $is_featured
 * @property bool $is_active
 * @property int $display_order
 * @property int $followers_count
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Reciter extends Model
{
    use HasFactory, HasLocalizedContent;

    /**
     * The table associated with the model.
     */
    protected $table = 'quran_reciters';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'slug',
        'profile_id',
        'kind',
        'style',
        'riwayah',
        'country',
        'profile_image',
        'is_featured',
        'is_active',
        'display_order',
        'followers_count',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
        'display_order' => 'integer',
        'followers_count' => 'integer',
    ];

    /**
     * Get the FQCN of the translation model.
     */
    public function getTranslationModelClass(): string
    {
        return ReciterTranslation::class;
    }

    /**
     * Get the list of translatable field names.
     */
    public function getTranslatableFields(): array
    {
        return ['name', 'description'];
    }

    /**
     * Boot the model
     */
    protected static function boot(): void
    {
        parent::boot();

        static::saved(function (self $recording): void {
            if ($recording->wasChanged('slug') && $recording->getOriginal('slug')) {
                DB::table('quran_reciter_aliases')->updateOrInsert(['slug' => $recording->getOriginal('slug')], ['recording_id' => $recording->id, 'created_at' => now(), 'updated_at' => now()]);
            }
            QuranCacheKeys::flushReciters();
        });

        static::creating(function ($reciter) {
            if (empty($reciter->slug)) {
                $name = $reciter->getTranslation('name', 'en') ?? $reciter->getTranslation('name', 'ar');
                $reciter->slug = Str::slug($name);
            }
        });
    }

    protected $with = ['sources'];

    public function profile(): BelongsTo
    {
        return $this->belongsTo(ReciterProfile::class, 'profile_id');
    }

    public function recordingLabel(string $locale): string
    {
        $style = $this->style ? __('quran.recordings.'.$this->style, [], $locale) : __('quran.recordings.unspecified', [], $locale);

        return $style.($this->riwayah ? ' · '.$this->riwayah : '');
    }

    public function sources(): HasMany
    {
        return $this->hasMany(RecordingSource::class, 'recording_id');
    }

    public function verseTemplate(): ?string
    {
        return app(RecordingSourceService::class)->verseTemplate($this);
    }

    /**
     * Get all recitations recorded by this reciter.
     */
    public function recitations(): HasMany
    {
        return $this->hasMany(Recitation::class, 'reciter_id');
    }

    /**
     * Get display name based on language
     */
    public function getDisplayName(string $lang = 'ar'): string
    {
        return $this->getTranslation('name', $lang) ?? $this->getTranslation('name', 'ar') ?? '';
    }

    /**
     * Get description based on language
     */
    public function getDescriptionText(string $lang = 'ar'): ?string
    {
        return $this->getTranslation('description', $lang);
    }

    /**
     * Get profile image URL
     */
    public function getProfileImageUrlAttribute(): ?string
    {
        if (! $this->profile_image) {
            return null;
        }

        // Portraits sourced from Wikimedia Commons are stored as absolute URLs;
        // uploaded files stay relative to the public storage disk.
        if (str_starts_with($this->profile_image, 'https://') || str_starts_with($this->profile_image, 'http://')) {
            return $this->profile_image;
        }

        return asset('storage/'.$this->profile_image);
    }

    /**
     * Scope: Featured reciters only
     *
     * @param  Builder  $query
     */
    public function scopeFeatured($query): Builder
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope: Active reciters only
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Quran reciters only, excluding recited translations.
     *
     * The everyayah catalogue mixes both, and a translation recording must never
     * appear where a listener is choosing a qari.
     */
    public function scopeRecitationsOnly(Builder $query): Builder
    {
        return $query->where('kind', 'recitation');
    }

    /**
     * Scope: Order by display order
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order');
    }

    /**
     * Scope: Filter by country
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeByCountry($query, string $country)
    {
        return $query->where('country', $country);
    }

    /**
     * Scope: Filter by style
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeByStyle($query, string $style)
    {
        return $query->where('style', $style);
    }

    // scopeSearch() was removed: it had zero callers anywhere in app/ or tests/
    // (every ->search() call in the codebase is on a repository or service, never on
    // this model's builder), and it was JSON_EXTRACT(name, '$.ar') against a column that no longer exists — the table is
    // `quran_reciters` and reciter names live in `reciter_translations`. Calling this
    // would have raised "Unknown column 'name'".
    // Searching this data goes through the repository, which uses
    // FullTextSearchHelper / a constrained whereHas instead.
}
