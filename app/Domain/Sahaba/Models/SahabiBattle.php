<?php

declare(strict_types=1);

namespace App\Domain\Sahaba\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * SahabiBattle Model
 *
 * Pivot record linking a Sahabi to a named battle they participated in.
 *
 * @property int $id
 * @property int $sahabi_id
 * @property string $battle_name
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class SahabiBattle extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'sahabi_battles';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'sahabi_id',
        'battle_name',
    ];

    /**
     * Get the companion this battle record belongs to.
     */
    public function sahabi(): BelongsTo
    {
        return $this->belongsTo(Sahabi::class);
    }
}
