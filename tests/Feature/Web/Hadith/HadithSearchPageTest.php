<?php

declare(strict_types=1);

namespace Tests\Feature\Web\Hadith;

use App\Domain\Hadith\Models\HadithCollection;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Tests\TestCase;

class HadithSearchPageTest extends TestCase
{
    use DatabaseTruncation;

    /**
     * FULLTEXT is not transactional: InnoDB does not update the index until
     * commit, so RefreshDatabase's wrapping transaction would leave MATCH seeing
     * no rows at all. Truncation is used instead.
     *
     * Only the hadith tables are truncated. Wiping everything also empties the
     * reference data that migrations seed — `languages` above all — and nothing
     * restores it, which silently breaks every later test in the run.
     *
     * @var list<string>
     */
    protected array $tablesToTruncate = [
        'hadith_gradings',
        'hadith_texts',
        'hadith_hadiths',
        'hadith_chapters',
        'hadith_books',
        'hadith_scholars',
        'hadith_collections',
        'hadith_import_runs',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        HadithCollection::query()->firstOrCreate(
            ['code' => 'bukhari'],
            ['name' => ['ar' => 'صحيح البخاري', 'en' => 'Sahih al-Bukhari'], 'is_published' => true],
        );
    }

    public function test_a_no_match_search_renders_a_meaningful_empty_state(): void
    {
        $this->get('/hadith/search?q=zzzznothingmatches')
            ->assertOk()
            ->assertSee(__('hadith.search_no_results'))
            ->assertSee(__('hadith.search_no_results_hint'));
    }

    public function test_a_missing_query_is_rejected_with_a_field_level_message(): void
    {
        $this->get('/hadith/search')
            ->assertRedirect()
            ->assertSessionHasErrors('q');
    }

    public function test_a_one_character_query_is_rejected(): void
    {
        // The floor is two because innodb_ft_min_token_size is 2; a single
        // character can never match and would look broken rather than invalid.
        $this->get('/hadith/search?q=x')
            ->assertRedirect()
            ->assertSessionHasErrors('q');
    }

    public function test_an_over_long_query_is_rejected(): void
    {
        $this->get('/hadith/search?q='.str_repeat('a', 201))
            ->assertRedirect()
            ->assertSessionHasErrors('q');
    }

    public function test_an_unknown_collection_filter_is_rejected(): void
    {
        $this->get('/hadith/search?q=test&collection=nosuchcollection')
            ->assertRedirect()
            ->assertSessionHasErrors('collection');
    }

    public function test_an_invalid_grade_filter_is_rejected(): void
    {
        $this->get('/hadith/search?q=test&grade=excellent')
            ->assertRedirect()
            ->assertSessionHasErrors('grade');
    }

    /** Oversized page sizes clamp rather than error, per the API contract. */
    public function test_an_oversized_per_page_is_accepted_and_clamped(): void
    {
        $this->get('/hadith/search?q=test&per_page=5000')->assertOk();
    }

    public function test_the_search_route_is_rate_limited(): void
    {
        $middleware = collect(\Illuminate\Support\Facades\Route::getRoutes()->getRoutes())
            ->firstWhere(fn ($route) => $route->getName() === 'public.hadith.search')
            ->gatherMiddleware();

        $this->assertContains('throttle:search', $middleware);
    }

    public function test_the_query_is_preserved_in_the_form(): void
    {
        $this->get('/hadith/search?q=intentions')
            ->assertOk()
            ->assertSee('value="intentions"', false);
    }

    /**
     * DatabaseTruncation clears tables BEFORE each test, so the last one in the
     * class leaves its rows committed for whatever runs next — and a
     * RefreshDatabase test then cannot create its own `bukhari`. Clearing on the
     * way out keeps the blast radius inside this class.
     */
    protected function tearDown(): void
    {
        foreach ($this->tablesToTruncate as $table) {
            \Illuminate\Support\Facades\DB::table($table)->delete();
        }

        parent::tearDown();
    }
}
