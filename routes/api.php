<?php

declare(strict_types=1);

use App\Http\Controllers\Api\ArticleController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\FilterController;
use App\Http\Controllers\Api\InteractionController;
use App\Http\Controllers\Api\MediaProgramController;
use App\Http\Controllers\Api\Quran\QuranApiController;
use App\Http\Controllers\Api\SahabiController;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\Api\SeerahEventController;
use App\Http\Controllers\LanguageController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::prefix('v1')->name('v1.')->group(function (): void {
    // Minimal public liveness response — the ONLY route without application
    // credentials. It exposes no database, dependency, host, build, or exception
    // details and is still rate limited.
    Route::get('/health', static function () {
        return response()->json([
            'status' => 'ok',
            'timestamp' => now()->toISOString(),
            'service' => 'The Last Messenger API',
            'version' => '1.0.0',
        ]);
    })->middleware('throttle:api')->name('health');

    // Every other route requires an API client (X-Api-Key + X-Api-Secret) and is
    // limited by that client's own budget (006). User identity (Sanctum) layers on top.
    Route::middleware(['api.client', 'throttle:api_client'])->group(function (): void {
        // Bootstrap routes: need an API client but no user token yet.
        Route::prefix('auth')->group(function (): void {
            Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:auth')->name('auth.register');
            Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:auth')->name('auth.login');

            Route::middleware(['auth:sanctum', 'abilities:api:access', 'throttle:api'])->group(function (): void {
                Route::post('/logout', [AuthController::class, 'logout'])->middleware('throttle:api_mutations')->name('auth.logout');
                Route::post('/logout-all', [AuthController::class, 'logoutAll'])->middleware('throttle:api_mutations')->name('auth.logout-all');
                Route::get('/user', [AuthController::class, 'user'])->name('auth.user');
            });
        });

        // Secure-by-default trust boundary: a future content route added here
        // automatically requires a valid Sanctum token and receives the global API
        // request budget. Tests enforce that only the bootstrap allowlist above may
        // live outside this group.
        Route::middleware(['auth:sanctum', 'abilities:api:access', 'throttle:api'])->group(function (): void {
            Route::prefix('categories')->group(function (): void {
                Route::get('/', [CategoryController::class, 'index'])->name('categories.index');
                Route::get('/{slug}', [CategoryController::class, 'show'])
                    ->where('slug', '[a-z0-9-]+')->name('categories.show');
            });

            Route::prefix('articles')->group(function (): void {
                Route::get('/', [ArticleController::class, 'index'])->name('articles.index');
                Route::get('/featured', [ArticleController::class, 'featured'])->name('articles.featured');
                Route::get('/popular', [ArticleController::class, 'popular'])->name('articles.popular');
                Route::get('/recent', [ArticleController::class, 'recent'])->name('articles.recent');
                Route::get('/search', [ArticleController::class, 'search'])
                    ->middleware('throttle:search')->name('articles.search');
                Route::get('/{slug}', [ArticleController::class, 'show'])
                    ->where('slug', '[a-z0-9-]+')->name('articles.show');
            });

            Route::prefix('media')->group(function (): void {
                Route::get('/programs', [MediaProgramController::class, 'index'])->name('media.programs.index');
                Route::get('/programs/popular', [MediaProgramController::class, 'popular'])->name('media.programs.popular');
                Route::get('/programs/recent', [MediaProgramController::class, 'recent'])->name('media.programs.recent');
                Route::get('/programs/search', [MediaProgramController::class, 'search'])
                    ->middleware('throttle:search')->name('media.programs.search');
                Route::get('/programs/category/{categoryId}', [MediaProgramController::class, 'byCategory'])
                    ->whereIn('categoryId', ['seerah', 'quran', 'hadith', 'kids', 'documentary'])
                    ->name('media.programs.by-category');
                Route::get('/programs/{slug}', [MediaProgramController::class, 'show'])
                    ->where('slug', '[a-z0-9-]+')->name('media.programs.show');
                Route::get('/programs/{slug}/episodes', [MediaProgramController::class, 'episodes'])
                    ->where('slug', '[a-z0-9-]+')->name('media.programs.episodes');
                Route::get('/programs/{slug}/episodes/{episodeNumber}', [MediaProgramController::class, 'episode'])
                    ->where('slug', '[a-z0-9-]+')->whereNumber('episodeNumber')->name('media.programs.episode');
                Route::post('/progress', [MediaProgramController::class, 'storeProgress'])->middleware('throttle:api_mutations')->name('media.progress.store');
                Route::get('/continue-watching', [MediaProgramController::class, 'continueWatching'])->name('media.continue-watching');
            });

            Route::prefix('seerah')->group(function (): void {
                Route::get('/', [SeerahEventController::class, 'index'])->name('seerah.index');
                Route::get('/search', [SeerahEventController::class, 'search'])
                    ->middleware('throttle:search')->name('seerah.search');
                Route::get('/map', [SeerahEventController::class, 'map'])->name('seerah.map');
                Route::get('/type/{type}', [SeerahEventController::class, 'byType'])
                    ->where('type', '[a-z0-9-]+')->name('seerah.by-type');
                Route::get('/hijri/{year}', [SeerahEventController::class, 'byHijriYear'])
                    ->where('year', '-?[0-9]+')->name('seerah.by-hijri-year');
                Route::get('/gregorian/{year}', [SeerahEventController::class, 'byGregorianYear'])
                    ->whereNumber('year')->name('seerah.by-gregorian-year');
                Route::get('/{slug}', [SeerahEventController::class, 'show'])
                    ->where('slug', '[a-z0-9-]+')->name('seerah.show');
            });

            Route::prefix('sahaba')->group(function (): void {
                Route::get('/search', [SahabiController::class, 'search'])
                    ->middleware('throttle:search')->name('sahaba.search');
                Route::get('/', [SahabiController::class, 'index'])->name('sahaba.index');
                Route::get('/{slug}/tree', [SahabiController::class, 'tree'])
                    ->where('slug', '[a-z0-9-]+')->name('sahaba.tree');
                Route::get('/{slug}', [SahabiController::class, 'show'])
                    ->where('slug', '[a-z0-9-]+')->name('sahaba.show');
            });
            Route::get('/roles', [SahabiController::class, 'roles'])->name('roles.index');

            // Quran (006): Quran Foundation Content API v4 shapes, served from local data.
            Route::prefix('quran')->name('quran.')->middleware('api.scope:quran')->controller(QuranApiController::class)->group(function (): void {
                Route::get('/chapters', 'chapters')->name('chapters.index');
                Route::get('/chapters/{id}', 'chapter')->whereNumber('id')->name('chapters.show');
                Route::get('/chapters/{id}/info', 'chapterInfo')->whereNumber('id')->name('chapters.info');

                Route::get('/verses/by_chapter/{chapter}', 'versesByChapter')->whereNumber('chapter')->name('verses.by-chapter');
                Route::get('/verses/by_page/{page}', 'versesByPage')->whereNumber('page')->name('verses.by-page');
                foreach (['juz', 'hizb', 'rub_el_hizb', 'manzil', 'ruku'] as $axis) {
                    Route::get("/verses/by_{$axis}/{number}", 'versesBySection')->whereNumber('number')->defaults('axis', $axis)
                        ->name('verses.by-'.str_replace('_', '-', $axis));
                }
                Route::get('/verses/by_range', 'versesByRange')->name('verses.by-range');
                Route::get('/verses/by_key/{key}', 'verseByKey')->where('key', '\d{1,3}:\d{1,3}')->name('verses.by-key');
                Route::get('/verses/random', 'randomVerse')->name('verses.random');

                foreach (['juz', 'hizb', 'rub_el_hizb', 'manzil', 'ruku'] as $axis) {
                    Route::get("/{$axis}s", 'sections')->defaults('axis', $axis)->name("sections.{$axis}.index");
                    Route::get("/{$axis}s/{number}", 'section')->whereNumber('number')->defaults('axis', $axis)->name("sections.{$axis}.show");
                }
                Route::get('/pages', 'pages')->name('pages.index');
                Route::get('/pages/lookup', 'pagesLookup')->name('pages.lookup');
                Route::get('/pages/{number}', 'page')->whereNumber('number')->name('pages.show');

                Route::get('/quran/verses/{script}', 'scriptVerses')->where('script', '[a-z0-9_]+')->name('scripts');
                Route::get('/quran/translations/{resource}', 'translationFull')->whereNumber('resource')->name('translations.full');
                Route::get('/quran/tafsirs/{resource}', 'tafsirFull')->whereNumber('resource')->name('tafsirs.full');
                Route::get('/quran/recitations/{resource}', 'recitationFiles')->whereNumber('resource')->name('recitations.files');

                $kinds = 'translations|tafsirs|languages|recitations|chapter_reciters|chapter_infos|verse_media|recitation_styles';
                Route::get('/resources/{kind}', 'resources')->where('kind', $kinds)->name('resources.index');
                Route::get('/resources/{kind}/{id}/info', 'resourceInfo')->where('kind', 'translations|tafsirs|recitations|chapter_reciters|chapter_infos')->whereNumber('id')->name('resources.info');

                Route::get('/chapter_recitations/{resource}', 'chapterRecitations')->whereNumber('resource')->name('chapter-recitations.index');
                Route::get('/chapter_recitations/{resource}/{chapter}', 'chapterRecitation')->whereNumber(['resource', 'chapter'])->name('chapter-recitations.show');
                Route::get('/audio/reciters/{resource}/timestamp', 'audioTimestamp')->whereNumber('resource')->name('audio.timestamp');
                Route::get('/audio/reciters/{resource}/lookup', 'audioLookup')->whereNumber('resource')->name('audio.lookup');

                Route::get('/foot_notes/{id}', 'footNote')->where('id', '\d+')->name('foot-notes.show');
                Route::get('/search', 'search')->middleware('throttle:search')->name('search');
            });

            Route::prefix('languages')->group(function (): void {
                Route::get('/active', [LanguageController::class, 'active'])->name('languages.active');
                Route::get('/all', [LanguageController::class, 'all'])->name('languages.all');
                Route::get('/current', [LanguageController::class, 'current'])->name('languages.current');
            });

            Route::prefix('search')->middleware('throttle:search')->group(function (): void {
                Route::post('/', [SearchController::class, 'index'])->name('search.index');
                Route::get('/suggestions', [SearchController::class, 'suggestions'])->name('search.suggestions');
                Route::get('/trending', [SearchController::class, 'trending'])->name('search.trending');
                Route::get('/modules', [SearchController::class, 'modules'])->name('search.modules');
                Route::get('/history', [SearchController::class, 'history'])->name('search.history');
                Route::delete('/history', [SearchController::class, 'clearHistory'])->middleware('throttle:api_mutations')->name('search.clear-history');
                Route::post('/{module}', [SearchController::class, 'searchModule'])
                    ->whereIn('module', ['article', 'media', 'seerah', 'sahaba', 'quran'])
                    ->name('search.module');
            });

            Route::prefix('filters')->group(function (): void {
                Route::get('/', [FilterController::class, 'index'])->name('filters.index');
                Route::post('/', [FilterController::class, 'store'])->middleware('throttle:api_mutations')->name('filters.store');
                Route::get('/public', [FilterController::class, 'publicFilters'])->name('filters.public');
                Route::get('/preferences/{module}', [FilterController::class, 'getPreferences'])
                    ->whereIn('module', ['article', 'media', 'seerah', 'sahaba', 'quran'])
                    ->name('filters.preferences.get');
                Route::post('/preferences/{module}', [FilterController::class, 'savePreferences'])->middleware('throttle:api_mutations')
                    ->whereIn('module', ['article', 'media', 'seerah', 'sahaba', 'quran'])
                    ->name('filters.preferences.save');
                Route::put('/{id}', [FilterController::class, 'update'])->whereNumber('id')->middleware('throttle:api_mutations')->name('filters.update');
                Route::delete('/{id}', [FilterController::class, 'destroy'])->whereNumber('id')->middleware('throttle:api_mutations')->name('filters.destroy');
                Route::post('/{id}/apply', [FilterController::class, 'apply'])->whereNumber('id')->middleware('throttle:api_mutations')->name('filters.apply');
                Route::post('/{id}/set-default', [FilterController::class, 'setDefault'])->whereNumber('id')->middleware('throttle:api_mutations')->name('filters.set-default');
            });

            Route::post('/interactions', [InteractionController::class, 'store'])->middleware('throttle:api_mutations')->name('interactions.store');
            Route::get('/engagement/me', [InteractionController::class, 'getMyEngagement'])->name('engagement.me');
            Route::get('/engagement/users/{id}', [InteractionController::class, 'getUserEngagement'])
                ->whereNumber('id')->name('engagement.user');
            Route::get('/interactions/me', [InteractionController::class, 'getMyActivity'])->name('interactions.me');
            Route::get('/engagement/trends', [InteractionController::class, 'getMyTrends'])->name('engagement.trends');
            Route::get('/engagement/content/top', [InteractionController::class, 'getTopContent'])->name('engagement.top-content');
            Route::get('/engagement/content/{type}/{id}', [InteractionController::class, 'getContentEngagement'])
                ->whereIn('type', ['Article', 'Hadith', 'MediaProgram', 'MediaEpisode', 'SeerahEvent', 'Sahabi', 'QuranVerse'])
                ->whereNumber('id')->name('engagement.content');
            Route::get('/engagement/leaderboard', [InteractionController::class, 'getLeaderboard'])->name('engagement.leaderboard');
            Route::get('/engagement/stats', [InteractionController::class, 'getPlatformStats'])->name('engagement.stats');
            Route::get('/engagement/export', [InteractionController::class, 'exportMyData'])->name('engagement.export');
        });
    }); // end api.client group
}); // end v1 prefix group
