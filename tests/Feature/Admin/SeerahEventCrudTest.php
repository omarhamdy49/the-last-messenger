<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Domain\Seerah\Models\SeerahEvent;
use App\Domain\Seerah\Models\SeerahEventType;
use App\Domain\Seerah\Models\SeerahMilestone;
use App\Domain\Shared\Models\Location;
use App\Domain\Shared\Services\LanguageService;
use Database\Seeders\SeerahEventTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SeerahEventCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(SeerahEventTypeSeeder::class);
        app(LanguageService::class)->updateSettings('fr', ['active' => true]);
        app(LanguageService::class)->updateSettings('ur', ['active' => true]);
    }

    public function test_admin_can_create_an_event_with_every_field(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        $this->actingAs($this->createAdminUser());

        $milestone = $this->createMilestone();
        $location = Location::factory()->create();

        $this->post(route('admin.seerah-events.store'), [
            ...$this->validPayload(),
            'description' => ['ar' => 'وصف عربي للحدث', 'en' => 'English event description'],
            'content' => ['ar' => 'تفاصيل عربية للحدث', 'en' => 'English event content'],
            'dispute_note' => ['ar' => 'قيل سنة 2 هـ وقيل 3 هـ', 'en' => 'Some place it in 2 AH, others in 3 AH'],
            'metadata' => [
                'ar' => [['key' => 'عدد المسلمين', 'value' => '313']],
                'en' => [['key' => 'Muslims', 'value' => '313']],
            ],
            'sources' => [
                'ar' => [['title' => 'السيرة النبوية', 'author' => 'ابن هشام', 'reference' => 'ج2 ص251']],
                'en' => [['title' => 'The Life of Muhammad', 'author' => 'Ibn Ishaq', 'reference' => 'p. 289']],
            ],
            'statistics' => [['label' => 'المسلمون', 'value' => '313'], ['label' => 'المشركون', 'value' => '1000']],
            'era' => 'medina',
            'hijri_year' => 2,
            'hijri_month' => 9,
            'hijri_day' => 17,
            'gregorian_date' => '0624-03-13',
            'sm_id' => $milestone->id,
            'location_id' => $location->id,
            'thumb' => UploadedFile::fake()->image('badr-thumb.jpg', 640, 360),
            'cover' => UploadedFile::fake()->image('badr-cover.jpg', 1200, 630),
        ])->assertRedirect(route('admin.seerah-events.index'));

        $event = SeerahEvent::query()->where('slug', 'battle-of-badr')->firstOrFail();

        $this->assertSame($this->battleTypeId(), $event->event_type);
        $this->assertSame('battle', $event->type->slug);
        $this->assertSame($milestone->id, $event->sm_id);
        $this->assertSame($location->id, $event->location_id);
        $this->assertSame(7, $event->order);
        $this->assertSame('published', $event->status->value);
        $this->assertSame('medina', $event->era->value);
        $this->assertSame([2, 9, 17], [$event->hijri_year, $event->hijri_month, $event->hijri_day]);
        $this->assertSame('0624-03-13', $event->gregorian_date->format('Y-m-d'));
        $this->assertSame(['المسلمون' => '313', 'المشركون' => '1000'], $event->statistics);

        $this->assertSame('غزوة بدر', $event->getTranslation('title', 'ar'));
        $this->assertSame('Battle of Badr', $event->getTranslation('title', 'en', false));
        $this->assertSame('English event content', $event->getTranslation('content', 'en', false));
        $this->assertSame('قيل سنة 2 هـ وقيل 3 هـ', $event->getTranslation('dispute_note', 'ar'));

        $ar = $event->translations()->where('locale', 'ar')->firstOrFail();
        $en = $event->translations()->where('locale', 'en')->firstOrFail();
        $this->assertSame([['key' => 'عدد المسلمين', 'value' => '313']], $ar->metadata);
        $this->assertSame([['title' => 'The Life of Muhammad', 'author' => 'Ibn Ishaq', 'reference' => 'p. 289']], $en->sources);

        $this->assertStringStartsWith('seerah-events/thumbs/', (string) $event->thumb_url);
        $this->assertStringStartsWith('seerah-events/covers/', (string) $event->cover_url);
        Storage::disk('public')->assertExists($event->thumb_url);
        Storage::disk('public')->assertExists($event->cover_url);
    }

    public function test_update_without_repeatable_keys_leaves_them_untouched_and_empty_metadata_clears(): void
    {
        $this->actingAs($this->createAdminUser());
        $event = SeerahEvent::factory()->create(['event_type' => $this->battleTypeId()]);
        $event->translations()->where('locale', 'ar')->firstOrFail()->forceFill([
            'metadata' => [['key' => 'مفتاح', 'value' => 'قيمة']],
            'sources' => [['title' => 'مصدر', 'author' => 'مؤلف', 'reference' => 'مرجع']],
        ])->save();

        $this->put(route('admin.seerah-events.update', $event->id), [
            ...$this->validPayload($event->slug),
            'title' => ['ar' => 'عنوان محدث'],
        ])->assertRedirect(route('admin.seerah-events.index'));

        $ar = $event->translations()->where('locale', 'ar')->firstOrFail();
        $this->assertSame('عنوان محدث', $ar->title);
        $this->assertSame([['key' => 'مفتاح', 'value' => 'قيمة']], $ar->metadata);
        $this->assertSame([['title' => 'مصدر', 'author' => 'مؤلف', 'reference' => 'مرجع']], $ar->sources);

        $this->put(route('admin.seerah-events.update', $event->id), [
            ...$this->validPayload($event->slug),
            'metadata' => ['ar' => ''],
        ])->assertRedirect(route('admin.seerah-events.index'));

        $ar->refresh();
        $this->assertNull($ar->metadata);
        $this->assertSame([['title' => 'مصدر', 'author' => 'مؤلف', 'reference' => 'مرجع']], $ar->sources);
    }

    public function test_seo_external_links_and_cross_module_links_round_trip(): void
    {
        $this->actingAs($this->createAdminUser());

        $this->post(route('admin.seerah-events.store'), [
            ...$this->validPayload(),
            'seo' => [
                'ar' => ['title' => 'غزوة بدر الكبرى', 'description' => 'أول معركة فاصلة في الإسلام'],
                'en' => ['title' => '', 'description' => ''],
            ],
            'external_links' => [
                'ar' => [['title' => 'خريطة بدر', 'url' => 'https://example.com/badr'], ['title' => 'بلا رابط', 'url' => '']],
            ],
            'links' => [
                'verse' => [12, 7],
                'hadith' => [3],
                'muslim' => [5],
                'article' => [9],
                'episode' => [4, 2],
                'tag' => [1],
                'category' => [2],
            ],
        ])->assertSessionHasNoErrors()->assertRedirect(route('admin.seerah-events.index'));

        $event = SeerahEvent::query()->where('slug', 'battle-of-badr')->firstOrFail();
        $ar = $event->translations()->where('locale', 'ar')->firstOrFail();

        $this->assertSame(['title' => 'غزوة بدر الكبرى', 'description' => 'أول معركة فاصلة في الإسلام'], $ar->seo_metadata);
        $this->assertNull($event->translations()->where('locale', 'en')->firstOrFail()->seo_metadata);
        // The row with no URL is dropped, not stored empty.
        // assertEquals: a MySQL JSON column does not preserve object key order.
        $this->assertEquals([['title' => 'خريطة بدر', 'url' => 'https://example.com/badr']], $ar->external_links);
        $this->assertSame(
            ['article' => [9], 'category' => [2], 'episode' => [4, 2], 'hadith' => [3], 'muslim' => [5], 'tag' => [1], 'verse' => [12, 7]],
            collect($event->load('links')->linkIdsByType())->sortKeys()->all()
        );

        // A type that is not posted is left alone; a type posted empty is cleared.
        $this->put(route('admin.seerah-events.update', $event->id), [
            ...$this->validPayload($event->slug),
            'links' => ['verse' => '', 'episode' => [2]],
        ])->assertRedirect(route('admin.seerah-events.index'));

        $byType = $event->fresh()->load('links')->linkIdsByType();
        $this->assertArrayNotHasKey('verse', $byType);
        $this->assertSame([2], $byType['episode']);
        $this->assertSame([3], $byType['hadith']);
        $this->assertSame('غزوة بدر الكبرى', $event->fresh()->translations->firstWhere('locale', 'ar')->seo_metadata['title']);
    }

    public function test_seo_limits_link_types_and_external_urls_are_validated(): void
    {
        $this->actingAs($this->createAdminUser());

        $this->post(route('admin.seerah-events.store'), [
            ...$this->validPayload(),
            'seo' => ['ar' => ['title' => str_repeat('ا', 71), 'description' => str_repeat('ب', 161)]],
            'external_links' => ['ar' => [['title' => 'x', 'url' => 'javascript:alert(1)']]],
            'links' => ['unknown' => [1], 'verse' => ['abc']],
        ])->assertSessionHasErrors(['seo.ar.title', 'seo.ar.description', 'external_links.ar.0.url', 'links', 'links.verse.0']);
    }

    public function test_remove_thumb_deletes_the_file_and_keeps_the_cover(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        $this->actingAs($this->createAdminUser());

        $this->post(route('admin.seerah-events.store'), [
            ...$this->validPayload(),
            'thumb' => UploadedFile::fake()->image('thumb.jpg', 640, 360),
            'cover' => UploadedFile::fake()->image('cover.jpg', 1200, 630),
        ])->assertRedirect(route('admin.seerah-events.index'));

        $event = SeerahEvent::query()->where('slug', 'battle-of-badr')->firstOrFail();
        $thumb = $event->thumb_url;
        $cover = $event->cover_url;

        $this->put(route('admin.seerah-events.update', $event->id), [
            ...$this->validPayload($event->slug),
            'remove_thumb' => '1',
        ])->assertRedirect(route('admin.seerah-events.index'));

        $event->refresh();
        $this->assertNull($event->thumb_url);
        $this->assertSame($cover, $event->cover_url);
        Storage::disk('public')->assertMissing($thumb);
        Storage::disk('public')->assertExists($cover);
    }

    public function test_removed_fields_are_rejected(): void
    {
        $this->actingAs($this->createAdminUser());

        foreach ([
            'importance' => 'high',
            'display_order' => 1,
            'participants' => [['name' => 'x']],
            'lessons' => ['ar' => 'درس'],
            'featured_image' => 'seerah-events/x.jpg',
            'latitude' => '23.78',
            'seerah_event_type_id' => $this->battleTypeId(),
        ] as $field => $value) {
            $this->from(route('admin.seerah-events.create'))
                ->post(route('admin.seerah-events.store'), [...$this->validPayload(), $field => $value])
                ->assertSessionHasErrors($field);

            $this->postJson(route('admin.seerah-events.store'), [...$this->validPayload(), $field => $value])
                ->assertUnprocessable()
                ->assertJsonValidationErrors($field);
        }

        $this->assertDatabaseMissing('seerah_events', ['slug' => 'battle-of-badr']);
    }

    public function test_store_validates_order_era_and_active_type(): void
    {
        $this->actingAs($this->createAdminUser());

        $payload = $this->validPayload();
        unset($payload['order']);
        $this->from(route('admin.seerah-events.create'))
            ->post(route('admin.seerah-events.store'), $payload)
            ->assertSessionHasErrors('order');

        $this->from(route('admin.seerah-events.create'))
            ->post(route('admin.seerah-events.store'), [...$this->validPayload(), 'era' => 'ottoman'])
            ->assertSessionHasErrors('era');

        $inactive = SeerahEventType::create([
            'slug' => 'retired',
            'locale' => ['ar' => 'متوقف'],
            'metadata' => ['icon' => 'circle'],
            'is_active' => false,
        ]);
        $this->from(route('admin.seerah-events.create'))
            ->post(route('admin.seerah-events.store'), [...$this->validPayload(), 'event_type' => $inactive->id])
            ->assertSessionHasErrors('event_type');

        $this->assertDatabaseMissing('seerah_events', ['slug' => 'battle-of-badr']);
    }

    public function test_show_and_edit_pages_render(): void
    {
        $this->actingAs($this->createAdminUser());
        $event = SeerahEvent::factory()->create();
        $event->syncTranslations(['title' => ['ar' => 'غزوة بدر']]);

        $this->get(route('admin.seerah-events.show', $event->id))->assertOk()->assertSee('غزوة بدر');
        $this->get(route('admin.seerah-events.edit', $event->id))->assertOk()->assertSee('غزوة بدر');
        $this->get(route('admin.seerah-events.create'))->assertOk();
    }

    public function test_destroy_deletes_the_row_and_its_files(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        $this->actingAs($this->createAdminUser());

        $this->post(route('admin.seerah-events.store'), [
            ...$this->validPayload(),
            'thumb' => UploadedFile::fake()->image('thumb.jpg', 640, 360),
            'cover' => UploadedFile::fake()->image('cover.jpg', 1200, 630),
        ])->assertRedirect(route('admin.seerah-events.index'));

        $event = SeerahEvent::query()->where('slug', 'battle-of-badr')->firstOrFail();

        $this->delete(route('admin.seerah-events.destroy', $event->id))
            ->assertRedirect(route('admin.seerah-events.index'));

        $this->assertDatabaseMissing('seerah_events', ['id' => $event->id]);
        $this->assertDatabaseMissing('seerah_event_translations', ['event_id' => $event->id]);
        Storage::disk('public')->assertMissing($event->thumb_url);
        Storage::disk('public')->assertMissing($event->cover_url);
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $event = SeerahEvent::factory()->create();

        $this->get(route('admin.seerah-events.index'))->assertRedirect(route('login'));
        $this->post(route('admin.seerah-events.store'), $this->validPayload())->assertRedirect(route('login'));
        $this->delete(route('admin.seerah-events.destroy', $event->id))->assertRedirect(route('login'));

        $this->assertDatabaseHas('seerah_events', ['id' => $event->id]);
        $this->assertDatabaseMissing('seerah_events', ['slug' => 'battle-of-badr']);
    }

    public function test_show_displays_formatted_content_in_each_available_language(): void
    {
        $this->actingAs($this->createAdminUser());
        $event = SeerahEvent::factory()->create();
        $event->translations()->where('locale', 'ar')->update([
            'description' => '<p>ملخص <strong>الحدث</strong></p><script>alert("unsafe-description")</script>',
            'content' => '<h2>تفاصيل الحدث</h2><p>النص <strong>الكامل</strong></p><script>alert("unsafe")</script>',
        ]);
        $event->translations()->where('locale', 'en')->update([
            'content' => '<p>Full English content</p>',
        ]);

        $this->get(route('admin.seerah-events.show', $event->id))
            ->assertOk()
            ->assertSee('<p>ملخص <strong>الحدث</strong></p>', false)
            ->assertDontSee('<script>alert("unsafe-description")</script>', false)
            ->assertSee('المحتوى الكامل')
            ->assertSee('<h2>تفاصيل الحدث</h2>', false)
            ->assertSee('<strong>الكامل</strong>', false)
            ->assertSee('<p>Full English content</p>', false)
            ->assertSee('id="event-content-ar"', false)
            ->assertSee('id="event-content-en"', false)
            ->assertDontSee('<script>alert("unsafe")</script>', false);
    }

    public function test_show_uses_first_available_content_language_and_hides_empty_content(): void
    {
        $this->actingAs($this->createAdminUser());
        $event = SeerahEvent::factory()->create();
        $event->translations()->update(['content' => null, 'description' => null]);
        $event->translations()->where('locale', 'fr')->update(['content' => '<p>Contenu complet</p>']);

        $this->get(route('admin.seerah-events.show', $event->id))
            ->assertOk()
            ->assertSee("x-data=\"{ activeTab: 'fr' }\"", false)
            ->assertSee('<p>Contenu complet</p>', false)
            ->assertDontSee('id="event-content-ar"', false);

        $emptyEvent = SeerahEvent::factory()->create();
        $emptyEvent->translations()->update(['content' => null]);
        $this->get(route('admin.seerah-events.show', $emptyEvent->id))
            ->assertOk()->assertDontSee('id="event-content-heading"', false);
    }

    /**
     * @return array<string, mixed>
     */
    public function test_blank_slug_is_generated_from_english_then_arabic_title(): void
    {
        $this->actingAs($this->createAdminUser());

        $this->post(route('admin.seerah-events.store'), ['slug' => ''] + $this->validPayload())->assertSessionHasNoErrors();
        $this->post(route('admin.seerah-events.store'), ['slug' => ''] + $this->validPayload())->assertSessionHasNoErrors();
        $this->post(route('admin.seerah-events.store'), ['slug' => '', 'title' => ['ar' => 'غزوة أحد']] + $this->validPayload())->assertSessionHasNoErrors();

        $this->assertDatabaseHas('seerah_events', ['slug' => 'battle-of-badr']);
        $this->assertDatabaseHas('seerah_events', ['slug' => 'battle-of-badr-1']);
        $arabic = SeerahEvent::query()->latest('id')->value('slug');
        $this->assertMatchesRegularExpression('/^[a-z0-9\-]+$/', $arabic);
        $this->assertNotSame('event', $arabic);

        // Clearing the slug on edit regenerates it, ignoring the event's own row.
        $event = SeerahEvent::query()->where('slug', 'battle-of-badr')->firstOrFail();
        $this->put(route('admin.seerah-events.update', $event), ['slug' => ''] + $this->validPayload())->assertSessionHasNoErrors();
        $this->assertSame('battle-of-badr', $event->fresh()->slug);
    }

    private function validPayload(string $slug = 'battle-of-badr'): array
    {
        return [
            'slug' => $slug,
            'event_type' => $this->battleTypeId(),
            'order' => 7,
            'status' => 'published',
            'title' => ['ar' => 'غزوة بدر', 'en' => 'Battle of Badr'],
        ];
    }

    private function battleTypeId(): int
    {
        return (int) SeerahEventType::query()->where('slug', 'battle')->value('id');
    }

    private function createMilestone(): SeerahMilestone
    {
        $milestone = SeerahMilestone::create([
            'slug' => 'medina-era',
            'order' => 1,
            'metadata' => ['icon' => 'moon'],
            'hijri_from' => 1,
            'hijri_to' => 11,
            'is_active' => true,
        ]);
        $milestone->syncTranslations(['title' => ['ar' => 'العهد المدني']]);

        return $milestone;
    }
}
