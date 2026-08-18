<?php

declare(strict_types=1);

namespace Tests\Feature\People;

use App\Domain\People\Models\Muslim;
use App\Domain\Shared\Models\Location;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 6,677 companions record a birthplace, all of it English-only, across 32
 * spellings. Medinah (743) + Medina (675) + Madinah (1) are one place stored
 * three ways.
 */
class MuslimBirthPlaceTest extends TestCase
{
    use RefreshDatabase;

    private function muslim(string $slug, ?string $birthPlace, ?int $placeId = null): Muslim
    {
        $muslim = new Muslim([
            'slug' => $slug,
            'name_ar' => 'اختبار',
            'birth_place_id' => $placeId,
        ]);

        if ($birthPlace !== null) {
            $muslim->setTranslation('birth_place', 'en', $birthPlace);
        }

        $muslim->save();

        return $muslim;
    }

    public function test_the_three_spellings_resolve_to_one_place_and_render_identically(): void
    {
        $madinah = Location::factory()->withNames(['ar' => 'المدينة المنورة', 'en' => 'Al-Madinah'])->create();

        $people = collect(['Medinah', 'Medina', 'Madinah'])
            ->map(fn (string $spelling): Muslim => $this->muslim('p-'.strtolower($spelling), $spelling, $madinah->id));

        $names = $people->map(
            fn (Muslim $m): ?string => $m->load('birthPlace.translations')->birthPlace?->name('ar')
        )->unique();

        $this->assertCount(1, $names);
        $this->assertSame('المدينة المنورة', $names->first());
    }

    public function test_the_place_renders_in_the_active_locale(): void
    {
        $place = Location::factory()->withNames(['ar' => 'مكة المكرمة', 'en' => 'Makkah', 'tr' => 'Mekke'])->create();
        $muslim = $this->muslim('p-makkah', 'Makkah', $place->id)->load('birthPlace.translations');

        $this->assertSame('مكة المكرمة', $muslim->birthPlace->name('ar'));
        $this->assertSame('Mekke', $muslim->birthPlace->name('tr'));
    }

    public function test_a_locale_with_no_name_falls_back_to_arabic(): void
    {
        // Arabic is default AND fallback: a place with only an Arabic name must
        // render in Arabic everywhere rather than rendering blank.
        $place = Location::factory()->withNames(['ar' => 'الحبشة'])->create();
        $muslim = $this->muslim('p-abyssinia', 'Abyssinia', $place->id)->load('birthPlace.translations');

        $this->assertSame('الحبشة', $muslim->birthPlace->name('fr'));
    }

    public function test_the_original_text_is_kept_alongside_the_resolved_place(): void
    {
        $place = Location::factory()->withNames(['ar' => 'المدينة المنورة'])->create();
        $muslim = $this->muslim('p-keep', 'Medinah', $place->id);

        $this->assertSame('Medinah', $muslim->fresh()->getTranslation('birth_place', 'en'));
    }

    public function test_an_unresolved_birthplace_keeps_its_recorded_text(): void
    {
        $muslim = $this->muslim('p-ambiguous', 'Makkah or Medinah', null);

        $this->assertNull($muslim->fresh()->birth_place_id);
        $this->assertSame('Makkah or Medinah', $muslim->fresh()->getTranslation('birth_place', 'en'));
    }

    public function test_a_companion_with_no_birthplace_has_neither(): void
    {
        $muslim = $this->muslim('p-none', null);

        $this->assertNull($muslim->fresh()->birth_place_id);
        $this->assertNull($muslim->fresh()->birthPlace);
    }
}
