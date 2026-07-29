<?php

namespace App\Domain\Hadith\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class HadithIsnad
 *
 * Represents the chain of narration (إسناد/سند) for a Hadith.
 * Each record is one link in the chain connecting narrators from the Prophet ﷺ to the compiler.
 *
 * @property int $id
 * @property int $hadith_id
 * @property int $position_in_chain Position in the chain (1 = nearest the Prophet)
 * @property int $narrator_id
 * @property string|null $transmission_method Narration verb (e.g., حدثنا، أخبرنا، سمعت)
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
        'position_in_chain' => 'integer',
        'narrator_id' => 'integer',
    ];

    /**
     * Get the hadith that this isnad belongs to.
     */
    public function hadith(): BelongsTo
    {
        return $this->belongsTo(Hadith::class, 'hadith_id');
    }

    /**
     * Get the narrator for this link in the chain.
     */
    public function narrator(): BelongsTo
    {
        return $this->belongsTo(HadithNarrator::class, 'narrator_id');
    }

    /**
     * Scope to order isnad by chain position.
     * Position 1 is nearest the Prophet; higher numbers move toward the compiler.
     */
    public function scopeOrderedByChain(Builder $query, string $direction = 'asc'): Builder
    {
        return $query->orderBy('position_in_chain', $direction);
    }

    /**
     * Scope to filter isnad by hadith.
     */
    public function scopeForHadith(Builder $query, int $hadithId): Builder
    {
        return $query->where('hadith_id', $hadithId);
    }

    /**
     * Scope to filter isnad by narrator.
     */
    public function scopeByNarrator(Builder $query, int $narratorId): Builder
    {
        return $query->where('narrator_id', $narratorId);
    }

    /**
     * Scope to filter isnad by narration verb.
     */
    public function scopeByVerb(Builder $query, string $verb): Builder
    {
        return $query->where('transmission_method', $verb);
    }

    /**
     * Scope to get isnad with narrator relationship.
     */
    public function scopeWithNarrator(Builder $query): Builder
    {
        return $query->with('narrator');
    }

    /**
     * Scope to get isnad with hadith relationship.
     */
    public function scopeWithHadith(Builder $query): Builder
    {
        return $query->with('hadith');
    }

    /**
     * Get the complete chain of narration for a hadith.
     * Returns the chain ordered from the Prophet toward the compiler.
     *
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
     */
    public function getNextNarrator(): ?HadithIsnad
    {
        return static::where('hadith_id', $this->hadith_id)
            ->where('position_in_chain', '>', $this->position_in_chain)
            ->orderBy('position_in_chain', 'asc')
            ->first();
    }

    /**
     * Get the previous narrator in the chain (lower order number).
     */
    public function getPreviousNarrator(): ?HadithIsnad
    {
        return static::where('hadith_id', $this->hadith_id)
            ->where('position_in_chain', '<', $this->position_in_chain)
            ->orderBy('position_in_chain', 'desc')
            ->first();
    }

    /**
     * Check if this is the first narrator in the chain (nearest the Prophet).
     */
    public function isFirstInChain(): bool
    {
        return $this->position_in_chain === 1;
    }

    /**
     * Get the formatted isnad link text.
     * Format: "{verb} {narrator_name}"
     */
    public function getFormattedText(): string
    {
        $narrator = $this->narrator;
        $name = $narrator ? $narrator->getDisplayName() : '';

        if ($this->transmission_method) {
            return $this->transmission_method.' '.$name;
        }

        return $name;
    }
}
