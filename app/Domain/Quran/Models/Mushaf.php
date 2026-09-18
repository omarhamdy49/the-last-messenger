<?php

declare(strict_types=1);

namespace App\Domain\Quran\Models;

use Database\Factories\Quran\MushafFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * A mushaf layout (QF mushaf id). Pages and word positions come from the `mushafs`
 * sync snapshot; font files from `quran:qf:download-assets`.
 *
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property int $pages_count
 * @property int $lines_per_page
 * @property string|null $default_font_name
 * @property string|null $mapping_mode
 * @property string|null $qirat_name
 * @property string|null $font_version v1|v2|hafs|indopak
 * @property bool $is_default
 * @property bool $is_active
 * @property Carbon|null $fonts_downloaded_at
 * @property int $records_count
 * @property Carbon|null $last_synced_at
 */
class Mushaf extends Model
{
    /** @use HasFactory<MushafFactory> */
    use HasFactory;

    protected $table = 'quran_mushafs';

    public $incrementing = false;

    protected $keyType = 'int';

    protected $fillable = [
        'id', 'name', 'description', 'pages_count', 'lines_per_page', 'default_font_name', 'mapping_mode',
        'qirat_name', 'font_version', 'is_default', 'is_active', 'fonts_downloaded_at', 'records_count',
        'last_synced_at',
    ];

    protected $casts = [
        'id' => 'integer',
        'pages_count' => 'integer',
        'lines_per_page' => 'integer',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'fonts_downloaded_at' => 'datetime',
        'records_count' => 'integer',
        'last_synced_at' => 'datetime',
    ];

    protected static function newFactory(): MushafFactory
    {
        return MushafFactory::new();
    }

    public function pages(): HasMany
    {
        return $this->hasMany(MushafPage::class, 'mushaf_id')->orderBy('page_number');
    }

    public function words(): HasMany
    {
        return $this->hasMany(MushafWord::class, 'mushaf_id');
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Glyph mushafs render with per-page QCF fonts.
     */
    public function usesPageFonts(): bool
    {
        return in_array($this->font_version, ['v1', 'v2'], true);
    }
}
