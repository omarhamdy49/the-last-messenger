<?php

declare(strict_types=1);

namespace App\Domain\Seerah\Models;

use App\Domain\Seerah\Enums\ParticipantRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Translatable\HasTranslations;

/**
 * SeerahParticipant Model
 *
 * @property int $id
 * @property int $event_id
 * @property string $participant_type
 * @property int|null $participant_id
 * @property array $name
 * @property ParticipantRole $role
 * @property array|null $description
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class SeerahParticipant extends Model
{
    use HasFactory, HasTranslations;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'seerah_participants';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'event_id',
        'participant_type',
        'participant_id',
        'name',
        'role',
        'description',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'name' => 'array',
        'description' => 'array',
        'role' => ParticipantRole::class,
    ];

    /**
     * Translatable attributes
     *
     * @var array<string>
     */
    public array $translatable = ['name', 'description'];

    /**
     * Get the event this participant belongs to
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(SeerahEvent::class, 'event_id');
    }

    /**
     * Scope: Filter by role
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByRole($query, ParticipantRole|string $role)
    {
        $roleValue = $role instanceof ParticipantRole ? $role->value : $role;

        return $query->where('role', $roleValue);
    }
}
