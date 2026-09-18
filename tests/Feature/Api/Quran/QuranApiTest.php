<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Quran;

use App\Domain\Quran\Models\Chapter;
use App\Domain\Quran\Models\ChapterAudioFile;
use App\Domain\Quran\Models\ChapterInfo;
use App\Domain\Quran\Models\QuranResource;
use App\Domain\Quran\Models\Tafsir;
use App\Domain\Quran\Models\Translation;
use App\Domain\Quran\Models\Verse;
use App\Domain\Quran\Models\Word;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * US6: `/api/v1/quran/*` serves QF v4 shapes from local data behind api.client + api.scope:quran.
 */
final class QuranApiTest extends TestCase
{
    use RefreshDatabase;

    private QuranResource $translation;

    private QuranResource $second;

    private QuranResource $tafsir;

    private QuranResource $reciter;

    protected function setUp(): void
    {
        parent::setUp();
        Sanctum::actingAs(User::factory()->create(), ['api:access']);

        Chapter::factory()->number(1)->withVerses(7)->create();
        Chapter::factory()->number(2)->withVerses(3)->create();
        foreach (Verse::query()->pluck('id') as $verseId) {
            Word::factory()->create(['verse_id' => $verseId]);
        }
        DB::table('quran_sections')->insert(['type' => 'juz', 'number' => 1, 'first_verse_id' => 1, 'last_verse_id' => 10, 'verses_count' => 10, 'verse_mapping' => json_encode(['1' => '1-7', '2' => '1-3'])]);
        DB::table('quran_verses')->update(['juz_number' => 1, 'hizb_number' => 1, 'rub_el_hizb_number' => 1, 'manzil_number' => 1, 'ruku_number' => 1, 'page_number' => 1]);

        $this->translation = QuranResource::factory()->translation('en')->asDefault()->create(['name' => 'Saheeh International']);
        $this->second = QuranResource::factory()->translation('fr')->create(['name' => 'Hamidullah']);
        $this->tafsir = QuranResource::factory()->tafsir('ar')->create(['name' => 'Ibn Kathir']);
        foreach (Verse::query()->pluck('id') as $verseId) {
            Translation::factory()->withFootnote('77', 'note')->create(['resource_id' => $this->translation->id, 'verse_id' => $verseId, 'text' => 'In the name<sup foot_note="77">1</sup> of God']);
            Translation::factory()->create(['resource_id' => $this->second->id, 'verse_id' => $verseId, 'text' => 'Au nom de Dieu']);
            Tafsir::factory()->create(['resource_id' => $this->tafsir->id, 'verse_id' => $verseId, 'text' => '<p>Tafsir text</p>']);
        }
        ChapterInfo::factory()->create(['resource_id' => QuranResource::factory()->chapterInfo('ar')->create()->id, 'chapter_id' => 1, 'text' => '<p>عن السورة</p>', 'language_iso' => 'ar']);

        $this->reciter = QuranResource::factory()->chapterReciter()->asDefault()->create(['name' => 'Mishary Alafasy', 'slug' => 'alafasy']);
        ChapterAudioFile::factory()->withTimestamps()->create(['resource_id' => $this->reciter->id, 'chapter_id' => 1]);
    }

    public function test_every_read_route_returns_the_success_envelope(): void
    {
        $t = $this->translation->id;
        $r = $this->reciter->id;
        $urls = [
            '/api/v1/quran/chapters', '/api/v1/quran/chapters/1', '/api/v1/quran/chapters/1/info',
            '/api/v1/quran/verses/by_chapter/1', '/api/v1/quran/verses/by_page/1', '/api/v1/quran/verses/by_juz/1',
            '/api/v1/quran/verses/by_range?from=1:1&to=1:3', '/api/v1/quran/verses/by_key/1:1', '/api/v1/quran/verses/random',
            '/api/v1/quran/juzs', '/api/v1/quran/juzs/1',
            '/api/v1/quran/quran/verses/uthmani?chapter_number=1', "/api/v1/quran/quran/translations/{$t}?chapter_number=1&foot_notes=true",
            "/api/v1/quran/quran/tafsirs/{$this->tafsir->id}?chapter_number=1",
            '/api/v1/quran/resources/translations', '/api/v1/quran/resources/chapter_reciters', "/api/v1/quran/resources/translations/{$t}/info",
            "/api/v1/quran/chapter_recitations/{$r}", "/api/v1/quran/chapter_recitations/{$r}/1?segments=true",
            "/api/v1/quran/audio/reciters/{$r}/timestamp?verse_key=1:1",
            '/api/v1/quran/foot_notes/77', '/api/v1/quran/search?query=name&mode=advanced',
        ];

        foreach ($urls as $url) {
            $response = $this->getJson($url);
            $this->assertSame(200, $response->status(), $url.' => '.$response->getContent());
            $response->assertJsonPath('success', true);
        }
    }

    public function test_verse_payload_carries_words_translations_and_tafsirs(): void
    {
        $response = $this->getJson("/api/v1/quran/verses/by_key/1:1?words=true&translations={$this->translation->id}&tafsirs={$this->tafsir->id}")->assertOk();

        $verse = $response->json('data.verse');
        $this->assertSame('1:1', $verse['verse_key']);
        $this->assertNotEmpty($verse['words']);
        $this->assertSame($this->translation->id, $verse['translations'][0]['resource_id']);
        $this->assertNotEmpty($verse['tafsirs']);
    }

    public function test_not_found_and_validation(): void
    {
        $this->getJson('/api/v1/quran/chapters/115')->assertNotFound();
        $this->getJson('/api/v1/quran/verses/by_key/9:99')->assertNotFound();
        $this->getJson('/api/v1/quran/verses/by_juz/31')->assertNotFound();
        $this->getJson('/api/v1/quran/foot_notes/999999')->assertNotFound();
        $this->getJson('/api/v1/quran/verses/by_chapter/1?per_page=51')->assertUnprocessable();
        $this->getJson('/api/v1/quran/verses/by_chapter/1?fields=bogus')->assertUnprocessable();
        $this->getJson('/api/v1/quran/verses/by_chapter/1?word_fields=nope')->assertUnprocessable();
        $this->getJson('/api/v1/quran/verses/by_range')->assertUnprocessable();
        $this->getJson('/api/v1/quran/search?query=x&mode=weird')->assertUnprocessable();
        $this->getJson('/api/v1/quran/quran/verses/klingon')->assertUnprocessable();
    }

    public function test_requires_api_client_and_quran_scope(): void
    {
        $this->withoutApiClient()->getJson('/api/v1/quran/chapters')->assertUnauthorized();
    }

    public function test_rejects_client_without_quran_scope(): void
    {
        $this->withHeaders($this->apiClientHeaders(['hadith']))->getJson('/api/v1/quran/chapters')->assertForbidden();
    }

    public function test_verse_list_query_budget(): void
    {
        $url = "/api/v1/quran/verses/by_chapter/1?per_page=50&words=true&translations={$this->translation->id},{$this->second->id}";
        $this->getJson($url); // warm the catalogue and language caches
        DB::enableQueryLog();
        DB::flushQueryLog();

        $this->getJson($url)->assertOk()->assertJsonCount(7, 'data.verses');

        $quranQueries = array_filter(DB::getQueryLog(), static fn (array $q): bool => str_contains($q['query'], 'quran_'));
        $this->assertLessThanOrEqual(6, count($quranQueries), implode("\n", array_column($quranQueries, 'query')));
    }
}
