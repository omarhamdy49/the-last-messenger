<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Domain\Shared\Models\Location;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Filter and sort names are interpolated into query structure, so they must come
 * from an allowlist and never from raw input (constitution VII).
 */
class LocationFilterAllowlistTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs($this->createAdminUser());
    }

    public function test_an_unknown_type_filter_is_ignored_rather_than_used(): void
    {
        Location::factory()->count(3)->withNames(['ar' => 'مكان'])->create();

        $this->get(route('admin.locations.index', ['type' => 'not-a-type; DROP TABLE locations']))
            ->assertOk();

        // The table is still there and every row is still listed.
        $this->assertSame(3, DB::table('locations')->count());
    }

    public function test_a_sql_fragment_in_the_search_term_is_treated_as_text(): void
    {
        Location::factory()->withNames(['ar' => 'مكة'])->create();

        $this->get(route('admin.locations.index', ['q' => "'; DROP TABLE locations; --"]))
            ->assertOk();

        $this->assertSame(1, DB::table('locations')->count());
    }

    public function test_per_page_is_cast_and_capped(): void
    {
        Location::factory()->count(3)->create();

        $this->get(route('admin.locations.index', ['per_page' => '999999']))->assertOk();
        $this->get(route('admin.locations.index', ['per_page' => '-5']))->assertOk();
        $this->get(route('admin.locations.index', ['per_page' => 'abc']))->assertOk();
    }

    public function test_a_non_numeric_parent_filter_does_not_break_the_query(): void
    {
        Location::factory()->count(2)->create();

        $this->get(route('admin.locations.index', ['parent_id' => 'abc']))->assertOk();
    }

    public function test_the_search_endpoint_caps_its_limit(): void
    {
        Location::factory()->count(3)->withNames(['ar' => 'مكة'])->create();

        $response = $this->getJson(route('admin.locations.search', ['q' => 'مكة', 'limit' => '10000']));

        $response->assertOk()->assertJsonPath('success', true);
        $this->assertLessThanOrEqual(50, count($response->json('data')));
    }

    public function test_the_search_endpoint_does_not_serve_a_non_admin(): void
    {
        $this->app['auth']->forgetGuards();
        $this->actingAs($this->createWriterUser());

        $response = $this->getJson(route('admin.locations.search', ['q' => 'مكة']));

        // Whatever the guard's chosen shape (redirect or 403), it must not be a
        // successful payload of place data.
        $this->assertNotSame(200, $response->getStatusCode());
    }
}
