<?php

declare(strict_types=1);

namespace App\Domain\Quran\Models;

use Database\Factories\Quran\LanguageFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A QF content language (the language of translations/tafsirs), distinct from the
 * site UI languages in App\Domain\Shared\Models\Language.
 *
 * @property int $id
 * @property string $name
 * @property string|null $native_name
 * @property string $iso_code
 * @property string $direction ltr|rtl
 * @property int $translations_count
 * @property bool $is_active
 */
class Language extends Model
{
    /** @use HasFactory<LanguageFactory> */
    use HasFactory;

    protected $table = 'quran_languages';

    public $incrementing = false;

    protected $keyType = 'int';

    protected $fillable = ['id', 'name', 'native_name', 'iso_code', 'direction', 'translations_count', 'is_active'];

    protected $casts = [
        'id' => 'integer',
        'translations_count' => 'integer',
        'is_active' => 'boolean',
    ];

    protected static function newFactory(): LanguageFactory
    {
        return LanguageFactory::new();
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function isRtl(): bool
    {
        return $this->direction === 'rtl';
    }
}
