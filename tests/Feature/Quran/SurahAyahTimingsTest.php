<?php

declare(strict_types=1);

namespace Tests\Feature\Quran;

use App\Domain\Quran\Models\Recitation;
use App\Domain\Quran\Models\Reciter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SurahAyahTimingsTest extends TestCase
{
    use RefreshDatabase;

    private const ENDPOINT = 'www.mp3quran.net/api/v3/ayat_timing*';

    /** Real ayah counts for the surahs used below. */
    private const AYAH_COUNTS = [1 => 7, 2 => 286, 114 => 6];

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedSurahs();
    }

    /**
     * Alafasy (read 123) publishes no bismillah row: al-Fatiha is 1..7.
     */
    public function test_a_list_of_real_ayah_numbers_is_served_as_is(): void
    {
        $reciter = $this->makeReciter(readId: 123);
        $this->fakeTimings($this->rows(1, 7));

        $this->getJson($this->url(1, $reciter->id))
            ->assertOk()
            ->assertJsonPath('ayahs', $this->triples(1, 7));

        Http::assertSent(fn (Request $request) => $request->url() ===
            'https://www.mp3quran.net/api/v3/ayat_timing?surah=1&read=123');
    }

    /**
     * Abdul Basit (read 53) opens with an ayah 0 bismillah: al-Fatiha is 0..7.
     * The bismillah row is kept — the player highlights nothing while it plays.
     */
    public function test_a_leading_bismillah_row_is_kept(): void
    {
        $reciter = $this->makeReciter(readId: 53);
        $this->fakeTimings($this->rows(0, 7));

        $this->getJson($this->url(1, $reciter->id))
            ->assertOk()
            ->assertExactJson([
                'surah' => 1,
                'reciter_id' => $reciter->id,
                'ayahs' => $this->triples(0, 7),
            ]);
    }

    /**
     * Husary (read 118) returns 0..6 for a 7-ayah al-Fatiha: his "ayah 0" is
     * really ayah 1, so every index is one short. Served raw, the reader would
     * be highlighted a verse behind for the whole surah.
     */
    public function test_a_shifted_list_is_corrected_to_real_ayah_numbers(): void
    {
        $reciter = $this->makeReciter(readId: 118);
        $this->fakeTimings($this->rows(0, 6));

        $this->getJson($this->url(1, $reciter->id))
            ->assertOk()
            // Same seven rows, renumbered 1..7 — no bismillah row for this surah.
            ->assertJsonPath('ayahs', [
                [1, 0, 1000], [2, 1000, 2000], [3, 2000, 3000], [4, 3000, 4000],
                [5, 4000, 5000], [6, 5000, 6000], [7, 6000, 7000],
            ]);
    }

    /**
     * Husary is only shifted in al-Fatiha; 0..286 for al-Baqarah is the normal
     * bismillah convention and must not be renumbered.
     */
    public function test_the_same_read_is_untouched_where_it_is_not_shifted(): void
    {
        $reciter = $this->makeReciter(readId: 118);
        $this->fakeTimings($this->rows(0, 286));

        $response = $this->getJson($this->url(2, $reciter->id))->assertOk();

        $this->assertSame([0, 0, 1000], $response->json('ayahs.0'));
        $this->assertSame(287, count($response->json('ayahs')));
    }

    public function test_a_max_ayah_above_the_surahs_ayah_count_returns_an_empty_list(): void
    {
        $reciter = $this->makeReciter(readId: 53);
        // Nine rows for a six-ayah surah: we cannot say which index is wrong.
        $this->fakeTimings($this->rows(0, 8));

        $this->getJson($this->url(114, $reciter->id))
            ->assertOk()
            ->assertJsonPath('ayahs', []);
    }

    public function test_duplicate_ayah_indices_return_an_empty_list(): void
    {
        $reciter = $this->makeReciter(readId: 53);
        $rows = $this->rows(0, 6);
        $rows[3]['ayah'] = 2;

        $this->fakeTimings($rows);

        $this->getJson($this->url(114, $reciter->id))
            ->assertOk()
            ->assertJsonPath('ayahs', []);
    }

    public function test_non_monotonic_times_return_an_empty_list(): void
    {
        $reciter = $this->makeReciter(readId: 53);
        $rows = $this->rows(0, 6);
        $rows[4]['start_time'] = 100;

        $this->fakeTimings($rows);

        $this->getJson($this->url(114, $reciter->id))
            ->assertOk()
            ->assertJsonPath('ayahs', []);
    }

    public function test_it_sorts_rows_the_upstream_returned_out_of_order(): void
    {
        $reciter = $this->makeReciter(readId: 53);
        $this->fakeTimings(array_reverse($this->rows(0, 7)));

        $this->getJson($this->url(1, $reciter->id))
            ->assertOk()
            ->assertJsonPath('ayahs', $this->triples(0, 7));
    }

    public function test_a_reciter_without_a_read_id_returns_an_empty_list_without_calling_upstream(): void
    {
        $reciter = $this->makeReciter(readId: null);
        Http::fake();

        $this->getJson($this->url(1, $reciter->id))
            ->assertOk()
            ->assertExactJson(['surah' => 1, 'reciter_id' => $reciter->id, 'ayahs' => []]);

        Http::assertNothingSent();
    }

    public function test_an_upstream_error_returns_an_empty_list_with_a_200(): void
    {
        $reciter = $this->makeReciter(readId: 53);
        Http::fake([self::ENDPOINT => Http::response('', 500)]);

        // Highlighting is progressive enhancement — a dead upstream must not
        // cost the reader the audio, let alone 500 the page.
        $this->getJson($this->url(2, $reciter->id))
            ->assertOk()
            ->assertExactJson(['surah' => 2, 'reciter_id' => $reciter->id, 'ayahs' => []]);
    }

    public function test_a_malformed_body_returns_an_empty_list_with_a_200(): void
    {
        $reciter = $this->makeReciter(readId: 53);
        Http::fake([self::ENDPOINT => Http::response('<html>not json</html>')]);

        $this->getJson($this->url(2, $reciter->id))
            ->assertOk()
            ->assertExactJson(['surah' => 2, 'reciter_id' => $reciter->id, 'ayahs' => []]);
    }

    public function test_a_body_of_rows_with_no_timings_returns_an_empty_list_with_a_200(): void
    {
        $reciter = $this->makeReciter(readId: 53);
        Http::fake([self::ENDPOINT => Http::response([['error' => 'no timing for this read']])]);

        $this->getJson($this->url(2, $reciter->id))
            ->assertOk()
            ->assertExactJson(['surah' => 2, 'reciter_id' => $reciter->id, 'ayahs' => []]);
    }

    public function test_the_second_request_is_served_from_cache(): void
    {
        $reciter = $this->makeReciter(readId: 53);
        $this->fakeTimings($this->rows(0, 6));

        $this->getJson($this->url(114, $reciter->id))->assertOk();
        $this->getJson($this->url(114, $reciter->id))
            ->assertOk()
            ->assertJsonPath('ayahs', $this->triples(0, 6));

        Http::assertSentCount(1);
    }

    public function test_an_out_of_range_surah_404s(): void
    {
        $reciter = $this->makeReciter(readId: 53);
        Http::fake();

        $this->getJson($this->url(115, $reciter->id))->assertNotFound();

        Http::assertNothingSent();
    }

    private function url(int $surahNumber, int $reciterId): string
    {
        return route('public.quran.surah.timings', [
            'surahNumber' => $surahNumber,
            'reciterId' => $reciterId,
        ]);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function fakeTimings(array $rows): void
    {
        Http::fake([self::ENDPOINT => Http::response($rows)]);
    }

    /**
     * Upstream rows for ayah indices $from..$to, one second each, carrying the
     * polygon/x/y/page fields the endpoint must drop.
     *
     * @return list<array<string, mixed>>
     */
    private function rows(int $from, int $to): array
    {
        $rows = [];

        foreach (range($from, $to) as $i => $ayah) {
            $rows[] = [
                'ayah' => $ayah,
                'polygon' => '181.08,18.31 57.54,18.31 57.54,48.94 181.08,48.94',
                'start_time' => $i * 1000,
                'end_time' => ($i + 1) * 1000,
                'x' => '66.48',
                'y' => '34.46',
                'page' => 'https://www.mp3quran.net/api/quran_pages_svg/001.svg',
            ];
        }

        return $rows;
    }

    /**
     * The triples {@see rows()} should reduce to.
     *
     * @return list<array{int, int, int}>
     */
    private function triples(int $from, int $to): array
    {
        return array_values(array_map(
            static fn (array $row): array => [$row['ayah'], $row['start_time'], $row['end_time']],
            $this->rows($from, $to),
        ));
    }

    private function makeReciter(?int $readId): Reciter
    {
        $reciter = Reciter::create([
            'slug' => 'abdul-basit-murattal',
            'kind' => 'recitation',
            'is_active' => true,
        ]);

        $source = $reciter->sources()->create(['provider' => 'mp3quran', 'kind' => 'surah', 'source_key' => 'fixture', 'external_timing_id' => $readId, 'base_url' => 'https://server7.mp3quran.net/basit/']);
        foreach (array_keys(self::AYAH_COUNTS) as $surah) {
            Recitation::create(['reciter_id' => $reciter->id, 'source_id' => $source->id, 'surah_id' => $surah, 'format' => 'mp3', 'audio_url' => sprintf('https://server7.mp3quran.net/basit/%03d.mp3', $surah)]);
        }
        $reciter->syncTranslations(['name' => ['ar' => 'عبد الباسط عبد الصمد']]);

        return $reciter->refresh();
    }

    private function seedSurahs(): void
    {
        foreach (self::AYAH_COUNTS as $number => $versesCount) {
            DB::table('quran_surahs')->insert([
                'id' => $number,
                'revelation_place' => 'makkah',
                'revelation_order' => $number,
                'name_ar' => "سورة {$number}",
                'name_en' => "Surah {$number}",
                'verses_count' => $versesCount,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
