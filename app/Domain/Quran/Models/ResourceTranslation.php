<?php

declare(strict_types=1);

namespace App\Domain\Quran\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Per-locale resource name and description.
 *
 * @property int $id
 * @property int $resource_id
 * @property string $locale
 * @property string $name
 * @property string|null $description
 * @property bool $is_ready
 */
class ResourceTranslation extends Model
{
    protected $table = 'quran_resource_translations';

    protected $fillable = ['resource_id', 'locale', 'name', 'description', 'is_ready'];

    protected $casts = [
        'resource_id' => 'integer',
        'is_ready' => 'boolean',
    ];

    public function resource(): BelongsTo
    {
        return $this->belongsTo(QuranResource::class, 'resource_id');
    }
}
