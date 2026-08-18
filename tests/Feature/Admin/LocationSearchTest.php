<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Domain\Shared\Models\Location;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The place picker's data source. It has to match aliases, not just names —
 * an editor who knows the record as "Medina" must find المدينة المنورة.
 */
class LocationSearchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Location::factory()
            ->withAliases(['Medinah', 'Medina', 'Madinah', 'يثرب'])
            ->withNames(['ar' => 'المدينة المنورة', 'en' => 'Al-Madinah'])
            ->create(['slug' => 'al-madinah']);

        Location::factory()
            ->withAliases(['Mecca'])
            ->withNames(['ar' => 'مكة المكرمة', 'en' => 'Makkah'])
            ->create(['slug' => 'makkah']);
    }

    public function test_search_finds_a_place_by_its_arabic_name(): void
    {
        $this->actingAs($this->createAdminUser());

        $this->getJson(route('admin.locations.search', ['q' => 'المدينة']))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.slug', 'al-madinah');
    }

    public function test_search_finds_a_place_by_a_registered_alias(): void
    {
        $this->actingAs($this->createAdminUser());

        foreach (['Medina', 'Medinah', 'يثرب'] as $alias) {
            $this->getJson(route('admin.locations.search', ['q' => $alias]))
                ->assertOk()
                ->assertJsonPath('data.0.slug', 'al-madinah');
        }
    }

    public function test_search_returns_the_localized_name_not_the_alias(): void
    {
        $this->actingAs($this->createAdminUser());

        $this->getJson(route('admin.locations.search', ['q' => 'Medina']))
            ->assertJsonPath('data.0.name', 'المدينة المنورة');
    }

    public function test_search_excludes_inactive_places(): void
    {
        $this->actingAs($this->createAdminUser());
        Location::query()->where('slug', 'makkah')->update(['is_active' => false]);

        $this->getJson(route('admin.locations.search', ['q' => 'مكة']))
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_an_empty_term_returns_nothing_rather_than_everything(): void
    {
        $this->actingAs($this->createAdminUser());

        $this->getJson(route('admin.locations.search', ['q' => '']))
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_search_requires_authentication(): void
    {
        // A JSON client gets 401 rather than a redirect to the login page.
        $this->getJson(route('admin.locations.search', ['q' => 'مكة']))->assertUnauthorized();
    }
}
