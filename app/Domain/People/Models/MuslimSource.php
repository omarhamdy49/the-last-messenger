<?php

declare(strict_types=1);

namespace App\Domain\People\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $muslim_id
 * @property string $source
 * @property string $external_id
 * @property string $source_url
 * @property string $dataset_hash
 * @property string|null $source_snapshot_hash
 * @property array<string, mixed>|null $metadata
 */
final class MuslimSource extends Model
{
    protected $table = 'muslim_sources';

    protected $fillable = [
        'muslim_id',
        'source',
        'external_id',
        'source_url',
        'dataset_hash',
        'source_snapshot_hash',
        'metadata',
    ];

    protected function casts(): array
    {
        return ['metadata' => 'array'];
    }

    public function muslim(): BelongsTo
    {
        return $this->belongsTo(Muslim::class, 'muslim_id');
    }
}
