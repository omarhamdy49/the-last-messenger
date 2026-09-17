<?php

declare(strict_types=1);

namespace Tests\Feature\Search;

use App\Domain\Seerah\Models\SeerahEvent;
use App\Domain\Seerah\Models\SeerahEventType;
use App\Domain\Shared\Enums\LocationType;
use App\Domain\Shared\Enums\SearchModule;
use App\Domain\Shared\Models\Location;
use App\Domain\Shared\Search\SearchQueryBuilder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Tests\Support\CommitsForFullTextSearch;
use Tests\TestCase;

/**
 * A place name should be a way into the content, including where the content
 * words it differently — or does not word it at all.
 */
class LocationSearchResolutionTest extends TestCase
{
    // The title/description match runs through FULLTEXT, which only sees committed rows.
    use CommitsForFullTextSearch;

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

    /**
     * One event with a single Arabic translation, so the search term appears
     * nowhere but where a test puts it.
     *
     * Built without the factory on purpose: its random translations would have
     * to be deleted or updated, and on a just-truncated table InnoDB FULLTEXT
     * intermittently hides rows written after such a delete (reused doc ids),
     * which made the title match fail about half the time.
     */
    private function event(?int $locationId, string $title = 'معسكر المسلمين', ?string $description = null): SeerahEvent
    {
        $type = SeerahEventType::query()->firstOrCreate(['slug' => 'other'], ['locale' => ['ar' => 'آخر'], 'is_active' => true]);

        $event = SeerahEvent::query()->create([
            'slug' => 'event-'.Str::lower(Str::random(8)),
            'event_type' => $type->id,
            'location_id' => $locationId,
            'status' => 'published',
        ]);
        $event->translations()->create(['locale' => 'ar', 'title' => $title, 'description' => $description, 'is_ready' => true]);

        return $event;
    }

    public function test_a_place_term_matches_content_resolved_to_that_place(): void
    {
        $event = $this->event($this->madinah->id, 'حصون بني قريظة جنوب المدينة');

        $this->assertTrue($this->search('المدينة')->contains($event->id));
    }

    public function test_a_place_term_matches_content_whose_prose_never_says_it(): void
    {
        // The prose says only "معسكر المسلمين" — the term never appears in it.
        // Only the place reference can connect the two.
        $event = $this->event($this->madinah->id);

        $this->assertTrue($this->search('المدينة')->contains($event->id));
    }

    public function test_an_alias_term_matches_too(): void
    {
        $event = $this->event($this->madinah->id);

        $this->assertTrue($this->search('Medina')->contains($event->id));
    }

    public function test_a_title_or_description_match_still_works(): void
    {
        // Additive: an event with no place reference at all must still be found
        // by the words in its own text.
        $byTitle = $this->event(null, 'المنطقة المحايدة بين الجيشين');

        $byDescription = $this->event(null, 'عنوان آخر', 'وقعت في الأرض المحايدة');

        $found = $this->search('المحايدة');

        $this->assertTrue($found->contains($byTitle->id));
        $this->assertTrue($found->contains($byDescription->id));
    }

    public function test_an_unrelated_place_does_not_match(): void
    {
        $makkah = Location::factory()->withNames(['ar' => 'مكة المكرمة'])->create(['slug' => 'makkah']);

        $event = $this->event($makkah->id);

        $this->assertFalse($this->search('المدينة')->contains($event->id));
    }
}
