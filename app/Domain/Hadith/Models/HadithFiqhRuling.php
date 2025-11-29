<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HadithFiqhRuling extends Model
{
    protected $table = 'hadith_fiqh_rulings';

    protected $fillable = [
        'hadith_id', 'principle', 'language_code', 'display_order',
    ];

    protected $casts = [
        'hadith_id' => 'integer',
        'display_order' => 'integer',
    ];

    public function hadith(): BelongsTo
    {
        return $this->belongsTo(Hadith::class);
    }
}
