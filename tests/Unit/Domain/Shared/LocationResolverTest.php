<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Shared;

use App\Domain\Shared\Enums\LocationType;
use App\Domain\Shared\Models\Location;
use App\Domain\Shared\Services\LocationResolver;
use App\Domain\Shared\Services\ResolutionOutcome;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Longest-alias-first selection, and the refusal to guess.
 *
 * Every example here is drawn from the live tables — see the worked-examples
 * table in specs/003-locations-module/data-model.md.
 */
class LocationResolverTest extends TestCase
{
    use RefreshDatabase;

    private LocationResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();

        $makkah = Location::factory()
            ->withAliases(['مكة المكرمة', 'مكة', 'بمكة', 'Makkah', 'Mecca'])
            ->create(['slug' => 'makkah', 'type' => LocationType::CITY->value]);

        $madinah = Location::factory()
            ->withAliases(['المدينة المنورة', 'المدينة', 'بالمدينة', 'Medinah', 'Medina', 'Madinah'])
            ->create(['slug' => 'al-madinah', 'type' => LocationType::CITY->value]);

        Location::factory()
            ->withAliases(['المسجد النبوي'])
            ->create(['slug' => 'masjid-an-nabawi', 'type' => LocationType::MOSQUE->value, 'parent_id' => $madinah->id]);

        Location::factory()
            ->withAliases(['المسجد الحرام'])
            ->create(['slug' => 'masjid-al-haram', 'type' => LocationType::MOSQUE->value, 'parent_id' => $makkah->id]);

        Location::factory()
            ->withAliases(["al-Ta'if", "Ta'if", 'الطائف'])
            ->create(['slug' => 'at-taif', 'type' => LocationType::CITY->value]);

        Location::factory()
            ->withAliases(['الحبشة', 'Abyssinia'])
            ->create(['slug' => 'abyssinia', 'type' => LocationType::COUNTRY->value]);

        Location::factory()
            ->withAliases(['اليمن', 'Yemen', 'Yeman'])
            ->create(['slug' => 'yemen', 'type' => LocationType::REGION->value]);

        $this->resolver = app(LocationResolver::class);
    }

    private function slugFor(string $text): ?string
    {
        $outcome = $this->resolver->resolve($text);

        return $outcome->locationId === null
            ? null
            : Location::query()->whereKey($outcome->locationId)->value('slug');
    }

    public function test_the_deepest_matching_place_wins_over_its_own_ancestor(): void
    {
        // Both the mosque and its parent city appear in the text. The mosque is
        // the answer. Note the city's alias "المدينة المنورة" is LONGER than
        // "المسجد النبوي", so plain longest-alias-first gets this wrong — depth
        // is what makes it right.
        $this->assertSame('masjid-an-nabawi', $this->slugFor('المسجد النبوي بالمدينة'));
        $this->assertSame('masjid-an-nabawi', $this->slugFor('المسجد النبوي بالمدينة المنورة'));
    }

    public function test_diacritics_in_the_source_do_not_prevent_a_match(): void
    {
        $this->assertSame('makkah', $this->slugFor('شِعب أبي طالب بمكة'));
        $this->assertSame('makkah', $this->slugFor('شِعب بني هاشم بمكة المكرمة'));
    }

    public function test_a_single_embedded_place_is_found(): void
    {
        $this->assertSame('al-madinah', $this->slugFor('حصون بني قريظة جنوب المدينة'));
        $this->assertSame('al-madinah', $this->slugFor('حمراء الأسد على نحو 8 أميال جنوب المدينة'));
    }

    public function test_text_naming_no_place_resolves_to_nothing(): void
    {
        $outcome = $this->resolver->resolve('المنطقة المحايدة بين الجيشين');

        $this->assertNull($outcome->locationId);
        $this->assertSame(ResolutionOutcome::NO_MATCH, $outcome->reason);
    }

    public function test_text_naming_two_places_is_left_unresolved_rather_than_guessed(): void
    {
        foreach (['Makkah or Medinah', 'Yemen/Makkah', 'Abyssinia or Medina', 'من مكة إلى الحبشة'] as $text) {
            $outcome = $this->resolver->resolve($text);

            $this->assertNull($outcome->locationId, "«{$text}» should not resolve");
            $this->assertSame(ResolutionOutcome::AMBIGUOUS, $outcome->reason, "«{$text}» should be ambiguous");
        }
    }

    public function test_the_madinah_spellings_all_resolve_to_one_place(): void
    {
        $slugs = array_map($this->slugFor(...), ['Medinah', 'Medina', 'Madinah']);

        $this->assertSame(['al-madinah', 'al-madinah', 'al-madinah'], $slugs);
    }

    public function test_the_article_prefix_does_not_split_a_place(): void
    {
        $this->assertSame('at-taif', $this->slugFor("al-Ta'if"));
        $this->assertSame('at-taif', $this->slugFor("Ta'if"));
    }

    public function test_an_inactive_place_is_not_matched(): void
    {
        Location::query()->where('slug', 'abyssinia')->update(['is_active' => false]);
        $this->resolver->forget();

        $outcome = $this->resolver->resolve('الحبشة');

        $this->assertNull($outcome->locationId);
    }

    public function test_empty_text_resolves_to_nothing_without_error(): void
    {
        foreach (['', '   ', null] as $text) {
            $outcome = $this->resolver->resolve($text);

            $this->assertNull($outcome->locationId);
            $this->assertSame(ResolutionOutcome::NO_MATCH, $outcome->reason);
        }
    }
}
