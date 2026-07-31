<?php

declare(strict_types=1);

use App\Http\Controllers\Api\ArticleController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\FilterController;
use App\Http\Controllers\Api\HadithController;
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

    // Authentication routes
    Route::prefix('auth')->group(function () {
        Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:auth')->name('auth.register');
        Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:auth')->name('auth.login');

        Route::middleware('auth:sanctum')->group(function () {
            Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout');
            Route::post('/logout-all', [AuthController::class, 'logoutAll'])->name('auth.logout-all');
            Route::get('/user', [AuthController::class, 'user'])->name('auth.user');
        });
    });

    // Categories routes
    Route::prefix('categories')->group(function () {
        Route::get('/', [CategoryController::class, 'index'])->name('categories.index');
        Route::get('/{slug}', [CategoryController::class, 'show'])->name('categories.show');
    });

    // Articles routes — public read endpoints only
    Route::prefix('articles')->group(function () {
        Route::get('/', [ArticleController::class, 'index'])->name('articles.index');
        Route::get('/featured', [ArticleController::class, 'featured'])->name('articles.featured');
        Route::get('/popular', [ArticleController::class, 'popular'])->name('articles.popular');
        Route::get('/recent', [ArticleController::class, 'recent'])->name('articles.recent');
        Route::get('/search', [ArticleController::class, 'search'])->name('articles.search');
        Route::get('/{slug}', [ArticleController::class, 'show'])->name('articles.show');
    });

    /*
     * Hadith — ten read-only endpoints (contracts/hadith-api.md).
     *
     * Static segments are declared before the dynamic ones so `random` can never
     * be read as a urn and `search` never as a collection code. Search reuses the
     * SAME `throttle:search` limiter and HadithSearchRequest as the web surface
     * rather than declaring its own.
     */
    Route::prefix('hadith')->name('hadith.')->group(function (): void {
        Route::get('/search', [HadithController::class, 'search'])
            ->middleware('throttle:search')->name('search');

        Route::get('/hadiths/random', [HadithController::class, 'random'])->name('random');
        Route::get('/hadiths/{urn}', [HadithController::class, 'urn'])
            ->whereNumber('urn')->name('urn');

        Route::get('/collections', [HadithController::class, 'collections'])->name('collections');
        Route::get('/collections/{code}', [HadithController::class, 'collection'])
            ->where('code', '[a-z0-9_]+')->name('collections.show');
        Route::get('/collections/{code}/books', [HadithController::class, 'books'])
            ->where('code', '[a-z0-9_]+')->name('collections.books');
        Route::get('/collections/{code}/books/{bookNumber}', [HadithController::class, 'book'])
            ->where(['code' => '[a-z0-9_]+', 'bookNumber' => '[A-Za-z0-9_-]+'])->name('books.show');
        Route::get('/collections/{code}/books/{bookNumber}/chapters', [HadithController::class, 'chapters'])
            ->where(['code' => '[a-z0-9_]+', 'bookNumber' => '[A-Za-z0-9_-]+'])->name('books.chapters');
        Route::get('/collections/{code}/books/{bookNumber}/hadiths', [HadithController::class, 'bookHadiths'])
            ->where(['code' => '[a-z0-9_]+', 'bookNumber' => '[A-Za-z0-9_-]+'])->name('books.hadiths');
        Route::get('/collections/{code}/hadiths/{hadithNumber}', [HadithController::class, 'hadith'])
            ->where(['code' => '[a-z0-9_]+', 'hadithNumber' => '[A-Za-z0-9._-]+'])->name('collections.hadith');
    });

    // Media/Programs routes — full domain implementation
    Route::prefix('media')->group(function () {
        // Public endpoints
        Route::get('/programs', [MediaProgramController::class, 'index'])->name('media.programs.index');
        Route::get('/programs/popular', [MediaProgramController::class, 'popular'])->name('media.programs.popular');
        Route::get('/programs/recent', [MediaProgramController::class, 'recent'])->name('media.programs.recent');
        Route::get('/programs/search', [MediaProgramController::class, 'search'])->middleware('throttle:search')->name('media.programs.search');
        Route::get('/programs/category/{categoryId}', [MediaProgramController::class, 'byCategory'])->name('media.programs.by-category');
        Route::get('/programs/{slug}', [MediaProgramController::class, 'show'])->name('media.programs.show');
        Route::get('/programs/{slug}/episodes', [MediaProgramController::class, 'episodes'])->name('media.programs.episodes');
        Route::get('/programs/{slug}/episodes/{episodeNumber}', [MediaProgramController::class, 'episode'])->name('media.programs.episode');

        // Authenticated endpoints
        Route::middleware('auth:sanctum')->group(function () {
            Route::post('/progress', [MediaProgramController::class, 'storeProgress'])->name('media.progress.store');
            Route::get('/continue-watching', [MediaProgramController::class, 'continueWatching'])->name('media.continue-watching');
        });
    });

    // Seerah routes (Prophet's Biography Timeline)
    Route::prefix('seerah')->group(function () {
        // Public routes
        Route::get('/', [SeerahEventController::class, 'index'])->name('seerah.index');
        Route::get('/featured', [SeerahEventController::class, 'featured'])->name('seerah.featured');
        Route::get('/search', [SeerahEventController::class, 'search'])->name('seerah.search');
        Route::get('/map', [SeerahEventController::class, 'map'])->name('seerah.map');
        Route::get('/type/{type}', [SeerahEventController::class, 'byType'])->name('seerah.by-type');
        Route::get('/hijri/{year}', [SeerahEventController::class, 'byHijriYear'])->name('seerah.by-hijri-year');
        Route::get('/gregorian/{year}', [SeerahEventController::class, 'byGregorianYear'])->name('seerah.by-gregorian-year');
        Route::get('/{slug}', [SeerahEventController::class, 'show'])->name('seerah.show');
    });

    // Sahaba routes (Companions of the Prophet)
    Route::prefix('sahaba')->group(function () {
        // Public routes
        Route::get('/', [SahabiController::class, 'index'])->name('sahaba.index');
        Route::get('/featured', [SahabiController::class, 'featured'])->name('sahaba.featured');
        Route::get('/early-muslims', [SahabiController::class, 'earlyMuslims'])->name('sahaba.early-muslims');
        Route::get('/top-narrators', [SahabiController::class, 'topNarrators'])->name('sahaba.top-narrators');
        Route::get('/search', [SahabiController::class, 'search'])->name('sahaba.search');
        Route::get('/status/{status}', [SahabiController::class, 'byStatus'])->name('sahaba.by-status');
        Route::get('/tribe/{tribe}', [SahabiController::class, 'byTribe'])->name('sahaba.by-tribe');
        Route::get('/battle/{battle}', [SahabiController::class, 'byBattle'])->name('sahaba.by-battle');
        Route::get('/{slug}', [SahabiController::class, 'show'])->name('sahaba.show');
    });

    // Quran routes (Holy Quran - Surahs & Verses)
    Route::prefix('quran')->group(function () {
        // Surah routes
        Route::get('/surahs', [QuranController::class, 'surahs'])->name('quran.surahs');
        Route::get('/surahs/{number}', [QuranController::class, 'surah'])->name('quran.surah');
        Route::get('/revelation/{place}', [QuranController::class, 'surahsByRevelation'])->name('quran.by-revelation');

        // Verse routes
        Route::get('/surahs/{surahNumber}/verses', [QuranController::class, 'verses'])->name('quran.verses');
        Route::get('/surahs/{surahNumber}/verses/{verseNumber}', [QuranController::class, 'verse'])->name('quran.verse');
        Route::get('/juz/{juz}', [QuranController::class, 'versesByJuz'])->name('quran.juz');
        Route::get('/page/{page}', [QuranController::class, 'versesByPage'])->name('quran.page');
        Route::get('/sajda', [QuranController::class, 'sajdaVerses'])->name('quran.sajda');
        Route::get('/random', [QuranController::class, 'randomVerse'])->name('quran.random');

        // Search
        Route::get('/search', [QuranController::class, 'search'])->name('quran.search');
        Route::get('/search/suggest', [QuranController::class, 'suggest'])
            ->middleware('throttle:search')
            ->name('quran.search.suggest');
    });

    // Language routes
    Route::prefix('languages')->group(function () {
        Route::get('/active', [LanguageController::class, 'active'])->name('languages.active');
        Route::get('/all', [LanguageController::class, 'all'])->name('languages.all');
        Route::get('/current', [LanguageController::class, 'current'])->name('languages.current');
    });

    // Search routes
    Route::prefix('search')->middleware('throttle:search')->group(function () {
        // Public search endpoints
        Route::post('/', [SearchController::class, 'index'])->name('search.index');
        Route::post('/{module}', [SearchController::class, 'searchModule'])->name('search.module');
        Route::get('/suggestions', [SearchController::class, 'suggestions'])->name('search.suggestions');
        Route::get('/trending', [SearchController::class, 'trending'])->name('search.trending');
        Route::get('/modules', [SearchController::class, 'modules'])->name('search.modules');

        // Protected search endpoints (require authentication)
        Route::middleware('auth:sanctum')->group(function () {
            Route::get('/history', [SearchController::class, 'history'])->name('search.history');
            Route::delete('/history', [SearchController::class, 'clearHistory'])->name('search.clear-history');
        });
    });

    // Filter routes (all require authentication)
    Route::middleware('auth:sanctum')->prefix('filters')->group(function () {
        Route::get('/', [FilterController::class, 'index'])->name('filters.index');
        Route::post('/', [FilterController::class, 'store'])->name('filters.store');
        Route::put('/{id}', [FilterController::class, 'update'])->name('filters.update');
        Route::delete('/{id}', [FilterController::class, 'destroy'])->name('filters.destroy');
        Route::post('/{id}/apply', [FilterController::class, 'apply'])->name('filters.apply');
        Route::post('/{id}/set-default', [FilterController::class, 'setDefault'])->name('filters.set-default');
        Route::get('/public', [FilterController::class, 'publicFilters'])->name('filters.public');

        // Search preferences
        Route::get('/preferences/{module}', [FilterController::class, 'getPreferences'])->name('filters.preferences.get');
        Route::post('/preferences/{module}', [FilterController::class, 'savePreferences'])->name('filters.preferences.save');
    });

    // Interaction & Engagement Tracking routes (all require authentication)
    Route::middleware('auth:sanctum')->group(function () {
        // Record interactions
        Route::post('/interactions', [InteractionController::class, 'store'])->name('interactions.store');

        // User engagement metrics
        Route::get('/engagement/me', [InteractionController::class, 'getMyEngagement'])->name('engagement.me');
        Route::get('/engagement/users/{id}', [InteractionController::class, 'getUserEngagement'])->name('engagement.user');

        // User activity
        Route::get('/interactions/me', [InteractionController::class, 'getMyActivity'])->name('interactions.me');
        Route::get('/engagement/trends', [InteractionController::class, 'getMyTrends'])->name('engagement.trends');

        // Content engagement stats (public content)
        Route::get('/engagement/content/{type}/{id}', [InteractionController::class, 'getContentEngagement'])->name('engagement.content');

        // Leaderboards and top content
        Route::get('/engagement/leaderboard', [InteractionController::class, 'getLeaderboard'])->name('engagement.leaderboard');
        Route::get('/engagement/content/top', [InteractionController::class, 'getTopContent'])->name('engagement.top-content');

        // Platform statistics
        Route::get('/engagement/stats', [InteractionController::class, 'getPlatformStats'])->name('engagement.stats');

        // Export user data
        Route::get('/engagement/export', [InteractionController::class, 'exportMyData'])->name('engagement.export');
    });

    // Health check
    Route::get('/health', function () {
        return response()->json([
            'status' => 'ok',
            'timestamp' => now()->toISOString(),
            'service' => 'The Last Messenger API',
            'version' => '1.0.0',
        ]);
    })->name('health');

}); // end v1 prefix group
