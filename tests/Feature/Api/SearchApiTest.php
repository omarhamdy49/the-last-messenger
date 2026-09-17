<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Domain\Seerah\Models\SeerahEventType;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\Support\CommitsForFullTextSearch;
use Tests\TestCase;

/**
 * Unified search API: per-module facet counts in the global search response.
 */
final class SearchApiTest extends TestCase
{
    /**
     * DatabaseTruncation, NOT RefreshDatabase — required, not a preference.
     *
     * Unified search now uses MySQL FULLTEXT (MATCH…AGAINST) instead of a full-table LIKE.
     * InnoDB does not populate a FULLTEXT index until the inserting transaction COMMITS, and
     * RefreshDatabase wraps each test in a transaction it rolls back — so MATCH against rows
     * the test just seeded matches nothing. The query is valid and silently returns zero
     * rows, which reads as a search bug rather than a test-isolation artefact.
     */
    use CommitsForFullTextSearch;

    private const SEARCH_TERM = 'السيرة';

    protected function setUp(): void
    {
        parent::setUp();
        Sanctum::actingAs(User::factory()->create(), ['api:access']);
    }

    /**
     * Seed one Seerah event whose Arabic title matches the search term.
     */
    private function makeMatchingSeerahEvent(string $slug): int
    {
        $eventId = (int) DB::table('seerah_events')->insertGetId([
            'slug' => $slug,
            'event_type' => SeerahEventType::query()->firstOrCreate(
                ['slug' => 'other'],
                ['locale' => ['ar' => 'حدث آخر', 'en' => 'Other Event'], 'is_active' => true],
            )->id,
            'status' => 'published',
            'order' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('seerah_event_translations')->insert([
            'event_id' => $eventId,
            'locale' => 'ar',
            'title' => 'حدث من أحداث السيرة النبوية',
            'description' => 'وصف الحدث',
            'is_ready' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $eventId;
    }

    /**
     * Seed one media program whose Arabic title matches the search term.
     */
    private function makeMatchingMediaProgram(string $slug): int
    {
        $programId = (int) DB::table('media_programs')->insertGetId([
            'slug' => $slug,
            'category' => 'seerah',
            'maturity_rating' => 'all',
            'year' => 2024,
            'rating' => 8.0,
            'is_featured' => false,
            'is_trending' => false,
            'views_count' => 0,
            'display_order' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('media_program_translations')->insert([
            'media_program_id' => $programId,
            'locale' => 'ar',
            'title' => 'برنامج السيرة الوثائقي',
            'description' => 'وصف البرنامج',
            'host' => null,
            'is_ready' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $programId;
    }

    public function test_global_search_returns_per_module_counts(): void
    {
        $this->makeMatchingSeerahEvent('search-counted-event');
        $this->makeMatchingMediaProgram('search-counted-program');

        $response = $this->postJson('/api/v1/search', [
            'query' => self::SEARCH_TERM,
            'modules' => ['seerah', 'media'],
        ], [
            'Accept-Language' => 'ar',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.counts.seerah', 1)
            ->assertJsonPath('data.counts.media', 1)
            ->assertJsonPath('data.total_results', 2);
    }

    public function test_counts_match_each_module_result_total(): void
    {
        $this->makeMatchingSeerahEvent('search-totals-event');

        $response = $this->postJson('/api/v1/search', [
            'query' => self::SEARCH_TERM,
            'modules' => ['seerah', 'media'],
        ], [
            'Accept-Language' => 'ar',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.counts.seerah', 1)
            ->assertJsonPath('data.results.seerah.total', 1)
            ->assertJsonPath('data.counts.media', 0)
            ->assertJsonPath('data.results.media.total', 0);
    }

    public function test_global_search_validates_query(): void
    {
        $this->postJson('/api/v1/search', ['query' => ''])
            ->assertStatus(422)
            ->assertJsonValidationErrors('query');
    }
}
