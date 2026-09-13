<?php

declare(strict_types=1);

namespace App\Domain\Quran\Models;

use App\Domain\Quran\Services\QuranCacheKeys;
use Illuminate\Database\Eloquent\Model;

class ReciterProfileTranslation extends Model
{
    protected $table = 'quran_reciter_profile_translations';

    protected $fillable = ['profile_id', 'locale', 'name', 'description'];

    protected static function booted(): void
    {
        static::saved(fn () => QuranCacheKeys::flushReciters());
    }
}
