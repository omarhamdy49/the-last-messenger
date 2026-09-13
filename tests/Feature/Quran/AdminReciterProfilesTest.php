<?php

declare(strict_types=1);

namespace Tests\Feature\Quran;

use App\Domain\Quran\Models\Recitation;
use App\Domain\Quran\Models\Reciter;
use App\Domain\Quran\Models\ReciterProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AdminReciterProfilesTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        $this->admin = $this->createAdminUser();
    }

    private function profile(string $slug, array $attributes = [], string $name = 'قارئ'): ReciterProfile
    {
        $profile = ReciterProfile::create($attributes + ['identity_key' => 'test:'.$slug, 'slug' => $slug, 'is_active' => true]);
        $profile->translations()->create(['locale' => 'ar', 'name' => $name.' '.$slug]);

        return $profile;
    }

    /** Recording rows written directly so translation observers do not regroup them. */
    private function recording(?ReciterProfile $profile, string $slug, bool $active = true): Reciter
    {
        $id = DB::table('quran_reciters')->insertGetId(['slug' => $slug, 'kind' => 'recitation', 'style' => 'murattal', 'is_active' => $active, 'profile_id' => $profile?->id, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('reciter_translations')->insert(['reciter_id' => $id, 'locale' => 'ar', 'name' => 'تسجيل '.$slug, 'created_at' => now(), 'updated_at' => now()]);

        return Reciter::findOrFail($id);
    }

    public function test_index_filters_by_status_featured_recordings_and_search(): void
    {
        $husary = $this->profile('husary', ['is_featured' => true, 'profile_image' => 'https://img.example/husary.jpg'], 'الحصري');
        $this->recording($husary, 'husary-murattal');
        $this->recording($husary, 'husary-mujawwad');
        $hidden = $this->profile('hidden', ['is_active' => false], 'مخفي');
        $this->recording($hidden, 'hidden-one');

        $index = fn (array $query) => $this->actingAs($this->admin)->get(route('admin.reciter-profiles.index', $query))->assertOk()->viewData('profiles')->pluck('id')->all();

        $this->assertSame([$hidden->id], $index(['status' => 'inactive']));
        $this->assertSame([$husary->id], $index(['featured' => '1']));
        $this->assertSame([$husary->id], $index(['recordings' => 'multiple']));
        $this->assertSame([$hidden->id], $index(['recordings' => 'single']));
        $this->assertSame([$husary->id], $index(['search' => 'الحصري']));
        $this->assertSame([$husary->id], $index(['search' => 'husary-mujawwad'])); // recording name
        $this->assertSame([$husary->id, $hidden->id], $index(['sort' => 'recordings']));
    }

    public function test_index_shows_profile_image_and_recordings_list_shows_fallback_image(): void
    {
        $profile = $this->profile('pictured', ['profile_image' => 'https://img.example/pictured.jpg']);
        $this->recording($profile, 'pictured-recording');

        $this->actingAs($this->admin)->get(route('admin.reciter-profiles.index'))
            ->assertOk()->assertSee('https://img.example/pictured.jpg', false)->assertSee('loading="lazy"', false);
        $this->actingAs($this->admin)->get(route('admin.reciters.index', ['recordings' => 1]))
            ->assertOk()->assertSee('https://img.example/pictured.jpg', false);
    }

    public function test_create_validates_and_stores_profile_with_generated_slug(): void
    {
        $this->actingAs($this->admin)->get(route('admin.reciter-profiles.create'))->assertOk();

        $this->actingAs($this->admin)->post(route('admin.reciter-profiles.store'), ['names' => ['ar' => ''], 'is_active' => 1, 'is_featured' => 0, 'display_order' => 0])
            ->assertSessionHasErrors('names.ar');

        $this->profile('taken');
        $this->actingAs($this->admin)->post(route('admin.reciter-profiles.store'), ['names' => ['ar' => 'قارئ'], 'slug' => 'taken', 'is_active' => 1, 'is_featured' => 0, 'display_order' => 0])
            ->assertSessionHasErrors('slug');

        $response = $this->actingAs($this->admin)->post(route('admin.reciter-profiles.store'), [
            'names' => ['ar' => 'محمد صديق المنشاوي', 'en' => 'Mohamed Siddiq Al-Minshawi'], 'descriptions' => ['ar' => 'وصف'],
            'country' => 'eg', 'is_active' => 1, 'is_featured' => 1, 'display_order' => 3,
        ]);
        $profile = ReciterProfile::where('slug', 'mohamed-siddiq-al-minshawi')->firstOrFail();
        $response->assertRedirect(route('admin.reciter-profiles.edit', $profile->id));
        $this->assertSame('EG', $profile->country);
        $this->assertTrue($profile->is_featured);
        $this->assertStringStartsWith('admin:', $profile->identity_key);
        $this->assertSame('وصف', $profile->translations()->where('locale', 'ar')->value('description'));
        $this->assertNull($profile->default_recording_id);
    }

    public function test_toggle_active_and_featured_return_json(): void
    {
        $profile = $this->profile('toggle');

        $this->actingAs($this->admin)->postJson(route('admin.reciter-profiles.toggle', [$profile->id, 'active']))
            ->assertOk()->assertJson(['success' => true, 'data' => ['is_active' => false]]);
        $this->actingAs($this->admin)->postJson(route('admin.reciter-profiles.toggle', [$profile->id, 'featured']))
            ->assertOk()->assertJson(['success' => true, 'data' => ['is_featured' => true]]);
        $this->actingAs($this->admin)->post(route('admin.reciter-profiles.toggle', [$profile->id, 'featured']))->assertRedirect();

        $this->assertFalse($profile->refresh()->is_active);
        $this->assertFalse($profile->is_featured);
    }

    public function test_delete_is_blocked_with_recordings_and_allowed_without(): void
    {
        $busy = $this->profile('busy');
        $this->recording($busy, 'busy-recording');
        $empty = $this->profile('empty');

        $this->actingAs($this->admin)->deleteJson(route('admin.reciter-profiles.destroy', $busy->id))->assertUnprocessable()->assertJson(['success' => false]);
        $this->actingAs($this->admin)->delete(route('admin.reciter-profiles.destroy', $busy->id))->assertRedirect()->assertSessionHas('error');
        $this->assertModelExists($busy);

        $this->actingAs($this->admin)->delete(route('admin.reciter-profiles.destroy', $empty->id))->assertRedirect(route('admin.reciter-profiles.index'));
        $this->assertModelMissing($empty);
    }

    public function test_attach_detach_and_default_rules(): void
    {
        $profile = $this->profile('owner');
        $other = $this->profile('other');
        $foreign = $this->recording($other, 'foreign');
        $loose = $this->recording(null, 'loose');

        $this->actingAs($this->admin)->get(route('admin.reciter-profiles.edit', $profile->id))->assertOk()->assertSee('loose');

        // Only recordings without a profile can be attached.
        $this->actingAs($this->admin)->post(route('admin.reciter-profiles.recordings.attach', $profile->id), ['recording_id' => $foreign->id])->assertSessionHasErrors('recording_id');
        $this->actingAs($this->admin)->post(route('admin.reciter-profiles.recordings.attach', $profile->id), ['recording_id' => $loose->id])->assertSessionHasNoErrors();
        $this->assertSame($profile->id, $loose->refresh()->profile_id);
        $this->assertSame($loose->id, $profile->refresh()->default_recording_id); // first active recording becomes default

        // The default cannot be detached; another recording can.
        $this->actingAs($this->admin)->delete(route('admin.reciter-profiles.recordings.detach', [$profile->id, $loose->id]))->assertSessionHas('error');
        $this->assertSame($profile->id, $loose->refresh()->profile_id);

        $second = $this->recording($profile, 'second');
        $this->actingAs($this->admin)->post(route('admin.reciter-profiles.recordings.default', [$profile->id, $second->id]))->assertSessionHas('success');
        $this->assertSame($second->id, $profile->refresh()->default_recording_id);
        $this->actingAs($this->admin)->post(route('admin.reciter-profiles.recordings.default', [$profile->id, $foreign->id]))->assertNotFound();

        $this->actingAs($this->admin)->delete(route('admin.reciter-profiles.recordings.detach', [$profile->id, $loose->id]))->assertSessionHas('success');
        $this->assertNull($loose->refresh()->profile_id);
        $this->actingAs($this->admin)->delete(route('admin.reciter-profiles.recordings.detach', [$profile->id, $foreign->id]))->assertNotFound();
    }

    public function test_source_delete_is_blocked_when_it_has_recitations(): void
    {
        DB::table('quran_surahs')->insert(['id' => 1, 'name_ar' => 'الفاتحة', 'name_en' => 'Al-Fatihah', 'transliteration' => 'Al-Fatihah', 'revelation_place' => 'makkah', 'revelation_order' => 5, 'verses_count' => 7, 'pages' => json_encode([1]), 'created_at' => now(), 'updated_at' => now()]);
        $recording = $this->recording($this->profile('sourced'), 'sourced-recording');
        $used = $recording->sources()->create(['provider' => 'direct', 'kind' => 'surah', 'source_key' => 'used', 'base_url' => 'https://audio.example/used/']);
        $unused = $recording->sources()->create(['provider' => 'direct', 'kind' => 'surah', 'source_key' => 'unused', 'base_url' => 'https://audio.example/unused/']);
        Recitation::create(['reciter_id' => $recording->id, 'source_id' => $used->id, 'surah_id' => 1, 'audio_url' => 'https://audio.example/used/001.mp3', 'format' => 'mp3']);

        $this->actingAs($this->admin)->get(route('admin.reciters.sources.index', $recording->id))->assertOk()->assertSee('unused');
        $this->actingAs($this->admin)->delete(route('admin.reciters.sources.destroy', [$recording->id, $used->id]))->assertSessionHas('error');
        $this->assertModelExists($used);
        $this->actingAs($this->admin)->delete(route('admin.reciters.sources.destroy', [$recording->id, $unused->id]))->assertSessionHas('success');
        $this->assertModelMissing($unused);
    }
}
