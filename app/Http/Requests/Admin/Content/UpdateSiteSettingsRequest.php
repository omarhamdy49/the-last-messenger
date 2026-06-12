<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Content;

use App\Domain\Content\Enums\SiteSettingType;
use App\Domain\Content\Models\SiteSetting;
use App\Domain\Content\Services\SiteSettingsService;
use App\Http\Requests\Admin\Content\Concerns\SanitizesRichText;
use Illuminate\Database\Eloquent\Collection;

/**
 * Validates one settings group save. Rules are built dynamically from the
 * group's settings: translatable types take a locale-keyed map, the rest a
 * scalar matching their SiteSettingType. Rich-text values are sanitized
 * (allowlist purifier) before validation.
 */
class UpdateSiteSettingsRequest extends ContentFormRequest
{
    use SanitizesRichText;

    /**
     * @var Collection<int, SiteSetting>|null
     */
    private ?Collection $groupSettings = null;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = ['settings' => ['required', 'array']];

        foreach ($this->settingsForGroup() as $setting) {
            $key = "settings.{$setting->key}";

            if ($setting->is_translatable) {
                $rules[$key] = ['nullable', 'array'];

                foreach (self::LOCALES as $locale) {
                    $rules["{$key}.{$locale}"] = ['nullable', 'string', 'max:100000'];
                }

                continue;
            }

            $rules[$key] = match ($setting->type) {
                SiteSettingType::BOOL => ['nullable', 'boolean'],
                SiteSettingType::URL, SiteSettingType::IMAGE => ['nullable', 'string', 'max:500', 'url'],
                default => ['nullable', 'string', 'max:2000'],
            };
        }

        return $rules;
    }

    /**
     * The settings of the route-bound group (memoized; the controller
     * reuses this to apply the save).
     *
     * @return Collection<int, SiteSetting>
     */
    public function settingsForGroup(): Collection
    {
        return $this->groupSettings ??= app(SiteSettingsService::class)
            ->group((string) $this->route('group'));
    }

    /**
     * Sanitize rich-text setting values before validation.
     */
    protected function prepareForValidation(): void
    {
        foreach ($this->settingsForGroup() as $setting) {
            if ($setting->type === SiteSettingType::RICHTEXT && $setting->is_translatable) {
                $this->sanitizeRichTextField("settings.{$setting->key}");
            }
        }
    }
}
