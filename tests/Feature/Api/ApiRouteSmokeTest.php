<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Domain\Blog\Models\Category;
use App\Domain\Hadith\Models\Hadith;
use App\Domain\Hadith\Models\HadithBook;
use App\Domain\Hadith\Models\HadithChapter;
use App\Domain\Hadith\Models\HadithCollection;
use App\Domain\Hadith\Models\HadithNarrator;
use App\Domain\Hadith\Models\HadithTheme;
use App\Domain\Hadith\Models\HadithTranslation;
use App\Domain\Media\Models\MediaEpisode;
use App\Domain\Media\Models\MediaEpisodeTranslation;
use App\Domain\Quran\Models\Surah;
use App\Domain\Quran\Models\Verse;
use App\Domain\Sahaba\Models\Sahabi;
use App\Domain\Sahaba\Models\SahabiBattle;
use App\Domain\Sahaba\Models\SahabiCategory;
use App\Domain\Sahaba\Models\SahabiTranslation;
use App\Domain\Seerah\Models\SeerahEventType;
use App\Domain\Shared\Models\SavedFilter;
use App\Models\Article;
use App\Models\User;
use Database\Factories\MediaProgramFactory;
use Database\Factories\SeerahEventFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route as LaravelRoute;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Testing\TestResponse;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use Throwable;

class ApiRouteSmokeTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    /** @var array<string, string|int> */
    private array $routeParameters;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        $this->seedRouteParameters();
    }

    public function test_every_registered_api_v1_route_boots_without_a_server_error(): void
    {
        $routes = collect(Route::getRoutes()->getRoutes())
            ->filter(fn (LaravelRoute $route): bool => str_starts_with($route->uri(), 'api/v1/'))
            ->values();

        $resolvedRoutes = [];
        $skipped = [];
        foreach ($routes as $route) {
            $missing = [];
            $uri = preg_replace_callback(
                '/\{([^}?]+)\??\}/',
                function (array $match) use ($route, &$missing): string {
                    $key = $route->getName().':'.$match[1];
                    if (! array_key_exists($key, $this->routeParameters)) {
                        $missing[] = $match[1];

                        return $match[0];
                    }

                    return rawurlencode((string) $this->routeParameters[$key]);
                },
                $route->uri()
            );

            $method = collect($route->methods())->first(fn (string $method): bool => $method !== 'HEAD');
            if ($missing !== []) {
                $skipped[] = "{$method} {$route->uri()}: no meaningful value for ".implode(', ', $missing);

                continue;
            }

            $resolvedRoutes[] = [$route, $method, '/'.$uri];
        }

        $failures = [];
        $requestCount = 0;

        foreach ($resolvedRoutes as [$route, $method, $uri]) {
            if (! $this->requiresSanctum($route)) {
                continue;
            }

            $response = $this->requestRoute($method, $uri, $this->payloadFor($route));
            $requestCount++;

            if ($response->getStatusCode() !== 401) {
                $failures[] = $this->failure($method, $route->uri(), $response, 'unauthenticated request must return 401');
            }
        }

        foreach ([false, true] as $authenticated) {
            foreach ($resolvedRoutes as [$route, $method, $uri]) {
                if ($this->requiresSanctum($route) !== $authenticated) {
                    continue;
                }

                if ($authenticated) {
                    Sanctum::actingAs($this->user);
                }

                try {
                    $response = $this->requestRoute($method, $uri, $this->payloadFor($route));
                    $requestCount++;

                    if ($response->getStatusCode() >= 500) {
                        $failures[] = $this->failure($method, $route->uri(), $response);
                    }
                } catch (Throwable $exception) {
                    $requestCount++;
                    $failures[] = [
                        'method' => $method,
                        'uri' => $route->uri(),
                        'status' => 'threw',
                        'cause' => $exception::class.': '.$exception->getMessage(),
                    ];
                }
            }
        }

        $this->writeReport($routes->count(), count($resolvedRoutes), $requestCount, $skipped, $failures);

        $issues = array_merge(
            array_map(fn (string $skip): string => 'SKIPPED '.$skip, $skipped),
            array_map(
                fn (array $failure): string => "{$failure['method']} {$failure['uri']} returned {$failure['status']}: {$failure['cause']}",
                $failures
            )
        );

        $this->assertSame([], $issues, implode(PHP_EOL, $issues));
    }

    private function seedRouteParameters(): void
    {
        $this->user = User::factory()->create([
            'email' => 'route-smoke@example.com',
            'password' => Hash::make('password123'),
        ]);

        $article = Article::factory()->published()->create([
            'writer_id' => $this->user->id,
            'slug' => 'route-smoke-article',
        ]);

        $category = Category::query()->create([
            'slug' => 'route-smoke-category',
            'type' => 'blog',
            'status' => 'active',
            'display_order' => 1,
        ]);
        $category->syncTranslations([
            'name' => ['ar' => 'تصنيف اختبار', 'en' => 'Smoke category'],
            'description' => ['ar' => 'وصف', 'en' => 'Description'],
        ]);

        $collection = HadithCollection::query()->create([
            'name_ar' => 'مجموعة اختبار',
            'name_en' => 'Smoke Collection',
            'slug_ar' => 'مجموعة-اختبار',
            'slug_en' => 'smoke-collection',
            'abbreviation' => 'SMK',
        ]);
        $book = HadithBook::query()->create([
            'collection_id' => $collection->id,
            'book_number' => '1',
            'title_ar' => 'كتاب الاختبار',
            'title_en' => 'Smoke Book',
            'slug_ar' => 'كتاب-الاختبار',
            'slug_en' => 'smoke-book',
        ]);
        $chapter = HadithChapter::query()->create([
            'book_id' => $book->id,
            'chapter_number' => 1,
            'name' => json_encode(['ar' => 'باب الاختبار', 'en' => 'Smoke Chapter'], JSON_THROW_ON_ERROR),
            'display_order' => 1,
        ]);
        $chapter->syncTranslations([
            'name' => ['ar' => 'باب الاختبار', 'en' => 'Smoke Chapter'],
        ]);
        $hadith = Hadith::query()->create([
            'book_id' => $book->id,
            'chapter_id' => $chapter->id,
            'hadith_number' => '1',
            'grading_status' => Hadith::GRADING_SAHIH,
        ]);
        HadithTranslation::query()->create([
            'hadith_id' => $hadith->id,
            'language_code' => 'en',
            'text' => 'Smoke test narration',
        ]);
        $narrator = HadithNarrator::query()->create([
            'name_ar' => 'راوي الاختبار',
            'name_en' => 'Smoke Narrator',
        ]);
        $theme = HadithTheme::query()->create([
            'theme_ar' => 'موضوع الاختبار',
            'theme_en' => 'Smoke Theme',
        ]);
        $hadith->themes()->attach($theme);

        $surah = Surah::query()->create([
            'revelation_place' => 'makkah',
            'revelation_order' => 1,
            'bismillah_pre' => true,
            'name_ar' => 'الفاتحة',
            'name_en' => 'Al-Fatihah',
            'transliteration' => 'Al-Fatihah',
            'verses_count' => 1,
            'pages' => [1],
        ]);
        Verse::query()->create([
            'surah_id' => $surah->id,
            'verse_number' => 1,
            'verse_key' => '1:1',
            'juz_number' => 1,
            'hizb_number' => 1,
            'rub_el_hizb_number' => 1,
            'ruku_number' => 1,
            'manzil_number' => 1,
            'sajdah_number' => 1,
            'page_number' => 1,
        ]);

        $sahabi = Sahabi::query()->create([
            'slug' => 'route-smoke-sahabi',
            'gender' => 'male',
            'person_type' => 'sahabi',
            'is_sahabi' => true,
            'is_verified' => true,
            'father_id' => null,
            'mother_id' => null,
            'tribe' => 'Quraysh',
            'roles' => ['narrator'],
            'reported_hadith_count' => 1,
            'views_count' => 0,
            'is_featured' => true,
            'importance_rank' => 1,
        ]);
        SahabiTranslation::query()->create([
            'sahabi_id' => $sahabi->id,
            'locale' => 'en',
            'name' => 'Smoke Companion',
            'full_name' => 'Smoke Companion',
            'biography' => 'Smoke biography',
            'is_ready' => true,
        ]);
        $sahabiCategory = SahabiCategory::query()->firstOrCreate(
            ['key' => 'badr'],
            ['rank' => 9]
        );
        $sahabi->categories()->syncWithoutDetaching($sahabiCategory);
        SahabiBattle::query()->create([
            'sahabi_id' => $sahabi->id,
            'battle_name' => 'Badr',
        ]);

        $eventType = SeerahEventType::query()->firstOrCreate(
            ['slug' => 'battle'],
            ['is_active' => true, 'display_order' => 1]
        );
        $eventType->syncTranslations(['name' => ['ar' => 'غزوة', 'en' => 'Battle']]);
        $event = SeerahEventFactory::new()->create([
            'slug' => 'route-smoke-event',
            'seerah_event_type_id' => $eventType->id,
            'hijri_year' => 1,
            'gregorian_year' => 622,
            'status' => 'published',
            'featured_image' => 'https://example.com/smoke.jpg',
        ]);

        $program = MediaProgramFactory::new()->seerah()->create([
            'slug' => 'route-smoke-program',
            'is_published' => true,
        ]);
        $episode = MediaEpisode::query()->create([
            'program_id' => $program->id,
            'episode_number' => 1,
            'season_number' => 1,
            'slug' => 'route-smoke-episode',
            'video_url' => 'https://www.youtube.com/embed/smoke123456',
            'duration_seconds' => 60,
            'is_published' => true,
        ]);
        MediaEpisodeTranslation::query()->create([
            'media_episode_id' => $episode->id,
            'locale' => 'en',
            'title' => 'Smoke Episode',
            'description' => 'Smoke episode description',
            'is_ready' => true,
        ]);

        $filters = collect(range(1, 4))->map(fn (int $number): SavedFilter => SavedFilter::query()->create([
            'user_id' => $this->user->id,
            'name' => "Smoke filter {$number}",
            'module' => 'hadith',
            'filters' => ['grading_status' => 'sahih'],
            'is_public' => true,
            'is_default' => false,
        ]));

        $this->routeParameters = [
            'v1.articles.show:slug' => $article->slug,
            'v1.categories.show:slug' => $category->slug,
            'v1.engagement.content:type' => 'Hadith',
            'v1.engagement.content:id' => $hadith->id,
            'v1.engagement.user:id' => $this->user->id,
            'v1.filters.preferences.get:module' => 'hadith',
            'v1.filters.preferences.save:module' => 'hadith',
            'v1.filters.update:id' => $filters[0]->id,
            'v1.filters.destroy:id' => $filters[1]->id,
            'v1.filters.apply:id' => $filters[2]->id,
            'v1.filters.set-default:id' => $filters[3]->id,
            'v1.hadith.by-book-number:bookId' => $book->id,
            'v1.hadith.by-book-number:number' => $hadith->hadith_number,
            'v1.hadith.books.show:slug' => $book->slug_en,
            'v1.hadith.by-theme:theme' => $theme->theme_en,
            'v1.hadith.chapters.show:id' => $chapter->id,
            'v1.hadith.collections.show:id' => $collection->id,
            'v1.hadith.collections.books:id' => $collection->id,
            'v1.hadith.narrators.show:id' => $narrator->id,
            'v1.hadith.narrators.hadiths:id' => $narrator->id,
            'v1.hadith.show:id' => $hadith->id,
            'v1.media.programs.by-category:categoryId' => 'seerah',
            'v1.media.programs.show:slug' => $program->slug,
            'v1.media.programs.episodes:slug' => $program->slug,
            'v1.media.programs.episode:slug' => $program->slug,
            'v1.media.programs.episode:episodeNumber' => $episode->episode_number,
            'v1.quran.juz:juz' => 1,
            'v1.quran.page:page' => 1,
            'v1.quran.by-revelation:place' => $surah->revelation_place,
            'v1.quran.surah:number' => $surah->revelation_order,
            'v1.quran.verses:surahNumber' => $surah->revelation_order,
            'v1.quran.verse:surahNumber' => $surah->revelation_order,
            'v1.quran.verse:verseNumber' => 1,
            'v1.sahaba.by-battle:battle' => 'Badr',
            'v1.sahaba.by-status:status' => $sahabiCategory->key,
            'v1.sahaba.by-tribe:tribe' => $sahabi->tribe,
            'v1.sahaba.show:slug' => $sahabi->slug,
            'v1.search.module:module' => 'hadith',
            'v1.seerah.by-gregorian-year:year' => $event->gregorian_year,
            'v1.seerah.by-hijri-year:year' => $event->hijri_year,
            'v1.seerah.by-type:type' => $eventType->slug,
            'v1.seerah.show:slug' => $event->slug,
        ];
    }

    /** @return array<string, mixed> */
    private function payloadFor(LaravelRoute $route): array
    {
        return match ($route->getName()) {
            'v1.auth.login' => [
                'email' => $this->user->email,
                'password' => 'password123',
            ],
            'v1.auth.register' => [
                'name' => 'Route Smoke Registration',
                'email' => 'route-smoke-registration@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ],
            'v1.filters.store', 'v1.filters.update' => [
                'name' => 'Route smoke filter',
                'module' => 'hadith',
                'filters' => ['grading_status' => 'sahih'],
            ],
            'v1.filters.apply' => [
                'query' => 'smoke',
                'per_page' => 1,
            ],
            'v1.filters.preferences.save' => [
                'default_filters' => ['grading_status' => 'sahih'],
                'default_sort' => 'relevance',
                'default_order' => 'desc',
                'default_per_page' => 10,
            ],
            'v1.interactions.store' => [
                'interactable_type' => 'Hadith',
                'interactable_id' => $this->routeParameters['v1.hadith.show:id'],
                'interaction_type' => 'view',
            ],
            'v1.media.progress.store' => [
                'episode_id' => MediaEpisode::query()->where('episode_number', 1)->value('id'),
                'seconds' => 1,
            ],
            'v1.search.index', 'v1.search.module' => [
                'query' => 'smoke',
                'per_page' => 1,
            ],
            default => [],
        };
    }

    private function requiresSanctum(LaravelRoute $route): bool
    {
        return collect($route->gatherMiddleware())
            ->contains(fn (string $middleware): bool => $middleware === 'auth:sanctum');
    }

    /** @param array<string, mixed> $payload */
    private function requestRoute(string $method, string $uri, array $payload): TestResponse
    {
        return $this->json($method, $uri, $payload, ['Accept-Language' => 'en']);
    }

    /**
     * @return array{method: string, uri: string, status: int, cause: string}
     */
    private function failure(
        string $method,
        string $uri,
        TestResponse $response,
        ?string $fallback = null
    ): array {
        $exception = $response->exceptions->last();
        $cause = $exception
            ? $exception::class.': '.$exception->getMessage()
            : $response->json('message')
                ?? $response->json('error.message')
                ?? $fallback
                ?? trim((string) $response->getContent());
        if (! is_string($cause)) {
            $cause = json_encode($cause, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: 'No exception message was exposed';
        }

        return [
            'method' => $method,
            'uri' => $uri,
            'status' => $response->getStatusCode(),
            'cause' => $cause !== '' ? $cause : 'No exception message was exposed',
        ];
    }

    /**
     * @param  array<int, string>  $skipped
     * @param  array<int, array{method: string, uri: string, status: int|string, cause: string}>  $failures
     */
    private function writeReport(
        int $routeCount,
        int $exercisedCount,
        int $requestCount,
        array $skipped,
        array $failures
    ): void {
        fwrite(
            STDOUT,
            PHP_EOL."API route smoke: {$routeCount} routes, {$exercisedCount} exercised, "
            .count($skipped)." skipped, {$requestCount} requests".PHP_EOL
        );

        foreach ($skipped as $skip) {
            fwrite(STDOUT, "SKIP | {$skip}".PHP_EOL);
        }

        foreach ($failures as $failure) {
            fwrite(
                STDOUT,
                "{$failure['method']} | {$failure['uri']} | {$failure['status']} | {$failure['cause']}".PHP_EOL
            );
        }
    }
}
