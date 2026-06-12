<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HadithSourceTranslation extends Model
{
    protected $fillable = ['hadith_source_id', 'locale', 'name', 'description', 'is_ready'];

    protected $casts = ['is_ready' => 'boolean'];

    public $timestamps = false;

    public function hadithSource(): BelongsTo
    {
        return $this->belongsTo(HadithSource::class);
    }
}
