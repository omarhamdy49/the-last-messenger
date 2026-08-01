<?php

declare(strict_types=1);

namespace Tests\Feature\Web;

use App\Domain\Content\Models\HomeSection;
use App\Domain\Content\Services\HomeSectionService;
use Database\Seeders\HomeSectionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class HomeSectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_registration_cta_uses_the_public_registration_route(): void
    {
        $this->seed(HomeSectionsSeeder::class);

        $this->get(route('public.home'))
            ->assertOk()
            ->assertSee('href="'.route('public.register').'"', false);
    }

    public function test_deactivated_section_disappears_from_home(): void
    {
        $this->seed(HomeSectionsSeeder::class);
        HomeSection::query()->where('key', 'seal_band')->update(['is_active' => false]);
        app(HomeSectionService::class)->flush();

        $this->get(route('public.home'))
            ->assertOk()
            ->assertDontSee('data-home-section="seal_band"', false);
    }

    public function test_reordering_changes_render_order(): void
    {
        $this->seed(HomeSectionsSeeder::class);
        HomeSection::query()->where('key', 'cta')->update(['display_order' => 0]);
        app(HomeSectionService::class)->flush();

        $content = $this->get(route('public.home'))->assertOk()->getContent();

        $this->assertLessThan(
            strpos($content, 'data-home-section="hero"'),
            strpos($content, 'data-home-section="cta"'),
        );
    }

    public function test_edited_heading_appears_in_requested_locale(): void
    {
        $this->actingAs($this->createAdminUser());
        $this->seed(HomeSectionsSeeder::class);
        $section = HomeSection::query()->where('key', 'hero_cards')->firstOrFail();

        $this->put(route('admin.content.home-sections.update', $section->id), [
            'heading' => ['ar' => 'استكشف الموسوعة', 'en' => 'Explore Trusted Knowledge'],
            'subheading' => ['ar' => 'اختر من المصادر الموثوقة', 'en' => 'Choose trusted sources'],
        ])->assertRedirect(route('admin.content.home-sections.index'));

        $this->withSession(['locale' => 'en'])
            ->get(route('public.home'))
            ->assertOk()
            ->assertSeeText('Explore Trusted Knowledge')
            ->assertDontSeeText('استكشف الموسوعة');
    }

    public function test_seeded_headings_match_arabic_and_english_home_copy(): void
    {
        $this->seed(HomeSectionsSeeder::class);

        $this->withSession(['locale' => 'ar'])->get(route('public.home'))
            ->assertOk()
            ->assertSeeText('الموسوعة الشاملة لخاتَمُ المُرْسَلِين ﷺ')
            ->assertSeeText('صحابة رسول الله ﷺ')
            ->assertSeeText('محمد رسول الله ﷺ')
            ->assertSeeText('آية اليوم')
            ->assertSeeText('استكشف الموسوعة')
            ->assertSeeText('انضم إلى مجتمعنا');

        $this->withSession(['locale' => 'en'])->get(route('public.home'))
            ->assertOk()
            ->assertSeeText('Comprehensive Encyclopedia of The Last Messenger ﷺ')
            ->assertSeeText('Companions of the Messenger ﷺ')
            ->assertSeeText('Muhammad, Messenger of Allah ﷺ')
            ->assertSeeText('Verse of the Day')
            ->assertSeeText('Explore the Encyclopedia')
            ->assertSeeText('Join Our Community');
    }

    public function test_reseeding_preserves_admin_changes_and_flushes_stale_cache(): void
    {
        $service = app(HomeSectionService::class);
        $this->assertSame([], $service->active('en'));

        $this->seed(HomeSectionsSeeder::class);
        $section = HomeSection::query()->where('key', 'hero')->firstOrFail();
        $section->update(['is_active' => false, 'display_order' => 99]);
        $section->translations()->where('locale', 'en')->update(['heading' => 'Admin heading']);

        $this->seed(HomeSectionsSeeder::class);

        $this->assertSame(7, count($service->active('en')));
        $this->assertDatabaseHas('home_sections', [
            'key' => 'hero',
            'is_active' => false,
            'display_order' => 99,
        ]);
        $this->assertDatabaseHas('home_section_translations', [
            'home_section_id' => $section->id,
            'locale' => 'en',
            'heading' => 'Admin heading',
        ]);
    }

    public function test_resolving_all_sections_and_translations_uses_two_queries(): void
    {
        $this->seed(HomeSectionsSeeder::class);
        $service = app(HomeSectionService::class);
        $service->flush();

        DB::enableQueryLog();
        $sections = $service->active('en');
        $queries = array_filter(
            DB::getQueryLog(),
            fn (array $query): bool => str_contains($query['query'], 'home_section'),
        );

        $this->assertCount(8, $sections);
        $this->assertCount(2, $queries);
    }
}
