<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Quran;

use App\Domain\Quran\Enums\SectionType;
use App\Domain\Quran\Services\StructureService;
use App\Http\Controllers\Admin\Quran\Concerns\QueuesQuranCommands;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Juz, hizb, rubʿ, manzil and rukūʿ divisions. Read-only: they come from Quran
 * Foundation and are refreshed with a structure re-import.
 */
final class SectionController extends Controller
{
    use QueuesQuranCommands;

    public function __construct(private readonly StructureService $structure) {}

    public function index(string $type): View
    {
        $sectionType = SectionType::from($type);

        return view('admin.quran.sections.index', [
            'type' => $sectionType,
            'sections' => $this->structure->sections($sectionType),
            'counts' => $this->structure->sectionCounts(),
        ]);
    }

    public function show(string $type, int $number): View
    {
        $sectionType = SectionType::from($type);

        return view('admin.quran.sections.show', [
            'type' => $sectionType,
            'section' => $this->structure->section($sectionType, $number) ?? abort(404),
        ]);
    }

    public function reimport(Request $request): RedirectResponse|JsonResponse
    {
        return $this->queueCommand($request, 'quran:qf:import-structure');
    }
}
