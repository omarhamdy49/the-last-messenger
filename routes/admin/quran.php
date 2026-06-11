<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\Quran\QuranController;
use App\Http\Controllers\Admin\Quran\ReciterController;
use App\Http\Controllers\Admin\Quran\RevelationContextController;
use App\Http\Controllers\Admin\Quran\SurahController;
use App\Http\Controllers\Admin\Quran\TafseerController;
use App\Http\Controllers\Admin\Quran\VerseController;
use Illuminate\Support\Facades\Route;

// Quran Routes (main entry point for Quran management)
Route::prefix('quran')->group(function (): void {
    Route::get('/search', [QuranController::class, 'search'])->name('admin.quran.search');
    Route::post('/bulk-delete', [QuranController::class, 'bulkDelete'])->name('admin.quran.bulk-delete');
});
Route::resource('quran', QuranController::class)->names([
    'index' => 'admin.quran.index',
    'create' => 'admin.quran.create',
    'store' => 'admin.quran.store',
    'show' => 'admin.quran.show',
    'edit' => 'admin.quran.edit',
    'update' => 'admin.quran.update',
    'destroy' => 'admin.quran.destroy',
]);

// Quran Surah Routes (no create/store - 114 surahs are fixed)
Route::prefix('surahs')->group(function (): void {
    Route::get('/search', [SurahController::class, 'search'])->name('admin.surahs.search');
});
Route::resource('surahs', SurahController::class)->except(['create', 'store', 'destroy'])->names([
    'index' => 'admin.surahs.index',
    'show' => 'admin.surahs.show',
    'edit' => 'admin.surahs.edit',
    'update' => 'admin.surahs.update',
]);

// Quran Verse Routes (full CRUD)
Route::prefix('verses')->group(function (): void {
    Route::get('/search', [VerseController::class, 'search'])->name('admin.verses.search');
    Route::get('/by-surah/{surahId}', [VerseController::class, 'bySurah'])->name('admin.verses.by-surah');
    Route::post('/bulk-delete', [VerseController::class, 'bulkDelete'])->name('admin.verses.bulk-delete');
});
Route::resource('verses', VerseController::class)->names([
    'index' => 'admin.verses.index',
    'create' => 'admin.verses.create',
    'store' => 'admin.verses.store',
    'show' => 'admin.verses.show',
    'edit' => 'admin.verses.edit',
    'update' => 'admin.verses.update',
    'destroy' => 'admin.verses.destroy',
]);

// Reciter Routes
Route::prefix('reciters')->group(function (): void {
    Route::get('/search', [ReciterController::class, 'search'])->name('admin.reciters.search');
    Route::post('/bulk-delete', [ReciterController::class, 'bulkDelete'])->name('admin.reciters.bulk-delete');
    Route::post('/reorder', [ReciterController::class, 'reorder'])->name('admin.reciters.reorder');
    Route::post('/{id}/toggle-featured', [ReciterController::class, 'toggleFeatured'])->name('admin.reciters.toggle-featured');
});
Route::resource('reciters', ReciterController::class)->names([
    'index' => 'admin.reciters.index',
    'create' => 'admin.reciters.create',
    'store' => 'admin.reciters.store',
    'show' => 'admin.reciters.show',
    'edit' => 'admin.reciters.edit',
    'update' => 'admin.reciters.update',
    'destroy' => 'admin.reciters.destroy',
]);

// Tafseer Routes
Route::prefix('tafseer')->group(function (): void {
    Route::get('/search', [TafseerController::class, 'search'])->name('admin.tafseer.search');
    Route::post('/bulk-delete', [TafseerController::class, 'bulkDelete'])->name('admin.tafseer.bulk-delete');
});
Route::resource('tafseer', TafseerController::class)->names([
    'index' => 'admin.tafseer.index',
    'create' => 'admin.tafseer.create',
    'store' => 'admin.tafseer.store',
    'show' => 'admin.tafseer.show',
    'edit' => 'admin.tafseer.edit',
    'update' => 'admin.tafseer.update',
    'destroy' => 'admin.tafseer.destroy',
]);

// Revelation Context (Asbab al-Nuzul) Routes
Route::resource('revelation-contexts', RevelationContextController::class)->names([
    'index' => 'admin.revelation-contexts.index',
    'create' => 'admin.revelation-contexts.create',
    'store' => 'admin.revelation-contexts.store',
    'show' => 'admin.revelation-contexts.show',
    'edit' => 'admin.revelation-contexts.edit',
    'update' => 'admin.revelation-contexts.update',
    'destroy' => 'admin.revelation-contexts.destroy',
]);
