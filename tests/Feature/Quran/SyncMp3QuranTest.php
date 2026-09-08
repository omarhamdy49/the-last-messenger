<?php

declare(strict_types=1);

namespace Tests\Feature\Quran;

use App\Domain\Quran\Models\Recitation;
use App\Domain\Quran\Models\Reciter;
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
            ['dry-run', 'keep-existing', 'only', 'map'],
            array_column($meta['options'], 'name'),
        );

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

    private function fakeApi(int $audioStatus = 200): void
    {
        Http::fake([
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
