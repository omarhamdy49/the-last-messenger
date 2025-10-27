<?php

declare(strict_types=1);

use App\Http\Controllers\Api\ArticleController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\HadithController;
use App\Http\Controllers\Api\MediaProgramController;
use App\Http\Controllers\Api\SeerahEventController;
use App\Http\Controllers\Api\SahabiController;
use App\Http\Controllers\Api\QuranController;
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

// Authentication routes
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register'])->name('auth.register');
    Route::post('/login', [AuthController::class, 'login'])->name('auth.login');
    
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::get('/user', [AuthController::class, 'user'])->name('auth.user');
    });
});

// Categories routes
Route::prefix('categories')->group(function () {
    Route::get('/', [CategoryController::class, 'index'])->name('categories.index');
    Route::get('/{slug}', [CategoryController::class, 'show'])->name('categories.show');
});

// Articles routes
Route::prefix('articles')->group(function () {
    // Public routes
    Route::get('/', [ArticleController::class, 'index'])->name('articles.index');
    Route::get('/featured', [ArticleController::class, 'featured'])->name('articles.featured');
    Route::get('/popular', [ArticleController::class, 'popular'])->name('articles.popular');
    Route::get('/recent', [ArticleController::class, 'recent'])->name('articles.recent');
    Route::get('/search', [ArticleController::class, 'search'])->name('articles.search');
    Route::get('/{slug}', [ArticleController::class, 'show'])->name('articles.show');

    // Protected routes (requires authentication)
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/', [ArticleController::class, 'store'])->name('articles.store');
        Route::put('/{article}', [ArticleController::class, 'update'])->name('articles.update');
        Route::delete('/{article}', [ArticleController::class, 'destroy'])->name('articles.destroy');
        
        // Article actions
        Route::post('/{article}/favorite', [ArticleController::class, 'toggleFavorite'])->name('articles.favorite');
        Route::post('/{article}/publish', [ArticleController::class, 'publish'])->name('articles.publish');
        Route::post('/{article}/unpublish', [ArticleController::class, 'unpublish'])->name('articles.unpublish');
        Route::post('/{article}/archive', [ArticleController::class, 'archive'])->name('articles.archive');
    });
});

// Hadith routes
Route::prefix('hadith')->group(function () {
    // Public routes
    Route::get('/', [HadithController::class, 'index'])->name('hadith.index');
    Route::get('/search', [HadithController::class, 'search'])->name('hadith.search');
    Route::get('/sahih', [HadithController::class, 'sahih'])->name('hadith.sahih');
    Route::get('/reliable', [HadithController::class, 'reliable'])->name('hadith.reliable');
    Route::get('/books', [HadithController::class, 'books'])->name('hadith.books');
    Route::get('/books/{slug}', [HadithController::class, 'bookShow'])->name('hadith.books.show');
    Route::get('/books/{bookId}/hadith/{number}', [HadithController::class, 'byBookAndNumber'])->name('hadith.by-book-number');
    Route::get('/chapters/{id}', [HadithController::class, 'chapterShow'])->name('hadith.chapters.show');
    Route::get('/{id}', [HadithController::class, 'show'])->name('hadith.show');
});

// Media/Programs routes
Route::prefix('media')->group(function () {
    // Public routes
    Route::get('/programs', [MediaProgramController::class, 'index'])->name('media.programs.index');
    Route::get('/programs/popular', [MediaProgramController::class, 'popular'])->name('media.programs.popular');
    Route::get('/programs/recent', [MediaProgramController::class, 'recent'])->name('media.programs.recent');
    Route::get('/programs/search', [MediaProgramController::class, 'search'])->name('media.programs.search');
    Route::get('/programs/category/{categoryId}', [MediaProgramController::class, 'byCategory'])->name('media.programs.by-category');
    Route::get('/programs/{slug}', [MediaProgramController::class, 'show'])->name('media.programs.show');
    Route::get('/programs/{slug}/episodes', [MediaProgramController::class, 'episodes'])->name('media.programs.episodes');
    Route::get('/programs/{slug}/episodes/{episodeNumber}', [MediaProgramController::class, 'episode'])->name('media.programs.episode');
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

