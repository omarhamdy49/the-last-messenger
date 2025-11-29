<?php

namespace App\Domain\Hadith\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

/**
 * Class HadithIsnad
 *
 * Represents the chain of narration (إسناد/سند) for a Hadith.
 * Each record is one link in the chain connecting narrators from the Prophet ﷺ to the compiler.
 *
 * @property int $id
 * @property int $hadith_id
 * @property int $order_number Position in the chain (1 = closest to compiler, higher = closer to Prophet)
 * @property int $narrator_id
 * @property string|null $verb Narration verb (e.g., حدثنا، أخبرنا، سمعت)
 * @property string|null $narrated_from Source of narration
 *
 * @property-read \App\Domain\Hadith\Models\Hadith $hadith
 * @property-read \App\Domain\Hadith\Models\HadithNarrator $narrator
 *
 * @method static Builder orderedByChain()
 * @method static Builder forHadith(int $hadithId)
 * @method static Builder byNarrator(int $narratorId)
 * @method static Builder byVerb(string $verb)
 */
class HadithIsnad extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'hadith_isnad';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'hadith_id',
        'narrator_id',
        'position_in_chain',
        'transmission_method',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'hadith_id' => 'integer',
        'order_number' => 'integer',
        'narrator_id' => 'integer',
    ];

    /**
     * Get the hadith that this isnad belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function hadith(): BelongsTo
    {
        return $this->belongsTo(Hadith::class, 'hadith_id');
    }

    /**
     * Get the narrator for this link in the chain.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function narrator(): BelongsTo
    {
        return $this->belongsTo(HadithNarrator::class, 'narrator_id');
    }

    /**
     * Scope to order isnad by chain position.
     * Order 1 = closest to the compiler, higher numbers = closer to the Prophet ﷺ
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $direction
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrderedByChain(Builder $query, string $direction = 'asc'): Builder
    {
        return $query->orderBy('order_number', $direction);
    }

    /**
     * Scope to filter isnad by hadith.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $hadithId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForHadith(Builder $query, int $hadithId): Builder
    {
        return $query->where('hadith_id', $hadithId);
    }

    /**
     * Scope to filter isnad by narrator.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $narratorId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByNarrator(Builder $query, int $narratorId): Builder
    {
        return $query->where('narrator_id', $narratorId);
    }

    /**
     * Scope to filter isnad by narration verb.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $verb
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByVerb(Builder $query, string $verb): Builder
    {
        return $query->where('verb', $verb);
    }

    /**
     * Scope to get isnad with narrator relationship.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithNarrator(Builder $query): Builder
    {
        return $query->with('narrator');
    }

    /**
     * Scope to get isnad with hadith relationship.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithHadith(Builder $query): Builder
    {
        return $query->with('hadith');
    }

    /**
     * Get the complete chain of narration for a hadith.
     * Returns collection ordered from compiler to Prophet ﷺ
     *
     * @param int $hadithId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getChainForHadith(int $hadithId)
    {
        return static::forHadith($hadithId)
            ->orderedByChain()
            ->withNarrator()
            ->get();
    }

    /**
     * Get the next narrator in the chain (higher order number).
     *
     * @return \App\Domain\Hadith\Models\HadithIsnad|null
     */
    public function getNextNarrator(): ?HadithIsnad
    {
        return static::where('hadith_id', $this->hadith_id)
            ->where('order_number', '>', $this->order_number)
            ->orderBy('order_number', 'asc')
            ->first();
    }

    /**
     * Get the previous narrator in the chain (lower order number).
     *
     * @return \App\Domain\Hadith\Models\HadithIsnad|null
     */
    public function getPreviousNarrator(): ?HadithIsnad
    {
        return static::where('hadith_id', $this->hadith_id)
            ->where('order_number', '<', $this->order_number)
            ->orderBy('order_number', 'desc')
            ->first();
    }

    /**
     * Check if this is the first narrator in the chain (closest to compiler).
     *
     * @return bool
     */
    public function isFirstInChain(): bool
    {
        return $this->order_number === 1;
    }

    /**
     * Get the formatted isnad link text.
     * Format: "{verb} {narrator_name}"
     *
     * @return string
     */
    public function getFormattedText(): string
    {
        $narrator = $this->narrator;
        $name = $narrator ? $narrator->getDisplayName() : '';

        if ($this->verb) {
            return $this->verb . ' ' . $name;
        }

        return $name;
    }
}
