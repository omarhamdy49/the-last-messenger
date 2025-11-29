<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Hadith\Models\HadithSyncLog;
use App\Domain\Hadith\Services\BookSyncService;
use App\Domain\Hadith\Services\ChapterSyncService;
use App\Domain\Hadith\Services\CollectionSyncService;
use App\Domain\Hadith\Services\HadithSyncService;
use App\Jobs\SyncCollectionJob;
use App\Services\Sunnah\SunnahApiClient;
use App\Services\Sunnah\SunnahDataTransformer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Console\Helper\Table;

/**
 * Command to sync Hadith data from Sunnah.com API
 *
 * Usage:
 *   php artisan hadith:sync                    # Sync all collections
 *   php artisan hadith:sync --collection=bukhari # Sync specific collection
 *   php artisan hadith:sync --parallel         # Dispatch to queue
 *   php artisan hadith:sync --resume=123       # Resume from checkpoint
 *   php artisan hadith:sync --test-connection  # Test API
 *   php artisan hadith:sync --list-collections # List available collections
 *   php artisan hadith:sync --dry-run          # Simulate without saving
 */
class SyncHadithCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'hadith:sync
                            {--collection= : Sync specific collection by name (e.g., bukhari, muslim)}
                            {--parallel : Dispatch jobs to queue for concurrent processing}
                            {--resume= : Resume from sync log checkpoint (provide sync log ID)}
                            {--dry-run : Simulate sync without saving to database}
                            {--test-connection : Test Sunnah API connection and exit}
                            {--list-collections : List all available collections from API and exit}';

    /**
     * The console command description.
     */
    protected $description = 'مزامنة بيانات الأحاديث من Sunnah.com API - Sync Hadith data from Sunnah.com API';

    /**
     * Exit codes
     */
    private const EXIT_SUCCESS = 0;
    private const EXIT_ERROR = 1;

    /**
     * Service dependencies
     */
    private SunnahApiClient $apiClient;
    private SunnahDataTransformer $transformer;
    private CollectionSyncService $collectionSyncService;
    private BookSyncService $bookSyncService;
    private ChapterSyncService $chapterSyncService;
    private HadithSyncService $hadithSyncService;

    /**
     * Statistics tracking
     */
    private array $stats = [
        'collections_synced' => 0,
        'books_synced' => 0,
        'chapters_synced' => 0,
        'hadiths_synced' => 0,
        'errors' => 0,
        'start_time' => null,
    ];

    /**
     * Create a new command instance.
     */
    public function __construct(
        SunnahApiClient $apiClient,
        SunnahDataTransformer $transformer,
        CollectionSyncService $collectionSyncService,
        BookSyncService $bookSyncService,
        ChapterSyncService $chapterSyncService,
        HadithSyncService $hadithSyncService
    ) {
        parent::__construct();

        $this->apiClient = $apiClient;
        $this->transformer = $transformer;
        $this->collectionSyncService = $collectionSyncService;
        $this->bookSyncService = $bookSyncService;
        $this->chapterSyncService = $chapterSyncService;
        $this->hadithSyncService = $hadithSyncService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->stats['start_time'] = now();

        // Display banner
        $this->displayBanner();

        // Handle specific options
        if ($this->option('test-connection')) {
            return $this->testConnection();
        }

        if ($this->option('list-collections')) {
            return $this->listCollections();
        }

        if ($this->option('resume')) {
            return $this->resumeSync((int) $this->option('resume'));
        }

        if ($this->option('parallel')) {
            return $this->syncParallel();
        }

        // Determine sync mode
        $collectionName = $this->option('collection');
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('🔸 وضع المحاكاة نشط - لن يتم حفظ البيانات');
            $this->warn('🔸 DRY RUN MODE - No data will be saved');
        }

        // Execute sync
        if ($collectionName) {
            return $this->syncCollection($collectionName, $dryRun);
        }

        return $this->syncAll($dryRun);
    }

    /**
     * Display command banner
     */
    private function displayBanner(): void
    {
        $this->info('');
        $this->info('╔═══════════════════════════════════════════════════════════════╗');
        $this->info('║   مزامنة الأحاديث النبوية - Hadith Sync Command           ║');
        $this->info('║   Sunnah.com API Integration                                 ║');
        $this->info('╚═══════════════════════════════════════════════════════════════╝');
        $this->info('');
    }

    /**
     * Test API connection
     */
    private function testConnection(): int
    {
        $this->info('📡 اختبار الاتصال بـ Sunnah API...');
        $this->info('📡 Testing connection to Sunnah API...');
        $this->newLine();

        try {
            $isConnected = $this->apiClient->testConnection();

            if ($isConnected) {
                $this->info('✅ الاتصال ناجح!');
                $this->info('✅ Connection successful!');
                $this->newLine();

                // Get a sample collection to verify data access
                $collections = $this->apiClient->getCollections(limit: 1);
                if (isset($collections->data) && count($collections->data) > 0) {
                    $this->info('✅ تم الوصول إلى البيانات بنجاح');
                    $this->info('✅ Data access verified');
                }

                return self::EXIT_SUCCESS;
            }

            $this->error('❌ فشل الاتصال');
            $this->error('❌ Connection failed');
            return self::EXIT_ERROR;

        } catch (\Exception $e) {
            $this->error('❌ خطأ في الاتصال: ' . $e->getMessage());
            $this->error('❌ Connection error: ' . $e->getMessage());
            Log::error('Hadith sync test connection failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return self::EXIT_ERROR;
        }
    }

    /**
     * List all available collections from API
     */
    private function listCollections(): int
    {
        $this->info('📚 جاري جلب المجموعات المتاحة...');
        $this->info('📚 Fetching available collections...');
        $this->newLine();

        try {
            $response = $this->apiClient->getCollections(limit: 50);

            if (!isset($response->data) || empty($response->data)) {
                $this->warn('⚠️  لم يتم العثور على مجموعات');
                $this->warn('⚠️  No collections found');
                return self::EXIT_SUCCESS;
            }

            // Prepare table data
            $tableData = [];
            foreach ($response->data as $collection) {
                $nameAr = '';
                $nameEn = '';

                if (isset($collection->collection) && is_array($collection->collection)) {
                    foreach ($collection->collection as $item) {
                        if ($item->lang === 'ar') {
                            $nameAr = $item->title ?? '';
                        } elseif ($item->lang === 'en') {
                            $nameEn = $item->title ?? '';
                        }
                    }
                }

                $tableData[] = [
                    $collection->name ?? 'N/A',
                    $nameAr ?: 'N/A',
                    $nameEn ?: 'N/A',
                    $collection->totalAvailableHadith ?? 0,
                    $collection->hasBooks ? '✓' : '✗',
                    $collection->hasChapters ? '✓' : '✗',
                ];
            }

            // Display table
            $this->table(
                ['Slug', 'Arabic Name (الاسم)', 'English Name', 'Hadiths (أحاديث)', 'Books', 'Chapters'],
                $tableData
            );

            $this->newLine();
            $this->info('📊 إجمالي المجموعات: ' . count($tableData));
            $this->info('📊 Total collections: ' . count($tableData));

            return self::EXIT_SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ خطأ في جلب المجموعات: ' . $e->getMessage());
            $this->error('❌ Error fetching collections: ' . $e->getMessage());
            Log::error('Hadith sync list collections failed', [
                'error' => $e->getMessage(),
            ]);
            return self::EXIT_ERROR;
        }
    }

    /**
     * Sync all collections sequentially
     */
    private function syncAll(bool $dryRun = false): int
    {
        $this->info('🚀 بدء مزامنة جميع المجموعات...');
        $this->info('🚀 Starting sync of all collections...');
        $this->newLine();

        try {
            // Create master sync log
            $masterLog = HadithSyncLog::create([
                'sync_type' => 'all_collections',
                'status' => 'in_progress',
                'started_at' => now(),
                'metadata' => [
                    'dry_run' => $dryRun,
                    'command' => 'hadith:sync',
                ],
            ]);

            // Fetch all collections
            $response = $this->apiClient->getCollections(limit: 50);

            if (!isset($response->data) || empty($response->data)) {
                $this->warn('⚠️  لم يتم العثور على مجموعات للمزامنة');
                $this->warn('⚠️  No collections found to sync');
                $masterLog->markCompleted(0, 0, 'No collections found');
                return self::EXIT_SUCCESS;
            }

            $collections = collect($response->data);
            $progressBar = $this->output->createProgressBar($collections->count());
            $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% - %message%');
            $progressBar->setMessage('جاري التحضير... Preparing...');
            $progressBar->start();

            $successCount = 0;
            $errorCount = 0;

            foreach ($collections as $collection) {
                $collectionName = $collection->name ?? 'unknown';
                $progressBar->setMessage("مزامنة {$collectionName}... Syncing {$collectionName}...");

                try {
                    $result = $this->collectionSyncService->syncCollection(
                        $collectionName,
                        $masterLog,
                        $dryRun
                    );

                    if ($result->status === 'completed') {
                        $successCount++;
                        $this->stats['collections_synced']++;
                    } else {
                        $errorCount++;
                        $this->stats['errors']++;
                    }

                } catch (\Exception $e) {
                    $errorCount++;
                    $this->stats['errors']++;
                    Log::error("Failed to sync collection {$collectionName}", [
                        'error' => $e->getMessage(),
                    ]);
                }

                $progressBar->advance();
            }

            $progressBar->setMessage('اكتمل! Completed!');
            $progressBar->finish();
            $this->newLine(2);

            // Update master log
            $masterLog->markCompleted(
                $successCount,
                $errorCount,
                "Synced {$successCount} collections, {$errorCount} errors"
            );

            // Display summary
            $this->displaySummary($masterLog);

            return $errorCount > 0 ? self::EXIT_ERROR : self::EXIT_SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ خطأ فادح في المزامنة: ' . $e->getMessage());
            $this->error('❌ Critical sync error: ' . $e->getMessage());
            Log::error('Hadith sync all failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return self::EXIT_ERROR;
        }
    }

    /**
     * Sync a single collection
     */
    private function syncCollection(string $collectionName, bool $dryRun = false): int
    {
        $this->info("🚀 بدء مزامنة مجموعة: {$collectionName}");
        $this->info("🚀 Starting sync of collection: {$collectionName}");
        $this->newLine();

        try {
            // Create sync log with correct schema fields
            $syncLog = null;
            if (!$dryRun) {
                $syncLog = HadithSyncLog::create([
                    'collection_name' => $collectionName,
                    'type' => 'collection',
                    'status' => 'in_progress',
                    'started_at' => now(),
                    'metadata' => [
                        'dry_run' => $dryRun,
                        'command' => 'hadith:sync --collection=' . $collectionName,
                    ],
                ]);
            }

            // Sync collection metadata
            $this->info('📥 مزامنة معلومات المجموعة...');
            $this->info('📥 Syncing collection metadata...');

            $result = $this->collectionSyncService->syncCollection(
                $collectionName,
                $syncLog,
                $dryRun
            );

            // Display results
            $this->newLine();
            if ($dryRun || $result !== null) {
                $this->info("✅ تمت مزامنة المجموعة بنجاح!");
                $this->info("✅ Collection synced successfully!");

                if (!$dryRun && $result) {
                    $this->table(
                        ['Field', 'Value'],
                        [
                            ['Collection ID', $result->id],
                            ['Name (AR)', $result->name_ar],
                            ['Name (EN)', $result->name_en],
                            ['Compiler', $result->compiler],
                        ]
                    );
                }
            } else {
                $this->error("❌ فشلت مزامنة المجموعة");
                $this->error("❌ Collection sync failed");
            }

            return self::EXIT_SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ خطأ في مزامنة المجموعة: ' . $e->getMessage());
            $this->error('❌ Collection sync error: ' . $e->getMessage());
            Log::error("Hadith sync collection {$collectionName} failed", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return self::EXIT_ERROR;
        }
    }

    /**
     * Dispatch sync jobs to queue for parallel processing
     */
    private function syncParallel(): int
    {
        $this->info('🚀 بدء المزامنة المتوازية...');
        $this->info('🚀 Starting parallel sync...');
        $this->newLine();

        try {
            $collectionName = $this->option('collection');
            $dryRun = $this->option('dry-run');

            // Create parent sync log
            $parentLog = HadithSyncLog::create([
                'sync_type' => $collectionName ? 'collection' : 'all_collections',
                'status' => 'queued',
                'started_at' => now(),
                'metadata' => [
                    'collection' => $collectionName,
                    'dry_run' => $dryRun,
                    'parallel' => true,
                    'command' => 'hadith:sync --parallel',
                ],
            ]);

            $jobCount = 0;

            if ($collectionName) {
                // Dispatch single collection job
                SyncCollectionJob::dispatch($collectionName, $parentLog->id, $dryRun);
                $jobCount = 1;

                $this->info("✅ تم إرسال مهمة مزامنة المجموعة: {$collectionName}");
                $this->info("✅ Dispatched sync job for collection: {$collectionName}");

            } else {
                // Fetch all collections and dispatch jobs
                $response = $this->apiClient->getCollections(limit: 50);

                if (!isset($response->data) || empty($response->data)) {
                    $this->warn('⚠️  لم يتم العثور على مجموعات');
                    $this->warn('⚠️  No collections found');
                    return self::EXIT_SUCCESS;
                }

                foreach ($response->data as $collection) {
                    $name = $collection->name ?? null;
                    if ($name) {
                        SyncCollectionJob::dispatch($name, $parentLog->id, $dryRun);
                        $jobCount++;
                    }
                }

                $this->info("✅ تم إرسال {$jobCount} مهمة مزامنة");
                $this->info("✅ Dispatched {$jobCount} sync jobs");
            }

            // Display instructions
            $this->newLine();
            $this->info('📋 لبدء معالجة المهام، قم بتشغيل workers باستخدام:');
            $this->info('📋 To start processing jobs, run workers using:');
            $this->newLine();
            $this->comment('   php artisan queue:work --queue=sync --workers=10 --timeout=3600');
            $this->newLine();
            $this->info("📊 معرف سجل المزامنة: {$parentLog->id}");
            $this->info("📊 Sync log ID: {$parentLog->id}");
            $this->info("🔍 لمراقبة التقدم: php artisan queue:monitor");
            $this->info("🔍 To monitor progress: php artisan queue:monitor");

            return self::EXIT_SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ خطأ في إرسال المهام: ' . $e->getMessage());
            $this->error('❌ Error dispatching jobs: ' . $e->getMessage());
            Log::error('Hadith sync parallel dispatch failed', [
                'error' => $e->getMessage(),
            ]);
            return self::EXIT_ERROR;
        }
    }

    /**
     * Resume sync from a checkpoint
     */
    private function resumeSync(int $logId): int
    {
        $this->info("🔄 استئناف المزامنة من السجل #{$logId}...");
        $this->info("🔄 Resuming sync from log #{$logId}...");
        $this->newLine();

        try {
            // Load sync log
            $syncLog = HadithSyncLog::find($logId);

            if (!$syncLog) {
                $this->error("❌ لم يتم العثور على سجل المزامنة #{$logId}");
                $this->error("❌ Sync log #{$logId} not found");
                return self::EXIT_ERROR;
            }

            // Check if has checkpoint
            $checkpoint = $syncLog->checkpoint;
            if (!$checkpoint) {
                $this->warn('⚠️  لا توجد نقطة استئناف في هذا السجل');
                $this->warn('⚠️  No checkpoint found in this log');
                return self::EXIT_ERROR;
            }

            // Display checkpoint info
            $this->info('📍 معلومات نقطة الاستئناف:');
            $this->info('📍 Checkpoint information:');
            $this->table(
                ['Field', 'Value'],
                [
                    ['Collection', $checkpoint['collection'] ?? 'N/A'],
                    ['Last Book', $checkpoint['last_book'] ?? 'N/A'],
                    ['Last Page', $checkpoint['last_page'] ?? 'N/A'],
                    ['Books Synced', $checkpoint['books_synced'] ?? 0],
                    ['Hadiths Synced', $checkpoint['hadiths_synced'] ?? 0],
                ]
            );
            $this->newLine();

            // Confirm resume
            if (!$this->confirm('هل تريد الاستئناف من هذه النقطة؟ Resume from this checkpoint?', true)) {
                $this->info('تم الإلغاء. Cancelled.');
                return self::EXIT_SUCCESS;
            }

            // Resume sync
            $collectionName = $checkpoint['collection'] ?? null;
            if (!$collectionName) {
                $this->error('❌ معلومات المجموعة غير متوفرة في نقطة الاستئناف');
                $this->error('❌ Collection information not available in checkpoint');
                return self::EXIT_ERROR;
            }

            $result = $this->collectionSyncService->resumeSync($syncLog);

            if ($result->status === 'completed') {
                $this->info('✅ تمت عملية الاستئناف بنجاح!');
                $this->info('✅ Resume completed successfully!');
                $this->displaySyncStats($result);
                return self::EXIT_SUCCESS;
            }

            $this->error("❌ فشل الاستئناف: {$result->error_message}");
            $this->error("❌ Resume failed: {$result->error_message}");
            return self::EXIT_ERROR;

        } catch (\Exception $e) {
            $this->error('❌ خطأ في استئناف المزامنة: ' . $e->getMessage());
            $this->error('❌ Resume sync error: ' . $e->getMessage());
            Log::error("Hadith sync resume from log {$logId} failed", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return self::EXIT_ERROR;
        }
    }

    /**
     * Display sync statistics
     */
    private function displaySyncStats($result): void
    {
        $this->newLine();
        $this->info('📊 إحصائيات المزامنة - Sync Statistics:');

        $tableData = [
            ['المجموعات - Collections', $result->synced_records ?? 0],
            ['الكتب - Books', $result->metadata['books_synced'] ?? 0],
            ['الفصول - Chapters', $result->metadata['chapters_synced'] ?? 0],
            ['الأحاديث - Hadiths', $result->metadata['hadiths_synced'] ?? 0],
            ['الأخطاء - Errors', $result->failed_records ?? 0],
        ];

        $this->table(['Metric (المقياس)', 'Count (العدد)'], $tableData);

        if ($result->started_at && $result->completed_at) {
            $duration = $result->started_at->diffForHumans($result->completed_at, true);
            $this->info("⏱️  المدة الزمنية: {$duration}");
            $this->info("⏱️  Duration: {$duration}");
        }
    }

    /**
     * Display final summary
     */
    private function displaySummary($syncLog): void
    {
        $this->newLine();
        $this->info('╔═══════════════════════════════════════════════════════════════╗');
        $this->info('║                   ملخص المزامنة - Sync Summary              ║');
        $this->info('╚═══════════════════════════════════════════════════════════════╝');
        $this->newLine();

        $tableData = [
            ['الحالة - Status', $syncLog->status === 'completed' ? '✅ مكتمل - Completed' : '❌ فشل - Failed'],
            ['السجلات المزامنة - Synced Records', $syncLog->synced_records ?? 0],
            ['الأخطاء - Errors', $syncLog->failed_records ?? 0],
            ['وقت البدء - Start Time', $syncLog->started_at?->format('Y-m-d H:i:s') ?? 'N/A'],
            ['وقت الانتهاء - End Time', $syncLog->completed_at?->format('Y-m-d H:i:s') ?? 'N/A'],
        ];

        if ($syncLog->started_at && $syncLog->completed_at) {
            $duration = $syncLog->started_at->diffForHumans($syncLog->completed_at, true);
            $tableData[] = ['المدة - Duration', $duration];
        }

        $this->table(['Metric (المقياس)', 'Value (القيمة)'], $tableData);

        if ($syncLog->error_message) {
            $this->newLine();
            $this->error('رسالة الخطأ - Error Message:');
            $this->error($syncLog->error_message);
        }

        $this->newLine();
        $this->info("📋 معرف السجل: {$syncLog->id}");
        $this->info("📋 Log ID: {$syncLog->id}");

        if ($syncLog->status !== 'completed' && $syncLog->checkpoint) {
            $this->newLine();
            $this->warn('💡 يمكنك استئناف المزامنة باستخدام:');
            $this->warn('💡 You can resume this sync using:');
            $this->comment("   php artisan hadith:sync --resume={$syncLog->id}");
        }

        $this->newLine();
    }
}
