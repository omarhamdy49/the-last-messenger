<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domain\Quran\Services\QuranCacheKeys;
use App\Domain\Quran\Services\SurahService;
use App\Domain\Shared\Services\AdminLoggerService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSurahRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

/**
 * Admin Surah Controller
 * Handles Quran surah (chapter) management
 * Note: No create/store as 114 surahs are fixed in the Quran
 */
class SurahController extends Controller
{
    /**
     * Constructor with dependency injection
     */
    public function __construct(
        protected SurahService $surahService,
        protected AdminLoggerService $logger
    ) {}

    /**
     * Display paginated list of all 114 surahs
     *
     * GET /su/portal/v1/admin/surahs
     */
    public function index(Request $request): View|JsonResponse
    {
        // Get pagination and search parameters
        $perPage = $request->integer('per_page', 15);
        $search = $request->string('search')->trim()->toString();
        $sortBy = $request->string('sort_by', 'number')->toString();

        // Get filters from request
        $filters = $request->only(['revelation_place']);

        // Fetch paginated data with filters applied
        $surahs = $this->surahService->paginateWithFilters(
            perPage: $perPage,
            search: $search,
            sortBy: $sortBy,
            filters: $filters
        );

        // Get statistics for stats cards
        $stats = [
            'total' => 114, // Fixed number of surahs
            'meccan' => $this->surahService->countByRevelation('makkah'),
            'medinan' => $this->surahService->countByRevelation('madinah'),
            'total_verses' => $this->surahService->getTotalVersesCount(),
        ];

        // Return JSON for AJAX requests, HTML for page loads
        if ($request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
            return response()->json([
                'success' => true,
                'data' => $surahs->items(),
                'stats' => $stats,
                'meta' => [
                    'current_page' => $surahs->currentPage(),
                    'per_page' => $surahs->perPage(),
                    'total' => $surahs->total(),
                    'last_page' => $surahs->lastPage(),
                ],
            ]);
        }

        return view('admin.surahs.index', compact('surahs', 'stats'));
    }

    /**
     * Show surah details with verses and tafseer tabs
     *
     * GET /su/portal/v1/admin/surahs/{id}
     */
    public function show(int $id): View
    {
        $surah = $this->surahService->getWithDetails($id);

        // Get verse statistics
        $stats = [
            'verses_count' => $surah->verses_count,
            'words_count' => $surah->words_count ?? 0,
            'letters_count' => $surah->letters_count ?? 0,
        ];

        // Get verses for this surah (paginated for performance)
        $verses = \App\Domain\Quran\Models\Verse::select(['id', 'surah_id', 'verse_number', 'verse_key'])
            ->where('surah_id', $id)
            ->with(['translations:id,verse_id,lang,text'])
            ->orderBy('verse_number')
            ->paginate(10);

        // Get tafseer entries for this surah (active only)
        $tafseer = \App\Domain\Quran\Models\VerseTafsir::select(['id', 'verse_id', 'verse_key', 'lang', 'text', 'active'])
            ->whereHas('verse', function ($q) use ($id) {
                $q->where('surah_id', $id);
            })
            ->with(['verse:id,surah_id,verse_key'])
            ->where('active', true)
            ->orderBy('verse_key')
            ->paginate(10);

        return view('admin.surahs.show', compact('surah', 'stats', 'verses', 'tafseer'));
    }

    /**
     * Show edit form for surah metadata
     *
     * GET /su/portal/v1/admin/surahs/{id}/edit
     */
    public function edit(int $id): View
    {
        $surah = $this->surahService->findOrFail($id);

        return view('admin.surahs.edit', compact('surah'));
    }

    /**
     * Update surah metadata (not content - surahs are fixed)
     *
     * PUT/PATCH /su/portal/v1/admin/surahs/{id}
     */
    public function update(UpdateSurahRequest $request, int $id): RedirectResponse
    {
        try {
            // Get old values before update
            $oldSurah = $this->surahService->findOrFail($id);
            $oldValues = $oldSurah->toArray();

            // Perform update
            $surah = $this->surahService->update($id, $request->validated());

            // Invalidate surah caches so public reads reflect the change
            Cache::forget(QuranCacheKeys::surahAll());
            Cache::forget(QuranCacheKeys::surahByNumber($surah->revelation_order));
            Cache::forget(QuranCacheKeys::surahsByRevelationPlace($surah->revelation_place));
            Cache::forget(QuranCacheKeys::surahSelector());
            Cache::forget(QuranCacheKeys::surahTotalVerses());

            // Log the update with diff
            $this->logger->logUpdate($surah, $oldValues, $surah->toArray(), $request->user());

            return redirect()
                ->route('admin.surahs.show', $id)
                ->with('success', 'Surah updated successfully');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Failed to update surah: '.$e->getMessage());
        }
    }

    /**
     * Search surahs (AJAX endpoint)
     *
     * GET /su/portal/v1/admin/surahs/search
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'q' => ['required', 'string', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'revelation_place' => ['nullable', 'string', 'in:makkah,madinah'],
        ]);

        $query = $request->input('q');
        $perPage = $request->integer('per_page', 15);
        $revelationPlace = $request->input('revelation_place');

        $surahs = $this->surahService->search($query, $perPage, $revelationPlace);

        return response()->json([
            'success' => true,
            'data' => $surahs->items(),
            'meta' => [
                'current_page' => $surahs->currentPage(),
                'per_page' => $surahs->perPage(),
                'total' => $surahs->total(),
                'last_page' => $surahs->lastPage(),
            ],
        ]);
    }
}
