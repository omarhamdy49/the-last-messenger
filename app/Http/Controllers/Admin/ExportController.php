<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domain\Blog\Exporters\ArticleExporter;
use App\Domain\Blog\Repositories\ArticleRepositoryInterface;
use App\Domain\Hadith\Exporters\HadithExporter;
use App\Domain\Hadith\Exporters\HadithNarratorExporter;
use App\Domain\Hadith\Repositories\HadithRepositoryInterface;
use App\Domain\Hadith\Repositories\HadithNarratorRepositoryInterface;
// use App\Domain\Media\Exporters\MediaProgramExporter;
// use App\Domain\Media\Repositories\MediaProgramRepositoryInterface;
use App\Domain\Quran\Exporters\ReciterExporter;
use App\Domain\Quran\Exporters\SurahExporter;
use App\Domain\Quran\Exporters\TafseerExporter;
use App\Domain\Quran\Exporters\VerseExporter;
use App\Domain\Quran\Repositories\ReciterRepositoryInterface;
use App\Domain\Quran\Repositories\SurahRepositoryInterface;
use App\Domain\Quran\Repositories\TafseerRepositoryInterface;
use App\Domain\Quran\Repositories\VerseRepositoryInterface;
use App\Domain\Sahaba\Exporters\SahabiExporter;
use App\Domain\Sahaba\Repositories\SahabiRepositoryInterface;
use App\Domain\Seerah\Exporters\SeerahEventExporter;
use App\Domain\Seerah\Repositories\SeerahEventRepositoryInterface;
use App\Domain\Shared\Enums\ExportFormat;
use App\Domain\Shared\Enums\ExportModule;
use App\Domain\Shared\Models\Export;
use App\Domain\Shared\Services\AdminLoggerService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ExportRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Export Controller
 * Handles bulk export of all modules to CSV/Excel
 */
class ExportController extends Controller
{
    /**
     * Constructor
     */
    public function __construct(
        protected AdminLoggerService $logger,
        protected ArticleRepositoryInterface $articleRepository,
        protected HadithRepositoryInterface $hadithRepository,
        protected HadithNarratorRepositoryInterface $narratorRepository,
        // protected MediaProgramRepositoryInterface $mediaProgramRepository,
        protected SeerahEventRepositoryInterface $seerahEventRepository,
        protected SahabiRepositoryInterface $sahabiRepository,
        protected SurahRepositoryInterface $surahRepository,
        protected VerseRepositoryInterface $verseRepository,
        protected ReciterRepositoryInterface $reciterRepository,
        protected TafseerRepositoryInterface $tafseerRepository
    ) {}

    /**
     * Export data from specified module
     *
     * POST /su/portal/v1/admin/export
     */
    public function export(ExportRequest $request): StreamedResponse|JsonResponse
    {
        try {
            $data = $request->validatedWithDefaults();

            // Create export record
            $exportRecord = Export::create([
                'user_id' => $request->user()->id,
                'module' => $data['module'],
                'format' => $data['format'],
                'filters' => $data['filters'],
                'status' => 'pending',
                'file_name' => $this->generateFilename($data['module'], $data['format']),
            ]);

            // Get exporter instance
            $exporter = $this->getExporter(
                ExportModule::from($data['module']),
                $data['language'],
                $data['filters'],
                $data['columns']
            );

            if (! $exporter) {
                $exportRecord->markAsFailed();

                return response()->json([
                    'success' => false,
                    'message' => 'Invalid module specified',
                ], 400);
            }

            // Get record count
            $collection = $exporter->collection();
            $recordCount = $collection->count();

            // Update export record with count
            $exportRecord->update(['record_count' => $recordCount]);

            // Generate filename
            $filename = $this->generateFilename($data['module'], $data['format']);
            $format = ExportFormat::from($data['format']);

            // Export to file
            $filePath = $exporter->exportToFile($format, pathinfo($filename, PATHINFO_FILENAME));

            // Get file size
            $fileSize = file_exists($filePath) ? filesize($filePath) : 0;

            // Mark export as completed
            $exportRecord->markAsCompleted($filePath, $fileSize);

            // Log export action
            $this->logger->logBulkAction(
                'export',
                Export::class,
                [$exportRecord->id],
                $request->user()
            );

            // Return file download
            return response()->download($filePath, $filename, [
                'Content-Type' => $format->getMimeType(),
                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            ])->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            if (isset($exportRecord)) {
                $exportRecord->markAsFailed();
            }

            return response()->json([
                'success' => false,
                'message' => 'Export failed: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get available columns for a module (AJAX)
     *
     * GET /su/portal/v1/admin/export/{module}/columns
     */
    public function getColumns(string $module): JsonResponse
    {
        try {
            $moduleEnum = ExportModule::from($module);
            $exporter = $this->getExporter($moduleEnum);

            if (! $exporter) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid module',
                ], 400);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'available_columns' => $exporter->getAvailableColumns(),
                    'default_columns' => $exporter->getDefaultColumns(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get export history
     *
     * GET /su/portal/v1/admin/export/history
     */
    public function history(Request $request): JsonResponse
    {
        $perPage = $request->integer('per_page', 20);

        $exports = Export::with('user')
            ->where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $exports->items(),
            'meta' => [
                'current_page' => $exports->currentPage(),
                'per_page' => $exports->perPage(),
                'total' => $exports->total(),
                'last_page' => $exports->lastPage(),
            ],
        ]);
    }

    /**
     * Get export statistics
     *
     * GET /su/portal/v1/admin/export/stats
     */
    public function stats(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $stats = [
            'total_exports' => Export::where('user_id', $userId)->count(),
            'completed_exports' => Export::where('user_id', $userId)->where('status', 'completed')->count(),
            'failed_exports' => Export::where('user_id', $userId)->where('status', 'failed')->count(),
            'total_records_exported' => Export::where('user_id', $userId)->where('status', 'completed')->sum('record_count'),
            'total_file_size' => Export::where('user_id', $userId)->where('status', 'completed')->sum('file_size'),
            'exports_by_module' => Export::where('user_id', $userId)
                ->selectRaw('module, COUNT(*) as count')
                ->groupBy('module')
                ->get()
                ->pluck('count', 'module'),
            'exports_by_format' => Export::where('user_id', $userId)
                ->selectRaw('format, COUNT(*) as count')
                ->groupBy('format')
                ->get()
                ->pluck('count', 'format'),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Delete old export files
     *
     * DELETE /su/portal/v1/admin/export/cleanup
     */
    public function cleanup(Request $request): JsonResponse
    {
        try {
            // Delete export files older than 24 hours
            $oldExports = Export::where('status', 'completed')
                ->where('created_at', '<', now()->subDay())
                ->get();

            $deletedCount = 0;
            foreach ($oldExports as $export) {
                if ($export->file_path && file_exists($export->file_path)) {
                    unlink($export->file_path);
                    $deletedCount++;
                }
            }

            // Update export records
            Export::where('status', 'completed')
                ->where('created_at', '<', now()->subDay())
                ->update(['file_path' => null]);

            return response()->json([
                'success' => true,
                'message' => "تم حذف {$deletedCount} ملف تصدير قديم",
                'data' => ['deleted_count' => $deletedCount],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'فشل تنظيف الملفات: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get exporter instance based on module
     */
    protected function getExporter(
        ExportModule $module,
        string $language = 'en',
        array $filters = [],
        array $columns = []
    ): ?object {
        return match ($module) {
            ExportModule::ARTICLES => new ArticleExporter(
                $this->articleRepository,
                $language,
                $filters,
                $columns
            ),
            ExportModule::HADITH => new HadithExporter(
                $this->hadithRepository,
                $language,
                $filters,
                $columns
            ),
            ExportModule::HADITH_NARRATORS => new HadithNarratorExporter(
                $this->narratorRepository,
                $language,
                $filters,
                $columns
            ),
            // DISABLED: Media programs exporter
            // ExportModule::MEDIA_PROGRAMS => new MediaProgramExporter(
            //     $this->mediaProgramRepository,
            //     $language,
            //     $filters,
            //     $columns
            // ),
            ExportModule::SEERAH_EVENTS => new SeerahEventExporter(
                $this->seerahEventRepository,
                $language,
                $filters,
                $columns
            ),
            ExportModule::SAHABA => new SahabiExporter(
                $this->sahabiRepository,
                $language,
                $filters,
                $columns
            ),
            ExportModule::QURAN_SURAHS => new SurahExporter(
                $this->surahRepository,
                $language,
                $filters,
                $columns
            ),
            ExportModule::QURAN_VERSES => new VerseExporter(
                $this->verseRepository,
                $language,
                $filters,
                $columns
            ),
            ExportModule::QURAN_RECITERS => new ReciterExporter(
                $this->reciterRepository,
                $language,
                $filters,
                $columns
            ),
            ExportModule::QURAN_TAFSEER => new TafseerExporter(
                $this->tafseerRepository,
                $language,
                $filters,
                $columns
            ),
            default => null,
        };
    }

    /**
     * Generate filename for export
     */
    protected function generateFilename(string $module, string $format): string
    {
        $timestamp = now()->format('Ymd_His');

        return "{$module}-{$format}-{$timestamp}.{$format}";
    }
}
