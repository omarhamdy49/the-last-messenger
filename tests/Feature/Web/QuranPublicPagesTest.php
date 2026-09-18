<?php

declare(strict_types=1);

namespace Tests\Feature\Web;

use App\Domain\Quran\Models\Chapter;
use App\Domain\Quran\Models\ChapterAudioFile;
use App\Domain\Quran\Models\ChapterInfo;
use App\Domain\Quran\Models\QuranResource;
use App\Domain\Quran\Models\Tafsir;
use App\Domain\Quran\Models\Translation;
use App\Domain\Quran\Models\Verse;
use App\Domain\Quran\Models\Word;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * US5: every public Quran page renders from local data (factories) in ar and en.
 */
final class QuranPublicPagesTest extends TestCase
{
    use RefreshDatabase;

    private QuranResource $translation;

    private QuranResource $tafsir;

    private QuranResource $reciter;

    protected function setUp(): void
    {
        parent::setUp();

        Chapter::factory()->number(1)->withVerses(7)->create();
        Chapter::factory()->number(2)->withVerses(3)->create();
        foreach (Verse::query()->where('chapter_id', 1)->pluck('id') as $verseId) {
            Word::factory()->create(['verse_id' => $verseId]);
        }
        DB::table('quran_sections')->insert(['type' => 'juz', 'number' => 1, 'first_verse_id' => 1, 'last_verse_id' => 10, 'verses_count' => 10, 'verse_mapping' => json_encode(['1' => '1-7', '2' => '1-3'])]);
        DB::table('quran_verses')->update(['juz_number' => 1, 'hizb_number' => 1, 'rub_el_hizb_number' => 1, 'manzil_number' => 1, 'ruku_number' => 1]);

        $this->translation = QuranResource::factory()->translation('en')->asDefault()->create(['name' => 'Saheeh International']);
        $this->tafsir = QuranResource::factory()->tafsir('ar')->create(['name' => 'Ibn Kathir']);
        foreach (Verse::query()->pluck('id') as $verseId) {
            Translation::factory()->withFootnote('1', 'note')->create(['resource_id' => $this->translation->id, 'verse_id' => $verseId, 'text' => 'In the name<sup foot_note="1">1</sup> of God']);
            Tafsir::factory()->create(['resource_id' => $this->tafsir->id, 'verse_id' => $verseId, 'text' => '<p>Tafsir text</p>']);
        }
        ChapterInfo::factory()->create(['resource_id' => QuranResource::factory()->chapterInfo('ar')->create()->id, 'chapter_id' => 1, 'text' => '<p>عن السورة</p>', 'language_iso' => 'ar']);

        $this->reciter = QuranResource::factory()->chapterReciter()->asDefault()->create(['name' => 'Mishary Alafasy', 'slug' => 'alafasy']);
        ChapterAudioFile::factory()->withTimestamps()->create(['resource_id' => $this->reciter->id, 'chapter_id' => 1]);
    }

    public function test_index_and_surahs_json(): void
    {
        foreach (['ar', 'en'] as $locale) {
            app()->setLocale($locale);
            $this->withSession(['locale' => $locale])->get(route('public.quran'))->assertOk()->assertSee('الفاتحة', false);
        }
        $this->getJson(route('public.quran.surahs.json'))->assertOk()->assertJsonPath('surahs.0.number', 1);
    }

    public function test_surah_page_renders_verses_translation_reciters_and_info(): void
    {
        $response = $this->get(route('public.quran.surah', 1))->assertOk();

        $verses = $response->viewData('verses');
        $this->assertCount(7, $verses);
        $this->assertSame('In the name of God', $verses[0]['translation']);
        $this->assertSame($this->reciter->id, $response->viewData('reciters')[0]['id']);
        $this->assertNotNull($response->viewData('reciters')[0]['surahAudioUrl']);
        $this->assertNotNull($response->viewData('chapterInfo'));
        $this->get(route('public.quran.surah.short', 1))->assertOk();
        $this->get(route('public.quran.surah', 99))->assertNotFound();
    }

    public function test_lazy_json_endpoints(): void
    {
        $this->getJson(route('public.quran.surah.translation', [1, $this->translation->id]))->assertOk()->assertJsonPath('1', 'In the name of God');
        $this->getJson(route('public.quran.verse.tafsir', [1, $this->tafsir->id]))->assertOk()->assertJsonPath('text', 'Tafsir text');
        $this->getJson(route('public.quran.surah.timings', [1, $this->reciter->id]))->assertOk()->assertJsonPath('ayahs.0.0', 1);
    }

    public function test_sections_page_verse_random_and_redirects(): void
    {
        foreach (['juz', 'hizb', 'rub', 'manzil', 'ruku'] as $section) {
            $this->get(route('public.quran.'.$section, 1))->assertOk();
        }
        $this->get(route('public.quran.juz', 31))->assertNotFound();
        $this->get(route('public.quran.page', 1))->assertOk();

        $this->get(route('public.quran.verse', '1:5'))->assertRedirect(route('public.quran.surah', 1).'#v5');
        $this->get('/quran/verse/1:99')->assertNotFound();
        $this->get(route('public.quran.random'))->assertRedirect();
        $this->get('/quran/live')->assertRedirect('/media/live')->assertStatus(301);
        $this->get('/quran/asbab-al-nuzul')->assertNotFound();
    }

    public function test_word_by_word_tajweed_and_resource_pages(): void
    {
        DB::table('quran_verses')->where('id', 1)->update(['text_uthmani_tajweed' => '<tajweed class="ham_wasl" onclick="x">ٱ</tajweed>للَّهِ']);

        $wbw = $this->get(route('public.quran.surah', [1, 'wbw' => 1]))->assertOk();
        $this->assertCount(1, $wbw->viewData('verses')[0]['words']);
        $this->assertTrue($wbw->viewData('wordByWord'));

        $tajweed = $this->get(route('public.quran.surah', [1, 'script' => 'uthmani_tajweed']))->assertOk();
        $this->assertSame('<tajweed class="ham_wasl">ٱ</tajweed>للَّهِ', $tajweed->viewData('verses')[0]['tajweed']);

        $this->get(route('public.quran.translation', $this->translation->id))->assertOk()->assertSee('Saheeh International');
        $this->get(route('public.quran.tafsir', $this->tafsir->id))->assertOk();
        $this->getJson(route('public.quran.tafsir.chapter', [$this->tafsir->id, 1]))->assertOk()->assertJsonPath('1', 'Tafsir text');
        $this->get(route('public.quran.translation', $this->tafsir->id))->assertNotFound();
    }

    public function test_arabic_search_ignores_tashkeel(): void
    {
        DB::table('quran_verses')->where('id', 1)->update(['text_imlaei_simple' => 'بسم الله الرحمن الرحيم']);

        foreach (['الرحمن', 'الرَّحْمَٰن'] as $query) {
            $this->getJson(route('public.quran.search.quick', ['q' => $query]))->assertOk()->assertJsonPath('result.verses.0.verse_key', '1:1');
        }
    }

    public function test_reciters_and_search(): void
    {
        $this->get(route('public.quran.reciters'))->assertOk()->assertSee('Mishary Alafasy');
        $this->get(route('public.quran.reciter', 'alafasy'))->assertOk();
        $this->get(route('public.quran.reciter', 'nobody'))->assertNotFound();

        $this->getJson(route('public.quran.search.quick', ['q' => '1:2']))->assertOk()->assertJsonPath('result.navigation.0.key', '1:2');
        $this->get(route('public.quran.search', ['q' => 'name']))->assertOk()->assertSee('<em>name</em>', false);
    }

    /**
     * The reader's whole component lives in one `x-data="…"` attribute, so a single
     * double quote anywhere inside it — in a comment, even — ends the attribute early
     * and every binding on the page dies with a bare "Unexpected token" in the console.
     * Assert the attribute still reaches its last method.
     */
    public function test_the_reader_component_attribute_is_not_cut_short(): void
    {
        $html = $this->get(route('public.quran.surah', 1))->assertOk()->getContent();

        $this->assertSame(1, preg_match('/x-data="(\{\s*surah:.*?)"\s/s', (string) $html, $m), 'reader x-data not found');
        foreach (['boot()', 'tapWordAt(', 'wordAt(', 'copyVerse('] as $method) {
            $this->assertStringContainsString($method, $m[1], "x-data is truncated before {$method}");
        }
    }

    /**
     * The reader plays one gapless recording and finds an ayah inside it with the
     * chapter's timings, so it only offers reciters that have a whole-chapter file:
     * a verse-by-verse recitation, and a chapter reciter missing this chapter, are
     * both left out rather than played as a chain of per-ayah files.
     */
    public function test_the_reader_only_offers_reciters_with_this_chapters_recording(): void
    {
        QuranResource::factory()->recitation()->create(['name' => 'Verse by verse']);
        $otherChapter = QuranResource::factory()->chapterReciter()->create(['name' => 'Only surah 2', 'slug' => 'only-2']);
        ChapterAudioFile::factory()->create(['resource_id' => $otherChapter->id, 'chapter_id' => 2]);

        $reciters = $this->get(route('public.quran.surah', 1))->assertOk()->viewData('reciters');

        $this->assertSame([$this->reciter->id], array_column($reciters, 'id'));
        foreach ($reciters as $reciter) {
            $this->assertNotNull($reciter['surahAudioUrl']);
        }
    }
}
