<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HadithThemeTranslation extends Model
{
    protected $fillable = ['hadith_theme_id', 'locale', 'name', 'description', 'is_ready'];

    protected $casts = ['is_ready' => 'boolean'];

    public $timestamps = false;

    public function hadithTheme(): BelongsTo
    {
        return $this->belongsTo(HadithTheme::class);
    }
}
