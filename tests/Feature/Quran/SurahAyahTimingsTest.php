<?php

declare(strict_types=1);

namespace Tests\Feature\Quran;

use App\Domain\Quran\Models\Reciter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SurahAyahTimingsTest extends TestCase
{
    use RefreshDatabase;

    private const ENDPOINT = 'www.mp3quran.net/api/v3/ayat_timing*';

    public function test_it_returns_compact_triples_in_order_including_the_bismillah(): void
    {
        $reciter = $this->makeReciter(readId: 53);
        // Deliberately out of order: the upstream order is not a contract.
        Http::fake([self::ENDPOINT => Http::response([
            $this->row(2, 8851, 14058),
            $this->row(0, 0, 4495),
            $this->row(1, 4495, 8851),
        ])]);

        $response = $this->getJson($this->url(1, $reciter->id));

        $response->assertOk()->assertExactJson([
            'surah' => 1,
            'reciter_id' => $reciter->id,
            // Ayah 0 is the bismillah and is kept: the player highlights nothing while it plays.
            'ayahs' => [[0, 0, 4495], [1, 4495, 8851], [2, 8851, 14058]],
        ]);

        Http::assertSent(fn (Request $request) => $request->url() ===
            'https://www.mp3quran.net/api/v3/ayat_timing?surah=1&read=53');
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
        Http::fake([self::ENDPOINT => Http::response([$this->row(1, 0, 4495)])]);

        $this->getJson($this->url(36, $reciter->id))->assertOk();
        $this->getJson($this->url(36, $reciter->id))
            ->assertOk()
            ->assertJsonPath('ayahs', [[1, 0, 4495]]);

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

    private function makeReciter(?int $readId): Reciter
    {
        $reciter = Reciter::create([
            'slug' => 'abdul-basit-murattal',
            'kind' => 'recitation',
            'mp3quran_read_id' => $readId,
            'mp3quran_folder' => $readId === null ? null : 'https://server7.mp3quran.net/basit/',
            'is_active' => true,
        ]);

        $reciter->syncTranslations(['name' => ['ar' => 'عبد الباسط عبد الصمد']]);

        return $reciter->refresh();
    }

    /**
     * An upstream row, with the polygon/x/y/page fields the endpoint must drop.
     *
     * @return array<string, mixed>
     */
    private function row(int $ayah, int $start, int $end): array
    {
        return [
            'ayah' => $ayah,
            'polygon' => '181.08,18.31 57.54,18.31 57.54,48.94 181.08,48.94',
            'start_time' => $start,
            'end_time' => $end,
            'x' => '66.48',
            'y' => '34.46',
            'page' => 'https://www.mp3quran.net/api/quran_pages_svg/001.svg',
        ];
    }
}
