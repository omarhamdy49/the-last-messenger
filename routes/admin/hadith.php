<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\Hadith\HadithBookController;
use App\Http\Controllers\Admin\Hadith\HadithChapterController;
use App\Http\Controllers\Admin\Hadith\HadithCollectionController;
use App\Http\Controllers\Admin\Hadith\HadithRecordController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Hadith administration
|--------------------------------------------------------------------------
|
| Collections → books → chapters → hadiths. Until now the module had a data
| layer and an importer but no panel at all, so 37,957 imported hadiths were
| readable by visitors and uneditable by editors.
|
| Every table is guarded by ON DELETE RESTRICT, so the controllers check for
| children before deleting rather than letting the constraint surface as a 500.
|
*/

// Static segments first: `toggle-status` and `search` must never be read as a
// {hadith} binding (the gotcha this repo has hit before with /trash and /history).
Route::prefix('hadith-collections')->group(function (): void {
    Route::patch('/{collection}/toggle-status', [HadithCollectionController::class, 'toggleStatus'])
        ->name('admin.hadith-collections.toggle-status');
});

Route::prefix('hadith-books')->group(function (): void {
    Route::patch('/{book}/toggle-status', [HadithBookController::class, 'toggleStatus'])
        ->name('admin.hadith-books.toggle-status');
});

Route::prefix('hadith-chapters')->group(function (): void {
    Route::patch('/{chapter}/toggle-status', [HadithChapterController::class, 'toggleStatus'])
        ->name('admin.hadith-chapters.toggle-status');
});

Route::prefix('hadiths')->group(function (): void {
    Route::patch('/{hadith}/toggle-status', [HadithRecordController::class, 'toggleStatus'])
        ->name('admin.hadiths.toggle-status');
});

Route::resource('hadith-collections', HadithCollectionController::class)
    ->parameters(['hadith-collections' => 'collection'])
    ->names('admin.hadith-collections');

Route::resource('hadith-books', HadithBookController::class)
    ->parameters(['hadith-books' => 'book'])
    ->except(['show'])
    ->names('admin.hadith-books');

Route::resource('hadith-chapters', HadithChapterController::class)
    ->parameters(['hadith-chapters' => 'chapter'])
    ->except(['show'])
    ->names('admin.hadith-chapters');

Route::resource('hadiths', HadithRecordController::class)
    ->parameters(['hadiths' => 'hadith'])
    ->names('admin.hadiths');
