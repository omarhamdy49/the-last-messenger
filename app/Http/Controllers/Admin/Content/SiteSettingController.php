<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Content;

use App\Domain\Content\Enums\SiteSettingType;
use App\Domain\Content\Models\SiteSetting;
use App\Domain\Content\Services\SiteSettingsService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Content\UpdateSiteSettingsRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Admin — grouped site settings editor.
 *
 * GET  /su/portal/v1/admin/site-settings?group=…
 * PUT  /su/portal/v1/admin/site-settings/{group}
 */
class SiteSettingController extends Controller
{
    public function __construct(
        protected SiteSettingsService $service,
    ) {}

    /**
     * Show one settings group (defaults to the first group).
     */
    public function index(Request $request): View
    {
        $groups = $this->service->groups();

        $activeGroup = $request->string('group')->toString();
        if (! in_array($activeGroup, $groups, true)) {
            $activeGroup = $groups[0] ?? '';
        }

        $settings = $activeGroup === ''
            ? collect()
            : $this->service->group($activeGroup);

        return view('admin.content.settings.index', [
            'groups' => $groups,
            'activeGroup' => $activeGroup,
            'settings' => $settings,
        ]);
    }

    /**
     * Save every submitted setting of one group (cache flushes inside set()).
     */
    public function update(UpdateSiteSettingsRequest $request, string $group): RedirectResponse
    {
        $values = $request->validated()['settings'] ?? [];

        try {
            foreach ($request->settingsForGroup() as $setting) {
                $this->applySetting($setting, $values);
            }

            return redirect()
                ->route('admin.content.settings.index', ['group' => $group])
                ->with('success', __('admin.content_settings_saved'));
        } catch (\Throwable $exception) {
            \Log::error('admin.content.settings.update_failed', [
                'user_id' => auth()->id(),
                'group' => $group,
                'error' => $exception->getMessage(),
            ]);

            return back()->withInput()->with('error', __('admin.operation_failed'));
        }
    }

    /**
     * Persist one setting from the validated group payload.
     *
     * @param  array<string, mixed>  $values
     */
    private function applySetting(SiteSetting $setting, array $values): void
    {
        if ($setting->is_translatable) {
            $translations = $values[$setting->key] ?? null;

            if (is_array($translations)) {
                $this->service->set($setting->key, $translations);
            }

            return;
        }

        if ($setting->type === SiteSettingType::BOOL) {
            // Unchecked checkboxes are absent from the payload — that means false.
            $this->service->set($setting->key, array_key_exists($setting->key, $values)
                ? filter_var($values[$setting->key], FILTER_VALIDATE_BOOL)
                : false);

            return;
        }

        if (array_key_exists($setting->key, $values)) {
            $this->service->set($setting->key, $values[$setting->key]);
        }
    }
}
