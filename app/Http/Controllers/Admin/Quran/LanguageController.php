<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Quran;

use App\Domain\Quran\Services\StructureService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Quran\LanguageRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

/**
 * Languages used by translations and word-by-word glosses. QF languages are imported;
 * editors may add languages for manual resources.
 */
final class LanguageController extends Controller
{
    public function __construct(private readonly StructureService $structure) {}

    public function index(Request $request): View
    {
        return view('admin.quran.languages.index', [
            'languages' => $this->structure->languagesForAdmin($request->string('search')->toString() ?: null, $request->integer('per_page', 50)),
        ]);
    }

    public function create(): View
    {
        return view('admin.quran.languages.form', ['language' => null]);
    }

    public function store(LanguageRequest $request): RedirectResponse
    {
        $this->structure->createLanguage($this->attributes($request));

        return redirect()->route('admin.quran.languages.index')->with('success', __('quran.manage.saved'));
    }

    public function edit(int $language): View
    {
        return view('admin.quran.languages.form', ['language' => $this->structure->language($language) ?? abort(404)]);
    }

    public function update(LanguageRequest $request, int $language): RedirectResponse
    {
        $this->structure->language($language) ?? abort(404);
        $this->structure->updateLanguage($language, $this->attributes($request));

        return redirect()->route('admin.quran.languages.index')->with('success', __('quran.manage.saved'));
    }

    public function toggle(int $language): RedirectResponse
    {
        $this->structure->language($language) ?? abort(404);
        $this->structure->toggleLanguage($language);

        return back()->with('success', __('quran.manage.saved'));
    }

    public function destroy(int $language): RedirectResponse
    {
        $this->structure->language($language) ?? abort(404);

        try {
            $this->structure->deleteLanguage($language);
        } catch (InvalidArgumentException) {
            return back()->with('error', __('quran.manage.qf_language_not_deletable'));
        }

        return back()->with('success', __('quran.manage.deleted'));
    }

    /**
     * @return array{name: string, native_name: ?string, iso_code: string, direction: string, is_active: bool}
     */
    private function attributes(LanguageRequest $request): array
    {
        $data = $request->validated();

        return [
            'name' => (string) $data['name'],
            'native_name' => isset($data['native_name']) ? (string) $data['native_name'] : null,
            'iso_code' => (string) $data['iso_code'],
            'direction' => (string) $data['direction'],
            'is_active' => (bool) ($data['is_active'] ?? false),
        ];
    }
}
