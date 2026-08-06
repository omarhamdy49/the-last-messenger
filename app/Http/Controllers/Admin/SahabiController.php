<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domain\People\DTOs\AdminSahabaFilters;
use App\Domain\People\DTOs\MuslimListFilters;
use App\Domain\People\Enums\Gender;
use App\Domain\People\Exceptions\GenealogyCycleException;
use App\Domain\People\Models\IslamicRole;
use App\Domain\People\Models\Muslim;
use App\Domain\People\Repositories\MuslimRepositoryInterface;
use App\Domain\People\Services\GenealogyTreeService;
use App\Domain\People\Services\MuslimDirectoryService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreMuslimRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SahabiController extends Controller
{
    public function __construct(
        private readonly MuslimDirectoryService $directory,
        private readonly MuslimRepositoryInterface $muslims,
        private readonly GenealogyTreeService $tree,
    ) {}

    public function index(Request $request): View
    {
        $filters = AdminSahabaFilters::fromRequest($request);

        // publishedOnly: false — the admin list must show drafts.
        $sahaba = $this->muslims->paginate(new MuslimListFilters(
            roleSlug: $request->query('role') ?: null,
            featured: $filters->isFeatured,
            gender: $filters->gender !== null
                ? Gender::tryFrom($filters->gender)
                : null,
            search: $filters->search,
            publishedOnly: false,
            perPage: min(max((int) $request->query('per_page', 15), 10), 100),
        ));

        return view('admin.sahaba.index', [
            'sahaba' => $sahaba,
            'stats' => $this->adminStats(),
            'filters' => $filters,
            'roles' => $this->directory->roles(),
        ]);
    }

    /**
     * @return array{total:int,male:int,female:int}
     */
    private function adminStats(): array
    {
        $base = Muslim::query();

        return [
            'total' => (clone $base)->count(),
            'male' => (clone $base)->where('gender', 'male')->count(),
            'female' => (clone $base)->where('gender', 'female')->count(),
        ];
    }

    public function create(): View
    {
        return view('admin.sahaba.create', ['roles' => $this->directory->roles()]);
    }

    public function store(StoreMuslimRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $roles = $data['roles'] ?? [];
        unset($data['roles']);

        $muslim = $this->muslims->create($data, $roles);

        return redirect()
            ->route('admin.sahaba.edit', $muslim)
            ->with('success', __('sahaba.saved'));
    }

    public function edit(Muslim $muslim): View
    {
        return view('admin.sahaba.edit', [
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
            ->route('admin.sahaba.edit', $muslim)
            ->with('success', __('sahaba.saved'));
    }

    public function destroy(Muslim $muslim): RedirectResponse
    {
        $this->muslims->delete($muslim);

        return redirect()
            ->route('admin.sahaba.index')
            ->with('success', __('sahaba.deleted'));
    }

    /**
     * Person picker for parent and relation selection.
     *
     * The ONE endpoint that deliberately returns unpublished people: an
     * unpublished person is still a valid ancestor. `is_published` is returned
     * so the picker can mark them, because an editor should know they are
     * linking to a hidden profile.
     */
    public function picker(Request $request): JsonResponse
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

    public function show(Muslim $muslim): View
    {
        return view('admin.sahaba.show', [
            'sahabi' => $muslim->load('roles'),
            'profile' => null,
        ]);
    }

    /**
     * Bulk actions the existing index view posts to. Each validates its ids
     * and writes through the repository.
     */
    public function bulkDelete(Request $request): RedirectResponse
    {
        $ids = array_filter(array_map('intval', (array) $request->input('ids', [])));
        Muslim::query()->whereIn('id', $ids)->get()
            ->each(fn ($m) => $this->muslims->delete($m));

        return back()->with('success', __('sahaba.deleted'));
    }

    public function bulkSetFeatured(Request $request): RedirectResponse
    {
        $ids = array_filter(array_map('intval', (array) $request->input('ids', [])));
        $featured = $request->boolean('is_featured');

        DB::table('muslims_roles')
            ->whereIn('muslim_id', $ids)->update(['is_featured' => $featured]);

        return back()->with('success', __('sahaba.saved'));
    }

    public function bulkSetPublished(Request $request): RedirectResponse
    {
        $ids = array_filter(array_map('intval', (array) $request->input('ids', [])));

        Muslim::query()->whereIn('id', $ids)
            ->update(['is_published' => $request->boolean('is_published')]);

        return back()->with('success', __('sahaba.saved'));
    }

    public function roles(): View
    {
        return view('admin.sahaba.roles', [
            'roles' => IslamicRole::query()->select(['id', 'slug', 'name_ar', 'name_en'])->get(),
        ]);
    }
}
