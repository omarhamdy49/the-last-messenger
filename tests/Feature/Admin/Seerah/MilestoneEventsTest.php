<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Seerah;

use App\Domain\Seerah\Models\SeerahEvent;
use App\Domain\Seerah\Models\SeerahMilestone;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * The milestone show page lists its events, and the events index filters by milestone.
 */
class MilestoneEventsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private SeerahMilestone $milestone;

    private SeerahEvent $inside;

    private SeerahEvent $outside;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('super_admin', 'web');
        $this->admin = User::factory()->create();
        $this->admin->assignRole('super_admin');

        $this->milestone = SeerahMilestone::create([
            'slug' => 'makkah-era',
            'order_index' => 1,
            'is_active' => true,
        ]);
        $this->milestone->syncTranslations(['title' => ['ar' => 'العهد المكي']]);

        $this->inside = SeerahEvent::factory()->create(['seerah_milestone_id' => $this->milestone->id]);
        $this->inside->syncTranslations(['title' => ['ar' => 'حدث داخل الحقبة']]);

        $this->outside = SeerahEvent::factory()->create(['seerah_milestone_id' => null]);
        $this->outside->syncTranslations(['title' => ['ar' => 'حدث خارج الحقبة']]);
    }

    public function test_milestone_show_lists_related_events(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.seerah-milestones.show', $this->milestone->id))
            ->assertOk()
            ->assertSee('حدث داخل الحقبة', false)
            ->assertDontSee('حدث خارج الحقبة', false);
    }

    public function test_events_index_filters_by_milestone(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.seerah-events.index', ['milestone' => $this->milestone->id]))
            ->assertOk()
            ->assertSee('حدث داخل الحقبة', false)
            ->assertDontSee('حدث خارج الحقبة', false);
    }

    public function test_events_index_without_filter_shows_all(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.seerah-events.index'))
            ->assertOk()
            ->assertSee('حدث داخل الحقبة', false)
            ->assertSee('حدث خارج الحقبة', false);
    }
}
