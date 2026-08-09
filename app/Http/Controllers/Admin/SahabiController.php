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
use App\Domain\People\ValueObjects\MuslimProfile;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BulkMuslimActionRequest;
use App\Http\Requests\Admin\StoreMuslimRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        $roles = $this->directory->roles();

        // The sidebar's Companions / Narrators / Scholars items are this list
        // scoped by `?role=`. An unknown slug is dropped rather than filtered
        // on, so a stale bookmark shows the full list instead of an empty one.
        $activeRole = is_string($request->query('role'))
            ? $roles->firstWhere('slug', $request->query('role'))
            : null;

        // publishedOnly: false — the admin list must show drafts.
        $sahaba = $this->muslims->paginate(new MuslimListFilters(
            roleSlug: $activeRole?->slug,
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
            'stats' => $this->adminStats($activeRole?->slug),
            'filters' => $filters,
            'roles' => $roles,
            'activeRole' => $activeRole,
        ]);
    }

    /**
     * Counts for the cards above the list. Scoped to the same role as the list
     * itself — on the Narrators view "total" has to mean narrators, or the
     * cards contradict the rows underneath them.
     *
     * @return array{total:int,male:int,female:int}
     */
    private function adminStats(?string $roleSlug): array
    {
        $base = Muslim::query()->when(
            $roleSlug !== null,
            fn (Builder $query) => $query->whereHas(
                'roles',
                fn (Builder $roles) => $roles->where('islamic_roles.slug', $roleSlug),
            ),
        );

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

        $data['profile'] = $this->profileFrom($request);

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

        $data['profile'] = $this->profileFrom($request);

        $this->muslims->update($muslim, $data, $roles);

        return redirect()
            ->route('admin.sahaba.edit', $muslim)
            ->with('success', __('sahaba.saved'));
    }

    /**
     * The extended profile, rebuilt from the submitted values.
     *
     * Always assigned, never merged: the form renders every field the person's
     * roles allow, so an emptied input must clear its value rather than leave
     * the previous one behind. Keys outside the registry — or outside those
     * roles — are dropped by fromFormData().
     */
    private function profileFrom(StoreMuslimRequest $request): MuslimProfile
    {
        return MuslimProfile::fromFormData(
            (array) $request->validated('profile', []),
            $request->submittedRoleSlugs(),
        );
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

    /**
     * Paginated person browser behind the parent-picker modal.
     *
     * Distinct from picker() above: that one is a type-ahead needing two
     * characters, this one opens cold on the latest people so an editor can
     * pick without knowing a name to type, then narrows by search and role.
     *
     * Like the type-ahead, it returns unpublished people — an unpublished
     * person is still a valid ancestor — and flags them so the editor knows.
     */
    public function browse(Request $request): JsonResponse
    {
        $search = trim((string) $request->query('q', ''));
        $roleSlug = $request->query('role');

        $people = $this->muslims->paginate(new MuslimListFilters(
            roleSlug: is_string($roleSlug) && $roleSlug !== '' ? $roleSlug : null,
            search: $search !== '' ? $search : null,
            publishedOnly: false,
            perPage: 10,
            excludeId: $request->filled('exclude') ? (int) $request->query('exclude') : null,
            latestFirst: true,
        ));

        $locale = app()->getLocale();

        return response()->json([
            'data' => $people->getCollection()
                ->map(fn (Muslim $m): array => [
                    'id' => $m->id,
                    'slug' => $m->slug,
                    'name' => $m->name($locale),
                    'name_ar' => $m->name_ar,
                    'name_en' => $m->name_en,
                    'kunya' => $m->getTranslation('kunya', $locale) ?: null,
                    'lifespan' => $this->lifespan($m),
                    'roles' => $m->roles->map(fn ($role): string => $role->name($locale))->all(),
                    'is_published' => (bool) $m->is_published,
                ])->all(),
            'meta' => [
                'current_page' => $people->currentPage(),
                'last_page' => $people->lastPage(),
                'total' => $people->total(),
                'from' => $people->firstItem(),
                'to' => $people->lastItem(),
            ],
        ]);
    }

    /** Signed Hijri years; negative is pre-Hijra. Null when neither is known. */
    private function lifespan(Muslim $muslim): ?string
    {
        if ($muslim->birth_year_hijri === null && $muslim->death_year_hijri === null) {
            return null;
        }

        return ($muslim->birth_year_hijri ?? '—').' – '.($muslim->death_year_hijri ?? '—');
    }

    public function show(Muslim $muslim): View
    {
        return view('admin.sahaba.show', [
            'muslim' => $muslim->load(['roles', 'father:id,slug,name_ar,name_en', 'mother:id,slug,name_ar,name_en']),
            // publishedOnly: false — an admin reviewing a profile needs to see
            // draft relatives too, otherwise the lineage looks incomplete.
            'relations' => $this->tree->relations($muslim, app()->getLocale(), publishedOnly: false),
        ]);
    }

    /**
     * Bulk actions the existing index view posts to. Each validates its ids
     * and writes through the repository.
     */
    public function bulkDelete(BulkMuslimActionRequest $request): RedirectResponse
    {
        $this->muslims->bulkDelete($request->ids());

        return back()->with('success', __('sahaba.deleted'));
    }

    public function bulkSetFeatured(BulkMuslimActionRequest $request): RedirectResponse
    {
        $this->muslims->bulkSetFeatured($request->ids(), $request->boolean('is_featured'));

        return back()->with('success', __('sahaba.saved'));
    }

    public function bulkSetPublished(BulkMuslimActionRequest $request): RedirectResponse
    {
        $this->muslims->bulkSetPublished($request->ids(), $request->boolean('is_published'));

        return back()->with('success', __('sahaba.saved'));
    }

    /**
     * The Islamic roles directory.
     *
     * Read-only by design: the five slugs are referenced from code and from the
     * sidebar's role-scoped links, so adding or deleting one here would break
     * callers silently. Names come from IslamicRoleSeeder. What this page owes
     * an editor is coverage — how many figures carry each role, how many of
     * those are live — and a way through to that list.
     */
    public function roles(): View
    {
        return view('admin.sahaba.roles', [
            'roles' => IslamicRole::query()
                ->select(['id', 'slug', 'name_ar', 'name_en'])
                ->withCount([
                    'muslims as people_count',
                    'muslims as published_count' => fn (Builder $query) => $query
                        ->where('muslims.is_published', true),
                    'muslims as featured_count' => fn (Builder $query) => $query
                        ->where('muslims_roles.is_featured', true),
                ])
                ->orderBy('id')
                ->get(),
            'totalPeople' => Muslim::query()->count(),
            // Figures nobody has classified yet — invisible on every
            // role-scoped list, which is exactly why they need surfacing.
            'unassignedCount' => Muslim::query()->whereDoesntHave('roles')->count(),
        ]);
    }
}
