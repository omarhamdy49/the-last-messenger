<?php

declare(strict_types=1);

namespace App\Domain\Blog\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * CategoryTranslation Model
 * Translatable fields: name, description
 *
 * @property int $id
 * @property int $category_id
 * @property string $locale
 * @property string $name
 * @property string|null $description
 * @property bool $is_ready
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class CategoryTranslation extends Model
{
    protected $fillable = [
        'category_id',
        'locale',
        'name',
        'description',
        'is_ready',
    ];

    protected $casts = [
        'is_ready' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
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
