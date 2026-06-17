<?php

declare(strict_types=1);

namespace Tests\Feature\Quran;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * "Ask the Quran" inquiry search endpoint. Upstream quran.com calls are faked so
 * the suite never touches the network.
 */
final class QuranAskTest extends TestCase
{
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
        Http::fake([
            'api.quran.com/*' => Http::response([
                'search' => [
                    'query' => 'mercy',
                    'total_results' => 1,
                    'results' => [[
                        'verse_key' => '1:1',
                        'text' => 'بِسْمِ ٱللَّهِ',
                        'translations' => [['text' => 'In the name of <em>mercy</em>', 'name' => 'Sahih International']],
                    ]],
                ],
            ], 200),
        ]);

        $this->getJson(route('public.quran.ask.search', ['q' => 'mercy']))
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('results.0.verseKey', '1:1')
            ->assertJsonPath('results.0.surah', 1)
            ->assertJsonPath('results.0.url', url('/quran/1').'#v1')
            ->assertJsonPath('results.0.translation', 'In the name of mercy') // <em> stripped
            ->assertJsonPath('translationName', 'Sahih International');
    }

    public function test_arabic_definite_article_fallback(): void
    {
        app()->setLocale('ar');
        // First call (الصبر) returns nothing; the service retries without the
        // definite article (صبر), which matches — and echoes the original query.
        Http::fake([
            'api.quran.com/*' => Http::sequence()
                ->push(['search' => ['total_results' => 0, 'results' => []]], 200)
                ->push(['search' => ['total_results' => 1, 'results' => [[
                    'verse_key' => '2:153',
                    'text' => 'وَٱصْبِرُوا',
                ]]]], 200),
        ]);

        $this->getJson(route('public.quran.ask.search', ['q' => 'الصبر']))
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('query', 'الصبر')
            ->assertJsonPath('results.0.verseKey', '2:153')
            ->assertJsonPath('results.0.translation', null); // Arabic locale: no translation
    }

    public function test_upstream_failure_returns_502(): void
    {
        Http::fake(['api.quran.com/*' => Http::response('', 500)]);

        $this->getJson(route('public.quran.ask.search', ['q' => 'mercy']))
            ->assertStatus(502)
            ->assertJsonStructure(['message']);
    }
}
