<?php

declare(strict_types=1);

namespace App\Domain\Quran\Models;

use App\Domain\Quran\Enums\ExternalSource;
use App\Domain\Quran\Enums\RecitationStyle;
use App\Domain\Quran\Enums\ResourceKind;
use App\Domain\Quran\Enums\ResourceSource;
use App\Domain\Shared\Traits\HasLocalizedContent;
use Database\Factories\Quran\ResourceFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * One catalogue resource of any kind: a translation, a tafsir, a chapter-info source,
 * a verse-media source, an ayah-by-ayah recitation, a chapter reciter, or a
 * word-by-word translation/transliteration.
 *
 * @property int $id
 * @property ResourceKind $kind
 * @property int|null $parent_id the reciter this row is filed under as one of their recordings
 * @property int|null $qf_id QF resource id (sync resource_id); null for manual rows
 * @property int|null $qf_content_id
 * @property ResourceSource $source
 * @property ExternalSource|null $external_source
 * @property string|null $external_ref_id
 * @property string $slug
 * @property string $name
 * @property string|null $author_name
 * @property string|null $language_iso
 * @property RecitationStyle|null $style
 * @property string|null $qirat
 * @property string|null $info
 * @property string|null $image
 * @property bool $is_active
 * @property bool $is_default
 * @property int $display_order
 * @property bool $sync_enabled
 * @property bool $store_audio_locally
 * @property int $records_count
 * @property Carbon|null $last_synced_at
 * @property Carbon|null $last_fetched_at
 * @property array<string, mixed>|null $last_fetch_report
 * @property array<string, mixed>|null $metadata
 */
class QuranResource extends Model
{
    /** @use HasFactory<ResourceFactory> */
    use HasFactory, HasLocalizedContent;

    protected $table = 'quran_resources';

    protected $fillable = [
        'kind', 'qf_id', 'qf_content_id', 'source', 'external_source', 'external_ref_id', 'slug', 'name',
        'author_name', 'language_iso', 'style', 'qirat', 'info', 'image', 'is_active', 'is_default',
        'display_order', 'sync_enabled', 'store_audio_locally', 'records_count', 'last_synced_at',
        'last_fetched_at', 'last_fetch_report', 'metadata',
    ];

    protected $casts = [
        'kind' => ResourceKind::class,
        'parent_id' => 'integer',
        'qf_id' => 'integer',
        'qf_content_id' => 'integer',
        'source' => ResourceSource::class,
        'external_source' => ExternalSource::class,
        'style' => RecitationStyle::class,
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'display_order' => 'integer',
        'sync_enabled' => 'boolean',
        'store_audio_locally' => 'boolean',
        'records_count' => 'integer',
        'last_synced_at' => 'datetime',
        'last_fetched_at' => 'datetime',
        'last_fetch_report' => 'array',
        'metadata' => 'array',
    ];

    protected static function newFactory(): ResourceFactory
    {
        return ResourceFactory::new();
    }

    public function getTranslationModelClass(): string
    {
        return ResourceTranslation::class;
    }

    public function getTranslatableFields(): array
    {
        return ['name', 'description'];
    }

    public function getTranslationForeignKey(): string
    {
        return 'resource_id';
    }

    /**
     * The reciter this recording is filed under; null for a reciter in their own right.
     *
     * @return BelongsTo<QuranResource, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * Other rows filed under this reciter as their recordings (one level only).
     *
     * @return HasMany<QuranResource, $this>
     */
    public function recordings(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('display_order')->orderBy('name');
    }

    public function translationRows(): HasMany
    {
        return $this->hasMany(Translation::class, 'resource_id');
    }

    public function tafsirRows(): HasMany
    {
        return $this->hasMany(Tafsir::class, 'resource_id');
    }

    public function chapterInfos(): HasMany
    {
        return $this->hasMany(ChapterInfo::class, 'resource_id');
    }

    public function audioFiles(): HasMany
    {
        return $this->hasMany(AudioFile::class, 'resource_id');
    }

    public function chapterAudioFiles(): HasMany
    {
        return $this->hasMany(ChapterAudioFile::class, 'resource_id');
    }

    public function wordGlosses(): HasMany
    {
        return $this->hasMany(WordGloss::class, 'resource_id');
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeOfKind(Builder $query, ResourceKind $kind): Builder
    {
        return $query->where('kind', $kind->value);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeSyncEnabled(Builder $query): Builder
    {
        return $query->where('sync_enabled', true)->whereNotNull('qf_id');
    }

    /**
     * Localized display name: translated name, else the canonical name.
     */
    public function displayName(?string $locale = null): string
    {
        return $this->getTranslation('name', $locale ?? app()->getLocale(), false) ?? $this->name;
    }

    public function isFromQf(): bool
    {
        return $this->source === ResourceSource::QF;
    }
}
