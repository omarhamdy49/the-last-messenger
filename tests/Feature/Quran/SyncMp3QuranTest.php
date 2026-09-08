<?php

declare(strict_types=1);

namespace Tests\Feature\Quran;

use App\Domain\Quran\Models\Recitation;
use App\Domain\Quran\Models\Reciter;
use App\Domain\Quran\Models\ReciterTranslation;
use App\Domain\Quran\Services\ReciterNameMatcher;
use App\Domain\Shared\Services\ArtisanCommandService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SyncMp3QuranTest extends TestCase
{
    use RefreshDatabase;

    private const BASIT = 'https://server7.mp3quran.net/basit/';

    public function test_it_scores_a_name_the_two_catalogues_space_differently_as_identical(): void
    {
        // The single most common difference between the catalogues.
        $this->assertSame(1.0, ReciterNameMatcher::score('عبد الباسط عبد الصمد', 'عبدالباسط عبدالصمد'));
        $this->assertSame(1.0, ReciterNameMatcher::score('عبد الرحمن السديس', 'عبدالرحمن السديس'));

        // Style notes and honorifics carry no identity.
        $this->assertSame(1.0, ReciterNameMatcher::score('محمود خليل الحصري (مجود)', 'محمود خليل الحصري'));
        $this->assertGreaterThanOrEqual(
            ReciterNameMatcher::AUTO_THRESHOLD,
            ReciterNameMatcher::score('أبو بكر الشاطري', 'شيخ أبو بكر الشاطري'),
        );

        // A latin-only name has nothing to compare.
        $this->assertSame(0.0, ReciterNameMatcher::score('Besim Korkut (Bosnian)', 'عبدالباسط عبدالصمد'));
    }

    /**
     * These six all have a plausible namesake in the catalogue and no certain
     * one. Auto-mapping any of them would file another qari's audio under their
     * name, so they must land in the review band instead.
     */
    public function test_it_leaves_the_known_ambiguous_names_below_the_auto_threshold(): void
    {
        $pairs = [
            ['مشاري بن راشد العفاسي', 'مشاري العفاسي'],
            ['محمد محمود الطبلاوي', 'محمد الطبلاوي'],
            ['خالد عبد الله القحطاني', 'خالد القحطاني'],
            ['ياسين الجزائري (ورش)', 'القارئ ياسين'],
            ['صلاح عبد الرحمن بوخاطر', 'صلاح بو خاطر'],
            ['أيمن سويد', 'عبدالرحمن السويّد'],
        ];

        foreach ($pairs as [$ours, $theirs]) {
            $score = ReciterNameMatcher::score($ours, $theirs);

            $this->assertGreaterThanOrEqual(ReciterNameMatcher::REVIEW_THRESHOLD, $score, "{$ours} vs {$theirs}");
            $this->assertLessThan(ReciterNameMatcher::AUTO_THRESHOLD, $score, "{$ours} vs {$theirs}");
        }
    }

    public function test_it_writes_audio_urls_and_the_mapping_for_a_confident_match(): void
    {
        $this->seedSurahs();
        $reciter = $this->makeReciter('abdul-basit-murattal', 'عبد الباسط عبد الصمد (مرتل)');
        $this->fakeApi();

        $this->artisan('quran:sync-mp3quran')->assertSuccessful();

        $reciter->refresh();
        $this->assertSame(53, $reciter->mp3quran_read_id);
        $this->assertSame(self::BASIT, $reciter->mp3quran_folder);

        $this->assertSame(3, Recitation::where('reciter_id', $reciter->id)->count());
        $this->assertSame(
            self::BASIT.'001.mp3',
            Recitation::where(['reciter_id' => $reciter->id, 'surah_id' => 1])->value('audio_url'),
        );
        // Zero padded to three digits — /basit/1.mp3 is a 404.
        $this->assertSame(
            self::BASIT.'003.mp3',
            Recitation::where(['reciter_id' => $reciter->id, 'surah_id' => 3])->value('audio_url'),
        );
    }

    public function test_it_picks_the_recording_matching_the_style_in_the_reciters_name(): void
    {
        $this->seedSurahs();
        $reciter = $this->makeReciter('abdul-basit-mujawwad', 'عبد الباسط عبد الصمد (مجود)');
        $this->fakeApi();

        $this->artisan('quran:sync-mp3quran')->assertSuccessful();

        $this->assertSame(
            self::BASIT.'Almusshaf-Al-Mojawwad/001.mp3',
            Recitation::where(['reciter_id' => $reciter->id, 'surah_id' => 1])->value('audio_url'),
        );
        // That folder has no timing data, so there is no read id to record.
        $this->assertNull($reciter->refresh()->mp3quran_read_id);
    }

    public function test_a_dry_run_writes_nothing(): void
    {
        $this->seedSurahs();
        $reciter = $this->makeReciter('abdul-basit-murattal', 'عبد الباسط عبد الصمد (مرتل)');
        $this->fakeApi();

        $this->artisan('quran:sync-mp3quran', ['--dry-run' => true])
            ->expectsOutputToContain('Dry run: nothing was written.')
            ->assertSuccessful();

        $this->assertSame(0, Recitation::count());
        $this->assertNull($reciter->refresh()->mp3quran_read_id);
        $this->assertNull($reciter->mp3quran_folder);
    }

    public function test_a_second_run_changes_nothing(): void
    {
        $this->seedSurahs();
        $this->makeReciter('abdul-basit-murattal', 'عبد الباسط عبد الصمد (مرتل)');
        $this->fakeApi();

        $this->artisan('quran:sync-mp3quran')->assertSuccessful();

        $before = Recitation::orderBy('surah_id')->get(['id', 'audio_url', 'updated_at'])->toArray();

        $this->artisan('quran:sync-mp3quran')
            ->expectsOutputToContain('up to date')
            ->assertSuccessful();

        $this->assertSame($before, Recitation::orderBy('surah_id')->get(['id', 'audio_url', 'updated_at'])->toArray());

        // Nothing to write means nothing to probe: the two HEADs are the first run's.
        $probes = collect(Http::recorded())->filter(fn (array $pair) => $pair[0]->method() === 'HEAD');
        $this->assertCount(2, $probes);
    }

    /**
     * Phase 2 highlighting reads mp3quran's timings, which only line up with
     * mp3quran's own take, so a mapped reciter is switched over wholesale.
     */
    public function test_it_replaces_existing_audio_by_default(): void
    {
        $this->seedSurahs();
        $reciter = $this->makeReciter('abdul-basit-murattal', 'عبد الباسط عبد الصمد (مرتل)');
        $this->seedExistingAudio($reciter, 'https://download.quranicaudio.com/basit/001.mp3');
        $this->fakeApi();

        $this->artisan('quran:sync-mp3quran')->assertSuccessful();

        $this->assertSame(
            self::BASIT.'001.mp3',
            Recitation::where(['reciter_id' => $reciter->id, 'surah_id' => 1])->value('audio_url'),
        );
        $this->assertSame(3, Recitation::where('reciter_id', $reciter->id)->count());
    }

    public function test_keep_existing_leaves_audio_we_already_have(): void
    {
        $this->seedSurahs();
        $reciter = $this->makeReciter('abdul-basit-murattal', 'عبد الباسط عبد الصمد (مرتل)');
        $this->seedExistingAudio($reciter, 'https://download.quranicaudio.com/basit/001.mp3');
        $this->fakeApi();

        $this->artisan('quran:sync-mp3quran', ['--keep-existing' => true])->assertSuccessful();

        $this->assertSame(
            'https://download.quranicaudio.com/basit/001.mp3',
            Recitation::where(['reciter_id' => $reciter->id, 'surah_id' => 1])->value('audio_url'),
        );
        // The surahs it had no audio for are still filled in.
        $this->assertSame(3, Recitation::where('reciter_id', $reciter->id)->count());
    }

    public function test_a_dry_run_shows_what_each_replacement_would_change(): void
    {
        $this->seedSurahs();
        $reciter = $this->makeReciter('abdul-basit-murattal', 'عبد الباسط عبد الصمد (مرتل)');
        $this->seedExistingAudio($reciter, 'https://download.quranicaudio.com/basit/001.mp3');
        $this->fakeApi();

        $this->artisan('quran:sync-mp3quran', ['--dry-run' => true])
            ->expectsOutputToContain('Sample of the URLs that would be replaced')
            // One substring, not two: the assertion matches a whole written
            // line, and the before/after pair shares a line.
            ->expectsOutputToContain('https://download.quranicaudio.com/basit/001.mp3  →  '.self::BASIT.'001.mp3')
            ->expectsOutputToContain('2 audio URLs created, 1 replaced')
            ->assertSuccessful();

        $this->assertSame(
            'https://download.quranicaudio.com/basit/001.mp3',
            Recitation::where(['reciter_id' => $reciter->id, 'surah_id' => 1])->value('audio_url'),
        );
    }

    public function test_an_unreachable_folder_never_half_replaces_a_reciter(): void
    {
        $this->seedSurahs();
        $reciter = $this->makeReciter('abdul-basit-murattal', 'عبد الباسط عبد الصمد (مرتل)');
        $this->seedExistingAudio($reciter, 'https://download.quranicaudio.com/basit/001.mp3');
        $this->fakeApi(audioStatus: 404);

        $this->artisan('quran:sync-mp3quran')
            ->expectsOutputToContain('is not reachable')
            ->assertSuccessful();

        $this->assertSame(1, Recitation::where('reciter_id', $reciter->id)->count());
        $this->assertSame(
            'https://download.quranicaudio.com/basit/001.mp3',
            Recitation::where(['reciter_id' => $reciter->id, 'surah_id' => 1])->value('audio_url'),
        );
    }

    /**
     * The admin panel can only launch what the allowlist names, so a rename
     * here would quietly remove the button rather than break anything loudly.
     */
    public function test_the_admin_settings_panel_can_launch_it(): void
    {
        $meta = app(ArtisanCommandService::class)->find('quran:sync-mp3quran');

        $this->assertNotNull($meta, 'quran:sync-mp3quran is missing from config/artisan-commands.php or is forbidden.');
        $this->assertTrue($meta['queued'], 'It calls an external API — an inline run dies on max_execution_time.');

        // The form is generated from the signature, so every input must be one
        // an admin can fill in without a console.
        $this->assertSame([], $meta['arguments']);
        $this->assertEqualsCanonicalizing(
            ['dry-run', 'import-new', 'inactive', 'fresh', 'keep-existing', 'only', 'map'],
            array_column($meta['options'], 'name'),
        );

        // --force is forced by the config, so the form must not offer it: the
        // panel has nothing to answer a ConfirmableTrait prompt with.
        $this->assertSame(['--force' => true], $meta['defaults']);

        foreach ($meta['options'] as $option) {
            $this->assertNotSame('', $option['description'], "Option --{$option['name']} has no form label.");
        }
    }

    public function test_it_reports_ambiguous_reciters_and_writes_nothing_for_them(): void
    {
        $this->seedSurahs();
        $this->makeReciter('alafasy', 'مشاري بن راشد العفاسي');
        $this->makeReciter('mohammad-al-tablaway', 'محمد محمود الطبلاوي');
        $this->fakeApi();

        $this->artisan('quran:sync-mp3quran')
            ->expectsOutputToContain('2 reciters need a human')
            ->assertSuccessful();

        $this->assertSame(0, Recitation::count());
        $this->assertSame(0, Reciter::whereNotNull('mp3quran_folder')->count());
    }

    public function test_map_resolves_an_ambiguous_reciter_by_hand(): void
    {
        $this->seedSurahs();
        $reciter = $this->makeReciter('alafasy', 'مشاري بن راشد العفاسي');
        $this->fakeApi();

        $this->artisan('quran:sync-mp3quran', ['--map' => ["{$reciter->id}:123"]])->assertSuccessful();

        $reciter->refresh();
        $this->assertSame(123, $reciter->mp3quran_read_id);
        $this->assertSame('https://server8.mp3quran.net/afs/', $reciter->mp3quran_folder);
        $this->assertSame(3, Recitation::where('reciter_id', $reciter->id)->count());
    }

    public function test_it_never_forces_a_recited_translation_onto_a_qari(): void
    {
        $this->seedSurahs();
        $this->makeReciter('besim-korkut-bosnian', 'Besim Korkut (Bosnian)');
        $this->fakeApi();

        $this->artisan('quran:sync-mp3quran')
            ->expectsOutputToContain('no Arabic name')
            ->assertSuccessful();

        $this->assertSame(0, Recitation::count());
    }

    public function test_an_api_failure_aborts_before_writing_anything(): void
    {
        $this->seedSurahs();
        $reciter = $this->makeReciter('abdul-basit-murattal', 'عبد الباسط عبد الصمد (مرتل)');

        Http::fake([
            'www.mp3quran.net/api/v3/ayat_timing/reads' => Http::response('', 503),
            '*' => Http::response($this->catalogue()),
        ]);

        $this->artisan('quran:sync-mp3quran')->assertFailed();

        $this->assertSame(0, Recitation::count());
        $this->assertNull($reciter->refresh()->mp3quran_folder);
    }

    // ------------------------------------------------------------------
    // --import-new
    // ------------------------------------------------------------------

    public function test_import_new_creates_one_reciter_per_recording(): void
    {
        $this->seedSurahs();
        $this->fakeApi();

        $this->artisan('quran:sync-mp3quran', ['--import-new' => true])->assertSuccessful();

        // Three recordings for Abdul Basit plus one each for the other two.
        $this->assertSame(6, Reciter::count());
        $this->assertSame(18, Recitation::count());

        $murattal = Reciter::where('mp3quran_folder', self::BASIT)->sole();
        // Written the way the rest of our catalogue writes it, not the way
        // mp3quran does — see houseSpelling().
        $this->assertSame('عبد الباسط عبد الصمد (مرتل)', $murattal->getDisplayName('ar'));
        $this->assertSame('Abdulbasit Abdulsamad (Murattal)', $murattal->getDisplayName('en'));
        $this->assertSame('murattal', $murattal->style);
        $this->assertSame("Hafs 'an 'Asim", $murattal->riwayah);
        $this->assertSame('recitation', $murattal->kind);
        $this->assertSame(53, $murattal->mp3quran_read_id);
        $this->assertSame(3, $murattal->recitations()->count());

        // A style suffix only where there is something to tell apart.
        $this->assertSame(
            'مشاري العفاسي',
            Reciter::where('mp3quran_folder', 'https://server8.mp3quran.net/afs/')->sole()->getDisplayName('ar'),
        );

        // Riwayah wins over style as the disambiguator, as it already does for
        // عبد الباسط عبد الصمد (ورش) in our own catalogue.
        $warsh = Reciter::where('mp3quran_folder', self::BASIT.'Rewayat-Warsh-A-n-Nafi/')->sole();
        $this->assertSame('عبد الباسط عبد الصمد (ورش)', $warsh->getDisplayName('ar'));
        $this->assertSame('Abdulbasit Abdulsamad (Warsh)', $warsh->getDisplayName('en'));
        $this->assertSame("Warsh 'an Nafi'", $warsh->riwayah);

        $mujawwad = Reciter::where('mp3quran_folder', self::BASIT.'Almusshaf-Al-Mojawwad/')->sole();
        $this->assertSame('mujawwad', $mujawwad->style);
        // A mujawwad mushaf is a Hafs recording however mp3quran files it.
        $this->assertSame("Hafs 'an 'Asim", $mujawwad->riwayah);
    }

    /**
     * An imported reciter that does not reach the public list or the surah
     * picker has not really been imported.
     */
    public function test_imported_reciters_are_publicly_visible(): void
    {
        $this->seedSurahs();
        $this->fakeApi();

        $this->artisan('quran:sync-mp3quran', ['--import-new' => true])->assertSuccessful();

        $this->assertSame(6, Reciter::where('is_active', true)->count());
        // Featured is a curated slot, not something an import claims.
        $this->assertSame(0, Reciter::where('is_featured', true)->count());
    }

    public function test_inactive_imports_them_hidden_for_review(): void
    {
        $this->seedSurahs();
        $this->fakeApi();

        $this->artisan('quran:sync-mp3quran', ['--import-new' => true, '--inactive' => true])
            ->expectsOutputToContain('imported hidden')
            ->assertSuccessful();

        $this->assertSame(6, Reciter::count());
        $this->assertSame(0, Reciter::where('is_active', true)->count());
    }

    /**
     * The acceptance case: عبد الله الخلف is not one of our 56 and arrives
     * spelled solid, which would sort him away from every other عبد الله in
     * the alphabetically-ordered picker.
     */
    public function test_it_writes_compound_names_the_way_the_rest_of_the_catalogue_is_written(): void
    {
        $this->seedSurahs();
        $this->fakeApi();

        $this->artisan('quran:sync-mp3quran', ['--import-new' => true])->assertSuccessful();

        $khalaf = Reciter::where('mp3quran_folder', 'https://server14.mp3quran.net/khalf/')->sole();
        $this->assertSame('عبد الله الخلف', $khalaf->getDisplayName('ar'));
        $this->assertSame(244, $khalaf->mp3quran_read_id);
        $this->assertTrue($khalaf->is_active);
        $this->assertSame(3, $khalaf->recitations()->count());
        $this->assertSame('عبد الباسط عبد الصمد (مرتل)', Reciter::where('mp3quran_folder', self::BASIT)->sole()->getDisplayName('ar'));
    }

    public function test_import_new_is_idempotent(): void
    {
        $this->seedSurahs();
        $this->fakeApi();

        $this->artisan('quran:sync-mp3quran', ['--import-new' => true])->assertSuccessful();
        $before = Reciter::orderBy('id')->pluck('slug')->all();

        $this->artisan('quran:sync-mp3quran', ['--import-new' => true])->assertSuccessful();

        $this->assertSame($before, Reciter::orderBy('id')->pluck('slug')->all());
        $this->assertSame(18, Recitation::count());
    }

    public function test_import_new_leaves_out_a_recording_whose_audio_is_dead(): void
    {
        $this->seedSurahs();
        Http::fake([
            'www.mp3quran.net/api/v3/reciters?language=eng' => Http::response($this->englishCatalogue()),
            'www.mp3quran.net/api/v3/reciters*' => Http::response($this->catalogue()),
            'www.mp3quran.net/api/v3/ayat_timing/reads' => Http::response($this->timedReads()),
            'server8.mp3quran.net/afs/*' => Http::response('', 404),
            '*.mp3' => Http::response(''),
        ]);
        Http::preventStrayRequests();

        $this->artisan('quran:sync-mp3quran', ['--import-new' => true])
            ->expectsOutputToContain('is not reachable')
            ->assertSuccessful();

        $this->assertSame(5, Reciter::count());
        $this->assertSame(0, Reciter::where('mp3quran_folder', 'https://server8.mp3quran.net/afs/')->count());
    }

    public function test_import_new_never_reuses_a_slug(): void
    {
        $this->seedSurahs();
        // Squat on the slug the Alafasy recording would generate.
        $squatter = $this->makeReciter('mishary-alafasi', 'قارئ آخر');
        $this->fakeApi();

        $this->artisan('quran:sync-mp3quran', ['--import-new' => true])->assertSuccessful();

        $imported = Reciter::where('mp3quran_folder', 'https://server8.mp3quran.net/afs/')->sole();
        $this->assertSame('mishary-alafasi-123', $imported->slug, 'A collision is settled by the recording id, not a counter.');
        $this->assertSame('mishary-alafasi', $squatter->refresh()->slug);
        $this->assertSame(Reciter::count(), Reciter::distinct()->count('slug'));
    }

    public function test_import_new_is_skipped_when_only_is_given(): void
    {
        $this->seedSurahs();
        $reciter = $this->makeReciter('abdul-basit-murattal', 'عبد الباسط عبد الصمد (مرتل)');
        $this->fakeApi();

        $this->artisan('quran:sync-mp3quran', ['--import-new' => true, '--only' => (string) $reciter->id])
            ->expectsOutputToContain('--import-new was skipped')
            ->assertSuccessful();

        $this->assertSame(1, Reciter::count());
    }

    // ------------------------------------------------------------------
    // --fresh
    // ------------------------------------------------------------------

    public function test_fresh_rebuilds_mapped_reciters_and_keeps_what_mp3quran_cannot_tell_us(): void
    {
        $this->seedSurahs();
        $reciter = $this->makeReciter('abdul-basit-murattal', 'عبد الباسط عبد الصمد (مرتل)');
        $reciter->forceFill([
            'mp3quran_folder' => self::BASIT,
            'mp3quran_read_id' => 53,
            'everyayah_folder' => 'AbdulSamad_64kbps_QuranExplorer.Com',
            'profile_image' => 'https://commons.example/basit.jpg',
            'country' => 'EG',
            'is_featured' => true,
            'is_active' => true,
            'display_order' => 7,
        ])->save();
        $this->seedExistingAudio($reciter, 'https://download.quranicaudio.com/basit/001.mp3');
        $this->fakeApi();

        $this->artisan('quran:sync-mp3quran', ['--fresh' => true, '--force' => true])
            ->expectsOutputToContain('Deleting 1 reciters')
            ->assertSuccessful();

        $rebuilt = Reciter::where('mp3quran_folder', self::BASIT)->sole();
        $this->assertNotSame($reciter->id, $rebuilt->id, 'The row is rebuilt, not updated.');

        // Curated and everyayah data survives the rebuild...
        $this->assertSame('abdul-basit-murattal', $rebuilt->slug);
        $this->assertSame('AbdulSamad_64kbps_QuranExplorer.Com', $rebuilt->everyayah_folder);
        $this->assertSame('https://commons.example/basit.jpg', $rebuilt->profile_image);
        $this->assertSame('EG', $rebuilt->country);
        $this->assertTrue($rebuilt->is_featured);
        $this->assertTrue($rebuilt->is_active);
        $this->assertSame(7, $rebuilt->display_order);
        $this->assertSame('عبد الباسط عبد الصمد (مرتل)', $rebuilt->getDisplayName('ar'));

        // ...and the audio is rebuilt from mp3quran, with the stale row gone.
        $this->assertSame(3, $rebuilt->recitations()->count());
        $this->assertSame(0, Recitation::where('audio_url', 'like', '%quranicaudio%')->count());
    }

    public function test_fresh_leaves_reciters_that_are_not_from_mp3quran_alone(): void
    {
        $this->seedSurahs();
        // A recited translation and an everyayah-only qari: neither exists on
        // mp3quran, so deleting them would be a one-way loss.
        $translation = $this->makeReciter('besim-korkut-bosnian', 'Besim Korkut (Bosnian)');
        $translation->forceFill(['kind' => 'translation', 'everyayah_folder' => 'Bosnian'])->save();
        $this->seedExistingAudio($translation, 'https://everyayah.com/bosnian/001.mp3');

        // Something for --fresh to actually rebuild, so the run reports scope.
        $this->makeReciter('abdul-basit-murattal', 'عبد الباسط عبد الصمد (مرتل)')
            ->forceFill(['mp3quran_folder' => self::BASIT, 'mp3quran_read_id' => 53])
            ->save();

        $this->fakeApi();

        $this->artisan('quran:sync-mp3quran', ['--fresh' => true, '--force' => true])
            ->expectsOutputToContain('1 reciters are not from mp3quran and are left alone')
            ->assertSuccessful();

        $this->assertTrue($translation->refresh()->exists);
        $this->assertSame('Bosnian', $translation->everyayah_folder);
        $this->assertSame(
            'https://everyayah.com/bosnian/001.mp3',
            Recitation::where(['reciter_id' => $translation->id, 'surah_id' => 1])->value('audio_url'),
        );
    }

    public function test_fresh_deletes_child_rows_with_their_reciter(): void
    {
        $this->seedSurahs();
        $reciter = $this->makeReciter('abdul-basit-murattal', 'عبد الباسط عبد الصمد (مرتل)');
        $reciter->forceFill(['mp3quran_folder' => self::BASIT, 'mp3quran_read_id' => 53])->save();
        $this->seedExistingAudio($reciter, 'https://download.quranicaudio.com/basit/001.mp3');
        $this->fakeApi();

        $this->artisan('quran:sync-mp3quran', ['--fresh' => true, '--force' => true])->assertSuccessful();

        $this->assertSame(0, Recitation::where('reciter_id', $reciter->id)->count());
        $this->assertSame(0, ReciterTranslation::where('reciter_id', $reciter->id)->count());
        $this->assertSame(0, Reciter::where('id', $reciter->id)->count());
        // Every surviving child belongs to a reciter that still exists.
        $this->assertSame(0, Recitation::whereNotIn('reciter_id', Reciter::query()->select('id'))->count());
        $this->assertSame(0, ReciterTranslation::whereNotIn('reciter_id', Reciter::query()->select('id'))->count());
    }

    /**
     * Two of our rows legitimately point at one recording (the same qari
     * imported twice under different slugs). A folder-keyed snapshot must put
     * both back, not silently drop one.
     */
    public function test_fresh_rebuilds_every_row_that_shared_a_folder(): void
    {
        $this->seedSurahs();

        foreach (['abdul-basit-murattal' => 'عبد الباسط عبد الصمد (مرتل)', 'abdulsamad-quranexplorercom' => 'عبد الباسط عبد الصمد'] as $slug => $name) {
            $this->makeReciter($slug, $name)
                ->forceFill(['mp3quran_folder' => self::BASIT, 'mp3quran_read_id' => 53])
                ->save();
        }

        $this->fakeApi();

        $this->artisan('quran:sync-mp3quran', ['--fresh' => true, '--force' => true])->assertSuccessful();

        $this->assertEqualsCanonicalizing(
            ['abdul-basit-murattal', 'abdulsamad-quranexplorercom'],
            Reciter::where('mp3quran_folder', self::BASIT)->pluck('slug')->all(),
        );
        // Scoped by reciter: a LIKE on the folder would also catch the Warsh
        // and mujawwad recordings, which live in subfolders of it.
        $this->assertSame(6, Recitation::whereIn(
            'reciter_id',
            Reciter::where('mp3quran_folder', self::BASIT)->select('id'),
        )->count());
    }

    public function test_a_fresh_dry_run_deletes_nothing(): void
    {
        $this->seedSurahs();
        $reciter = $this->makeReciter('abdul-basit-murattal', 'عبد الباسط عبد الصمد (مرتل)');
        $reciter->forceFill(['mp3quran_folder' => self::BASIT, 'mp3quran_read_id' => 53])->save();
        $this->seedExistingAudio($reciter, 'https://download.quranicaudio.com/basit/001.mp3');
        $this->fakeApi();

        $this->artisan('quran:sync-mp3quran', ['--fresh' => true, '--dry-run' => true])
            ->expectsOutputToContain('Would delete 1 reciters')
            ->assertSuccessful();

        $this->assertSame(1, Reciter::count());
        $this->assertSame(1, Recitation::count());
        $this->assertSame($reciter->id, Reciter::sole()->id);
    }

    private function fakeApi(int $audioStatus = 200): void
    {
        Http::fake([
            // Listed first: Str::is treats '?' literally, so the wildcard stub
            // below would otherwise answer the English request in Arabic.
            'www.mp3quran.net/api/v3/reciters?language=eng' => Http::response($this->englishCatalogue()),
            'www.mp3quran.net/api/v3/reciters*' => Http::response($this->catalogue()),
            'www.mp3quran.net/api/v3/ayat_timing/reads' => Http::response($this->timedReads()),
            '*.mp3' => Http::response('', $audioStatus),
        ]);

        Http::preventStrayRequests();
    }

    /**
     * A trimmed copy of the real payload: three surahs per recording instead of
     * 114, and only the reciters the tests need.
     *
     * @return array<string, mixed>
     */
    private function catalogue(): array
    {
        return [
            'reciters' => [
                [
                    'id' => 51,
                    'name' => 'عبدالباسط عبدالصمد',
                    'moshaf' => [
                        $this->moshaf(53, 'حفص عن عاصم - مرتل', 1, self::BASIT),
                        $this->moshaf(52, 'ورش عن نافع - مرتل', 2, self::BASIT.'Rewayat-Warsh-A-n-Nafi/'),
                        $this->moshaf(51, 'المصحف المجود - المصحف المجود', 22, self::BASIT.'Almusshaf-Al-Mojawwad/'),
                    ],
                ],
                [
                    'id' => 123,
                    'name' => 'مشاري العفاسي',
                    'moshaf' => [$this->moshaf(123, 'حفص عن عاصم - مرتل', 1, 'https://server8.mp3quran.net/afs/')],
                ],
                [
                    'id' => 106,
                    'name' => 'محمد الطبلاوي',
                    'moshaf' => [$this->moshaf(106, 'حفص عن عاصم - مرتل', 1, 'https://server12.mp3quran.net/tblawi/')],
                ],
                [
                    'id' => 244,
                    'name' => 'عبدالله الخلف',
                    'moshaf' => [$this->moshaf(244, 'حفص عن عاصم - مرتل', 1, 'https://server14.mp3quran.net/khalf/')],
                ],
            ],
        ];
    }

    /**
     * The same payload in English. Only the names are read from it.
     *
     * @return array<string, mixed>
     */
    private function englishCatalogue(): array
    {
        return [
            'reciters' => [
                [
                    'id' => 51,
                    'name' => 'Abdulbasit Abdulsamad',
                    'moshaf' => [
                        $this->moshaf(53, "Rewayat Hafs A'n Assem - Murattal", 1, self::BASIT),
                        $this->moshaf(52, "Rewayat Warsh A'n Nafi - Murattal", 2, self::BASIT.'Rewayat-Warsh-A-n-Nafi/'),
                        $this->moshaf(51, 'Almusshaf Al Mojawwad - Almusshaf Al Mojawwad', 22, self::BASIT.'Almusshaf-Al-Mojawwad/'),
                    ],
                ],
                [
                    'id' => 123,
                    'name' => 'Mishary Alafasi',
                    'moshaf' => [$this->moshaf(123, "Rewayat Hafs A'n Assem - Murattal", 1, 'https://server8.mp3quran.net/afs/')],
                ],
                [
                    'id' => 106,
                    'name' => 'Mohammad Al-Tablaway',
                    'moshaf' => [$this->moshaf(106, "Rewayat Hafs A'n Assem - Murattal", 1, 'https://server12.mp3quran.net/tblawi/')],
                ],
                [
                    'id' => 244,
                    'name' => 'Abdullah Al-Khalaf',
                    'moshaf' => [$this->moshaf(244, "Rewayat Hafs A'n Assem - Murattal", 1, 'https://server14.mp3quran.net/khalf/')],
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function moshaf(int $id, string $name, int $rewaya, string $server): array
    {
        return [
            'id' => $id,
            'name' => $name,
            'rewaya_id' => $rewaya,
            'server' => $server,
            'surah_total' => 3,
            'surah_list' => '1,2,3',
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function timedReads(): array
    {
        return [
            ['id' => 53, 'name' => 'عبدالباسط عبدالصمد', 'rewaya' => 'حفص عن عاصم', 'folder_url' => self::BASIT, 'soar_count' => 3],
            ['id' => 123, 'name' => 'مشاري العفاسي', 'rewaya' => 'حفص عن عاصم', 'folder_url' => 'https://server8.mp3quran.net/afs/', 'soar_count' => 3],
            ['id' => 244, 'name' => 'عبدالله الخلف', 'rewaya' => 'حفص عن عاصم', 'folder_url' => 'https://server14.mp3quran.net/khalf/', 'soar_count' => 3],
        ];
    }

    private function makeReciter(string $slug, string $name): Reciter
    {
        $reciter = Reciter::create([
            'slug' => $slug,
            'kind' => 'recitation',
            'riwayah' => str_contains($name, 'ورش') ? "Warsh 'an Nafi'" : "Hafs 'an 'Asim",
            'is_active' => true,
        ]);

        $reciter->syncTranslations(['name' => ['ar' => $name]]);

        return $reciter->refresh();
    }

    private function seedExistingAudio(Reciter $reciter, string $url): void
    {
        Recitation::create([
            'reciter_id' => $reciter->id,
            'surah_id' => 1,
            'audio_url' => $url,
            'format' => 'mp3',
        ]);
    }

    private function seedSurahs(): void
    {
        foreach ([1, 2, 3] as $id) {
            DB::table('quran_surahs')->insert([
                'id' => $id,
                'revelation_place' => 'makkah',
                'revelation_order' => $id,
                'name_ar' => "سورة {$id}",
                'name_en' => "Surah {$id}",
                'verses_count' => 7,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
