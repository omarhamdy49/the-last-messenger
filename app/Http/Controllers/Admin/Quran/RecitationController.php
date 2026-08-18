<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Quran;

use App\Domain\Quran\Services\RecitationService;
use App\Domain\Shared\Services\AdminLoggerService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreRecitationRequest;
use App\Http\Requests\Admin\UpdateRecitationRequest;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Admin Recitation Controller
 * Manages a reciter's per-surah audio recordings in the admin portal.
 *
 * All actions are nested under a reciter: /su/portal/v1/admin/reciters/{reciter}/recitations
 */
class RecitationController extends Controller
{
    /**
     * Constructor
     */
    public function __construct(
        protected RecitationService $recitationService,
        protected AdminLoggerService $logger
    ) {}

    /**
     * Store a new recitation for the reciter.
     *
     * POST /su/portal/v1/admin/reciters/{reciter}/recitations
     */
    public function store(StoreRecitationRequest $request, int $reciter): RedirectResponse|JsonResponse
    {
        try {
            $recitation = DB::transaction(function () use ($request) {
                $recitation = $this->recitationService->create($request->validated());
                $this->logger->logCreate($recitation, $request->user());

                return $recitation;
            });

            if ($this->expectsJson($request)) {
                return response()->json([
                    'success' => true,
                    'message' => __('quran.recitations.created'),
                    'data' => $recitation,
                ], 201);
            }

            return redirect()
                ->route('admin.reciters.show', $reciter)
                ->with('success', __('quran.recitations.created'));
        } catch (\Throwable $e) {
            report($e);

            return $this->failure($request, __('quran.recitations.create_failed'));
        }
    }

    /**
     * Update (edit / replace) an existing recitation.
     *
     * PUT/PATCH /su/portal/v1/admin/reciters/{reciter}/recitations/{recitation}
     */
    public function update(
        UpdateRecitationRequest $request,
        int $reciter,
        int $recitation
    ): RedirectResponse|JsonResponse {
        try {
            $updated = DB::transaction(function () use ($request, $reciter, $recitation) {
                $model = $this->recitationService->findForReciter($reciter, $recitation);
                $oldValues = $model->toArray();
                $model = $this->recitationService->update($reciter, $recitation, $request->validated());
                $this->logger->logUpdate($model, $oldValues, $model->toArray(), $request->user());

                return $model;
            });

            if ($this->expectsJson($request)) {
                return response()->json([
                    'success' => true,
                    'message' => __('quran.recitations.updated'),
                    'data' => $updated,
                ]);
            }

            return redirect()
                ->route('admin.reciters.show', $reciter)
                ->with('success', __('quran.recitations.updated'));
        } catch (ModelNotFoundException $e) {
            report($e);

            return $this->notFound($request);
        } catch (\Throwable $e) {
            report($e);

            return $this->failure($request, __('quran.recitations.update_failed'));
        }
    }

    /**
     * Delete a recitation.
     *
     * DELETE /su/portal/v1/admin/reciters/{reciter}/recitations/{recitation}
     */
    public function destroy(
        Request $request,
        int $reciter,
        int $recitation
    ): RedirectResponse|JsonResponse {
        try {
            DB::transaction(function () use ($request, $reciter, $recitation) {
                $model = $this->recitationService->findForReciter($reciter, $recitation);
                $this->recitationService->delete($reciter, $recitation);
                $this->logger->logDelete($model, $request->user());
            });

            if ($this->expectsJson($request)) {
                return response()->json([
                    'success' => true,
                    'message' => __('quran.recitations.deleted'),
                ]);
            }

            return redirect()
                ->route('admin.reciters.show', $reciter)
                ->with('success', __('quran.recitations.deleted'));
        } catch (ModelNotFoundException $e) {
            report($e);

            return $this->notFound($request);
        } catch (\Throwable $e) {
            report($e);

            return $this->failure($request, __('quran.recitations.delete_failed'));
        }
    }

    /**
     * Whether the request expects a JSON response.
     */
    private function expectsJson(Request $request): bool
    {
        return $request->wantsJson()
            || $request->header('X-Requested-With') === 'XMLHttpRequest';
    }

    /**
     * Build a 422 failure response (JSON or redirect-back).
     */
    private function failure(Request $request, string $message): RedirectResponse|JsonResponse
    {
        if ($this->expectsJson($request)) {
            return response()->json([
                'success' => false,
                'message' => $message,
            ], 422);
        }

        return redirect()
            ->back()
            ->withInput()
            ->withErrors(['error' => $message]);
    }

    /**
     * Build a 404 not-found response (JSON or aborts for web).
     */
    private function notFound(Request $request): JsonResponse
    {
        if ($this->expectsJson($request)) {
            return response()->json([
                'success' => false,
                'message' => __('quran.recitations.not_found'),
            ], 404);
        }

        abort(404);
    }
}
