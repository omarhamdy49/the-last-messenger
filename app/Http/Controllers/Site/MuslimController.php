<?php

declare(strict_types=1);

namespace App\Http\Controllers\Site;

use App\Domain\People\DTOs\MuslimListFilters;
use App\Domain\People\Services\GenealogyTreeService;
use App\Domain\People\Services\MuslimDirectoryService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class MuslimController extends Controller
{
    public function __construct(
        private readonly MuslimDirectoryService $directory,
        private readonly GenealogyTreeService $tree,
    ) {}

    public function index(Request $request): View
    {
        return view('public.muslims.index', [
            'people' => $this->directory->list(MuslimListFilters::fromRequest($request)),
            'roles' => $this->directory->roles(),
        ]);
    }

    public function show(string $slug): View
    {
        $muslim = $this->directory->profile($slug);

        if ($muslim === null) {
            throw new NotFoundHttpException;
        }

        return view('public.muslims.show', [
            'muslim' => $muslim,
            'relations' => $this->tree->relations($muslim, app()->getLocale()),
        ]);
    }

    public function tree(string $slug): View
    {
        $muslim = $this->directory->profile($slug);

        if ($muslim === null) {
            throw new NotFoundHttpException;
        }

        $ancestors = $this->tree->ancestors($muslim);
        $descendants = $this->tree->descendants($muslim);

        return view('public.muslims.tree', [
            'muslim' => $muslim,
            // Publication and hide_from_tree are applied here, at render —
            // never at selection.
            'ancestors' => $this->visible($ancestors['nodes']),
            'descendants' => $this->visible($descendants['nodes']),
            'relations' => $this->tree->relations($muslim, app()->getLocale()),
            'depthCapped' => $ancestors['depth_capped'] || $descendants['depth_capped'],
        ]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $nodes
     * @return array<int, array<string, mixed>>
     */
    private function visible(array $nodes): array
    {
        return array_values(array_filter(
            $nodes,
            static fn (array $n): bool => (bool) $n['is_published'] && ! (bool) $n['hide_from_tree'],
        ));
    }
}
