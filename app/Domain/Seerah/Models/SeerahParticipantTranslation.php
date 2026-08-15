<?php

declare(strict_types=1);

namespace App\Domain\Seerah\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * SeerahParticipantTranslation Model
 *
 * Represents translations for Seerah event participants
 *
 * @property int $id
 * @property int $seerah_participant_id Reference to SeerahParticipant
 * @property string $locale Language locale (e.g., 'ar', 'en')
 * @property string|null $name Translated participant name
 * @property string|null $description Translated participant description
 * @property bool $is_ready Indicates if translation is complete
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class SeerahParticipantTranslation extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'seerah_participant_translations';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'seerah_participant_id',
        'locale',
        'name',
        'description',
        'is_ready',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'is_ready' => 'boolean',
    ];

    /**
     * Get the parent SeerahParticipant model
     */
    public function participant(): BelongsTo
    {
        return $this->belongsTo(SeerahParticipant::class, 'seerah_participant_id');
    }
}
