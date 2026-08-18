<?php

declare(strict_types=1);

namespace App\Domain\Quran\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ReciterTranslation Model
 *
 * Represents translations for Quran reciters
 *
 * @property int $id
 * @property int $reciter_id Reference to Reciter
 * @property string $locale Language locale (e.g., 'ar', 'en')
 * @property string|null $name Translated reciter name
 * @property string|null $description Translated reciter description
 * @property bool $is_ready Indicates if translation is complete
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class ReciterTranslation extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'reciter_translations';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'reciter_id',
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
     * Get the parent Reciter model
     */
    public function reciter(): BelongsTo
    {
        return $this->belongsTo(Reciter::class, 'reciter_id');
    }
}
