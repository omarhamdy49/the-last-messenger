<?php

declare(strict_types=1);

namespace App\Domain\Seerah\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * SeerahEventTypeTranslation Model
 *
 * Stores per-locale names for SeerahEventType.
 *
 * @property int $id
 * @property int $seerah_event_type_id
 * @property string $locale
 * @property string|null $name
 * @property bool $is_ready
 */
class SeerahEventTypeTranslation extends Model
{
    /**
     * @var string
     */
    protected $table = 'seerah_event_type_translations';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'seerah_event_type_id',
        'locale',
        'name',
        'is_ready',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'seerah_event_type_id' => 'integer',
        'is_ready' => 'boolean',
    ];

    /**
     * Get the parent event type.
     */
    public function eventType(): BelongsTo
    {
        return $this->belongsTo(SeerahEventType::class, 'seerah_event_type_id');
    }
}
