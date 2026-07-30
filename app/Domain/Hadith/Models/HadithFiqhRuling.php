<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HadithFiqhRuling extends Model
{
    protected $table = 'hadith_fiqh_rulings';

    protected $fillable = [
        'hadith_id',
        'scholar_id',
        'type',
        'title',
        'ruling',
        'details',
        'display_order',
        'review_status',
    ];

    protected $casts = [
        'hadith_id' => 'integer',
        'scholar_id' => 'integer',
        'title' => 'array',
        'ruling' => 'array',
        'details' => 'array',
        'display_order' => 'integer',
    ];

    public function hadith(): BelongsTo
    {
        return $this->belongsTo(Hadith::class, 'hadith_id');
    }

    public function scholar(): BelongsTo
    {
        return $this->belongsTo(HadithScholar::class, 'scholar_id');
    }
}
