<?php

declare(strict_types=1);

namespace App\Domain\Seerah\Models;

use App\Domain\Shared\Traits\HasLocalizedContent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * SeerahMilestone Model (الحقبة)
 *
 * A high-level chronological era of the Prophetic biography that groups multiple
 * Seerah events and carries a Hijri + Gregorian year range plus a narrative story
 * title for the public hero-journey timeline.
 *
 * @property int $id
 * @property string $slug
 * @property int $order
 * @property array{icon?: string, color?: string, gradient?: string}|null $metadata
 * @property int|null $hijri_from Negative = before the Hijra.
 * @property int|null $hijri_to
 * @property int|null $gregorian_from
 * @property int|null $gregorian_to
 * @property bool $is_active
 * @property-read string|null $icon
 * @property-read string|null $color
 * @property-read string|null $gradient
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
        'order',
        'metadata',
        'hijri_from',
        'hijri_to',
        'gregorian_from',
        'gregorian_to',
        'is_active',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'order' => 'integer',
        'metadata' => 'array',
        'hijri_from' => 'integer',
        'hijri_to' => 'integer',
        'gregorian_from' => 'integer',
        'gregorian_to' => 'integer',
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
        return 'sm_id';
    }

    /**
     * Events grouped under this milestone.
     */
    public function events(): HasMany
    {
        return $this->hasMany(SeerahEvent::class, 'sm_id');
    }

    /**
     * Look-and-feel lives in `metadata`; these keep `$milestone->icon` readable.
     */
    protected function icon(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->metadata['icon'] ?? null);
    }

    protected function color(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->metadata['color'] ?? null);
    }

    protected function gradient(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->metadata['gradient'] ?? null);
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
        return $query->orderBy('order')->orderBy('hijri_from');
    }

    /**
     * Compact "from – to" Gregorian range label, e.g. "570 – 610 م".
     */
    public function gregorianRangeLabel(): ?string
    {
        if ($this->gregorian_from === null && $this->gregorian_to === null) {
            return null;
        }

        $from = $this->gregorian_from ?? '…';
        $to = $this->gregorian_to ?? '…';

        return "{$from} – {$to} م";
    }

    /**
     * Compact "from – to" Hijri range label derived from the year numbers,
     * e.g. "53 ق.هـ – 1 هـ".
     */
    public function hijriRangeLabel(): ?string
    {
        if ($this->hijri_from === null && $this->hijri_to === null) {
            return null;
        }

        return trim(self::hijriYearLabel($this->hijri_from).' – '.self::hijriYearLabel($this->hijri_to));
    }

    /**
     * "3 هـ" / "13 ق.هـ"; negative years are before the Hijra.
     */
    public static function hijriYearLabel(?int $year): string
    {
        if ($year === null) {
            return '…';
        }

        return $year < 0
            ? abs($year).' '.__('seerah.public.before_hijra_suffix')
            : $year.' '.__('seerah.public.hijri_suffix');
    }
}
