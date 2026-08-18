<?php

declare(strict_types=1);

namespace Tests\Feature\Search;

use App\Domain\Seerah\Models\SeerahEvent;
use App\Domain\Shared\Enums\LocationType;
use App\Domain\Shared\Enums\SearchModule;
use App\Domain\Shared\Models\Location;
use App\Domain\Shared\Search\SearchQueryBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

/**
 * A place name should be a way into the content, including where the content
 * words it differently — or does not word it at all.
 */
class LocationSearchResolutionTest extends TestCase
{
    use RefreshDatabase;

    private Location $madinah;

    private Location $uhud;

    protected function setUp(): void
    {
        parent::setUp();

        $this->madinah = Location::factory()
            ->withAliases(['المدينة', 'Medinah', 'Medina'])
            ->withNames(['ar' => 'المدينة المنورة'])
            ->create(['slug' => 'al-madinah', 'type' => LocationType::CITY->value]);

        $this->uhud = Location::factory()
            ->withAliases(['أحد'])
            ->withNames(['ar' => 'جبل أُحُد'])
            ->create([
                'slug' => 'uhud',
                'type' => LocationType::MOUNTAIN->value,
                'parent_id' => $this->madinah->id,
            ]);
    }

    private function search(string $term): Collection
    {
        $query = SeerahEvent::query();

        app(SearchQueryBuilder::class)->applySearch(
            $query,
            SearchModule::SEERAH,
            $term,
        );

        return $query->pluck('id');
    }

    public function test_a_place_term_matches_content_resolved_to_that_place(): void
    {
        $event = SeerahEvent::factory()->create([
            'location' => 'حصون بني قريظة جنوب المدينة',
            'location_id' => $this->madinah->id,
        ]);

        $this->assertTrue($this->search('المدينة')->contains($event->id));
    }

    public function test_a_place_term_matches_content_whose_prose_never_says_it(): void
    {
        // The prose says only "معسكر المسلمين" — the term never appears in it.
        // Only the place reference can connect the two, which is the whole
        // point of resolving the column.
        $event = SeerahEvent::factory()->create([
            'location' => 'معسكر المسلمين',
            'location_id' => $this->madinah->id,
        ]);

        $this->assertTrue($this->search('المدينة')->contains($event->id));
    }

    public function test_an_alias_term_matches_too(): void
    {
        $event = SeerahEvent::factory()->create([
            'location' => 'معسكر المسلمين',
            'location_id' => $this->madinah->id,
        ]);

        $this->assertTrue($this->search('Medina')->contains($event->id));
    }

    public function test_the_existing_prose_match_still_works(): void
    {
        // Additive: an event with no place reference at all must still be found
        // by the words in its own text.
        $event = SeerahEvent::factory()->create([
            'location' => 'المنطقة المحايدة بين الجيشين',
            'location_id' => null,
        ]);

        $this->assertTrue($this->search('المحايدة')->contains($event->id));
    }

    public function test_an_unrelated_place_does_not_match(): void
    {
        $makkah = Location::factory()->withNames(['ar' => 'مكة المكرمة'])->create(['slug' => 'makkah']);

        $event = SeerahEvent::factory()->create([
            'location' => 'معسكر المسلمين',
            'location_id' => $makkah->id,
        ]);

        $this->assertFalse($this->search('المدينة')->contains($event->id));
    }
}
