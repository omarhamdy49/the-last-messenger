<?php

declare(strict_types=1);

namespace Tests\Feature\Web;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Public unified-search page: real per-module facet counts on the sidebar
 * content-type checkboxes (B-7), with and without an active type filter.
 */
final class SearchPageTest extends TestCase
{
    use RefreshDatabase;

    private const SEARCH_TERM = 'السيرة';

    private function makeMatchingSeerahEvent(string $slug): void
    {
        $eventId = (int) DB::table('seerah_events')->insertGetId([
            'slug' => $slug,
            'seerah_event_type_id' => DB::table('seerah_event_types')->where('slug', 'other')->value('id'),
            'status' => 'published',
            'importance' => 'medium',
            'views_count' => 0,
            'display_order' => 0,
            'is_featured' => false,
            'is_pivotal' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('seerah_event_translations')->insert([
            'seerah_event_id' => $eventId,
            'locale' => 'ar',
            'title' => 'حدث من أحداث السيرة النبوية',
            'description' => 'وصف الحدث',
            'is_ready' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_search_page_renders_facet_counts(): void
    {
        $this->makeMatchingSeerahEvent('facets-event');

        $this->get(route('public.search', ['q' => self::SEARCH_TERM]))
            ->assertOk()
            ->assertViewIs('public.search.index')
            ->assertViewHas('facets', function (array $facets): bool {
                return ($facets['seerah'] ?? 0) === 1
                    && array_key_exists('quran', $facets)
                    && array_key_exists('hadith', $facets)
                    && array_key_exists('article', $facets)
                    && array_key_exists('sahaba', $facets);
            });
    }

    public function test_type_filter_keeps_real_counts_for_excluded_modules(): void
    {
        $this->makeMatchingSeerahEvent('filtered-facets-event');

        // Filter to hadith only — the seerah checkbox must still show its
        // real count (computed via a COUNT query, not 0).
        $this->get(route('public.search', ['q' => self::SEARCH_TERM, 'type' => ['hadith']]))
            ->assertOk()
            ->assertViewHas('activeTypes', ['hadith'])
            ->assertViewHas('facets', fn (array $facets): bool => ($facets['seerah'] ?? 0) === 1)
            ->assertViewHas('results', fn (array $results): bool => $results === []);
    }

    public function test_empty_query_renders_discovery_state(): void
    {
        $this->get(route('public.search'))
            ->assertOk()
            ->assertViewHas('totalResults', 0)
            ->assertViewHas('facets', fn (array $facets): bool => array_sum($facets) === 0);
    }
}
