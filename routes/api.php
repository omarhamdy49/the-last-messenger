<?php

declare(strict_types=1);

use App\Http\Controllers\Api\ArticleController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\FilterController;
use App\Http\Controllers\Api\InteractionController;
use App\Http\Controllers\Api\MediaProgramController;
use App\Http\Controllers\Api\QuranController;
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
    // Deliberately public bootstrap routes. Everything else in this file is
    // secure-by-default inside the auth:sanctum group below.
    Route::prefix('auth')->group(function (): void {
        Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:auth')->name('auth.register');
        Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:auth')->name('auth.login');

        Route::middleware(['auth:sanctum', 'abilities:api:access', 'throttle:api'])->group(function (): void {
            Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout');
            Route::post('/logout-all', [AuthController::class, 'logoutAll'])->name('auth.logout-all');
            Route::get('/user', [AuthController::class, 'user'])->name('auth.user');
        });
    });

    // Minimal public liveness response. It exposes no database, dependency,
    // host, build, or exception details and is still rate limited.
    Route::get('/health', static function () {
        return response()->json([
            'status' => 'ok',
            'timestamp' => now()->toISOString(),
            'service' => 'The Last Messenger API',
            'version' => '1.0.0',
        ]);
    })->middleware('throttle:api')->name('health');

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
            Route::post('/progress', [MediaProgramController::class, 'storeProgress'])->name('media.progress.store');
            Route::get('/continue-watching', [MediaProgramController::class, 'continueWatching'])->name('media.continue-watching');
        });

        Route::prefix('seerah')->group(function (): void {
            Route::get('/', [SeerahEventController::class, 'index'])->name('seerah.index');
            Route::get('/featured', [SeerahEventController::class, 'featured'])->name('seerah.featured');
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

        Route::prefix('quran')->group(function (): void {
            Route::get('/surahs', [QuranController::class, 'surahs'])->name('quran.surahs');
            Route::get('/surahs/{number}', [QuranController::class, 'surah'])
                ->whereNumber('number')->name('quran.surah');
            Route::get('/revelation/{place}', [QuranController::class, 'surahsByRevelation'])
                ->whereIn('place', ['makkah', 'madinah'])->name('quran.by-revelation');
            Route::get('/surahs/{surahNumber}/verses', [QuranController::class, 'verses'])
                ->whereNumber('surahNumber')->name('quran.verses');
            Route::get('/surahs/{surahNumber}/verses/{verseNumber}', [QuranController::class, 'verse'])
                ->whereNumber('surahNumber')->whereNumber('verseNumber')->name('quran.verse');
            Route::get('/juz/{juz}', [QuranController::class, 'versesByJuz'])
                ->whereNumber('juz')->name('quran.juz');
            Route::get('/page/{page}', [QuranController::class, 'versesByPage'])
                ->whereNumber('page')->name('quran.page');
            Route::get('/sajda', [QuranController::class, 'sajdaVerses'])->name('quran.sajda');
            Route::get('/random', [QuranController::class, 'randomVerse'])->name('quran.random');
            Route::get('/search', [QuranController::class, 'search'])
                ->middleware('throttle:search')->name('quran.search');
            Route::get('/search/suggest', [QuranController::class, 'suggest'])
                ->middleware('throttle:search')->name('quran.search.suggest');
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
            Route::delete('/history', [SearchController::class, 'clearHistory'])->name('search.clear-history');
            Route::post('/{module}', [SearchController::class, 'searchModule'])
                ->whereIn('module', ['article', 'media', 'seerah', 'sahaba', 'quran'])
                ->name('search.module');
        });

        Route::prefix('filters')->group(function (): void {
            Route::get('/', [FilterController::class, 'index'])->name('filters.index');
            Route::post('/', [FilterController::class, 'store'])->name('filters.store');
            Route::get('/public', [FilterController::class, 'publicFilters'])->name('filters.public');
            Route::get('/preferences/{module}', [FilterController::class, 'getPreferences'])
                ->whereIn('module', ['article', 'media', 'seerah', 'sahaba', 'quran'])
                ->name('filters.preferences.get');
            Route::post('/preferences/{module}', [FilterController::class, 'savePreferences'])
                ->whereIn('module', ['article', 'media', 'seerah', 'sahaba', 'quran'])
                ->name('filters.preferences.save');
            Route::put('/{id}', [FilterController::class, 'update'])->whereNumber('id')->name('filters.update');
            Route::delete('/{id}', [FilterController::class, 'destroy'])->whereNumber('id')->name('filters.destroy');
            Route::post('/{id}/apply', [FilterController::class, 'apply'])->whereNumber('id')->name('filters.apply');
            Route::post('/{id}/set-default', [FilterController::class, 'setDefault'])->whereNumber('id')->name('filters.set-default');
        });

        Route::post('/interactions', [InteractionController::class, 'store'])->name('interactions.store');
        Route::get('/engagement/me', [InteractionController::class, 'getMyEngagement'])->name('engagement.me');
        Route::get('/engagement/users/{id}', [InteractionController::class, 'getUserEngagement'])
            ->whereNumber('id')->name('engagement.user');
        Route::get('/interactions/me', [InteractionController::class, 'getMyActivity'])->name('interactions.me');
        Route::get('/engagement/trends', [InteractionController::class, 'getMyTrends'])->name('engagement.trends');
        Route::get('/engagement/content/top', [InteractionController::class, 'getTopContent'])->name('engagement.top-content');
        Route::get('/engagement/content/{type}/{id}', [InteractionController::class, 'getContentEngagement'])
            ->whereIn('type', ['Article', 'Hadith', 'MediaProgram', 'MediaEpisode', 'SeerahEvent', 'Sahabi', 'QuranVerse', 'QuranSurah'])
            ->whereNumber('id')->name('engagement.content');
        Route::get('/engagement/leaderboard', [InteractionController::class, 'getLeaderboard'])->name('engagement.leaderboard');
        Route::get('/engagement/stats', [InteractionController::class, 'getPlatformStats'])->name('engagement.stats');
        Route::get('/engagement/export', [InteractionController::class, 'exportMyData'])->name('engagement.export');
    });
}); // end v1 prefix group
