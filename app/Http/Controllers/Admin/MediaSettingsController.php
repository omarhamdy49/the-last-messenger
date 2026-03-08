<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\SaveSizePresetRequest;
use App\Models\MediaSizePreset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MediaSettingsController extends AdminController
{
    public function index(Request $request)
    {
        $this->authorizeAdmin('manage settings');

        $presets = MediaSizePreset::query()->ordered()->get();

        if ($this->expectsJson($request)) {
            return $this->successResponse($presets);
        }

        return view('admin.media.settings', [
            'presets' => $presets,
        ]);
    }

    public function storePreset(SaveSizePresetRequest $request): JsonResponse|RedirectResponse
    {
        $this->authorizeAdmin('manage settings');

        $preset = MediaSizePreset::query()->create($request->validated());

        if ($this->expectsJson($request)) {
            return $this->createdResponse($preset, __('media.messages.size_preset_created'));
        }

        return redirect()
            ->route('admin.media-library.settings')
            ->with('success', __('media.messages.size_preset_created'));
    }

    public function updatePreset(SaveSizePresetRequest $request, MediaSizePreset $preset): JsonResponse|RedirectResponse
    {
        $this->authorizeAdmin('manage settings');

        $preset->update($request->validated());

        if ($this->expectsJson($request)) {
            return $this->successResponse($preset->fresh(), __('media.messages.size_preset_updated'));
        }

        return redirect()
            ->route('admin.media-library.settings')
            ->with('success', __('media.messages.size_preset_updated'));
    }

    public function destroyPreset(Request $request, MediaSizePreset $preset): JsonResponse|RedirectResponse
    {
        $this->authorizeAdmin('manage settings');

        $preset->delete();

        if ($this->expectsJson($request)) {
            return $this->successResponse(null, __('media.messages.size_preset_deleted'));
        }

        return redirect()
            ->route('admin.media-library.settings')
            ->with('success', __('media.messages.size_preset_deleted'));
    }

    public function reorder(Request $request): JsonResponse
    {
        $this->authorizeAdmin('manage settings');

        $validated = $request->validate([
            'items' => ['required', 'array'],
            'items.*.id' => ['required', 'integer', 'exists:media_size_presets,id'],
            'items.*.sort_order' => ['required', 'integer', 'min:0'],
        ]);

        foreach ($validated['items'] as $item) {
            MediaSizePreset::query()
                ->whereKey($item['id'])
                ->update(['sort_order' => $item['sort_order']]);
        }

        return $this->successResponse(
            MediaSizePreset::query()->ordered()->get(),
            __('media.messages.preset_order_updated')
        );
    }
}
