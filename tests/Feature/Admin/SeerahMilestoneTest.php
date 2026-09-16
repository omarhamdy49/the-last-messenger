<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Domain\Seerah\Models\SeerahEvent;
use App\Domain\Seerah\Models\SeerahMilestone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeerahMilestoneTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_admin_can_crud_toggle_and_bulk_delete_milestones(): void
    {
        $admin = $this->createAdminUser();
        $this->actingAs($admin);

        // Create
        $this->post(route('admin.seerah-milestones.store'), [
            'slug' => 'birth-and-preparation',
            'order_index' => 1,
            'title' => ['ar' => 'من اليتيم إلى الأمين', 'en' => 'From orphan to the trustworthy'],
            'subtitle' => ['ar' => 'ميلاد النور'],
            'description' => ['ar' => 'فترة الإعداد الإلهي'],
            'icon' => 'sunrise',
            'gradient' => 'from-amber-400 to-orange-500',
            'hijri_label_from' => '53 ق.هـ',
            'hijri_label_to' => '13 ق.هـ',
            'hijri_year_from' => -53,
            'hijri_year_to' => -13,
            'gregorian_year_from' => 570,
            'gregorian_year_to' => 610,
            'is_active' => '1',
        ])->assertRedirect(route('admin.seerah-milestones.index'));

        $milestone = SeerahMilestone::query()->where('slug', 'birth-and-preparation')->firstOrFail();
        $this->assertSame('من اليتيم إلى الأمين', $milestone->getTranslation('title', 'ar'));
        $this->assertSame('ميلاد النور', $milestone->getTranslation('subtitle', 'ar'));
        $this->assertSame(-53, $milestone->hijri_year_from);
        $this->assertSame(570, $milestone->gregorian_year_from);
        $this->assertTrue($milestone->is_active);

        // Update
        $this->put(route('admin.seerah-milestones.update', $milestone->id), [
            'slug' => 'birth-and-preparation',
            'order_index' => 1,
            'title' => ['ar' => 'من اليتيم إلى الأمين (معدّل)'],
            'is_active' => '1',
        ])->assertRedirect(route('admin.seerah-milestones.index'));
        $this->assertSame('من اليتيم إلى الأمين (معدّل)', $milestone->fresh()->getTranslation('title', 'ar'));

        // Toggle active
        $this->post(route('admin.seerah-milestones.toggle-active', $milestone->id))
            ->assertRedirect(route('admin.seerah-milestones.index'));
        $this->assertFalse($milestone->fresh()->is_active);

        // Show
        $this->get(route('admin.seerah-milestones.show', $milestone->id))->assertOk()->assertSee('معدّل');

        // Bulk delete (string ids to mirror the browser)
        $this->post(route('admin.seerah-milestones.bulk-delete'), ['ids' => [(string) $milestone->id]])
            ->assertRedirect(route('admin.seerah-milestones.index'));
        $this->assertDatabaseMissing('seerah_milestones', ['id' => $milestone->id]);
    }

    public function test_show_renders_formatted_milestone_descriptions_safely(): void
    {
        $this->actingAs($this->createAdminUser());
        $milestone = SeerahMilestone::query()->create(['slug' => 'rich-preview', 'order_index' => 1, 'is_active' => true]);
        $milestone->syncTranslations([
            'title' => ['ar' => 'الحقبة'],
            'description' => [
                'ar' => '<h2>تفاصيل الحقبة</h2><p>وصف <strong>الحقبة</strong></p><script>alert("unsafe")</script>',
                'en' => '<ul><li>Era detail</li></ul>',
            ],
        ]);

        $this->get(route('admin.seerah-milestones.show', $milestone->id))
            ->assertOk()
            ->assertSee('<h2>تفاصيل الحقبة</h2>', false)
            ->assertSee('<p>وصف <strong>الحقبة</strong></p>', false)
            ->assertSee('<ul><li>Era detail</li></ul>', false)
            ->assertDontSee('&lt;h2&gt;', false)
            ->assertDontSee('<script>alert("unsafe")</script>', false);
    }

    public function test_event_can_be_linked_to_a_milestone(): void
    {
        $admin = $this->createAdminUser();
        $this->actingAs($admin);

        $milestone = SeerahMilestone::query()->create([
            'slug' => 'dawn-of-revelation',
            'order_index' => 2,
            'is_active' => true,
        ]);
        $milestone->syncTranslations(['title' => ['ar' => 'فجر الرسالة']]);

        $this->post(route('admin.seerah-events.store'), [
            'slug' => 'first-revelation',
            'title' => ['ar' => 'نزول الوحي'],
            'seerah_milestone_id' => $milestone->id,
            'status' => 'published',
        ])->assertRedirect(route('admin.seerah-events.index'));

        $event = SeerahEvent::query()->where('slug', 'first-revelation')->firstOrFail();
        $this->assertSame($milestone->id, $event->seerah_milestone_id);
        $this->assertSame('dawn-of-revelation', $event->milestone->slug);
    }

    public function test_deleting_milestone_unlinks_events_not_deletes_them(): void
    {
        $admin = $this->createAdminUser();
        $this->actingAs($admin);

        $milestone = SeerahMilestone::query()->create(['slug' => 'temp', 'order_index' => 9, 'is_active' => true]);
        $milestone->syncTranslations(['title' => ['ar' => 'مؤقت']]);
        $event = SeerahEvent::factory()->create(['seerah_milestone_id' => $milestone->id]);

        $this->delete(route('admin.seerah-milestones.destroy', $milestone->id))
            ->assertRedirect(route('admin.seerah-milestones.index'));

        $this->assertDatabaseMissing('seerah_milestones', ['id' => $milestone->id]);
        $this->assertDatabaseHas('seerah_events', ['id' => $event->id, 'seerah_milestone_id' => null]);
    }

    public function test_milestone_store_requires_arabic_title(): void
    {
        $admin = $this->createAdminUser();
        $this->actingAs($admin);

        $this->from(route('admin.seerah-milestones.create'))
            ->post(route('admin.seerah-milestones.store'), ['slug' => 'no-title', 'title' => ['en' => 'No Arabic']])
            ->assertSessionHasErrors('title.ar');

        $this->assertDatabaseMissing('seerah_milestones', ['slug' => 'no-title']);
    }

    public function test_non_admin_cannot_create_milestone(): void
    {
        $writer = $this->createWriterUser();
        $this->actingAs($writer);

        $response = $this->post(route('admin.seerah-milestones.store'), [
            'slug' => 'forbidden',
            'title' => ['ar' => 'ممنوع'],
        ]);

        $this->assertContains($response->getStatusCode(), [302, 403]);
        $this->assertDatabaseMissing('seerah_milestones', ['slug' => 'forbidden']);
    }
}
