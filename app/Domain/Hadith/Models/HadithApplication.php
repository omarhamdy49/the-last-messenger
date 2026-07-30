<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HadithApplication extends Model
{
    protected $table = 'hadith_applications';

    protected $fillable = [
        'hadith_id',
        'category',
        'description',
        'example',
        'display_order',
        'review_status',
    ];

    protected $casts = [
        'hadith_id' => 'integer',
        'category' => 'array',
        'description' => 'array',
        'example' => 'array',
        'display_order' => 'integer',
    ];

    public function hadith(): BelongsTo
    {
        return $this->belongsTo(Hadith::class, 'hadith_id');
    }
}
