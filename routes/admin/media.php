<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\Media\MediaCategoryController;
use App\Http\Controllers\Admin\Media\MediaEpisodeController;
use App\Http\Controllers\Admin\Media\MediaProgramController;
use Illuminate\Support\Facades\Route;

// Media Programs
Route::prefix('media-programs')->group(function (): void {
    Route::get('/search', [MediaProgramController::class, 'search'])->name('admin.media-programs.search');
    Route::post('/bulk-delete', [MediaProgramController::class, 'bulkDelete'])->name('admin.media-programs.bulk-delete');
    Route::post('/{media_program}/images', [MediaProgramController::class, 'updateImages'])->name('admin.media-programs.images');
    Route::post('/{media_program}/episodes/reorder', [MediaProgramController::class, 'reorderEpisodes'])->name('admin.media-programs.episodes.reorder');
    Route::post('/{media_program}/episodes/assign', [MediaProgramController::class, 'assignEpisodes'])->name('admin.media-programs.episodes.assign');
});
Route::resource('media-programs', MediaProgramController::class)->names([
    'index' => 'admin.media-programs.index',
    'create' => 'admin.media-programs.create',
    'store' => 'admin.media-programs.store',
    'show' => 'admin.media-programs.show',
    'edit' => 'admin.media-programs.edit',
    'update' => 'admin.media-programs.update',
    'destroy' => 'admin.media-programs.destroy',
]);

// Media Categories — presentation, visibility and order for the public programs list
Route::get('media-categories', [MediaCategoryController::class, 'index'])->name('admin.media-categories.index');
Route::post('media-categories/reorder', [MediaCategoryController::class, 'reorder'])->name('admin.media-categories.reorder');
Route::get('media-categories/{key}', [MediaCategoryController::class, 'show'])->name('admin.media-categories.show');
Route::patch('media-categories/{key}', [MediaCategoryController::class, 'update'])->name('admin.media-categories.update');
Route::delete('media-categories/{key}', [MediaCategoryController::class, 'destroy'])->name('admin.media-categories.destroy');

// Media Episodes
Route::prefix('media-episodes')->group(function (): void {
    Route::post('/bulk-delete', [MediaEpisodeController::class, 'bulkDelete'])->name('admin.media-episodes.bulk-delete');
    Route::post('/{media_episode}/thumbnail', [MediaEpisodeController::class, 'updateThumbnail'])->name('admin.media-episodes.thumbnail');
});
Route::resource('media-episodes', MediaEpisodeController::class)->names([
    'index' => 'admin.media-episodes.index',
    'create' => 'admin.media-episodes.create',
    'store' => 'admin.media-episodes.store',
    'show' => 'admin.media-episodes.show',
    'edit' => 'admin.media-episodes.edit',
    'update' => 'admin.media-episodes.update',
    'destroy' => 'admin.media-episodes.destroy',
]);
