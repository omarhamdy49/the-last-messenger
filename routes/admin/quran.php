<?php

declare(strict_types=1);

use App\Domain\Quran\Enums\ResourceKind;
use App\Domain\Quran\Enums\SectionType;
use App\Http\Controllers\Admin\Quran\AiInquiryController;
use App\Http\Controllers\Admin\Quran\AudioFileController;
use App\Http\Controllers\Admin\Quran\ChapterAudioController;
use App\Http\Controllers\Admin\Quran\ChapterController;
use App\Http\Controllers\Admin\Quran\LanguageController;
use App\Http\Controllers\Admin\Quran\MushafController;
use App\Http\Controllers\Admin\Quran\PlaylistController;
use App\Http\Controllers\Admin\Quran\ReciterSourceController;
use App\Http\Controllers\Admin\Quran\ResourceController;
use App\Http\Controllers\Admin\Quran\ResourceRowController;
use App\Http\Controllers\Admin\Quran\SectionController;
use App\Http\Controllers\Admin\Quran\SyncController;
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

// Visitor-built reciter playlists (moderation). `{playlist}` is the numeric id:
// the model's route key is the public slug, so no implicit binding here.
Route::prefix('playlists')->name('admin.playlists.')->controller(PlaylistController::class)->group(function (): void {
    Route::get('/', 'index')->name('index');
    Route::post('/bulk-unpublish', 'bulkUnpublish')->name('bulk-unpublish');
    Route::get('/coverage/{surah}', 'coverage')->whereNumber('surah')->name('coverage');
    Route::get('/{playlist}/edit', 'edit')->whereNumber('playlist')->name('edit');
    Route::put('/{playlist}', 'update')->whereNumber('playlist')->name('update');
    Route::delete('/{playlist}', 'destroy')->whereNumber('playlist')->name('destroy');
    Route::post('/{playlist}/toggle/{field}', 'toggle')->whereNumber('playlist')->whereIn('field', ['public', 'featured'])->name('toggle');
});

/*
| Quran Foundation content (006). The corpus (chapters, verses, words, sections)
| has no create/delete routes. Static segments are registered before {id} routes.
*/
$kinds = array_map(static fn (ResourceKind $k): string => $k->routeSegment(), ResourceKind::cases());
$rowKinds = ['translations', 'tafsirs', 'chapter-infos'];

Route::prefix('quran')->name('admin.quran.')->group(function () use ($kinds, $rowKinds): void {
    // Reads
    Route::middleware('permission:view quran,admin')->group(function () use ($kinds, $rowKinds): void {
        Route::get('chapters', [ChapterController::class, 'index'])->name('chapters.index');
        Route::get('chapters/search', [ChapterController::class, 'search'])->name('chapters.search');
        Route::get('chapters/{chapter}', [ChapterController::class, 'show'])->whereNumber('chapter')->name('chapters.show');

        Route::get('verses', [VerseController::class, 'index'])->name('verses.index');
        Route::get('verses/{verse}', [VerseController::class, 'show'])->whereNumber('verse')->name('verses.show');
        Route::get('verses/{verse}/words', [VerseController::class, 'words'])->whereNumber('verse')->name('verses.words.index');

        Route::get('sections/{type}', [SectionController::class, 'index'])->whereIn('type', SectionType::values())->name('sections.index');
        Route::get('sections/{type}/{number}', [SectionController::class, 'show'])->whereIn('type', SectionType::values())->whereNumber('number')->name('sections.show');

        Route::get('mushafs', [MushafController::class, 'index'])->name('mushafs.index');
        Route::get('mushafs/{mushaf}', [MushafController::class, 'show'])->whereNumber('mushaf')->name('mushafs.show');

        Route::get('languages', [LanguageController::class, 'index'])->name('languages.index');

        Route::get('resources/{kind}', [ResourceController::class, 'index'])->whereIn('kind', $kinds)->name('resources.index');
        Route::get('resources/{resource}', [ResourceController::class, 'show'])->whereNumber('resource')->name('resources.show');

        foreach ($rowKinds as $rows) {
            Route::get("resources/{resource}/{$rows}", [ResourceRowController::class, 'index'])->whereNumber('resource')->defaults('rows', $rows)->name("resources.{$rows}.index");
        }
        Route::get('resources/{resource}/audio-files', [AudioFileController::class, 'index'])->whereNumber('resource')->name('resources.audio-files.index');
        Route::get('resources/{resource}/chapter-audio', [ChapterAudioController::class, 'index'])->whereNumber('resource')->name('resources.chapter-audio.index');

        Route::get('sync', [SyncController::class, 'index'])->name('sync.index');
        Route::get('sync/runs/{run}', [SyncController::class, 'run'])->whereNumber('run')->name('sync.runs.show');
    });

    // Writes
    Route::middleware('permission:edit quran,admin')->group(function () use ($kinds, $rowKinds): void {
        Route::get('chapters/{chapter}/edit', [ChapterController::class, 'edit'])->whereNumber('chapter')->name('chapters.edit');
        Route::put('chapters/{chapter}', [ChapterController::class, 'update'])->whereNumber('chapter')->name('chapters.update');

        Route::post('verses/bulk-lock', [VerseController::class, 'bulkLock'])->name('verses.bulk-lock');
        Route::get('verses/{verse}/edit', [VerseController::class, 'edit'])->whereNumber('verse')->name('verses.edit');
        Route::put('verses/{verse}', [VerseController::class, 'update'])->whereNumber('verse')->name('verses.update');
        Route::patch('verses/{verse}/lock', [VerseController::class, 'lock'])->whereNumber('verse')->name('verses.lock');
        Route::put('words/{word}', [VerseController::class, 'updateWord'])->whereNumber('word')->name('words.update');

        Route::put('mushafs/{mushaf}', [MushafController::class, 'update'])->whereNumber('mushaf')->name('mushafs.update');

        Route::get('languages/create', [LanguageController::class, 'create'])->name('languages.create');
        Route::post('languages', [LanguageController::class, 'store'])->name('languages.store');
        Route::get('languages/{language}/edit', [LanguageController::class, 'edit'])->whereNumber('language')->name('languages.edit');
        Route::put('languages/{language}', [LanguageController::class, 'update'])->whereNumber('language')->name('languages.update');
        Route::delete('languages/{language}', [LanguageController::class, 'destroy'])->whereNumber('language')->name('languages.destroy');
        Route::post('languages/{language}/toggle', [LanguageController::class, 'toggle'])->whereNumber('language')->name('languages.toggle');

        Route::get('resources/{kind}/create', [ResourceController::class, 'create'])->whereIn('kind', $kinds)->name('resources.create');
        Route::post('resources/{kind}', [ResourceController::class, 'store'])->whereIn('kind', $kinds)->name('resources.store');
        Route::post('resources/{kind}/reorder', [ResourceController::class, 'reorder'])->whereIn('kind', $kinds)->name('resources.reorder');
        Route::get('resources/{resource}/edit', [ResourceController::class, 'edit'])->whereNumber('resource')->name('resources.edit');
        Route::put('resources/{resource}', [ResourceController::class, 'update'])->whereNumber('resource')->name('resources.update');
        Route::delete('resources/{resource}', [ResourceController::class, 'destroy'])->whereNumber('resource')->name('resources.destroy');
        Route::post('resources/{resource}/toggle/{field}', [ResourceController::class, 'toggle'])->whereNumber('resource')
            ->whereIn('field', ['is_active', 'is_default', 'sync_enabled'])->name('resources.toggle');

        foreach ($rowKinds as $rows) {
            Route::post("resources/{resource}/{$rows}", [ResourceRowController::class, 'store'])->whereNumber('resource')->defaults('rows', $rows)->name("resources.{$rows}.store");
            Route::post("resources/{resource}/{$rows}/import", [ResourceRowController::class, 'import'])->whereNumber('resource')->defaults('rows', $rows)->name("resources.{$rows}.import");
            Route::put("resources/{resource}/{$rows}/{row}", [ResourceRowController::class, 'update'])->whereNumber(['resource', 'row'])->defaults('rows', $rows)->name("resources.{$rows}.update");
            Route::delete("resources/{resource}/{$rows}/{row}", [ResourceRowController::class, 'destroy'])->whereNumber(['resource', 'row'])->defaults('rows', $rows)->name("resources.{$rows}.destroy");
        }

        Route::post('resources/{resource}/audio-files', [AudioFileController::class, 'store'])->whereNumber('resource')->name('resources.audio-files.store');
        Route::post('resources/{resource}/audio-files/fill', [AudioFileController::class, 'fill'])->whereNumber('resource')->name('resources.audio-files.fill');
        Route::put('resources/{resource}/audio-files/{file}', [AudioFileController::class, 'update'])->whereNumber(['resource', 'file'])->name('resources.audio-files.update');
        Route::delete('resources/{resource}/audio-files/{file}', [AudioFileController::class, 'destroy'])->whereNumber(['resource', 'file'])->name('resources.audio-files.destroy');

        Route::post('resources/{resource}/chapter-audio', [ChapterAudioController::class, 'store'])->whereNumber('resource')->name('resources.chapter-audio.store');
        Route::post('resources/{resource}/chapter-audio/fill', [ChapterAudioController::class, 'fill'])->whereNumber('resource')->name('resources.chapter-audio.fill');
        Route::put('resources/{resource}/chapter-audio/{file}', [ChapterAudioController::class, 'update'])->whereNumber(['resource', 'file'])->name('resources.chapter-audio.update');
        Route::delete('resources/{resource}/chapter-audio/{file}', [ChapterAudioController::class, 'destroy'])->whereNumber(['resource', 'file'])->name('resources.chapter-audio.destroy');
        Route::get('resources/{resource}/chapter-audio/{file}/timestamps', [ChapterAudioController::class, 'timestamps'])->whereNumber(['resource', 'file'])->name('resources.chapter-audio.timestamps.edit');
        Route::put('resources/{resource}/chapter-audio/{file}/timestamps', [ChapterAudioController::class, 'updateTimestamps'])->whereNumber(['resource', 'file'])->name('resources.chapter-audio.timestamps.update');
    });

    // Actions that call Quran Foundation or run long imports
    Route::middleware('permission:sync quran,admin')->group(function () use ($kinds): void {
        Route::post('sections/reimport', [SectionController::class, 'reimport'])->name('sections.reimport');
        Route::post('mushafs/{mushaf}/fonts', [MushafController::class, 'fonts'])->whereNumber('mushaf')->name('mushafs.fonts');
        Route::post('mushafs/{mushaf}/resync', [MushafController::class, 'resync'])->whereNumber('mushaf')->name('mushafs.resync');

        Route::get('resources/{kind}/catalogue', [ResourceController::class, 'catalogue'])->whereIn('kind', $kinds)->name('resources.catalogue');
        Route::post('resources/{kind}/import-from-qf', [ResourceController::class, 'importFromQf'])->whereIn('kind', $kinds)->name('resources.import-from-qf');
        Route::post('resources/{resource}/sync', [ResourceController::class, 'sync'])->whereNumber('resource')->name('resources.sync');

        // Source-backed reciters (US4): preview a QF / mp3quran id, fetch in the background.
        Route::post('resources/reciter-lookup', [ReciterSourceController::class, 'lookup'])->name('resources.lookup');
        Route::post('resources/{resource}/fetch', [ReciterSourceController::class, 'fetch'])->whereNumber('resource')->name('resources.fetch');
        Route::get('resources/{resource}/fetch-status', [ReciterSourceController::class, 'status'])->whereNumber('resource')->name('resources.fetch-status');

        Route::post('sync/bootstrap', [SyncController::class, 'bootstrap'])->name('sync.bootstrap');
        Route::post('sync/incremental', [SyncController::class, 'incremental'])->name('sync.incremental');
        Route::post('sync/assets', [SyncController::class, 'assets'])->name('sync.assets');
    });
});
