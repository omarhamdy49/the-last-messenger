<?php

declare(strict_types=1);

namespace Tests\Feature\Quran;

use App\Domain\Quran\Models\Recitation;
use App\Domain\Quran\Models\Reciter;
use App\Domain\Quran\Models\ReciterProfile;
use App\Domain\Quran\Models\RecordingSource;
use App\Domain\Quran\Repositories\RecitationRepository;
use App\Domain\Quran\Services\AyahTimingService;
use App\Domain\Quran\Services\ReciterDirectoryService;
use App\Domain\Quran\Services\ReciterGroupingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RecordingSourcesAndProfilesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        DB::table('quran_surahs')->insert(['id' => 1, 'name_ar' => 'الفاتحة', 'name_en' => 'Al-Fatihah', 'transliteration' => 'Al-Fatihah', 'revelation_place' => 'makkah', 'revelation_order' => 5, 'verses_count' => 7, 'pages' => json_encode([1]), 'created_at' => now(), 'updated_at' => now()]);
        Http::preventStrayRequests();
    }

    private function recording(string $slug, string $style = 'murattal', string $person = '112'): Reciter
    {
        $recording = Reciter::create(['slug' => $slug, 'kind' => 'recitation', 'style' => $style, 'riwayah' => "Hafs 'an 'Asim", 'is_active' => true]);
        $recording->sources()->create(['provider' => 'mp3quran', 'kind' => 'surah', 'source_key' => 'fixture', 'external_reciter_id' => $person, 'external_recording_id' => 'recording-'.$recording->id, 'external_timing_id' => '112', 'base_url' => 'https://audio.example/'.$slug.'/']);
        $recording->syncTranslations(['name' => ['ar' => 'القارئ '.$slug, 'en' => $slug]]);

        return $recording->refresh();
    }

    private function audio(Reciter $recording, ?RecordingSource $source = null): Recitation
    {
        $source ??= $recording->sources->first();

        return Recitation::create(['reciter_id' => $recording->id, 'source_id' => $source->id, 'surah_id' => 1, 'audio_url' => $source->base_url.'001.mp3', 'format' => 'mp3']);
    }

    public function test_provider_columns_are_replaced_with_generic_sources(): void
    {
        foreach (['mp3quran_folder', 'mp3quran_read_id', 'everyayah_folder'] as $column) {
            $this->assertFalse(Schema::hasColumn('quran_reciters', $column));
        }
        $this->assertTrue(Schema::hasColumn('quran_recording_sources', 'external_recording_id'));
    }

    public function test_multiple_sources_keep_separate_audio_and_choose_priority(): void
    {
        $recording = $this->recording('multiple');
        $first = $this->audio($recording);
        $other = $recording->sources()->create(['provider' => 'direct', 'kind' => 'surah', 'source_key' => 'alternative', 'base_url' => 'https://alternate.example/', 'priority' => 10]);
        $second = $this->audio($recording, $other);
        $repository = app(RecitationRepository::class);
        $this->assertSame($first->audio_url, $repository->getSurahAudioSources(1)[$recording->id]['url']);
        $recording->sources->first()->update(['priority' => 20]);
        $this->assertSame($second->audio_url, $repository->getSurahAudioSources(1)[$recording->id]['url']);
        $this->assertSame(2, $recording->recitations()->count());
    }

    public function test_audio_cannot_reference_another_recordings_source(): void
    {
        $one = $this->recording('one');
        $two = $this->recording('two');
        $this->expectException(\InvalidArgumentException::class);
        $this->audio($one, $two->sources->first());
    }

    public function test_timings_reject_wrong_source_and_stale_revision(): void
    {
        $one = $this->recording('one');
        $two = $this->recording('two');
        $this->audio($one);
        $this->audio($two);
        $service = app(AyahTimingService::class);
        $this->assertSame([], $service->forSurah(1, $one->id, $two->sources->first()->id)['ayahs']);
        $source = $one->sources->first();
        $source->update(['external_timing_id' => 'new']);
        $this->assertSame([], $service->forSurah(1, $one->id, $source->id, 1)['ayahs']);
        Http::assertNothingSent();
    }

    public function test_grouping_keeps_recordings_and_counts_people_once(): void
    {
        $one = $this->recording('minshawy-murattal');
        $two = $this->recording('minshawy-mujawwad', 'mujawwad');
        $this->audio($one);
        $this->audio($two);
        $this->assertSame($one->profile_id, $two->profile_id);
        $directory = app(ReciterDirectoryService::class)->listing('en');
        $this->assertSame(1, $directory['total']);
        $this->assertSame(2, $directory['all'][0]['recordingsCount']);
        $this->assertSame(1, $directory['all'][0]['recitationsCount']);
        $this->assertCount(2, $directory['all'][0]['styles']);
    }

    public function test_equal_names_without_provider_identity_do_not_merge(): void
    {
        foreach (['unknown-a', 'unknown-b'] as $slug) {
            $r = Reciter::create(['slug' => $slug, 'kind' => 'recitation']);
            $r->syncTranslations(['name' => ['ar' => 'اسم متشابه']]);
        }
        $this->assertSame(2, ReciterProfile::count());
    }

    public function test_grouping_rerun_preserves_profile_recording_and_source_ids(): void
    {
        $r = $this->recording('minshawy-murattal');
        $ids = [$r->profile_id, $r->id, $r->sources->first()->id];
        app(ReciterGroupingService::class)->attach($r);
        app(ReciterGroupingService::class)->attach($r->refresh());
        $r->refresh();
        $this->assertSame($ids, [$r->profile_id, $r->id, $r->sources->first()->id]);
        $this->assertSame(1, ReciterProfile::count());
    }

    public function test_legacy_slug_redirects_to_exact_recording_and_foreign_selection_is_rejected(): void
    {
        $r = $this->recording('minshawy-mujawwad', 'mujawwad');
        $other = $this->recording('other-person', 'murattal', '999');
        $this->audio($r);
        $this->get(route('public.quran.reciter', $r->slug))->assertRedirect(route('public.quran.reciter', ['slug' => 'minshawy', 'recording' => $r->id]));
        $this->get(route('public.quran.reciter', ['slug' => 'minshawy', 'recording' => $other->id]))->assertNotFound();
        $this->get(route('public.quran.reciter', ['slug' => 'minshawy', 'recording' => $r->id]))->assertOk()->assertViewHas('selectedRecordingId', $r->id);
    }

    public function test_public_recording_switcher_counts_distinct_surahs_and_marks_selection(): void
    {
        $murattal = $this->recording('switch-murattal', 'murattal', '301');
        $mujawwad = $this->recording('switch-mujawwad', 'mujawwad', '302');
        $profile = ReciterProfile::create(['identity_key' => 'switch-fixture', 'slug' => 'switch-fixture', 'is_active' => true, 'default_recording_id' => $murattal->id]);
        $profile->translations()->create(['locale' => 'ar', 'name' => 'قارئ الاختبار']);
        Reciter::whereIn('id', [$murattal->id, $mujawwad->id])->update(['profile_id' => $profile->id]);

        // Same surah from two active sources counts once; an inactive source counts not at all.
        $this->audio($murattal);
        $this->audio($murattal, $murattal->sources()->create(['provider' => 'direct', 'kind' => 'surah', 'source_key' => 'alt', 'base_url' => 'https://alt.example/', 'priority' => 5]));
        $mujawwad->sources()->first()->update(['is_active' => false]);
        $this->audio($mujawwad->refresh());

        app()->setLocale('ar');
        $response = $this->get(route('public.quran.reciter', ['slug' => 'switch-fixture', 'recording' => $mujawwad->id]))->assertOk();

        $counts = $response->viewData('recordingVariants')->pluck('surahs_count', 'id');
        $this->assertSame(1, (int) $counts[$murattal->id]);
        $this->assertSame(0, (int) $counts[$mujawwad->id]);
        $response->assertSee('aria-current="true"', false)
            ->assertSee(__('quran.recordings.mujawwad'))
            ->assertSee(route('public.quran.reciter', ['slug' => 'switch-fixture', 'recording' => $murattal->id]).'#recordings', false);
    }

    public function test_inactive_profile_and_spoken_translations_do_not_appear(): void
    {
        $r = $this->recording('hidden');
        $r->profile->update(['is_active' => false]);
        $translation = Reciter::create(['slug' => 'translation', 'kind' => 'translation']);
        $translation->syncTranslations(['name' => ['en' => 'Spoken translation']]);
        $this->assertNull($translation->refresh()->profile_id);
        $this->assertSame(0, app(ReciterDirectoryService::class)->listing('en')['total']);
        $this->get(route('public.quran.reciter', $r->profile->slug))->assertNotFound();
    }

    public function test_admin_profile_source_and_recording_pages_render(): void
    {
        $admin = $this->createAdminUser();
        $r = $this->recording('admin-recording');
        foreach ([route('admin.reciter-profiles.index'), route('admin.reciter-profiles.edit', $r->profile_id), route('admin.reciters.sources.index', $r->id), route('admin.reciters.show', $r->id), route('admin.reciters.edit', $r->id)] as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }
    }

    public function test_admin_cannot_edit_another_recordings_source_or_default(): void
    {
        $admin = $this->createAdminUser();
        $one = $this->recording('admin-one');
        $two = $this->recording('admin-two', 'murattal', '999');
        $this->actingAs($admin)->put(route('admin.reciters.sources.update', [$one->id, $two->sources->first()->id]), [])->assertNotFound();
        $this->actingAs($admin)->putJson(route('admin.reciter-profiles.update', $one->profile_id), ['names' => ['ar' => 'قارئ'], 'default_recording_id' => $two->id, 'is_active' => 1, 'is_featured' => 0, 'display_order' => 0])->assertUnprocessable()->assertJsonValidationErrors('default_recording_id');
    }

    public function test_generic_source_admin_save_preserves_assets_and_bumps_revision(): void
    {
        $admin = $this->createAdminUser();
        $r = $this->recording('source-edit');
        $audio = $this->audio($r);
        $source = $r->sources->first();
        $this->actingAs($admin)->put(route('admin.reciters.sources.update', [$r->id, $source->id]), ['provider' => 'mp3quran', 'kind' => 'surah', 'source_key' => 'fixture', 'base_url' => $source->base_url, 'external_timing_id' => 'new-timing', 'priority' => 2, 'is_active' => 1, 'metadata_json' => '{}'])->assertRedirect();
        $this->assertSame(2, $source->refresh()->revision);
        $this->assertSame($audio->audio_url, $audio->refresh()->audio_url);
    }
}
