<?php

declare(strict_types=1);

namespace App\Domain\Seerah\Models;

use App\Domain\Seerah\Enums\ParticipantRole;
use App\Domain\Shared\Traits\HasLocalizedContent;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class SeerahParticipant extends Model
{
    use HasFactory, HasLocalizedContent;

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
        'role',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'role' => ParticipantRole::class,
    ];

    /**
     * Get the FQCN of the translation model.
     */
    public function getTranslationModelClass(): string
    {
        return SeerahParticipantTranslation::class;
    }

    /**
     * Get the list of translatable field names.
     */
    public function getTranslatableFields(): array
    {
        return ['name', 'description'];
    }

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
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeByRole($query, ParticipantRole|string $role)
    {
        $roleValue = $role instanceof ParticipantRole ? $role->value : $role;

        return $query->where('role', $roleValue);
    }
}
