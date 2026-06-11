<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\Hadith\CollectionController;
use App\Http\Controllers\Admin\Hadith\GradingLevelController;
use App\Http\Controllers\Admin\Hadith\HadithController;
use App\Http\Controllers\Admin\Hadith\NarratorController;
use App\Http\Controllers\Admin\Hadith\ScholarController;
use App\Http\Controllers\Admin\Hadith\ThemeController;
use Illuminate\Support\Facades\Route;

// DISABLED: Hadith Routes (module removed - use API endpoints instead)
Route::prefix('hadith')->group(function (): void {
    Route::get('/search', [HadithController::class, 'search'])->name('admin.hadith.search');
    Route::post('/bulk-delete', [HadithController::class, 'bulkDelete'])->name('admin.hadith.bulk-delete');
});
Route::resource('hadith', HadithController::class)->names([
    'index' => 'admin.hadith.index',
    'create' => 'admin.hadith.create',
    'store' => 'admin.hadith.store',
    'show' => 'admin.hadith.show',
    'edit' => 'admin.hadith.edit',
    'update' => 'admin.hadith.update',
    'destroy' => 'admin.hadith.destroy',
]);

// Hadith Collection Routes
Route::prefix('hadith/collections')->group(function (): void {
    Route::get('/search', [CollectionController::class, 'search'])->name('admin.hadith.collections.search');
    Route::post('/bulk-delete', [CollectionController::class, 'bulkDelete'])->name('admin.hadith.collections.bulk-delete');
    Route::post('/reorder', [CollectionController::class, 'reorder'])->name('admin.hadith.collections.reorder');
    Route::post('/{id}/toggle-active', [CollectionController::class, 'toggleActive'])->name('admin.hadith.collections.toggle-active');
    Route::post('/{id}/toggle-featured', [CollectionController::class, 'toggleFeatured'])->name('admin.hadith.collections.toggle-featured');
});
Route::resource('hadith/collections', CollectionController::class)->names([
    'index' => 'admin.hadith.collections.index',
    'create' => 'admin.hadith.collections.create',
    'store' => 'admin.hadith.collections.store',
    'show' => 'admin.hadith.collections.show',
    'edit' => 'admin.hadith.collections.edit',
    'update' => 'admin.hadith.collections.update',
    'destroy' => 'admin.hadith.collections.destroy',
]);

// Hadith Narrator Routes
Route::prefix('hadith/narrators')->group(function (): void {
    Route::get('/search', [NarratorController::class, 'search'])->name('admin.hadith.narrators.search');
    Route::post('/bulk-delete', [NarratorController::class, 'bulkDelete'])->name('admin.hadith.narrators.bulk-delete');
});
Route::resource('hadith/narrators', NarratorController::class)->names([
    'index' => 'admin.hadith.narrators.index',
    'create' => 'admin.hadith.narrators.create',
    'store' => 'admin.hadith.narrators.store',
    'show' => 'admin.hadith.narrators.show',
    'edit' => 'admin.hadith.narrators.edit',
    'update' => 'admin.hadith.narrators.update',
    'destroy' => 'admin.hadith.narrators.destroy',
]);

// Hadith Scholar Routes
Route::prefix('hadith/scholars')->group(function (): void {
    Route::get('/search', [ScholarController::class, 'search'])->name('admin.hadith.scholars.search');
    Route::post('/bulk-delete', [ScholarController::class, 'bulkDelete'])->name('admin.hadith.scholars.bulk-delete');
});
Route::resource('hadith/scholars', ScholarController::class)->names([
    'index' => 'admin.hadith.scholars.index',
    'create' => 'admin.hadith.scholars.create',
    'store' => 'admin.hadith.scholars.store',
    'show' => 'admin.hadith.scholars.show',
    'edit' => 'admin.hadith.scholars.edit',
    'update' => 'admin.hadith.scholars.update',
    'destroy' => 'admin.hadith.scholars.destroy',
]);

// Hadith Theme Routes
Route::prefix('hadith/themes')->group(function (): void {
    Route::get('/search', [ThemeController::class, 'search'])->name('admin.hadith.themes.search');
    Route::post('/bulk-delete', [ThemeController::class, 'bulkDelete'])->name('admin.hadith.themes.bulk-delete');
});
Route::resource('hadith/themes', ThemeController::class)->names([
    'index' => 'admin.hadith.themes.index',
    'create' => 'admin.hadith.themes.create',
    'store' => 'admin.hadith.themes.store',
    'show' => 'admin.hadith.themes.show',
    'edit' => 'admin.hadith.themes.edit',
    'update' => 'admin.hadith.themes.update',
    'destroy' => 'admin.hadith.themes.destroy',
]);

// Hadith Grading Level Routes (enum-based, not CRUD)
Route::prefix('hadith/grading-levels')->group(function (): void {
    Route::get('/', [GradingLevelController::class, 'index'])->name('admin.hadith.grading-levels.index');
    Route::get('/{grade}', [GradingLevelController::class, 'show'])->name('admin.hadith.grading-levels.show');
    Route::get('/{grade}/statistics', [GradingLevelController::class, 'statistics'])->name('admin.hadith.grading-levels.statistics');
});
