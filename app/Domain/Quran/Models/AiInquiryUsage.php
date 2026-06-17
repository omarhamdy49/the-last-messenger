<?php

declare(strict_types=1);

namespace App\Domain\Quran\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Per-user, per-calendar-month quota counter for the "Ask Quran" AI feature.
 *
 * The (user_id, period) unique key backs the race-safe atomic reservation in
 * AiInquiryQuotaRepository. `period` is 'YYYY-MM'.
 *
 * @property int $id
 * @property int $user_id
 * @property string $period
 * @property int $count
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class AiInquiryUsage extends Model
{
    use HasFactory;

    protected $table = 'ai_inquiry_usages';

    /**
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'period',
        'count',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'count' => 'integer',
    ];

    // User referenced by string (not ::class): DomainQuran may only depend on
    // Shared, so importing App\Models\User would cross a module boundary.
    public function user(): BelongsTo
    {
        return $this->belongsTo('App\Models\User', 'user_id');
    }
}
