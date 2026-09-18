<?php

declare(strict_types=1);

namespace App\Http\Controllers\Site\Quran;

use App\Domain\Quran\Services\SearchService;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Quran search over local data: a full results page and the command-bar JSON.
 */
final class SearchController extends Controller
{
    public function __construct(private readonly SearchService $search) {}

    public function page(Request $request): View
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'translations' => ['nullable', 'array', 'max:5'],
            'translations.*' => ['integer'],
            'exact' => ['nullable', 'boolean'],
            'page' => ['nullable', 'integer', 'min:1', 'max:500'],
        ]);
        $query = trim((string) ($validated['q'] ?? ''));

        return view('public.quran.search', [
            'query' => $query,
            'exact' => $request->boolean('exact'),
            'results' => $query === '' ? null : $this->search->advanced(
                $query,
                array_map('intval', $validated['translations'] ?? []),
                $request->boolean('exact'),
                true,
                (int) ($validated['page'] ?? 1),
                20,
                app()->getLocale(),
            ),
        ]);
    }

    public function quick(Request $request): JsonResponse
    {
        $validated = $request->validate(['q' => ['required', 'string', 'max:100']]);

        return response()->json(['result' => $this->search->quick((string) $validated['q'], app()->getLocale())]);
    }
}
