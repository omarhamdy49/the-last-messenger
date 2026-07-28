<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Content;

use App\Domain\Content\Models\HomeSection;
use App\Domain\Content\Services\HomeSectionService;
use Database\Seeders\HomeSectionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class HomeSectionAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_reorder_endpoint_requires_authentication(): void
    {
        $this->seed(HomeSectionsSeeder::class);
        $section = HomeSection::query()->firstOrFail();

        $this->post(route('admin.content.home-sections.reorder'), [
            'sections' => [['id' => $section->id, 'display_order' => 1]],
        ])->assertRedirect(route('login'));
    }

    public function test_admin_reorders_all_submitted_sections(): void
    {
        $this->actingAs($this->createAdminUser());
        $this->seed(HomeSectionsSeeder::class);
        $sections = HomeSection::query()->orderByDesc('display_order')->get();

        $payload = $sections->values()->map(
            fn (HomeSection $section, int $index): array => [
                'id' => $section->id,
                'display_order' => $index + 1,
            ],
        )->all();

        $this->post(route('admin.content.home-sections.reorder'), ['sections' => $payload])
            ->assertRedirect();

        $this->assertSame(
            $sections->pluck('id')->all(),
            HomeSection::query()->orderBy('display_order')->pluck('id')->all(),
        );
    }

    public function test_admin_toggle_flushes_cached_sections(): void
    {
        $this->actingAs($this->createAdminUser());
        $this->seed(HomeSectionsSeeder::class);
        $service = app(HomeSectionService::class);
        $section = HomeSection::query()->where('key', 'seal_band')->firstOrFail();
        $this->assertCount(8, $service->active('en'));

        $this->patch(route('admin.content.home-sections.toggle', $section->id), [
            'is_active' => false,
        ])->assertRedirect();

        $this->assertCount(7, $service->active('en'));
        $this->assertDatabaseHas('home_sections', [
            'id' => $section->id,
            'is_active' => false,
        ]);
    }
}
