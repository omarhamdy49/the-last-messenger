<?php

declare(strict_types=1);

namespace App\Domain\Shared\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Import Mapping Model
 *
 * Stores column mapping configuration for imports
 *
 * @property int $id
 * @property int $import_id
 * @property string $csv_column
 * @property string $db_field
 * @property string|null $transform_rule
 * @property int $column_index
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class ImportMapping extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'import_id',
        'csv_column',
        'db_field',
        'transform_rule',
        'column_index',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'column_index' => 'integer',
        ];
    }

    /**
     * Get the import this mapping belongs to
     */
    public function import(): BelongsTo
    {
        return $this->belongsTo(Import::class);
    }
}
