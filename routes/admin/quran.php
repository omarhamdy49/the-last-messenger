<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\Quran\AiInquiryController;
use App\Http\Controllers\Admin\Quran\RecitationController;
use App\Http\Controllers\Admin\Quran\ReciterController;
use App\Http\Controllers\Admin\Quran\ReciterProfileController;
use App\Http\Controllers\Admin\Quran\RecordingSourceController;
use App\Http\Controllers\Admin\Quran\RevelationContextController;
use App\Http\Controllers\Admin\Quran\SurahController;
use App\Http\Controllers\Admin\Quran\TafseerController;
use App\Http\Controllers\Admin\Quran\TafsirResourceController;
use App\Http\Controllers\Admin\Quran\TranslationResourceController;
use App\Http\Controllers\Admin\Quran\VerseController;
use Illuminate\Support\Facades\Route;

// Ask Quran AI inquiries — moderation + publish-as-FAQ
Route::prefix('ai-inquiries')->name('admin.quran.ai-inquiries.')->group(function (): void {
    Route::get('/', [AiInquiryController::class, 'index'])->name('index');
    Route::get('/categories', [AiInquiryController::class, 'categories'])->name('categories');
    Route::get('/{inquiry}', [AiInquiryController::class, 'show'])->whereNumber('inquiry')->name('show');
    Route::put('/{inquiry}/answer', [AiInquiryController::class, 'updateAnswer'])->whereNumber('inquiry')->name('answer');
    Route::post('/{inquiry}/publish', [AiInquiryController::class, 'publishAsFaq'])->whereNumber('inquiry')->name('publish');
    Route::delete('/{inquiry}', [AiInquiryController::class, 'destroy'])->whereNumber('inquiry')->name('destroy');
});

// The former `quran` verse-CRUD surface was removed: it was a duplicate of the
// `verses` routes below (the only ones linked from the sidebar) and it was wired
// to the Surah repository, so "delete verse" deleted the Surah of that id and
// cascaded to all of its verses. Use admin.verses.* for verse management.

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
Route::prefix('reciter-profiles')->name('admin.reciter-profiles.')->controller(ReciterProfileController::class)->group(function (): void {
    // Static segments first, then {profile} routes.
    Route::get('/', 'index')->name('index');
    Route::get('/create', 'create')->name('create');
    Route::post('/', 'store')->name('store');
    Route::get('/{profile}/edit', 'edit')->whereNumber('profile')->name('edit');
    Route::put('/{profile}', 'update')->whereNumber('profile')->name('update');
    Route::delete('/{profile}', 'destroy')->whereNumber('profile')->name('destroy');
    Route::post('/{profile}/toggle/{field}', 'toggle')->whereNumber('profile')->whereIn('field', ['active', 'featured'])->name('toggle');
    Route::post('/{profile}/recordings', 'attach')->whereNumber('profile')->name('recordings.attach');
    Route::delete('/{profile}/recordings/{recording}', 'detach')->whereNumber(['profile', 'recording'])->name('recordings.detach');
    Route::post('/{profile}/recordings/{recording}/default', 'setDefault')->whereNumber(['profile', 'recording'])->name('recordings.default');
});

Route::prefix('reciters')->group(function (): void {
    Route::get('/{reciter}/sources', [RecordingSourceController::class, 'index'])->whereNumber('reciter')->name('admin.reciters.sources.index');
    Route::post('/{reciter}/sources', [RecordingSourceController::class, 'store'])->whereNumber('reciter')->name('admin.reciters.sources.store');
    Route::put('/{reciter}/sources/{source}', [RecordingSourceController::class, 'update'])->whereNumber(['reciter', 'source'])->name('admin.reciters.sources.update');
    Route::delete('/{reciter}/sources/{source}', [RecordingSourceController::class, 'destroy'])->whereNumber(['reciter', 'source'])->name('admin.reciters.sources.destroy');
    Route::get('/search', [ReciterController::class, 'search'])->name('admin.reciters.search');
    Route::post('/bulk-delete', [ReciterController::class, 'bulkDelete'])->name('admin.reciters.bulk-delete');
    Route::post('/reorder', [ReciterController::class, 'reorder'])->name('admin.reciters.reorder');
    Route::post('/{id}/toggle-featured', [ReciterController::class, 'toggleFeatured'])->name('admin.reciters.toggle-featured');

    // Per-surah audio recordings (recitations) nested under a reciter
    Route::get('/{reciter}/recitations', [ReciterController::class, 'recitations'])
        ->whereNumber('reciter')
        ->name('admin.reciters.recitations.index');
    Route::post('/{reciter}/recitations', [RecitationController::class, 'store'])
        ->whereNumber('reciter')
        ->name('admin.reciters.recitations.store');
    Route::put('/{reciter}/recitations/{recitation}', [RecitationController::class, 'update'])
        ->whereNumber(['reciter', 'recitation'])
        ->name('admin.reciters.recitations.update');
    Route::delete('/{reciter}/recitations/{recitation}', [RecitationController::class, 'destroy'])
        ->whereNumber(['reciter', 'recitation'])
        ->name('admin.reciters.recitations.destroy');
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

// Tafsir Resource (source) Routes
Route::resource('tafsir-resources', TafsirResourceController::class)->except(['show'])->names([
    'index' => 'admin.tafsir-resources.index',
    'create' => 'admin.tafsir-resources.create',
    'store' => 'admin.tafsir-resources.store',
    'edit' => 'admin.tafsir-resources.edit',
    'update' => 'admin.tafsir-resources.update',
    'destroy' => 'admin.tafsir-resources.destroy',
]);

// Translation Edition (resource) Routes
Route::resource('translation-resources', TranslationResourceController::class)->except(['show'])->names([
    'index' => 'admin.translation-resources.index',
    'create' => 'admin.translation-resources.create',
    'store' => 'admin.translation-resources.store',
    'edit' => 'admin.translation-resources.edit',
    'update' => 'admin.translation-resources.update',
    'destroy' => 'admin.translation-resources.destroy',
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
