<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domain\Quran\Services\QuranCacheKeys;
use App\Domain\Quran\Services\SurahService;
use App\Domain\Quran\Services\VerseService;
use App\Domain\Shared\Services\AdminLoggerService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreVerseRequest;
use App\Http\Requests\Admin\UpdateVerseRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

/**
 * Admin Verse Controller
 * Handles Quran verse (ayah) management with full CRUD operations
 */
class VerseController extends Controller
{
    /**
     * Constructor with dependency injection
     */
    public function __construct(
        protected VerseService $verseService,
        protected SurahService $surahService,
        protected AdminLoggerService $logger
    ) {}

    /**
     * Display paginated list of verses
     *
     * GET /su/portal/v1/admin/verses
     */
    public function index(Request $request): View|JsonResponse
    {
        // Get pagination and search parameters
        $perPage = $request->integer('per_page', 15);
        $search = $request->string('search')->trim()->toString();
        $sortBy = $request->string('sort_by', 'verse_key')->toString();

        // Get filters from request
        $filters = $request->only(['surah_id', 'juz_number', 'page_number', 'is_sajda']);

        // Fetch paginated data with filters applied
        $verses = $this->verseService->paginateWithFilters(
            perPage: $perPage,
            search: $search,
            sortBy: $sortBy,
            filters: $filters
        );

        // Get statistics for stats cards
        $stats = [
            'total' => $this->verseService->count(),
            'total_surahs' => 114,
            'sajda_verses' => $this->verseService->countSajdaVerses(),
            'total_juz' => 30,
        ];

        // Return JSON for AJAX requests, HTML for page loads
        if ($request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
            return response()->json([
                'success' => true,
                'data' => $verses->items(),
                'stats' => $stats,
                'meta' => [
                    'current_page' => $verses->currentPage(),
                    'per_page' => $verses->perPage(),
                    'total' => $verses->total(),
                    'last_page' => $verses->lastPage(),
                ],
            ]);
        }

        // Get surahs for filter dropdown
        $surahs = $this->getSurahsForSelector();

        return view('admin.verses.index', compact('verses', 'stats', 'surahs'));
    }

    /**
     * Show create verse form
     *
     * GET /su/portal/v1/admin/verses/create
     */
    public function create(): View
    {
        $surahs = $this->getSurahsForSelector();

        return view('admin.verses.create', compact('surahs'));
    }

    /**
     * Store new verse
     *
     * POST /su/portal/v1/admin/verses
     */
    public function store(StoreVerseRequest $request): RedirectResponse
    {
        try {
            $verse = $this->verseService->create($request->validated());

            // Invalidate verse caches affected by the new verse
            Cache::forget(QuranCacheKeys::versesBySurah($verse->surah_id));
            Cache::forget(QuranCacheKeys::surahTotalVerses());
            Cache::forget(QuranCacheKeys::sajdaVersesCount());
            if ($verse->is_sajda) {
                Cache::forget(QuranCacheKeys::sajdaVerses());
            }

            // Log the creation
            $this->logger->logCreate($verse, $request->user());

            return redirect()
                ->route('admin.verses.show', $verse->id)
                ->with('success', 'Verse created successfully');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Failed to create verse: '.$e->getMessage());
        }
    }

    /**
     * Show verse details
     *
     * GET /su/portal/v1/admin/verses/{id}
     */
    public function show(int $id): View
    {
        $verse = $this->verseService->getWithRelations($id);

        // Get verse statistics
        $stats = [
            'words_count' => $verse->words_count ?? 0,
            'letters_count' => $verse->letters_count ?? 0,
            'tafseer_count' => $verse->tafsirs()->count(),
        ];

        return view('admin.verses.show', compact('verse', 'stats'));
    }

    /**
     * Show edit form
     *
     * GET /su/portal/v1/admin/verses/{id}/edit
     */
    public function edit(int $id): View
    {
        $verse = $this->verseService->findOrFail($id);
        $surahs = $this->getSurahsForSelector();

        return view('admin.verses.edit', compact('verse', 'surahs'));
    }

    /**
     * Update verse
     *
     * PUT/PATCH /su/portal/v1/admin/verses/{id}
     */
    public function update(UpdateVerseRequest $request, int $id): RedirectResponse
    {
        try {
            // Get old values before update
            $oldVerse = $this->verseService->findOrFail($id);
            $oldValues = $oldVerse->toArray();

            // Perform update
            $verse = $this->verseService->update($id, $request->validated());

            // Invalidate verse caches affected by the update
            Cache::forget(QuranCacheKeys::versesBySurah($verse->surah_id));
            if ($verse->surah_id !== ($oldValues['surah_id'] ?? $verse->surah_id)) {
                Cache::forget(QuranCacheKeys::versesBySurah($oldValues['surah_id']));
            }
            Cache::forget(QuranCacheKeys::sajdaVerses());
            Cache::forget(QuranCacheKeys::sajdaVersesCount());

            // Log the update with diff
            $this->logger->logUpdate($verse, $oldValues, $verse->toArray(), $request->user());

            return redirect()
                ->route('admin.verses.show', $id)
                ->with('success', 'Verse updated successfully');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Failed to update verse: '.$e->getMessage());
        }
    }

    /**
     * Delete verse
     *
     * DELETE /su/portal/v1/admin/verses/{id}
     */
    public function destroy(int $id): RedirectResponse
    {
        try {
            // Get model before deletion
            $verse = $this->verseService->findOrFail($id);

            // Perform deletion
            $this->verseService->delete($id);

            // Invalidate verse caches affected by the deletion
            Cache::forget(QuranCacheKeys::versesBySurah($verse->surah_id));
            Cache::forget(QuranCacheKeys::surahTotalVerses());
            Cache::forget(QuranCacheKeys::sajdaVerses());
            Cache::forget(QuranCacheKeys::sajdaVersesCount());

            // Log the deletion
            $this->logger->logDelete($verse, request()->user());

            return redirect()
                ->route('admin.verses.index')
                ->with('success', 'Verse deleted successfully');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Failed to delete verse: '.$e->getMessage());
        }
    }

    /**
     * Bulk delete multiple verses
     *
     * POST /su/portal/v1/admin/verses/bulk-delete
     */
    public function bulkDelete(Request $request): RedirectResponse|JsonResponse
    {
        $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:quran_verses,id'],
        ]);

        $ids = $request->input('ids');

        try {
            $deleted = $this->verseService->bulkDelete($ids);

            // Log bulk action
            $this->logger->logBulkAction(
                'bulk_delete',
                \App\Domain\Quran\Models\Verse::class,
                $ids,
                $request->user()
            );

            $message = "Deleted {$deleted} verses successfully";

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'deleted' => $deleted,
                ]);
            }

            return redirect()
                ->route('admin.verses.index')
                ->with('success', $message);
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete verses: '.$e->getMessage(),
                ], 422);
            }

            return redirect()
                ->back()
                ->with('error', 'Failed to delete verses: '.$e->getMessage());
        }
    }

    /**
     * Search verses (AJAX endpoint)
     *
     * GET /su/portal/v1/admin/verses/search
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'q' => ['required', 'string', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'surah_id' => ['nullable', 'integer', 'exists:quran_surahs,id'],
            'language' => ['nullable', 'string', 'in:ar,en,fr,tr,ur'],
        ]);

        $query = $request->input('q');
        $perPage = $request->integer('per_page', 15);
        $surahId = $request->input('surah_id');
        $language = $request->input('language', 'en');

        $verses = $this->verseService->search($query, $perPage, $surahId, $language);

        return response()->json([
            'success' => true,
            'data' => $verses->items(),
            'meta' => [
                'current_page' => $verses->currentPage(),
                'per_page' => $verses->perPage(),
                'total' => $verses->total(),
                'last_page' => $verses->lastPage(),
            ],
        ]);
    }

    /**
     * Helper: Get surahs for dropdown selector
     * Cached for 30 minutes
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function getSurahsForSelector()
    {
        return cache()->remember(QuranCacheKeys::surahSelector(), now()->addMinutes(30), function () {
            return \App\Domain\Quran\Models\Surah::select(['id', 'revelation_order', 'name_ar'])
                ->orderBy('revelation_order')
                ->get();
        });
    }
}
