<?php

declare(strict_types=1);

namespace App\Domain\Content\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * StaticPageTranslation Model
 *
 * Body holds sanitized HTML produced by the admin editor.
 *
 * @property int $id
 * @property int $static_page_id
 * @property string $locale
 * @property string|null $title
 * @property string|null $body
 * @property string|null $seo_title
 * @property string|null $seo_description
 * @property bool $is_ready
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read StaticPage $page
 */
class StaticPageTranslation extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'static_page_translations';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'static_page_id',
        'locale',
        'title',
        'body',
        'seo_title',
        'seo_description',
        'is_ready',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'static_page_id' => 'integer',
        'is_ready' => 'boolean',
    ];

    /**
     * Get the parent page.
     */
    public function page(): BelongsTo
    {
        return $this->belongsTo(StaticPage::class, 'static_page_id');
    }

    /**
     * Scope: Filter by locale.
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeByLocale($query, string $locale)
    {
        return $query->where('locale', $locale);
    }
}
