<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Domain\People\DTOs\MuslimListFilters;
use App\Domain\People\Models\Muslim;
use App\Domain\People\Repositories\MuslimRepositoryInterface;
use App\Domain\Shared\Models\Location;
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
}
