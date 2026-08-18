<?php

declare(strict_types=1);

namespace App\Domain\Shared\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * LanguageTranslation Model
 *
 * Represents translations for language names
 *
 * @property int $id
 * @property int $language_id Reference to Language
 * @property string $locale Language locale (e.g., 'ar', 'en')
 * @property string|null $name Translated language name
 * @property bool $is_ready Indicates if translation is complete
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class LanguageTranslation extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'language_translations';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'language_id',
        'locale',
        'name',
        'is_ready',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'is_ready' => 'boolean',
    ];

    /**
     * Get the parent Language model
     */
    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class, 'language_id');
    }
}
