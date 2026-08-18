<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Domain\People\DTOs\MuslimListFilters;
use App\Domain\People\Models\Muslim;
use App\Domain\People\Repositories\MuslimRepositoryInterface;
use App\Domain\Seerah\Models\SeerahEvent;
use App\Domain\Seerah\Repositories\SeerahEventRepositoryInterface;
use App\Domain\Shared\Models\Location;
use App\Domain\Shared\Services\LocationUsageRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The editor's review queue.
 *
 * The distinction that matters: a record with NO place text is not unresolved —
 * it simply names no place, and must never appear in the queue. Only records
 * that name a place in prose but carry no canonical reference belong there.
 */
class UnmappedLocationFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_seerah_queue_lists_only_rows_with_text_and_no_key(): void
    {
        $place = Location::factory()->create();

        $unresolved = SeerahEvent::factory()->create(['location' => 'المنطقة المحايدة بين الجيشين', 'location_id' => null]);
        $resolved = SeerahEvent::factory()->create(['location' => 'مكة المكرمة', 'location_id' => $place->id]);
        $noPlace = SeerahEvent::factory()->create(['location' => null, 'location_id' => null]);
        $emptyPlace = SeerahEvent::factory()->create(['location' => '', 'location_id' => null]);

        $ids = app(SeerahEventRepositoryInterface::class)
            ->paginateWithFilters(['unmapped' => true], 50)
            ->pluck('id');

        $this->assertTrue($ids->contains($unresolved->id));
        $this->assertFalse($ids->contains($resolved->id), 'a resolved row is not in the queue');
        $this->assertFalse($ids->contains($noPlace->id), 'a row naming no place is not unresolved');
        $this->assertFalse($ids->contains($emptyPlace->id), 'an empty string is not place text');
    }

    public function test_the_seerah_index_accepts_the_unmapped_query_parameter(): void
    {
        $this->actingAs($this->createAdminUser());
        SeerahEvent::factory()->create(['location' => 'مكان مجهول', 'location_id' => null]);

        $this->get(route('admin.seerah-events.index', ['unmapped' => 1]))->assertOk();
    }

    public function test_the_companion_queue_lists_only_people_with_text_and_no_key(): void
    {
        $place = Location::factory()->create();

        $unresolved = tap(new Muslim(['slug' => 'p-unresolved', 'name_ar' => 'أ']))
            ->setTranslation('birth_place', 'en', 'Makkah or Medinah');
        $unresolved->save();

        $resolved = tap(new Muslim(['slug' => 'p-resolved', 'name_ar' => 'ب', 'birth_place_id' => $place->id]))
            ->setTranslation('birth_place', 'en', 'Makkah');
        $resolved->save();

        $noPlace = Muslim::create(['slug' => 'p-none', 'name_ar' => 'ج']);

        $ids = app(MuslimRepositoryInterface::class)
            ->paginate(new MuslimListFilters(publishedOnly: false, perPage: 50, unmappedBirthPlaceOnly: true))
            ->pluck('id');

        $this->assertTrue($ids->contains($unresolved->id));
        $this->assertFalse($ids->contains($resolved->id));
        $this->assertFalse($ids->contains($noPlace->id), 'a person with no birthplace is not unresolved');
    }

    public function test_the_registry_counts_match_the_filtered_lists(): void
    {
        SeerahEvent::factory()->count(2)->create(['location' => 'مكان', 'location_id' => null]);
        SeerahEvent::factory()->create(['location' => null, 'location_id' => null]);

        $counts = app(LocationUsageRegistry::class)->countUnresolved(['seerah_events' => 'location']);

        $this->assertSame(2, $counts['seerah_events']);
    }

    public function test_assigning_a_place_removes_a_row_from_the_queue(): void
    {
        $place = Location::factory()->create();
        $event = SeerahEvent::factory()->create(['location' => 'مكان', 'location_id' => null]);

        $repository = app(SeerahEventRepositoryInterface::class);
        $this->assertTrue($repository->paginateWithFilters(['unmapped' => true], 50)->pluck('id')->contains($event->id));

        $event->location_id = $place->id;
        $event->save();

        $this->assertFalse($repository->paginateWithFilters(['unmapped' => true], 50)->pluck('id')->contains($event->id));
    }
}
