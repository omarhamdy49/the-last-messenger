<?php

declare(strict_types=1);

namespace Tests\Feature\Quran;

use App\Domain\Quran\Enums\RevelationPlace;
use App\Domain\Quran\Models\Surah;
use App\Domain\Quran\Repositories\SurahRepositoryInterface;
use App\Domain\Shared\Models\Location;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The surah place is ADDITIVE. `revelation_place` classifies the period of
 * revelation (مكية = before the Hijra) and is read in 14 files; it is never
 * derived from, nor overwritten by, the place.
 */
class SurahRevelationLocationTest extends TestCase
{
    use RefreshDatabase;

    private function surah(int $id, string $place, ?int $locationId = null): void
    {
        DB::table('quran_surahs')->insert([
            'id' => $id,
            'revelation_place' => $place,
            'revelation_location_id' => $locationId,
            'revelation_order' => $id,
            'name_ar' => "سورة {$id}",
            'name_en' => "Surah {$id}",
            'verses_count' => 5,
        ]);
    }

    public function test_a_surah_exposes_its_place_alongside_its_classification(): void
    {
        $makkah = Location::factory()->withNames(['ar' => 'مكة المكرمة'])->create([
            'latitude' => '21.3891000', 'longitude' => '39.8579000',
        ]);
        $this->surah(1, 'makkah', $makkah->id);

        $surah = Surah::with('revelationLocation.translations')->find(1);

        $this->assertSame('makkah', $surah->revelation_place);
        $this->assertSame('مكة المكرمة', $surah->revelationLocation->name('ar'));
        $this->assertTrue($surah->revelationLocation->hasCoordinates());
    }

    public function test_the_classification_is_not_derived_from_the_place(): void
    {
        // A Makki surah revealed at Taif: the classification is about the period,
        // the place is about geography. Both stand, neither rewrites the other.
        $taif = Location::factory()->withNames(['ar' => 'الطائف'])->create();
        $this->surah(1, 'makkah', $taif->id);

        $surah = Surah::with('revelationLocation.translations')->find(1);

        $this->assertSame('makkah', $surah->revelation_place);
        $this->assertSame('الطائف', $surah->revelationLocation->name('ar'));
    }

    public function test_filtering_by_revelation_place_still_reads_the_enum(): void
    {
        $place = Location::factory()->create();
        $this->surah(1, 'makkah', $place->id);
        $this->surah(2, 'madinah', $place->id);

        // Both surahs point at the SAME location, so a filter that read the
        // place instead of the enum would return both. It must return one each.
        $repository = app(SurahRepositoryInterface::class);

        $this->assertSame([1], $repository->getByRevelationPlace(RevelationPlace::MAKKAH)->pluck('id')->all());
        $this->assertSame([2], $repository->getByRevelationPlace(RevelationPlace::MADINAH)->pluck('id')->all());
    }

    public function test_the_enum_still_governs_the_vocabulary(): void
    {
        $this->assertSame(['makkah', 'madinah'], RevelationPlace::values());
        $this->assertSame('مكية', RevelationPlace::MAKKAH->label());
        $this->assertSame('مدنية', RevelationPlace::MADINAH->label());
    }

    public function test_deleting_the_place_leaves_the_classification_intact(): void
    {
        $place = Location::factory()->create();
        $this->surah(1, 'madinah', $place->id);

        $place->delete();

        $surah = Surah::find(1);

        $this->assertSame('madinah', $surah->revelation_place);
        $this->assertNull($surah->revelation_location_id);
    }
}
