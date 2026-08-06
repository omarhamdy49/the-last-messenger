<?php

declare(strict_types=1);

namespace App\Http\Controllers\Site;

use App\Domain\People\Models\Muslim;
use App\Domain\People\Repositories\MuslimRepositoryInterface;
use App\Domain\People\Services\GenealogyTreeService;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * The public family tree, rebuilt on `muslims`.
 *
 * The view (`resources/views/public/genealogy/index.blade.php`) is the one that
 * existed before; only the data source changed. Descent comes from
 * `father_id`/`mother_id` on the person row, and non-descent edges from
 * `muslim_relationships` — see specs/002 → Genealogy.
 *
 * Fields the old `people` table carried and `muslims` does not (tribe, person
 * type, gregorian dates) resolve to null; the view already guards them.
 */
final class GenealogyController extends Controller
{
    /** How many generations the landing tree renders before the CTE cap. */
    private const LANDING_DEPTH = 3;

    public function __construct(
        private readonly MuslimRepositoryInterface $muslims,
        private readonly GenealogyTreeService $tree,
    ) {}

    public function index(Request $request): View
    {
        $locale = app()->getLocale();
        $rootSlug = (string) $request->query('root', '');

        $payload = $rootSlug !== ''
            ? $this->buildPayload($locale, $rootSlug)
            : Cache::remember(
                "public_genealogy_{$locale}",
                1800,
                fn (): array => $this->buildPayload($locale, null),
            );

        return view('public.genealogy.index', $payload);
    }

    /**
     * Person search behind the tree explorer's picker.
     * Public, so it serves published people only.
     */
    public function people(Request $request): JsonResponse
    {
        $term = trim((string) $request->query('q', ''));

        if (mb_strlen($term) < 2) {
            return response()->json(['data' => []]);
        }

        $locale = app()->getLocale();

        return response()->json([
            'data' => $this->muslims->search($term, 20)
                ->map(fn (Muslim $m): array => [
                    'id' => $m->id,
                    'slug' => $m->slug,
                    'name' => $m->name($locale),
                ])->all(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildPayload(string $locale, ?string $rootSlug): array
    {
        $root = $rootSlug !== null
            ? $this->muslims->findBySlug($rootSlug)
            : $this->resolveDefaultRoot();

        if ($root === null) {
            return [
                'root' => null, 'tree' => null, 'spouses' => [], 'people' => [],
                'hasTree' => false, 'spouseIds' => [], 'childrenIds' => [],
                'grandchildrenIds' => [],
                'stats' => ['people' => 0, 'children' => 0, 'generations' => 0],
            ];
        }

        $tree = $this->buildNode($root, $locale, self::LANDING_DEPTH, true);

        $spouses = collect($this->tree->relations($root, $locale))
            ->where('type', 'spouse')
            ->map(fn (array $r): array => [
                'id' => $r['slug'], 'slug' => $r['slug'], 'name' => $r['name'],
                'title' => $r['label'], 'gender' => null, 'hasProfile' => true,
            ])->values()->all();

        return [
            'root' => $root,
            'tree' => $tree,
            'spouses' => $spouses,
            'people' => $this->flatten($tree),
            'hasTree' => $tree['children'] !== [],
            'spouseIds' => array_column($spouses, 'id'),
            'childrenIds' => array_column($tree['children'], 'id'),
            'grandchildrenIds' => collect($tree['children'])
                ->flatMap(fn (array $c): array => array_column($c['children'], 'id'))
                ->all(),
            'stats' => [
                'people' => Muslim::query()->published()->count(),
                'children' => count($tree['children']),
                'generations' => self::LANDING_DEPTH,
            ],
        ];
    }

    /**
     * The person with the most descendants makes the most useful landing tree.
     */
    private function resolveDefaultRoot(): ?Muslim
    {
        $id = Muslim::query()
            ->published()
            ->whereNull('father_id')
            ->withCount('childrenByFather')
            ->orderByDesc('children_by_father_count')
            ->value('id');

        return $id === null
            ? Muslim::query()->published()->orderBy('id')->first()
            : $this->muslims->findById((int) $id);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildNode(Muslim $person, string $locale, int $depth, bool $isRoot = false): array
    {
        $node = $this->mapPerson($person, $locale, $isRoot);
        $node['children'] = [];

        if ($depth <= 0) {
            return $node;
        }

        $children = Muslim::query()
            ->published()
            ->where('hide_from_tree', false)
            ->where(fn ($q) => $q->where('father_id', $person->id)->orWhere('mother_id', $person->id))
            ->orderByRaw('COALESCE(tree_display_order, 4294967295), id')
            ->get();

        foreach ($children as $child) {
            $node['children'][] = $this->buildNode($child, $locale, $depth - 1);
        }

        return $node;
    }

    /**
     * @return array<string, mixed>
     */
    private function mapPerson(Muslim $person, string $locale, bool $isRoot = false): array
    {
        return [
            'id' => $person->id,
            'slug' => $person->slug,
            'name' => $person->name($locale),
            'title' => $person->getTranslation('kunya', $locale) ?: null,
            'biography' => $person->getTranslation('bio', $locale) ?: null,
            'notes' => null,
            'gender' => $person->gender?->value,
            'isRoot' => $isRoot,
            // Explicit and opt-in. There is no `person_type` column any more,
            // so this is false until someone carries a `prophet` role. Never
            // inferred from being the tree root — see _node.blade.php.
            'isProphet' => $person->relationLoaded('roles')
                ? $person->roles->contains('slug', 'prophet')
                : false,
            // Every person now has a public profile at /sahaba/{slug}; there is
            // no separate "not a companion" case to guard against.
            'hasProfile' => true,
            'birthDateHijri' => $person->birth_date_hijri,
            'deathDateHijri' => $person->death_date_hijri,

            // Retired with the old `people` table.
            'tribe' => null,
            'type' => null,
            'typeLabel' => null,
            'birthDateGregorian' => null,
            'deathDateGregorian' => null,
        ];
    }

    /**
     * @param  array<string, mixed>  $node
     * @return array<int, array<string, mixed>>
     */
    private function flatten(array $node): array
    {
        $flat = [$node];

        foreach ($node['children'] as $child) {
            $flat = array_merge($flat, $this->flatten($child));
        }

        return $flat;
    }
}
