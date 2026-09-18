<?php

declare(strict_types=1);

namespace Tests\Feature\Quran;

use App\Domain\Quran\Models\Chapter;
use App\Domain\Quran\Models\QuranResource;
use App\Domain\Quran\Models\Translation;
use App\Domain\Quran\Models\Verse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * "Ask the Quran" inquiry search endpoint. Since 006 it searches our own tables,
 * so there is no upstream call to fake.
 */
final class QuranAskTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_short_query_is_rejected_with_422(): void
    {
        $this->getJson(route('public.quran.ask.search', ['q' => 'a']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('q');
    }

    public function test_inquiry_returns_mapped_verses(): void
    {
        app()->setLocale('en');
        Chapter::factory()->number(1)->withVerses(1)->create();
        $verse = Verse::query()->where('verse_key', '1:1')->firstOrFail();
        $verse->update(['text_uthmani' => 'بِسْمِ ٱللَّهِ', 'text_imlaei_simple' => 'بسم الله']);
        $edition = QuranResource::factory()->translation('en')->asDefault()->create(['name' => 'Sahih International']);
        Translation::factory()->create(['resource_id' => $edition->id, 'verse_id' => $verse->id, 'text' => 'In the name of mercy']);

        $this->withSession(['locale' => 'en'])->getJson(route('public.quran.ask.search', ['q' => 'mercy']))
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('results.0.verseKey', '1:1')
            ->assertJsonPath('results.0.surah', 1)
            ->assertJsonPath('results.0.url', url('/quran/1').'#v1')
            ->assertJsonPath('results.0.translation', 'In the name of mercy')
            ->assertJsonPath('translationName', 'Sahih International');
    }

    public function test_arabic_definite_article_fallback(): void
    {
        app()->setLocale('ar');
        // "الصبر" matches nothing; the service retries without the definite article
        // ("صبر"), which matches — and echoes the original query back.
        Chapter::factory()->number(2)->withVerses(153)->create();
        Verse::query()->where('verse_key', '2:153')->update([
            'text_uthmani' => 'وَٱصْبِرُوا',
            'text_imlaei_simple' => 'واصبروا صبر',
        ]);

        $this->withSession(['locale' => 'ar'])->getJson(route('public.quran.ask.search', ['q' => 'الصبر']))
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('query', 'الصبر')
            ->assertJsonPath('results.0.verseKey', '2:153')
            ->assertJsonPath('results.0.translation', null); // Arabic locale: no translation
    }

    public function test_a_query_with_no_match_returns_an_empty_result(): void
    {
        app()->setLocale('en');
        Chapter::factory()->number(1)->withVerses(1)->create();

        $this->withSession(['locale' => 'en'])->getJson(route('public.quran.ask.search', ['q' => 'zzzznomatch']))
            ->assertOk()
            ->assertJsonPath('total', 0)
            ->assertJsonPath('results', []);
    }
}
