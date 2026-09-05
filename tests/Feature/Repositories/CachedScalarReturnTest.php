<?php

declare(strict_types=1);

namespace Tests\Feature\Repositories;

use App\Domain\Quran\Repositories\SurahRepositoryInterface;
use App\Domain\Quran\Repositories\VerseRepositoryInterface;
use App\Domain\Quran\Services\QuranCacheKeys;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Regression: repository counters that return `Cache::remember(...)` under a
 * declared scalar return type.
 *
 * Production runs CACHE_STORE=redis. Redis has no types — everything is a
 * string — and Illuminate\Cache\RedisStore::unserialize() deliberately returns
 * numeric payloads verbatim rather than unserialising them, so `increment()`
 * and `decrement()` keep working on raw counters:
 *
 *     if (is_numeric($value)) { return $value; }   // string, not int
 *
 * So the closure returns int on a cache MISS and the store hands back a STRING
 * on every HIT. A method declared `: int` therefore worked on the first request
 * after a cache clear and threw a TypeError on every request thereafter —
 * 500ing /admin/surahs and /admin/verses.
 *
 * These tests pre-seed the cache with a string, which is exactly the shape
 * Redis returns, and assert the accessor still honours its return type. That
 * reproduces the bug on any cache driver, so it holds under the array store
 * used by the suite.
 */
class CachedScalarReturnTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_surah_count_by_revelation_returns_int_on_a_string_cache_hit(): void
    {
        Cache::put(QuranCacheKeys::surahCountByRevelation('makkah'), '86', 60);

        $count = app(SurahRepositoryInterface::class)->countByRevelation('makkah');

        $this->assertSame(86, $count);
    }

    public function test_sajda_verse_count_returns_int_on_a_string_cache_hit(): void
    {
        Cache::put(QuranCacheKeys::sajdaVersesCount(), '15', 60);

        $count = app(VerseRepositoryInterface::class)->countSajdaVerses();

        $this->assertSame(15, $count);
    }

    public function test_average_verses_per_surah_returns_float_on_a_string_cache_hit(): void
    {
        Cache::put(QuranCacheKeys::surahAverageVerses(), '54.7', 60);

        $average = app(SurahRepositoryInterface::class)->getAverageVersesPerSurah();

        $this->assertSame(54.7, $average);
    }
}
