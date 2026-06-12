<?php

declare(strict_types=1);

namespace App\Domain\Content\Models;

use App\Domain\Content\Enums\SiteSettingType;
use App\Domain\Shared\Traits\HasLocalizedContent;
use Illuminate\Database\Eloquent\Model;

/**
 * SiteSetting Model
 *
 * Editable global chrome of the public site (site name, SEO defaults,
 * social URLs, footer copy, contact email, ...).
 *
 * @property int $id
 * @property string $key
 * @property string $group
 * @property SiteSettingType $type
 * @property string|null $value Non-translatable value (urls/images/bools)
 * @property bool $is_translatable
 * @property int $sort_order
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class SiteSetting extends Model
{
    use HasLocalizedContent;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'site_settings';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'key',
        'group',
        'type',
        'value',
        'is_translatable',
        'sort_order',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'type' => SiteSettingType::class,
        'is_translatable' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Get the translation model class.
     */
    public function getTranslationModelClass(): string
    {
        return SiteSettingTranslation::class;
    }

    /**
     * Get the list of translatable field names.
     */
    public function getTranslatableFields(): array
    {
        return ['value'];
    }

    /**
     * Get the foreign key name for translations.
     */
    public function getTranslationForeignKey(): string
    {
        return 'site_setting_id';
    }

    /**
     * Scope: Filter by settings group.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByGroup($query, string $group)
    {
        return $query->where('group', $group);
    }
}
