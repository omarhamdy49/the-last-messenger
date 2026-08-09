<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domain\People\DTOs\MuslimListFilters;
use App\Domain\People\Models\Muslim;
use App\Domain\People\Services\GenealogyTreeService;
use App\Domain\People\Services\MuslimDirectoryService;
use App\Http\Controllers\Controller;
use App\Http\Resources\IslamicRoleResource;
use App\Http\Resources\MuslimResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Replaces the entire /api/v1/sahaba surface and the narrator profile route.
 * One person, one profile, many roles.
 *
 * Controllers call exactly one service method and hold no Eloquent
 * (constitution I).
 */
class SahabiController extends Controller
{
    public function __construct(
        private readonly MuslimDirectoryService $directory,
        private readonly GenealogyTreeService $tree,
    ) {}

    public function roles(): JsonResponse
    {
        return $this->ok(IslamicRoleResource::collection($this->directory->roles()));
    }

    public function index(Request $request): JsonResponse
    {
        $people = $this->directory->list(MuslimListFilters::fromRequest($request));

        return response()->json([
            'success' => true,
            'data' => MuslimResource::collection($people->items()),
            'meta' => [
                'current_page' => $people->currentPage(),
                'per_page' => $people->perPage(),
                'total' => $people->total(),
                'last_page' => $people->lastPage(),
            ],
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        $term = trim((string) $request->query('q', ''));

        if (mb_strlen($term) < 2 || mb_strlen($term) > 200) {
            return response()->json([
                'success' => false,
                'message' => __('sahaba.errors.query_too_short'),
            ], 422);
        }

        return $this->ok(MuslimResource::collection($this->directory->search($term)));
    }

    public function show(string $slug): JsonResponse
    {
        $muslim = $this->directory->profile($slug);

        if ($muslim === null) {
            // Indistinguishable from "does not exist" on purpose: a distinct
            // response would leak the existence of unpublished people.
            return $this->notFound();
        }

        return $this->ok(new MuslimResource($muslim));
    }

    public function tree(Request $request, string $slug): JsonResponse
    {
        $muslim = $this->directory->profile($slug);

        if ($muslim === null) {
            return $this->notFound();
        }

        $requested = (int) $request->query('depth', GenealogyTreeService::MAX_DEPTH);
        $ancestors = $this->tree->ancestors($muslim, $requested);
        $descendants = $this->tree->descendants($muslim, $requested);

        return $this->ok([
            'root' => ['slug' => $muslim->slug, 'name' => $muslim->name()],
            'ancestors' => $this->visible($ancestors['nodes']),
            'descendants' => $this->visible($descendants['nodes']),
            'relations' => $this->tree->relations($muslim, app()->getLocale()),
            'depth_reached' => max($ancestors['depth_reached'], $descendants['depth_reached']),
            // Reported, never silently truncated (constitution X).
            'depth_capped' => $ancestors['depth_capped'] || $descendants['depth_capped'],
        ]);
    }

    /**
     * Publication and hide_from_tree are applied at RENDER, not at selection —
     * an unpublished person is still a valid ancestor.
     *
     * @param  array<int, array<string, mixed>>  $nodes
     * @return array<int, array<string, mixed>>
     */
    private function visible(array $nodes): array
    {
        $locale = app()->getLocale();

        return array_values(array_map(
            static fn (array $n): array => [
                'slug' => $n['slug'],
                // CTE rows, not models — so the shared fallback is reached
                // through Muslim's static form rather than name().
                'name' => Muslim::preferredName($n['name_ar'] ?? null, $n['name_en'] ?? null, $locale),
                'depth' => (int) $n['depth'],
                'relation' => $n['relation'] ?? null,
            ],
            array_filter(
                $nodes,
                static fn (array $n): bool => (bool) $n['is_published'] && ! (bool) $n['hide_from_tree'],
            ),
        ));
    }

    private function ok(mixed $data): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $data]);
    }

    private function notFound(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => __('sahaba.errors.not_found'),
        ], 404);
    }
}
