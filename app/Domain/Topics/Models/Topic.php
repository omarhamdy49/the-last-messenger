<?php

declare(strict_types=1);

namespace App\Domain\Topics\Models;

use App\Domain\Shared\Traits\HasLocalizedContent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Topic Model
 *
 * A cross-module curated theme (e.g. الصبر / Patience) that aggregates real
 * Quran verses, hadiths and articles via polymorphic {@see TopicItem} rows.
 *
 * Translatable title/description live in `topic_translations`, mirroring the
 * SeerahEvent translatable pattern exactly (HasLocalizedContent + a dedicated
 * translation model).
 *
 * @property int $id
 * @property string $slug
 * @property string $icon
 * @property string|null $color
 * @property bool $is_featured
 * @property int $display_order
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Collection<int, TopicTranslation> $translations
 * @property-read Collection<int, TopicItem> $items
 */
class Topic extends Model
{
    use HasFactory, HasLocalizedContent;

    protected $table = 'topics';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'slug',
        'icon',
        'color',
        'is_featured',
        'display_order',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'is_featured' => 'boolean',
        'display_order' => 'integer',
    ];

    /**
     * FQCN of the translation model (HasLocalizedContent contract).
     */
    public function getTranslationModelClass(): string
    {
        return TopicTranslation::class;
    }

    /**
     * Translatable field names (HasLocalizedContent contract).
     *
     * @return array<int, string>
     */
    public function getTranslatableFields(): array
    {
        return ['title', 'description'];
    }

    /**
     * FK column on the translations table (HasLocalizedContent contract).
     */
    public function getTranslationForeignKey(): string
    {
        return 'topic_id';
    }

    /**
     * Polymorphic content items attached to this topic.
     */
    public function items(): HasMany
    {
        return $this->hasMany(TopicItem::class, 'topic_id');
    }

    /**
     * Scope: featured topics first, then by display order.
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeOrdered($query)
    {
        return $query->orderByDesc('is_featured')->orderBy('display_order')->orderBy('id');
    }
}
