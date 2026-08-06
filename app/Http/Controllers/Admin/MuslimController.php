<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domain\People\DTOs\MuslimListFilters;
use App\Domain\People\Exceptions\GenealogyCycleException;
use App\Domain\People\Models\IslamicRole;
use App\Domain\People\Models\Muslim;
use App\Domain\People\Repositories\MuslimRepositoryInterface;
use App\Domain\People\Services\GenealogyTreeService;
use App\Domain\People\Services\MuslimDirectoryService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreMuslimRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MuslimController extends Controller
{
    public function __construct(
        private readonly MuslimDirectoryService $directory,
        private readonly MuslimRepositoryInterface $muslims,
        private readonly GenealogyTreeService $tree,
    ) {}

    public function index(Request $request): View
    {
        // publishedOnly: false — the admin list must show drafts.
        $people = $this->directory->list(
            MuslimListFilters::fromRequest($request, publishedOnly: false)
        );

        return view('admin.muslims.index', [
            'people' => $people,
            'roles' => $this->directory->roles(),
        ]);
    }

    public function create(): View
    {
        return view('admin.muslims.create', ['roles' => $this->directory->roles()]);
    }

    public function store(StoreMuslimRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $roles = $data['roles'] ?? [];
        unset($data['roles']);

        $muslim = $this->muslims->create($data, $roles);

        return redirect()
            ->route('admin.muslims.edit', $muslim)
            ->with('success', __('people.saved'));
    }

    public function edit(Muslim $muslim): View
    {
        return view('admin.muslims.edit', [
            'muslim' => $muslim->load('roles'),
            'roles' => $this->directory->roles(),
            'relations' => $this->tree->relations($muslim, app()->getLocale(), publishedOnly: false),
        ]);
    }

    public function update(StoreMuslimRequest $request, Muslim $muslim): RedirectResponse
    {
        $data = $request->validated();
        $roles = $data['roles'] ?? null;
        unset($data['roles']);

        // Parent assignment goes through the service, never straight to the
        // column: the ancestor walk is the only thing standing between an
        // editor and an unrenderable cyclic tree.
        foreach (['father_id', 'mother_id'] as $column) {
            if (! array_key_exists($column, $data)) {
                continue;
            }

            try {
                $this->tree->assignParent($muslim, $column, $data[$column] === null ? null : (int) $data[$column]);
            } catch (GenealogyCycleException $e) {
                return back()->withInput()->withErrors([$column => $e->getMessage()]);
            }

            unset($data[$column]);
        }

        $this->muslims->update($muslim, $data, $roles);

        return redirect()
            ->route('admin.muslims.edit', $muslim)
            ->with('success', __('people.saved'));
    }

    public function destroy(Muslim $muslim): RedirectResponse
    {
        $this->muslims->delete($muslim);

        return redirect()
            ->route('admin.muslims.index')
            ->with('success', __('people.deleted'));
    }

    /**
     * Person picker for parent and relation selection.
     *
     * The ONE endpoint that deliberately returns unpublished people: an
     * unpublished person is still a valid ancestor. `is_published` is returned
     * so the picker can mark them, because an editor should know they are
     * linking to a hidden profile.
     */
    public function picker(Request $request): \Illuminate\Http\JsonResponse
    {
        $term = trim((string) $request->query('q', ''));

        if (mb_strlen($term) < 2) {
            return response()->json(['data' => []]);
        }

        $exclude = $request->filled('exclude') ? (int) $request->query('exclude') : null;

        return response()->json([
            'data' => $this->directory->picker($term, $exclude)
                ->map(fn (Muslim $m): array => [
                    'id' => $m->id,
                    'slug' => $m->slug,
                    'name_ar' => $m->name_ar,
                    'name_en' => $m->name_en,
                    'is_published' => (bool) $m->is_published,
                ])->all(),
        ]);
    }

    public function roles(): View
    {
        return view('admin.muslims.roles', [
            'roles' => IslamicRole::query()->select(['id', 'slug', 'name_ar', 'name_en'])->get(),
        ]);
    }
}
