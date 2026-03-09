<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domain\Sahaba\Services\SahabiService;
use App\Domain\Shared\Services\AdminLoggerService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSahabiRequest;
use App\Http\Requests\Admin\UpdateSahabiRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Admin Sahabi Controller
 * Handles Sahabi management for admin portal
 */
class SahabiController extends Controller
{
    /**
     * Constructor
     */
    public function __construct(
        protected SahabiService $sahabiService,
        protected AdminLoggerService $logger
    ) {}

    /**
     * Display a listing of all Sahaba
     *
     * GET /su/portal/v1/admin/sahaba
     */
    public function index(Request $request): View|JsonResponse
    {
        $perPage = $request->integer('per_page', 15);
        $sahaba = $this->sahabiService->paginate($perPage, ['*'], ['translations']);

        $sahabiCounts = DB::table('sahaba')
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN gender = 'male' THEN 1 ELSE 0 END) as male,
                SUM(CASE WHEN gender = 'female' THEN 1 ELSE 0 END) as female
            ")
            ->whereNull('deleted_at')
            ->first();

        $stats = [
            'total'  => (int) ($sahabiCounts->total ?? 0),
            'male'   => (int) ($sahabiCounts->male ?? 0),
            'female' => (int) ($sahabiCounts->female ?? 0),
        ];

        // Return JSON for AJAX requests
        if ($request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
            return response()->json([
                'success' => true,
                'data' => $sahaba->items(),
                'stats' => $stats,
                'meta' => [
                    'current_page' => $sahaba->currentPage(),
                    'per_page' => $sahaba->perPage(),
                    'total' => $sahaba->total(),
                    'last_page' => $sahaba->lastPage(),
                ],
            ]);
        }

        return view('admin.sahaba.index', compact('sahaba', 'stats'));
    }

    /**
     * Show the form for creating a new Sahabi
     *
     * GET /su/portal/v1/admin/sahaba/create
     */
    public function create(): View
    {
        return view('admin.sahaba.create');
    }

    /**
     * Show the form for editing the specified Sahabi
     *
     * GET /su/portal/v1/admin/sahaba/{id}/edit
     */
    public function edit(int $id): View
    {
        $sahabi = $this->sahabiService->find($id);

        if (! $sahabi) {
            abort(404, 'Sahabi not found');
        }

        $sahabi->loadMissing('translations');

        return view('admin.sahaba.edit', compact('sahabi'));
    }

    /**
     * Store a newly created Sahabi
     *
     * POST /su/portal/v1/admin/sahaba
     */
    public function store(StoreSahabiRequest $request): RedirectResponse
    {
        $data = $request->validated();

        // Separate translatable data from model data
        $translatableData = $this->extractTranslatableData($data);

        // Handle is_featured checkbox (unchecked checkboxes are not sent)
        $data['is_featured'] = $request->boolean('is_featured');

        $sahabi = $this->sahabiService->create($data);

        // Sync translations
        $sahabi->syncTranslations($translatableData);

        // Handle profile image (file upload or media picker selection)
        if ($request->hasFile('profile_image')) {
            $sahabi->addMedia($request->file('profile_image'))
                ->toMediaCollection('profile_image');
        } elseif ($request->has('profile_image_media_id')) {
            $media = \Spatie\MediaLibrary\MediaCollections\Models\Media::find($request->input('profile_image_media_id'));
            if ($media) {
                $media->copy($sahabi, 'profile_image');
            }
        }

        // Handle gallery (multiple images)
        if ($request->hasFile('gallery')) {
            foreach ($request->file('gallery') as $file) {
                $sahabi->addMedia($file)->toMediaCollection('gallery');
            }
        }

        if ($request->has('gallery_media_ids')) {
            foreach ($request->input('gallery_media_ids', []) as $mediaId) {
                $media = \Spatie\MediaLibrary\MediaCollections\Models\Media::find($mediaId);
                if ($media) {
                    $media->copy($sahabi, 'gallery');
                }
            }
        }

        // Refresh the model to include media
        $sahabi->refresh();

        // Log the creation
        $this->logger->logCreate($sahabi, $request->user());

        return redirect()->route('admin.sahaba.index')
            ->with('success', 'تم إنشاء الصحابي بنجاح');
    }

    /**
     * Display the specified Sahabi
     *
     * GET /su/portal/v1/admin/sahaba/{id}
     */
    public function show(int $id): View
    {
        $sahabi = $this->sahabiService->find($id);

        if (! $sahabi) {
            abort(404, 'Sahabi not found');
        }

        $sahabi->loadMissing('translations');

        return view('admin.sahaba.show', compact('sahabi'));
    }

    /**
     * Update the specified Sahabi
     *
     * PUT/PATCH /su/portal/v1/admin/sahaba/{id}
     */
    public function update(UpdateSahabiRequest $request, int $id): RedirectResponse
    {
        // Get old values before update
        $oldSahabi = $this->sahabiService->find($id);

        if (! $oldSahabi) {
            abort(404, 'Sahabi not found');
        }

        $oldValues = $oldSahabi->toArray();

        $data = $request->validated();

        // Separate translatable data from model data
        $translatableData = $this->extractTranslatableData($data);

        // Handle is_featured checkbox (unchecked checkboxes are not sent)
        $data['is_featured'] = $request->boolean('is_featured');

        // Perform update
        $this->sahabiService->update($id, $data);

        $sahabi = $this->sahabiService->find($id);

        // Sync translations
        $sahabi->syncTranslations($translatableData);

        // Handle profile image (file upload or media picker selection)
        if ($request->hasFile('profile_image')) {
            $sahabi->clearMediaCollection('profile_image');
            $sahabi->addMedia($request->file('profile_image'))
                ->toMediaCollection('profile_image');
        } elseif ($request->has('profile_image_media_id')) {
            $sahabi->clearMediaCollection('profile_image');
            $media = \Spatie\MediaLibrary\MediaCollections\Models\Media::find($request->input('profile_image_media_id'));
            if ($media) {
                $media->copy($sahabi, 'profile_image');
            }
        }

        // Handle gallery - replace mode
        if ($request->has('replace_gallery') && $request->input('replace_gallery')) {
            $sahabi->clearMediaCollection('gallery');
        }

        // Handle gallery (multiple images)
        if ($request->hasFile('gallery')) {
            foreach ($request->file('gallery') as $file) {
                $sahabi->addMedia($file)->toMediaCollection('gallery');
            }
        }

        if ($request->has('gallery_media_ids')) {
            foreach ($request->input('gallery_media_ids', []) as $mediaId) {
                $media = \Spatie\MediaLibrary\MediaCollections\Models\Media::find($mediaId);
                if ($media) {
                    $media->copy($sahabi, 'gallery');
                }
            }
        }

        // Refresh the model to include media
        $sahabi->refresh();

        // Log the update with diff
        $this->logger->logUpdate($sahabi, $oldValues, $sahabi->toArray(), $request->user());

        return redirect()->route('admin.sahaba.index')
            ->with('success', 'تم تحديث الصحابي بنجاح');
    }

    /**
     * Remove the specified Sahabi
     *
     * DELETE /su/portal/v1/admin/sahaba/{id}
     */
    public function destroy(int $id): RedirectResponse
    {
        // Get model before deletion
        $sahabi = $this->sahabiService->find($id);

        if (! $sahabi) {
            abort(404, 'Sahabi not found');
        }

        // Perform deletion
        $this->sahabiService->delete($id);

        // Log the deletion
        $this->logger->logDelete($sahabi, request()->user());

        return redirect()->route('admin.sahaba.index')
            ->with('success', 'تم حذف الصحابي بنجاح');
    }

    /**
     * Bulk delete Sahaba
     *
     * POST /su/portal/v1/admin/sahaba/bulk-delete
     */
    public function bulkDelete(Request $request): RedirectResponse
    {
        $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:sahaba,id'],
        ]);

        $ids = $request->input('ids');
        $deleted = 0;
        foreach ($ids as $id) {
            try {
                $this->sahabiService->delete($id);
                $deleted++;
            } catch (\Exception $e) {
                // Continue deleting
            }
        }

        // Log bulk action
        $this->logger->logBulkAction(
            'bulk_delete',
            \App\Domain\Sahaba\Models\Sahabi::class,
            $ids,
            $request->user()
        );

        return redirect()->route('admin.sahaba.index')
            ->with('success', "تم حذف {$deleted} صحابي بنجاح");
    }

    /**
     * Search Sahaba
     *
     * GET /su/portal/v1/admin/sahaba/search
     */
    public function search(Request $request)
    {
        $request->validate([
            'q' => ['required', 'string', 'min:2'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = $request->input('q');
        $perPage = $request->integer('per_page', 15);

        $sahaba = $this->sahabiService->searchCompanions($query, $perPage);

        return response()->json([
            'success' => true,
            'data' => $sahaba->items(),
            'meta' => [
                'current_page' => $sahaba->currentPage(),
                'last_page' => $sahaba->lastPage(),
                'per_page' => $sahaba->perPage(),
                'total' => $sahaba->total(),
            ],
        ]);
    }

    /**
     * Extract translatable fields from validated data.
     * Removes them from $data (by reference) so the model doesn't receive them.
     *
     * @param array<string, mixed> $data
     * @return array<string, array<string, string>>
     */
    private function extractTranslatableData(array &$data): array
    {
        $translatableFields = ['name', 'full_name', 'kunya', 'laqab', 'biography', 'virtues', 'famous_narrations'];
        $translatableData = [];

        foreach ($translatableFields as $field) {
            if (isset($data[$field]) && is_array($data[$field])) {
                $translatableData[$field] = $data[$field];
                unset($data[$field]);
            }
        }

        return $translatableData;
    }
}
