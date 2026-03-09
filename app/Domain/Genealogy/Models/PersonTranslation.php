<?php

declare(strict_types=1);

namespace App\Domain\Genealogy\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * PersonTranslation Model
 *
 * @property int $id
 * @property int $person_id Reference to Person
 * @property string $locale Language locale (e.g., 'ar', 'en')
 * @property string|null $name Translated name
 * @property string|null $title Translated title
 * @property string|null $biography Translated biography
 * @property string|null $birth_place Translated birth place
 * @property string|null $death_place Translated death place
 * @property string|null $burial_place Translated burial place
 * @property string|null $occupation Translated occupation
 * @property string|null $notes Translated notes
 * @property bool $is_ready Indicates if translation is complete
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class PersonTranslation extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'person_translations';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'person_id',
        'locale',
        'name',
        'title',
        'biography',
        'birth_place',
        'death_place',
        'burial_place',
        'occupation',
        'notes',
        'is_ready',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'is_ready' => 'boolean',
    ];

    /**
     * Get the parent Person model
     */
    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'person_id');
    }
}
