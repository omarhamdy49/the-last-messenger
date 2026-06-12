<?php

declare(strict_types=1);

namespace App\Domain\Quran\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * RevelationContextTranslation Model
 *
 * Per-locale content for Asbab al-Nuzul entries.
 *
 * @property int $id
 * @property int $revelation_context_id
 * @property string $locale
 * @property string|null $title
 * @property string|null $narrative
 * @property string|null $source_reference
 * @property bool $is_ready
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class RevelationContextTranslation extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'quran_revelation_context_translations';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'revelation_context_id',
        'locale',
        'title',
        'narrative',
        'source_reference',
        'is_ready',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_ready' => 'boolean',
    ];

    /**
     * Get the parent revelation context.
     */
    public function revelationContext(): BelongsTo
    {
        return $this->belongsTo(RevelationContext::class, 'revelation_context_id');
    }
}
