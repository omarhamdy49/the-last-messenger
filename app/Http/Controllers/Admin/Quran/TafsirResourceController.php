<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Quran;

use App\Domain\Quran\Models\TafsirResource;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Admin CRUD for tafsir sources (resources). A tafsir's verse text is imported
 * via `php artisan quran:import-tafsir`; this manages the source metadata that
 * drives the reader's tafsir-source selector.
 */
class TafsirResourceController extends Controller
{
    public function index(): View
    {
        $resources = TafsirResource::query()
            ->withCount('tafsirs')
            ->orderBy('display_order')
            ->orderBy('id')
            ->paginate(20);

        return view('admin.quran.tafsir-resources.index', compact('resources'));
    }

    public function create(): View
    {
        return view('admin.quran.tafsir-resources.create');
    }

    public function store(Request $request): RedirectResponse
    {
        TafsirResource::create($this->validatedData($request));
        Cache::flush();

        return redirect()->route('admin.tafsir-resources.index')
            ->with('success', __('quran.admin.tafsir_resource_saved'));
    }

    public function edit(int $id): View
    {
        $resource = TafsirResource::findOrFail($id);

        return view('admin.quran.tafsir-resources.edit', compact('resource'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $resource = TafsirResource::findOrFail($id);
        $resource->update($this->validatedData($request, $id));
        Cache::flush();

        return redirect()->route('admin.tafsir-resources.index')
            ->with('success', __('quran.admin.tafsir_resource_saved'));
    }

    public function destroy(int $id): RedirectResponse
    {
        // Tafsir rows keep their text but lose the resource link (nullOnDelete).
        TafsirResource::findOrFail($id)->delete();
        Cache::flush();

        return redirect()->route('admin.tafsir-resources.index')
            ->with('success', __('quran.admin.tafsir_resource_deleted'));
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedData(Request $request, ?int $id = null): array
    {
        $validated = $request->validate([
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:100', 'alpha_dash', Rule::unique('quran_tafsir_resources', 'slug')->ignore($id)],
            'author' => ['nullable', 'string', 'max:255'],
            'language' => ['required', 'string', 'in:ar,en,fr,tr,ur'],
            'source' => ['nullable', 'string', 'max:255'],
            'display_order' => ['nullable', 'integer', 'min:0'],
        ]);

        return [
            'slug' => $validated['slug'],
            'name' => ['ar' => $validated['name_ar'], 'en' => $validated['name_en'] ?? $validated['name_ar']],
            'author' => $validated['author'] ?? null,
            'language' => $validated['language'],
            'source' => $validated['source'] ?? null,
            'is_active' => $request->boolean('is_active'),
            'display_order' => $validated['display_order'] ?? 0,
        ];
    }
}
