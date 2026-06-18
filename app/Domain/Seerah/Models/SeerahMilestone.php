<?php

declare(strict_types=1);

namespace App\Domain\Seerah\Models;

use App\Domain\Shared\Traits\HasLocalizedContent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * SeerahMilestone Model (الحقبة)
 *
 * A high-level chronological era of the Prophetic biography that groups multiple
 * Seerah events and carries a Hijri + Gregorian date range plus a narrative story
 * title for the public hero-journey timeline.
 *
 * @property int $id
 * @property string $slug
 * @property int $order_index
 * @property string|null $icon
 * @property string|null $color
 * @property string|null $gradient
 * @property string|null $hijri_label_from
 * @property string|null $hijri_label_to
 * @property int|null $hijri_year_from
 * @property int|null $hijri_year_to
 * @property int|null $gregorian_year_from
 * @property int|null $gregorian_year_to
 * @property bool $is_active
 */
class SeerahMilestone extends Model
{
    use HasFactory, HasLocalizedContent;

    /**
     * @var string
     */
    protected $table = 'seerah_milestones';

    /**
     * @var array<string>
     */
    protected $fillable = [
        'slug',
        'order_index',
        'icon',
        'color',
        'gradient',
        'hijri_label_from',
        'hijri_label_to',
        'hijri_year_from',
        'hijri_year_to',
        'gregorian_year_from',
        'gregorian_year_to',
        'is_active',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'order_index' => 'integer',
        'hijri_year_from' => 'integer',
        'hijri_year_to' => 'integer',
        'gregorian_year_from' => 'integer',
        'gregorian_year_to' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Get the translation model class.
     */
    public function getTranslationModelClass(): string
    {
        return SeerahMilestoneTranslation::class;
    }

    /**
     * Get the list of translatable field names.
     */
    public function getTranslatableFields(): array
    {
        return ['title', 'subtitle', 'description'];
    }

    /**
     * Get the foreign key name for translations.
     */
    public function getTranslationForeignKey(): string
    {
        return 'seerah_milestone_id';
    }

    /**
     * Events grouped under this milestone.
     */
    public function events(): HasMany
    {
        return $this->hasMany(SeerahEvent::class, 'seerah_milestone_id');
    }

    /**
     * Scope: active milestones only.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: ordered chronologically (by explicit order then Hijri year).
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('order_index')->orderBy('hijri_year_from');
    }

    /**
     * Compact "from – to" Gregorian range label, e.g. "570 – 610 م".
     */
    public function gregorianRangeLabel(): ?string
    {
        if ($this->gregorian_year_from === null && $this->gregorian_year_to === null) {
            return null;
        }

        $from = $this->gregorian_year_from ?? '…';
        $to = $this->gregorian_year_to ?? '…';

        return "{$from} – {$to} م";
    }

    /**
     * Compact "from – to" Hijri range label using the human labels.
     */
    public function hijriRangeLabel(): ?string
    {
        if (! $this->hijri_label_from && ! $this->hijri_label_to) {
            return null;
        }

        return trim(($this->hijri_label_from ?? '').' – '.($this->hijri_label_to ?? ''));
    }
}
