<?php

declare(strict_types=1);

namespace Tests\Feature\Quran;

use App\Domain\Quran\Models\Reciter;
use App\Domain\Quran\Models\ReciterProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * The shared <x-admin.image-upload> external-URL mode, wired to recordings:
 * a changed URL replaces the image, an untouched one keeps it, remove clears it.
 */
class ReciterImageInputTest extends TestCase
{
    use RefreshDatabase;

    private const URL = 'https://commons.wikimedia.org/wiki/Special:FilePath/Hussary.jpg?width=512';

    private function recording(?string $image): Reciter
    {
        $recording = Reciter::create(['slug' => 'image-fixture', 'kind' => 'recitation', 'is_active' => true, 'profile_image' => $image]);
        $recording->syncTranslations(['name' => ['ar' => 'قارئ الصورة', 'en' => 'Image Reciter']]);

        return $recording->refresh();
    }

    private function update(Reciter $recording, array $image): void
    {
        $this->actingAs($this->createAdminUser())
            ->put(route('admin.reciters.update', $recording->id), [
                'name' => ['ar' => 'قارئ الصورة', 'en' => 'Image Reciter'],
                'slug' => 'image-fixture',
                ...$image,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();
    }

    public function test_external_url_replaces_a_local_image_and_deletes_the_file(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('reciters/profiles/old.jpg', 'x');
        $recording = $this->recording('reciters/profiles/old.jpg');
        // Grouping gave the recording a profile that mirrors its image; make the recording the sole user.
        ReciterProfile::whereKey($recording->profile_id)->update(['profile_image' => null]);

        $this->update($recording, ['profile_image_url' => self::URL]);

        $this->assertSame(self::URL, $recording->refresh()->profile_image);
        $this->assertSame(self::URL, $recording->profile_image_url);
        Storage::disk('public')->assertMissing('reciters/profiles/old.jpg');
    }

    public function test_untouched_input_keeps_the_current_image(): void
    {
        $recording = $this->recording(self::URL);

        // The component re-submits the current external URL, with no removal flag.
        $this->update($recording, ['profile_image_url' => self::URL, 'remove_profile_image' => 0]);
        $this->assertSame(self::URL, $recording->refresh()->profile_image);

        // A local image submits an empty URL.
        $recording->update(['profile_image' => 'reciters/profiles/kept.jpg']);
        $this->update($recording, ['profile_image_url' => '', 'remove_profile_image' => 0]);
        $this->assertSame('reciters/profiles/kept.jpg', $recording->refresh()->profile_image);
    }

    public function test_remove_flag_clears_the_image(): void
    {
        $recording = $this->recording(self::URL);

        $this->update($recording, ['profile_image_url' => '', 'remove_profile_image' => 1]);

        $this->assertNull($recording->refresh()->profile_image);
    }

    public function test_non_http_urls_are_rejected(): void
    {
        $recording = $this->recording(null);

        $this->actingAs($this->createAdminUser())
            ->put(route('admin.reciters.update', $recording->id), [
                'name' => ['ar' => 'قارئ الصورة', 'en' => 'Image Reciter'],
                'slug' => 'image-fixture',
                'profile_image_url' => 'javascript:alert(1)',
            ])
            ->assertSessionHasErrors('profile_image_url');

        $this->assertNull($recording->refresh()->profile_image);
    }

    public function test_edit_page_prefills_the_external_url_option(): void
    {
        $recording = $this->recording(self::URL);

        $this->actingAs($this->createAdminUser())
            ->get(route('admin.reciters.edit', $recording->id))
            ->assertOk()
            ->assertSee('name="profile_image_url"', false)
            ->assertSee('name="remove_profile_image"', false)
            ->assertDontSee('storage/https://', false);
    }

    public function test_saving_the_form_keeps_the_stored_style(): void
    {
        $recording = $this->recording(null);
        $recording->update(['style' => 'mujawwad']);

        $this->actingAs($this->createAdminUser())
            ->get(route('admin.reciters.edit', $recording->id))
            ->assertSee('<option value="mujawwad" selected', false);

        $this->update($recording, ['style' => 'mujawwad']);
        $this->assertSame('mujawwad', $recording->refresh()->style);
    }

    private function profilePayload(array $image): array
    {
        return ['names' => ['ar' => 'قارئ الملف'], 'slug' => 'image-profile', 'is_active' => 1, 'is_featured' => 0, 'display_order' => 0, ...$image];
    }

    public function test_profile_accepts_an_external_url_on_create_and_update(): void
    {
        $admin = $this->createAdminUser();

        $this->actingAs($admin)->post(route('admin.reciter-profiles.store'), $this->profilePayload(['profile_image_url' => self::URL]))
            ->assertSessionHasNoErrors()->assertRedirect();
        $profile = ReciterProfile::where('slug', 'image-profile')->firstOrFail();
        $this->assertSame(self::URL, $profile->profile_image);

        $recording = $this->recording(null);
        $recording->update(['profile_id' => $profile->id]);
        $payload = $this->profilePayload(['default_recording_id' => $recording->id]);

        $other = 'https://example.org/reciter.webp';
        $this->actingAs($admin)->put(route('admin.reciter-profiles.update', $profile->id), $payload + ['profile_image_url' => $other])
            ->assertSessionHasNoErrors();
        $this->assertSame($other, $profile->refresh()->profile_image);

        $this->actingAs($admin)->put(route('admin.reciter-profiles.update', $profile->id), $payload + ['profile_image_url' => '', 'remove_profile_image' => 1])
            ->assertSessionHasNoErrors();
        $this->assertNull($profile->refresh()->profile_image);
    }

    public function test_replacing_an_image_keeps_a_file_another_row_still_uses(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('reciters/profiles/shared.jpg', 'x');
        // Grouping copies the recording's image path onto its profile — the real-world sharing case.
        $recording = $this->recording('reciters/profiles/shared.jpg');
        $profile = ReciterProfile::findOrFail($recording->profile_id);
        $this->assertSame('reciters/profiles/shared.jpg', $profile->profile_image);

        $this->actingAs($this->createAdminUser())
            ->put(route('admin.reciter-profiles.update', $profile->id), $this->profilePayload(['default_recording_id' => $recording->id, 'profile_image_url' => self::URL]))
            ->assertSessionHasNoErrors();

        $this->assertSame(self::URL, $profile->refresh()->profile_image);
        Storage::disk('public')->assertExists('reciters/profiles/shared.jpg');
        $this->assertSame('reciters/profiles/shared.jpg', $recording->refresh()->profile_image);

        // The recording was the last user: now the file goes.
        $this->update($recording, ['profile_image_url' => self::URL]);
        Storage::disk('public')->assertMissing('reciters/profiles/shared.jpg');
    }
}
