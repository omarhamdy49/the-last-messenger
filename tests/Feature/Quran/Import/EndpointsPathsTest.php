<?php

declare(strict_types=1);

namespace Tests\Feature\Quran\Import;

use App\Domain\Quran\Enums\ResourceKind;
use App\Domain\Quran\Enums\Script;
use App\Domain\Quran\Enums\SectionType;
use App\Domain\Quran\Enums\SyncResourceGroup;
use App\Domain\Quran\Import\QuranFoundation\Endpoints;
use Closure;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Feature\Quran\Import\Concerns\FakesQuranFoundation;
use Tests\TestCase;

final class EndpointsPathsTest extends TestCase
{
    use FakesQuranFoundation;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpQuranFoundation();
        Http::fake(['apis.test/*' => Http::response([])]);
    }

    /**
     * @return array<string, array{0: Closure(Endpoints): mixed, 1: string}>
     */
    public static function calls(): array
    {
        return [
            'chapters' => [fn (Endpoints $e) => $e->chapters('fr'), 'content/api/v4/chapters?language=fr'],
            'rub el hizbs' => [fn (Endpoints $e) => $e->sections(SectionType::RUB_EL_HIZB), 'content/api/v4/rub_el_hizbs'],
            'script' => [fn (Endpoints $e) => $e->scriptVerses(Script::UTHMANI_TAJWEED, ['chapter_number' => 2]), 'content/api/v4/quran/verses/uthmani_tajweed?chapter_number=2'],
            'catalogue' => [fn (Endpoints $e) => $e->catalogue(ResourceKind::CHAPTER_RECITER, 'ar'), 'content/api/v4/resources/chapter_reciters?language=ar'],
            'info' => [fn (Endpoints $e) => $e->resourceInfo(ResourceKind::TAFSIR, 169), 'content/api/v4/resources/tafsirs/169/info'],
            'chapter recitation' => [fn (Endpoints $e) => $e->chapterRecitation(7, 1, true), 'content/api/v4/chapter_recitations/7/1?segments=true'],
            'snapshot' => [fn (Endpoints $e) => $e->snapshot(SyncResourceGroup::WORD_BY_WORD_TRANSLITERATIONS, 60), 'content/api/v4/resources/snapshots/word_by_word_transliterations/60'],
            'follow' => [fn (Endpoints $e) => $e->followRelative('/api/v4/resources/sync?cursor=abc'), 'content/api/v4/resources/sync?cursor=abc'],
            'hadith refs' => [fn (Endpoints $e) => $e->hadithReferences('2:255', 'ar'), 'content/api/v4/hadiths/2:255/references?language=ar'],
            'reflect' => [fn (Endpoints $e) => $e->reflectFeed(['limit' => 1]), 'quran-reflect/v1/posts/feed?limit=1'],
        ];
    }

    /**
     * @param  Closure(Endpoints): mixed  $call
     */
    #[DataProvider('calls')]
    public function test_endpoint_hits_the_documented_qf_path(Closure $call, string $expectedPath): void
    {
        $call(app(Endpoints::class));

        Http::assertSent(fn (Request $r): bool => urldecode($r->url()) === 'https://apis.test/'.$expectedPath);
    }
}
