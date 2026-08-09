<?php

declare(strict_types=1);

namespace Tests\Performance;

use App\Models\Article;
use App\Domain\Hadith\Models\Hadith;
use App\Domain\Quran\Models\Surah;
use App\Domain\Shared\Services\LanguageCacheService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Translation Performance Test
 *
 * Benchmarks translation retrieval speed, cache performance,
 * and bulk operations with translatable fields.
 */
class TranslationPerformanceTest extends TestCase
{
    use RefreshDatabase;

    protected LanguageCacheService $languageCacheService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->languageCacheService = app(LanguageCacheService::class);

        // Seed basic test data
        $this->seedTestData();
    }

    /**
     * Test language cache service performance
     */
    public function test_language_cache_service_performance(): void
    {
        $iterations = 100;

        // Test 1: Cache miss (first load)
        Cache::flush();
        $startTime = microtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            $this->languageCacheService->getActiveLanguages();
        }
        $cacheMissTime = microtime(true) - $startTime;

        // Test 2: Cache hit (subsequent loads)
        $this->languageCacheService->warmCache();
        $startTime = microtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            $this->languageCacheService->getActiveLanguages();
        }
        $cacheHitTime = microtime(true) - $startTime;

        // Performance metrics
        $improvement = ($cacheMissTime - $cacheHitTime) / $cacheMissTime * 100;

        echo "\n";
        echo "=== Language Cache Performance ===\n";
        echo sprintf("Cache Miss Time (%d iterations): %.4f seconds\n", $iterations, $cacheMissTime);
        echo sprintf("Cache Hit Time  (%d iterations): %.4f seconds\n", $iterations, $cacheHitTime);
        echo sprintf("Performance Improvement: %.2f%%\n", $improvement);
        echo sprintf("Average per call (cached): %.6f seconds\n", $cacheHitTime / $iterations);

        // Assert cache is significantly faster
        $this->assertLessThan($cacheMissTime / 2, $cacheHitTime, 'Cache should be at least 2x faster');
    }

    /**
     * Test translation retrieval performance
     */
    public function test_translation_retrieval_performance(): void
    {
        $iterations = 50;
        $article = Article::factory()->create([
            'title' => [
                'ar' => 'عنوان المقال',
                'en' => 'Article Title',
                'fr' => 'Titre de l\'article',
                'tr' => 'Makale Başlığı',
                'ur' => 'مضمون کا عنوان',
            ],
        ]);

        // Test: Translation access speed
        app()->setLocale('ar');
        $startTime = microtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            $title = $article->title;
        }
        $arabicTime = microtime(true) - $startTime;

        app()->setLocale('en');
        $startTime = microtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            $title = $article->title;
        }
        $englishTime = microtime(true) - $startTime;

        echo "\n";
        echo "=== Translation Access Performance ===\n";
        echo sprintf("Arabic translations (%d iterations): %.6f seconds\n", $iterations, $arabicTime);
        echo sprintf("English translations (%d iterations): %.6f seconds\n", $iterations, $englishTime);
        echo sprintf("Average per access: %.6f seconds\n", ($arabicTime + $englishTime) / ($iterations * 2));

        $this->assertLessThan(1.0, $arabicTime, 'Translation access should be fast');
        $this->assertLessThan(1.0, $englishTime, 'Translation access should be fast');
    }

    /**
     * Test bulk translation operations performance
     */
    public function test_bulk_translation_operations_performance(): void
    {
        $recordCount = 100;

        // Create test records
        Article::factory()->count($recordCount)->create();

        // Test 1: Fetch all without eager loading (baseline)
        Cache::flush();
        DB::enableQueryLog();
        $startTime = microtime(true);

        $articles = Article::limit($recordCount)->get();
        foreach ($articles as $article) {
            $title = $article->title;
        }

        $withoutOptimizationTime = microtime(true) - $startTime;
        $withoutOptimizationQueries = count(DB::getQueryLog());
        DB::disableQueryLog();

        // Test 2: With select optimization
        DB::enableQueryLog();
        $startTime = microtime(true);

        $articles = Article::select(['id', 'title', 'slug'])
            ->limit($recordCount)
            ->get();
        foreach ($articles as $article) {
            $title = $article->title;
        }

        $withOptimizationTime = microtime(true) - $startTime;
        $withOptimizationQueries = count(DB::getQueryLog());
        DB::disableQueryLog();

        // Calculate improvement
        $timeImprovement = ($withoutOptimizationTime - $withOptimizationTime) / $withoutOptimizationTime * 100;

        echo "\n";
        echo "=== Bulk Operations Performance ===\n";
        echo sprintf("Records processed: %d\n", $recordCount);
        echo sprintf("Without optimization: %.6f seconds (%d queries)\n", $withoutOptimizationTime, $withoutOptimizationQueries);
        echo sprintf("With select() optimization: %.6f seconds (%d queries)\n", $withOptimizationTime, $withOptimizationQueries);
        echo sprintf("Time improvement: %.2f%%\n", $timeImprovement);
        echo sprintf("Average per record (optimized): %.6f seconds\n", $withOptimizationTime / $recordCount);

        $this->assertLessThanOrEqual($withoutOptimizationTime, $withOptimizationTime, 'Optimized query should not be slower');
    }

    /**
     * Test translation fallback performance
     */
    public function test_translation_fallback_performance(): void
    {
        $iterations = 50;

        // Create article with incomplete translations
        $article = Article::factory()->create([
            'title' => [
                'ar' => 'عنوان المقال',
                'en' => '', // Empty - should fallback
                'fr' => '', // Empty - should fallback
            ],
        ]);

        // Test fallback performance
        app()->setLocale('en');
        $startTime = microtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            $title = $article->title; // Should fallback to Arabic
        }
        $fallbackTime = microtime(true) - $startTime;

        echo "\n";
        echo "=== Translation Fallback Performance ===\n";
        echo sprintf("Fallback operations (%d iterations): %.6f seconds\n", $iterations, $fallbackTime);
        echo sprintf("Average per fallback: %.6f seconds\n", $fallbackTime / $iterations);

        $this->assertLessThan(1.0, $fallbackTime, 'Fallback should be fast');
        $this->assertEquals('عنوان المقال', $article->title, 'Should fallback to Arabic');
    }

    /**
     * Test cache invalidation performance
     */
    public function test_cache_invalidation_performance(): void
    {
        // Warm cache
        $this->languageCacheService->warmCache();

        // Test cache clear performance
        $startTime = microtime(true);
        $this->languageCacheService->clearCache();
        $clearTime = microtime(true) - $startTime;

        // Test cache refresh performance
        $startTime = microtime(true);
        $this->languageCacheService->refreshCache();
        $refreshTime = microtime(true) - $startTime;

        echo "\n";
        echo "=== Cache Management Performance ===\n";
        echo sprintf("Cache clear time: %.6f seconds\n", $clearTime);
        echo sprintf("Cache refresh time: %.6f seconds\n", $refreshTime);

        $this->assertLessThan(0.5, $clearTime, 'Cache clear should be fast');
        $this->assertLessThan(2.0, $refreshTime, 'Cache refresh should be fast');
    }

    /**
     * Test database index effectiveness
     */
    public function test_database_index_effectiveness(): void
    {
        // Create test data
        Article::factory()->count(50)->create();

        // Test 1: Query with indexed column (status)
        DB::enableQueryLog();
        $startTime = microtime(true);

        Article::where('status', 'published')->get();

        $indexedTime = microtime(true) - $startTime;
        $indexedQueries = DB::getQueryLog();
        DB::disableQueryLog();

        // Test 2: Query with composite index (category_id + published_at)
        DB::enableQueryLog();
        $startTime = microtime(true);

        Article::where('category_id', 1)
            ->orderBy('published_at', 'desc')
            ->get();

        $compositeIndexTime = microtime(true) - $startTime;
        $compositeIndexQueries = DB::getQueryLog();
        DB::disableQueryLog();

        echo "\n";
        echo "=== Database Index Performance ===\n";
        echo sprintf("Indexed column query: %.6f seconds\n", $indexedTime);
        echo sprintf("Composite index query: %.6f seconds\n", $compositeIndexTime);

        $this->assertLessThan(1.0, $indexedTime, 'Indexed query should be fast');
        $this->assertLessThan(1.0, $compositeIndexTime, 'Composite index query should be fast');
    }

    /**
     * Generate performance summary report
     */
    public function test_generate_performance_summary(): void
    {
        echo "\n";
        echo "╔════════════════════════════════════════════════════════════╗\n";
        echo "║         TRANSLATION SYSTEM PERFORMANCE SUMMARY            ║\n";
        echo "╚════════════════════════════════════════════════════════════╝\n";

        // Cache statistics
        $stats = $this->languageCacheService->getCacheStats();
        echo "\nCache Status:\n";
        foreach ($stats as $key => $cached) {
            echo sprintf("  - %s: %s\n", ucfirst($key), $cached ? '✓ Cached' : '✗ Not cached');
        }

        // Language information
        $languages = $this->languageCacheService->getActiveLanguages();
        echo sprintf("\nActive Languages: %d\n", $languages->count());
        foreach ($languages as $lang) {
            echo sprintf("  - %s (%s) - %s\n", $lang->name, $lang->code, $lang->direction);
        }

        // Database statistics
        echo "\nDatabase Statistics:\n";
        echo sprintf("  - Articles: %d\n", Article::count());
        echo sprintf("  - Hadith: %d\n", Hadith::count());
        echo sprintf("  - Surahs: %d\n", Surah::count());

        $this->assertTrue(true, 'Performance summary generated');
    }

    /**
     * Seed test data for performance testing
     */
    protected function seedTestData(): void
    {
        // This will be called by setUp() and can be extended as needed
        // Factories will create the necessary test data
    }
}
