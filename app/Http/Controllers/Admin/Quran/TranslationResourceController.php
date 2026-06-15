<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Quran;

use App\Domain\Quran\Models\TranslationResource;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Admin CRUD for translation editions (resources). A translation's verse text is
 * imported via `php artisan quran:import-translation`; this manages the edition
 * metadata that drives the reader's translation-edition selector. Exactly one
 * edition per language may be the default (used by the legacy single-translation
 * read path).
 */
class TranslationResourceController extends Controller
{
    public function index(): View
    {
        $resources = TranslationResource::query()
            ->withCount('translations')
            ->orderBy('language')
            ->orderBy('display_order')
            ->orderBy('id')
            ->paginate(20);

        return view('admin.quran.translation-resources.index', compact('resources'));
    }

    public function create(): View
    {
        return view('admin.quran.translation-resources.create');
    }

    public function store(Request $request): RedirectResponse
    {
        DB::transaction(function () use ($request) {
            $data = $this->validatedData($request);
            $resource = TranslationResource::create($data);
            $this->syncDefault($resource);
        });
        Cache::flush();

        return redirect()->route('admin.translation-resources.index')
            ->with('success', __('quran.admin.translation_resource_saved'));
    }

    public function edit(int $id): View
    {
        $resource = TranslationResource::findOrFail($id);

        return view('admin.quran.translation-resources.edit', compact('resource'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        DB::transaction(function () use ($request, $id) {
            $resource = TranslationResource::findOrFail($id);
            $resource->update($this->validatedData($request, $id));
            $this->syncDefault($resource);
        });
        Cache::flush();

        return redirect()->route('admin.translation-resources.index')
            ->with('success', __('quran.admin.translation_resource_saved'));
    }

    public function destroy(int $id): RedirectResponse
    {
        // Translation rows keep their text but lose the resource link (nullOnDelete).
        TranslationResource::findOrFail($id)->delete();
        Cache::flush();

        return redirect()->route('admin.translation-resources.index')
            ->with('success', __('quran.admin.translation_resource_deleted'));
    }

    /**
     * Enforce a single default edition per language.
     */
    private function syncDefault(TranslationResource $resource): void
    {
        if (! $resource->is_default) {
            return;
        }

        TranslationResource::query()
            ->where('language', $resource->language)
            ->where('id', '!=', $resource->id)
            ->update(['is_default' => false]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedData(Request $request, ?int $id = null): array
    {
        $validated = $request->validate([
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:100', 'alpha_dash', Rule::unique('quran_translation_resources', 'slug')->ignore($id)],
            'author' => ['nullable', 'string', 'max:255'],
            'language' => ['required', 'string', 'in:ar,en,en-tl,fr,tr,ur'],
            'direction' => ['required', 'string', 'in:rtl,ltr'],
            'source' => ['nullable', 'string', 'max:255'],
            'display_order' => ['nullable', 'integer', 'min:0'],
        ]);

        return [
            'slug' => $validated['slug'],
            'name' => ['ar' => $validated['name_ar'], 'en' => $validated['name_en'] ?? $validated['name_ar']],
            'author' => $validated['author'] ?? null,
            'language' => $validated['language'],
            'direction' => $validated['direction'],
            'source' => $validated['source'] ?? null,
            'is_default' => $request->boolean('is_default'),
            'is_active' => $request->boolean('is_active'),
            'display_order' => $validated['display_order'] ?? 0,
        ];
    }
}
