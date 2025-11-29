<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HadithApplication extends Model
{
    protected $table = 'hadith_applications';

    protected $fillable = [
        'hadith_id', 'category_ar', 'category_en',
        'description_ar', 'description_en', 'display_order',
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
