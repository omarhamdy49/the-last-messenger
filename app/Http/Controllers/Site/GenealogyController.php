<?php

declare(strict_types=1);

namespace App\Http\Controllers\Site;

use App\Domain\Genealogy\Enums\PersonType;
use App\Domain\Genealogy\Models\Person;
use App\Domain\Genealogy\Services\GenealogyService;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;
use Throwable;

final class GenealogyController extends Controller
{
    public function __construct(
        private readonly GenealogyService $genealogyService,
    ) {}

    /**
     * Genealogy index — the Prophet's family tree (شجرة النسب النبوي).
     *
     * Renders the family tree rooted at the Prophet ﷺ when available;
     * if the tree cannot be built it degrades to a flat grid of all people
     * so the page always has real content to show.
     */
    public function index(): View
    {
        $locale = app()->getLocale();

        $payload = Cache::remember(
            "public_genealogy_index_{$locale}",
            3600,
            fn (): array => $this->buildPayload($locale),
        );

        return view('public.genealogy.index', [
            'root' => $payload['root'],
            'tree' => $payload['tree'],
            'people' => $payload['people'],
            'stats' => $payload['stats'],
            'hasTree' => $payload['hasTree'],
        ]);
    }

    /**
     * Assemble the view payload: root node, nested tree, flat people list and stats.
     *
     * @return array<string, mixed>
     */
    private function buildPayload(string $locale): array
    {
        $totalPeople = $this->countPeople();

        $rootPerson = $this->resolveRootPerson($locale);

        $tree = null;
        $hasTree = false;

        if ($rootPerson !== null) {
            try {
                $familyTree = $this->genealogyService->getFamilyTree($rootPerson['id'], 3);
                $tree = $this->mapTreeNode($familyTree['person'], $familyTree['descendants'] ?? [], $locale);
                $hasTree = ! empty($tree['children']);
            } catch (Throwable $e) {
                report($e);
                $tree = null;
                $hasTree = false;
            }
        }

        // Flat list used by the list view and as the tree fallback.
        $people = $this->mapPeople($locale);

        $childCount = $tree !== null ? count($tree['children']) : 0;

        return [
            'root' => $rootPerson,
            'tree' => $tree,
            'people' => $people,
            'hasTree' => $hasTree,
            'stats' => [
                'people' => $totalPeople,
                'children' => $childCount,
                'generations' => 3,
            ],
        ];
    }

    /**
     * Resolve the tree root (the Prophet ﷺ) as a plain array, or null when absent.
     *
     * @return array<string, mixed>|null
     */
    private function resolveRootPerson(string $locale): ?array
    {
        try {
            $prophet = $this->genealogyService
                ->getPeopleByType(PersonType::PROPHET, 1)
                ->first();
        } catch (Throwable $e) {
            report($e);

            return null;
        }

        if (! $prophet instanceof Person) {
            return null;
        }

        return $this->mapPerson($prophet, $locale, isRoot: true);
    }

    /**
     * Recursively map a tree node (person + descendants) into a plain nested array.
     *
     * @param  array<int, array<string, mixed>>  $descendants
     * @return array<string, mixed>
     */
    private function mapTreeNode(Person $person, array $descendants, string $locale): array
    {
        $node = $this->mapPerson($person, $locale);
        $node['children'] = array_map(
            fn (array $child): array => $this->mapTreeNode(
                $child['person'],
                $child['descendants'] ?? [],
                $locale,
            ),
            $descendants,
        );

        return $node;
    }

    /**
     * Map the full people list (real data only) to plain arrays for the list view.
     *
     * @return array<int, array<string, mixed>>
     */
    private function mapPeople(string $locale): array
    {
        try {
            $paginator = $this->genealogyService->getAllPeople(100);
        } catch (Throwable $e) {
            report($e);

            return [];
        }

        return collect($paginator->items())
            ->map(fn (Person $person): array => $this->mapPerson($person, $locale))
            ->values()
            ->all();
    }

    /**
     * Map a single Person model into the plain array shape the view consumes.
     *
     * @return array<string, mixed>
     */
    private function mapPerson(Person $person, string $locale, bool $isRoot = false): array
    {
        $type = $person->person_type;

        return [
            'id' => $person->id,
            'slug' => $person->slug,
            'name' => $this->localized($person, 'name', $locale),
            'title' => $this->localized($person, 'title', $locale),
            'biography' => $this->localized($person, 'biography', $locale),
            'notes' => $this->localized($person, 'notes', $locale),
            'tribe' => $person->tribe,
            'gender' => $person->gender,
            'type' => $type?->value,
            'typeLabel' => $type?->label()[$locale] ?? ($type?->label()['ar'] ?? null),
            'isRoot' => $isRoot,
            'isProphet' => $type === PersonType::PROPHET,
        ];
    }

    /**
     * Read a translatable field with Arabic fallback; never returns null to the view.
     */
    private function localized(Person $person, string $field, string $locale): string
    {
        return $person->getTranslation($field, $locale)
            ?: $person->getTranslation($field, 'ar')
            ?: '';
    }

    /**
     * Total number of people in the genealogy, falling back to relationship count.
     */
    private function countPeople(): int
    {
        try {
            return $this->genealogyService->getAllPeople(1)->total();
        } catch (Throwable $e) {
            report($e);

            try {
                return $this->genealogyService->countRelationships();
            } catch (Throwable) {
                return 0;
            }
        }
    }
}
