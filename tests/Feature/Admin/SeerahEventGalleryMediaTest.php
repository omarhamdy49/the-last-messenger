<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Domain\Seerah\Models\SeerahEvent;
use App\Domain\Seerah\Models\SeerahEventGalleryItem;
use App\Models\Media;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Event gallery and attached media: replace-on-submit keyed by media-library id,
 * absent key = untouched, empty key = clear, files follow their rows.
 */
class SeerahEventGalleryMediaTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private SeerahEvent $event;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->admin = $this->createAdminUser();
        $this->actingAs($this->admin);
        $this->event = SeerahEvent::factory()->create();
    }

    public function test_gallery_is_created_reordered_and_pruned_with_its_files(): void
    {
        [$m1, $m2, $m3] = [$this->createMedia('m1'), $this->createMedia('m2'), $this->createMedia('m3')];

        $this->updateEvent([
            'gallery' => [$m1->id, $m2->id, $m3->id],
            'gallery_captions' => [$m1->id => ['ar' => 'تعليق', 'en' => 'Caption']],
        ]);

        $rows = $this->galleryRows();
        $this->assertSame([$m1->id, $m2->id, $m3->id], $rows->pluck('media_id')->all());
        $this->assertSame([0, 1, 2], $rows->pluck('order')->all());
        $this->assertSame(['ar' => 'تعليق', 'en' => 'Caption'], $rows->first()->caption);
        $rows->each(fn (SeerahEventGalleryItem $row) => Storage::disk('public')->assertExists($row->image));

        $paths = $rows->pluck('image', 'media_id');

        $this->updateEvent(['gallery' => [$m3->id, $m1->id]]);

        $rows = $this->galleryRows();
        $this->assertSame([$m3->id, $m1->id], $rows->pluck('media_id')->all());
        $this->assertSame([0, 1], $rows->pluck('order')->all());
        $this->assertSame($paths[$m3->id], $rows[0]->image);
        $this->assertSame($paths[$m1->id], $rows[1]->image);
        Storage::disk('public')->assertMissing($paths[$m2->id]);
        Storage::disk('public')->assertExists($paths[$m1->id]);
    }

    public function test_absent_gallery_key_is_untouched_and_empty_gallery_clears(): void
    {
        $m1 = $this->createMedia('m1');
        $this->updateEvent(['gallery' => [$m1->id]]);
        $image = $this->galleryRows()->sole()->image;

        $this->updateEvent([]);
        $this->assertSame($image, $this->galleryRows()->sole()->image);

        $this->updateEvent(['gallery' => '']);
        $this->assertCount(0, $this->galleryRows());
        Storage::disk('public')->assertMissing($image);
    }

    public function test_media_items_are_replaced_on_submit(): void
    {
        [$a, $b] = [$this->createMedia('a'), $this->createMedia('b')];

        $this->updateEvent(['media_items' => [$a->id, $b->id]]);
        $this->assertEqualsCanonicalizing([$a->id, $b->id], $this->event->mediaLinks()->pluck('media_id')->all());

        $this->updateEvent(['media_items' => [$b->id]]);
        $this->assertSame([$b->id], $this->event->mediaLinks()->pluck('media_id')->map(fn ($id) => (int) $id)->all());
    }

    public function test_edit_page_round_trips_gallery_media_ids(): void
    {
        [$m1, $m2] = [$this->createMedia('m1'), $this->createMedia('m2')];
        $this->updateEvent(['gallery' => [$m2->id, $m1->id], 'media_items' => [$m1->id]]);

        $this->get(route('admin.seerah-events.edit', $this->event->id))
            ->assertOk()
            ->assertViewHas('galleryItems', fn (array $items): bool => array_column($items, 'id') === [$m2->id, $m1->id])
            ->assertViewHas('mediaItems', fn (array $items): bool => array_column($items, 'id') === [$m1->id]);
    }

    public function test_deleting_the_event_removes_gallery_rows_and_files(): void
    {
        $m1 = $this->createMedia('m1');
        $this->updateEvent(['gallery' => [$m1->id], 'media_items' => [$m1->id]]);
        $image = $this->galleryRows()->sole()->image;

        $this->delete(route('admin.seerah-events.destroy', $this->event->id))
            ->assertRedirect(route('admin.seerah-events.index'));

        $this->assertDatabaseMissing('seerah_events_gallery', ['event_id' => $this->event->id]);
        $this->assertDatabaseMissing('seerah_events_media', ['event_id' => $this->event->id]);
        Storage::disk('public')->assertMissing($image);
        $this->assertDatabaseHas('media', ['id' => $m1->id]);
    }

    public function test_public_media_endpoint_streams_only_for_published_events(): void
    {
        $media = $this->createMedia('stream');
        $unattached = $this->createMedia('unattached');
        $this->updateEvent(['media_items' => [$media->id]]);

        $this->get(route('public.articles.media', $media->publicRouteKey()))->assertOk();
        $this->get(route('public.articles.media', $unattached->publicRouteKey()))->assertNotFound();

        $this->event->update(['status' => 'draft']);

        $this->get(route('public.articles.media', $media->publicRouteKey()))->assertNotFound();
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function updateEvent(array $extra): void
    {
        $this->put(route('admin.seerah-events.update', $this->event->id), [
            'slug' => $this->event->slug,
            'event_type' => $this->event->event_type,
            'order' => $this->event->order,
            'status' => 'published',
            'title' => ['ar' => 'غزوة بدر'],
            ...$extra,
        ])->assertSessionHasNoErrors()->assertRedirect(route('admin.seerah-events.index'));
    }

    /**
     * @return Collection<int, SeerahEventGalleryItem>
     */
    private function galleryRows()
    {
        return $this->event->gallery()->get();
    }

    private function createMedia(string $name): Media
    {
        $path = "media/{$name}.jpg";
        Storage::disk('public')->put($path, UploadedFile::fake()->image("{$name}.jpg", 800, 600)->getContent());

        return Media::query()->create([
            'filename' => "{$name}.jpg",
            'original_filename' => "{$name}.jpg",
            'path' => $path,
            'mime_type' => 'image/jpeg',
            'size' => Storage::disk('public')->size($path),
            'width' => 800,
            'height' => 600,
            'disk' => 'public',
            'type' => 'image',
            'mediable_type' => User::class,
            'mediable_id' => $this->admin->id,
            'collection_name' => 'images',
        ]);
    }
}
