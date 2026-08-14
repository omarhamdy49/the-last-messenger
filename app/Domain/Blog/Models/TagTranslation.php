<?php

declare(strict_types=1);

namespace App\Domain\Blog\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * TagTranslation Model
 * Translatable fields: name, description
 *
 * @property int $id
 * @property int $tag_id
 * @property string $locale
 * @property string $name
 * @property string|null $description
 * @property bool $is_ready
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class TagTranslation extends Model
{
    protected $fillable = [
        'tag_id',
        'locale',
        'name',
        'description',
        'is_ready',
    ];

    protected $casts = [
        'is_ready' => 'boolean',
    ];

    public function tag(): BelongsTo
    {
        return $this->belongsTo(Tag::class);
    }

    public function scopeByLocale($query, string $locale)
    {
        return $query->where('locale', $locale);
    }

    public function scopeReady($query)
    {
        return $query->where('is_ready', true);
    }
}
