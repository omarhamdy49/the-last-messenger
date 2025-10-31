<?php

declare(strict_types=1);

namespace App\Domain\Shared\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Import Error Model
 *
 * Stores validation and processing errors from imports
 *
 * @property int $id
 * @property int $import_id
 * @property int $row_number
 * @property string|null $field_name
 * @property string $error_message
 * @property string $severity
 * @property array|null $row_data
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class ImportError extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'import_id',
        'row_number',
        'field_name',
        'error_message',
        'severity',
        'row_data',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'row_data' => 'array',
            'row_number' => 'integer',
        ];
    }

    /**
     * Get the import this error belongs to
     */
    public function import(): BelongsTo
    {
        return $this->belongsTo(Import::class);
    }

    /**
     * Check if this is an error (not a warning)
     */
    public function isError(): bool
    {
        return $this->severity === 'error';
    }

    /**
     * Check if this is a warning
     */
    public function isWarning(): bool
    {
        return $this->severity === 'warning';
    }
}
