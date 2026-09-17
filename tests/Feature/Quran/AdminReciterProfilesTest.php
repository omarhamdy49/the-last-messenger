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

    public function test_merge_moves_recordings_bookmarks_and_redirects_the_absorbed_reciter(): void
    {
        $kept = $this->profile('husary', ['country' => null], 'الحصري');
        $keptRecording = $this->recording($kept, 'husary-murattal');
        $kept->update(['default_recording_id' => $keptRecording->id]);
        $absorbed = $this->profile('al-husary', ['country' => 'EG', 'followers_count' => 5, 'is_featured' => true], 'محمود خليل');
        $absorbed->translations()->create(['locale' => 'en', 'name' => 'Al-Husary', 'description' => 'Egyptian reciter']);
        $moved = $this->recording($absorbed, 'husary-mujawwad');
        $absorbed->update(['default_recording_id' => $moved->id]);

        $both = User::factory()->create();
        $onlyAbsorbed = User::factory()->create();
        foreach ([[$both, $kept], [$both, $absorbed], [$onlyAbsorbed, $absorbed]] as [$user, $profile]) {
            DB::table('favorites')->insert(['user_id' => $user->id, 'favorable_type' => ReciterProfile::class, 'favorable_id' => $profile->id, 'created_at' => now(), 'updated_at' => now()]);
        }

        $this->actingAs($this->admin)
            ->post(route('admin.reciter-profiles.merge', $kept->id), ['source_profile_id' => $absorbed->id])
            ->assertRedirect()->assertSessionHas('success');

        $this->assertModelMissing($absorbed);
        $this->assertSame($kept->id, $moved->fresh()->profile_id);
        $kept->refresh()->load('translations');
        $this->assertSame($keptRecording->id, $kept->default_recording_id); // kept data wins
        $this->assertSame('الحصري husary', $kept->displayName('ar'));
        $this->assertSame('Al-Husary', $kept->displayName('en')); // gaps filled
        $this->assertSame(['EG', 5, true], [$kept->country, $kept->followers_count, $kept->is_featured]);
        $this->assertSame(3 - 1, DB::table('favorites')->where('favorable_id', $kept->id)->count()); // duplicate dropped
        $this->assertSame(0, DB::table('favorites')->where('favorable_id', $absorbed->id)->count());

        $this->get(route('public.quran.reciter', 'al-husary'))
            ->assertRedirect(route('public.quran.reciter', ['slug' => 'husary', 'recording' => $moved->id]));
    }

    public function test_merge_rejects_merging_a_reciter_into_itself(): void
    {
        $profile = $this->profile('solo');

        $this->actingAs($this->admin)
            ->post(route('admin.reciter-profiles.merge', $profile->id), ['source_profile_id' => $profile->id])
            ->assertSessionHasErrors('source_profile_id');
        $this->assertModelExists($profile);
    }

    public function test_fill_adds_only_surah_files_the_host_actually_serves(): void
    {
        foreach ([1, 2, 3] as $n) {
            DB::table('quran_surahs')->insert(['id' => $n, 'name_ar' => 'سورة '.$n, 'name_en' => 'S'.$n, 'transliteration' => 'S'.$n, 'revelation_place' => 'makkah', 'revelation_order' => $n, 'verses_count' => 7, 'pages' => json_encode([1]), 'created_at' => now(), 'updated_at' => now()]);
        }
        $recording = $this->recording($this->profile('filler'), 'filler-murattal');
        $source = $recording->sources()->create(['provider' => 'direct', 'kind' => 'surah', 'source_key' => 'cdn', 'base_url' => 'https://cdn.example/filler/', 'is_active' => true]);
        Recitation::create(['reciter_id' => $recording->id, 'source_id' => $source->id, 'surah_id' => 1, 'audio_url' => 'https://cdn.example/filler/001.mp3', 'format' => 'mp3']);
        Http::fake([
            'cdn.example/filler/002.mp3' => Http::response('x', 206, ['Content-Range' => 'bytes 0-0/2048000']),
            '*' => Http::response('', 404),
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.reciters.sources.fill', [$recording->id, $source->id]), ['naming' => 'padded', 'format' => 'mp3'])
            ->assertRedirect()->assertSessionHas('success');

        $this->assertSame([1, 2], $source->recitations()->orderBy('surah_id')->pluck('surah_id')->all()); // 3 is a 404 → skipped
        $this->assertSame(2000, $source->recitations()->where('surah_id', 2)->value('file_size_kb'));
        Http::assertNotSent(fn ($request) => str_ends_with($request->url(), '/001.mp3')); // existing file never re-probed
    }
}
