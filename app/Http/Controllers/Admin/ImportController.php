<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domain\Blog\Importers\ArticleImporter;
use App\Domain\Quran\Importers\QuranSurahImporter;
use App\Domain\Quran\Importers\QuranVerseImporter;
use App\Domain\Quran\Importers\ReciterImporter;
use App\Domain\Quran\Importers\TafseerImporter;
use App\Domain\Seerah\Importers\SeerahEventImporter;
use App\Domain\Shared\Models\Import;
use App\Domain\Shared\Repositories\ImportRepositoryInterface;
use App\Domain\Shared\Services\AdminLoggerService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ImportExecuteRequest;
use App\Http\Requests\Admin\ImportRequest;
use App\Jobs\ProcessImport;
use App\Support\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Import Controller
 *
 * Handles bulk import operations from CSV/Excel files
 */
class ImportController extends Controller
{
    use ApiResponse;

    /**
     * Constructor
     */
    public function __construct(
        private readonly ImportRepositoryInterface $importRepository,
        private readonly AdminLoggerService $adminLogger
    ) {
        $this->middleware('auth:admin');
    }

    /**
     * Show import form for a specific module
     *
     * GET /admin/import/{module}
     */
    public function importForm(string $module): JsonResponse
    {
        if (! $this->isValidModule($module)) {
            return $this->errorResponse('Invalid module', 400);
        }

        $columns = $this->getModuleColumns($module);

        return $this->successResponse([
            'module' => $module,
            'required_columns' => $columns['required'],
            'optional_columns' => $columns['optional'],
            'sample_data' => $this->getSampleData($module),
        ]);
    }

    /**
     * Parse and validate uploaded file
     *
     * POST /admin/import/{module}/parse
     */
    public function parseFile(string $module, ImportRequest $request): JsonResponse
    {
        try {
            if (! $this->isValidModule($module)) {
                return $this->errorResponse('Invalid module', 400);
            }

            // Store uploaded file
            $file = $request->file('file');
            $fileName = time().'_'.$file->getClientOriginalName();
            $filePath = $file->storeAs('imports/'.$module, $fileName);

            // Create import record
            $import = Import::create([
                'user_id' => $request->user()->id,
                'module' => $module,
                'file_name' => $file->getClientOriginalName(),
                'file_path' => $filePath,
                'file_type' => $file->getClientOriginalExtension(),
                'file_size' => $file->getSize(),
                'status' => 'pending',
                'options' => [
                    'has_header' => $request->boolean('has_header', true),
                    'skip_duplicates' => $request->boolean('skip_duplicates', false),
                    'update_existing' => $request->boolean('update_existing', false),
                    'dry_run' => $request->boolean('dry_run', false),
                ],
            ]);

            // Get importer and parse file
            $importer = $this->getImporter($import);
            $parseResult = $importer->parseFile();

            if (! $parseResult['success']) {
                $import->markAsFailed($parseResult['message']);

                return $this->errorResponse($parseResult['message'], 400);
            }

            // Log action
            $this->adminLogger->logCustomAction(
                action: 'import_parsed',
                model: $import,
                user: $request->user(),
                newValues: [
                    'module' => $module,
                    'file_name' => $import->file_name,
                    'total_rows' => $import->total_rows,
                ]
            );

            return $this->successResponse([
                'import_id' => $import->id,
                'total_rows' => $import->total_rows,
                'preview' => $parseResult['preview'],
                'message' => 'File parsed successfully',
            ]);
        } catch (Throwable $e) {
            Log::error('Import parsing failed', [
                'module' => $module,
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse('Failed to parse file: '.$e->getMessage(), 500);
        }
    }

    /**
     * Validate import data
     *
     * POST /admin/import/{module}/validate
     */
    public function validateData(Request $request): JsonResponse
    {
        try {
            $importId = $request->input('import_id');
            $import = $this->importRepository->find($importId);

            if (! $import) {
                return $this->errorResponse('Import not found', 404);
            }

            // Get importer and validate
            $importer = $this->getImporter($import);
            $errors = $importer->validate();

            // Group errors by severity
            $criticalErrors = array_filter($errors, fn ($e) => $e->isError());
            $warnings = array_filter($errors, fn ($e) => $e->isWarning());

            return $this->successResponse([
                'is_valid' => count($criticalErrors) === 0,
                'total_errors' => count($criticalErrors),
                'total_warnings' => count($warnings),
                'errors' => array_map(fn ($e) => $e->toArray(), array_slice($criticalErrors, 0, 20)),
                'warnings' => array_map(fn ($e) => $e->toArray(), array_slice($warnings, 0, 20)),
                'message' => count($criticalErrors) === 0
                    ? 'Validation passed successfully'
                    : 'Validation failed with '.count($criticalErrors).' errors',
            ]);
        } catch (Throwable $e) {
            Log::error('Import validation failed', [
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse('Validation failed: '.$e->getMessage(), 500);
        }
    }

    /**
     * Preview import data
     *
     * GET /admin/import/{importId}/preview
     */
    public function preview(int $importId): JsonResponse
    {
        try {
            $import = $this->importRepository->find($importId);

            if (! $import) {
                return $this->errorResponse('Import not found', 404);
            }

            $importer = $this->getImporter($import);
            $preview = $importer->preview(10);

            return $this->successResponse([
                'import_id' => $import->id,
                'module' => $import->module,
                'total_rows' => $import->total_rows,
                'preview' => $preview,
            ]);
        } catch (Throwable $e) {
            return $this->errorResponse('Failed to preview: '.$e->getMessage(), 500);
        }
    }

    /**
     * Execute import
     *
     * POST /admin/import/{module}/execute
     */
    public function execute(string $module, ImportExecuteRequest $request): JsonResponse
    {
        try {
            $import = Import::findOrFail($request->input('import_id'));

            // Check if import belongs to authenticated user or user is admin
            if ($import->user_id !== $request->user()->id && ! $request->user()->hasRole('super_admin')) {
                return $this->errorResponse('Unauthorized', 403);
            }

            // Check if already processing or completed
            if ($import->isProcessing() || $import->isCompleted()) {
                return $this->errorResponse('Import is already being processed or completed', 400);
            }

            // For large files (> 1000 rows), use queue
            if ($import->total_rows > 1000) {
                ProcessImport::dispatch($import);

                return $this->successResponse([
                    'import_id' => $import->id,
                    'status' => 'queued',
                    'message' => 'Import queued for processing. You will be notified when complete.',
                ]);
            }

            // For small files, process immediately
            $importer = $this->getImporter($import);
            $result = $importer->import();

            // Log action
            $this->adminLogger->logCustomAction(
                action: 'import_completed',
                model: $import,
                user: $request->user(),
                newValues: [
                    'module' => $module,
                    'total_rows' => $result->totalRows,
                    'success_count' => $result->successCount,
                    'error_count' => $result->errorCount,
                    'success_rate' => $result->getSuccessRate(),
                ]
            );

            return $this->successResponse([
                'import_id' => $import->id,
                'status' => 'completed',
                'result' => $result->toArray(),
                'message' => 'Import completed successfully',
            ]);
        } catch (Throwable $e) {
            Log::error('Import execution failed', [
                'module' => $module,
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse('Import failed: '.$e->getMessage(), 500);
        }
    }

    /**
     * Check import status
     *
     * GET /admin/import/{importId}/status
     */
    public function status(int $importId): JsonResponse
    {
        try {
            $import = $this->importRepository->getWithErrors($importId);

            if (! $import) {
                return $this->errorResponse('Import not found', 404);
            }

            return $this->successResponse([
                'import_id' => $import->id,
                'module' => $import->module,
                'status' => $import->status,
                'total_rows' => $import->total_rows,
                'success_count' => $import->success_count,
                'error_count' => $import->error_count,
                'skipped_count' => $import->skipped_count,
                'success_rate' => $import->getSuccessRate(),
                'started_at' => $import->started_at?->toIso8601String(),
                'completed_at' => $import->completed_at?->toIso8601String(),
                'processing_time_seconds' => $import->processing_time_seconds,
                'error_message' => $import->error_message,
                'has_errors' => $import->errors()->count() > 0,
            ]);
        } catch (Throwable $e) {
            return $this->errorResponse('Failed to get status: '.$e->getMessage(), 500);
        }
    }

    /**
     * Get import history
     *
     * GET /admin/import/history
     */
    public function history(Request $request): JsonResponse
    {
        try {
            $perPage = (int) $request->input('per_page', 15);
            $userId = $request->user()->hasRole('super_admin')
                ? $request->input('user_id')
                : $request->user()->id;

            $imports = $userId
                ? $this->importRepository->getByUser($userId, $perPage)
                : $this->importRepository->paginate($perPage);

            return $this->paginatedResponse($imports);
        } catch (Throwable $e) {
            return $this->errorResponse('Failed to get history: '.$e->getMessage(), 500);
        }
    }

    /**
     * Get import errors
     *
     * GET /admin/import/{importId}/errors
     */
    public function errors(int $importId, Request $request): JsonResponse
    {
        try {
            $import = $this->importRepository->getWithErrors($importId);

            if (! $import) {
                return $this->errorResponse('Import not found', 404);
            }

            $perPage = (int) $request->input('per_page', 50);
            $errors = $import->errors()->paginate($perPage);

            return $this->paginatedResponse($errors);
        } catch (Throwable $e) {
            return $this->errorResponse('Failed to get errors: '.$e->getMessage(), 500);
        }
    }

    /**
     * Delete import record
     *
     * DELETE /admin/import/{importId}
     */
    public function destroy(int $importId, Request $request): JsonResponse
    {
        try {
            $import = Import::findOrFail($importId);

            // Check authorization
            if ($import->user_id !== $request->user()->id && ! $request->user()->hasRole('super_admin')) {
                return $this->errorResponse('Unauthorized', 403);
            }

            // Delete file
            if (Storage::exists($import->file_path)) {
                Storage::delete($import->file_path);
            }

            // Delete import (cascade deletes errors and mappings)
            $import->delete();

            // Log action
            $this->adminLogger->logDelete($import, $request->user());

            return $this->successResponse(['message' => 'Import deleted successfully']);
        } catch (Throwable $e) {
            return $this->errorResponse('Failed to delete import: '.$e->getMessage(), 500);
        }
    }

    /**
     * Get appropriate importer for import
     */
    private function getImporter(Import $import): mixed
    {
        return match ($import->module) {
            'articles' => new ArticleImporter($import),
            'seerah_events' => new SeerahEventImporter($import),
            'quran_surahs' => new QuranSurahImporter($import),
            'quran_verses' => new QuranVerseImporter($import),
            'reciters' => new ReciterImporter($import),
            'tafseer' => new TafseerImporter($import),
            default => throw \App\Domain\Shared\Exceptions\ImporterNotFoundException::forModule($import->module),
        };
    }

    /**
     * Check if module is valid
     */
    private function isValidModule(string $module): bool
    {
        return in_array($module, [
            'articles',
            'seerah_events',
            'sahaba',
            'quran_surahs',
            'quran_verses',
            'reciters',
            'tafseer',
        ]);
    }

    /**
     * Get required and optional columns for a module
     *
     * @return array{required: array<string>, optional: array<string>}
     */
    private function getModuleColumns(string $module): array
    {
        return match ($module) {
            'articles' => [
                'required' => ['title_ar', 'title_en', 'content_ar', 'content_en', 'author_email'],
                'optional' => ['title_fr', 'title_tr', 'title_ur', 'content_fr', 'content_tr', 'content_ur', 'excerpt_ar', 'excerpt_en', 'excerpt_fr', 'excerpt_tr', 'excerpt_ur', 'category_name', 'tags', 'status', 'published_at'],
            ],
            'seerah_events' => [
                'required' => ['title_ar', 'title_en', 'description_ar', 'description_en', 'event_date'],
                'optional' => ['title_fr', 'title_tr', 'title_ur', 'description_fr', 'description_tr', 'description_ur', 'hijri_date', 'location', 'status'],
            ],
            'sahaba' => [
                'required' => ['name_ar', 'name_en'],
                'optional' => ['name_fr', 'name_tr', 'name_ur', 'tribe', 'birth_date', 'death_date', 'battles', 'status'],
            ],
            'quran_surahs' => [
                'required' => ['number', 'name_ar', 'name_en', 'revelation_place', 'verses_count'],
                'optional' => ['name_fr', 'name_tr', 'name_ur', 'meaning_ar', 'meaning_en', 'meaning_fr', 'meaning_tr', 'meaning_ur', 'revelation_order', 'words_count', 'letters_count', 'juz', 'page_number', 'description_ar', 'description_en', 'description_fr', 'description_tr', 'description_ur', 'theme_ar', 'theme_en', 'theme_fr', 'theme_tr', 'theme_ur'],
            ],
            'quran_verses' => [
                'required' => ['surah_number', 'ayah_number', 'arabic_text', 'english_translation'],
                'optional' => ['transliteration', 'juz_number', 'page_number', 'french_translation', 'turkish_translation', 'urdu_translation'],
            ],
            'reciters' => [
                'required' => ['name_ar', 'name_en'],
                'optional' => ['name_fr', 'name_tr', 'name_ur', 'country', 'style', 'biography_ar', 'biography_en', 'biography_fr', 'biography_tr', 'biography_ur', 'featured'],
            ],
            'tafseer' => [
                'required' => ['surah_number', 'ayah_number', 'source', 'author', 'tafseer_ar', 'tafseer_en'],
                'optional' => ['tafseer_fr', 'tafseer_tr', 'tafseer_ur'],
            ],
            default => ['required' => [], 'optional' => []],
        };
    }

    /**
     * Get sample data for a module
     *
     * @return array<array<string, string>>
     */
    private function getSampleData(string $module): array
    {
        return match ($module) {
            'articles' => [
                [
                    'title_ar' => 'عنوان المقال',
                    'title_en' => 'Article Title',
                    'content_ar' => 'محتوى المقال بالعربية',
                    'content_en' => 'Article content in English',
                    'author_email' => 'author@example.com',
                    'category_name' => 'Seerah',
                    'tags' => 'Prophet,Biography,History',
                    'status' => 'published',
                ],
            ],
            default => [],
        };
    }
}
