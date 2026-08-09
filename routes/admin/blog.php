<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\Blog\ArticleController;
use App\Http\Controllers\Admin\Blog\ArticleModuleController;
use App\Http\Controllers\Admin\Blog\CategoryController;
use App\Http\Controllers\Admin\Blog\SeriesController;
use App\Http\Controllers\Admin\Blog\TagController;
use Illuminate\Support\Facades\Route;

// Article Routes
// Every literal-segment route lives in this group so it is registered before
// Route::resource('articles'), otherwise `articles/{article}` swallows it.
Route::prefix('articles')->group(function (): void {
    // Authenticated module lookups used by the article WYSIWYG/block editor.
    Route::get('/module-options/surahs', [ArticleModuleController::class, 'surahs'])
        ->name('admin.articles.modules.surahs');
    Route::get('/module-options/surahs/{surah}/verses', [ArticleModuleController::class, 'verses'])
        ->whereNumber('surah')
        ->name('admin.articles.modules.verses');
    Route::get('/module-options/hadith-collections', [ArticleModuleController::class, 'hadithCollections'])
        ->name('admin.articles.modules.hadith-collections');
    Route::get('/module-options/hadiths', [ArticleModuleController::class, 'hadiths'])
        ->name('admin.articles.modules.hadiths');

    // Statistics
    Route::get('/statistics', [ArticleController::class, 'statistics'])->name('admin.articles.statistics');

    // Bulk actions
    Route::post('/bulk-delete', [ArticleController::class, 'bulkDestroy'])->name('admin.articles.bulk-delete');
    Route::post('/bulk-publish', [ArticleController::class, 'bulkPublish'])->name('admin.articles.bulk-publish');

    // Search
    Route::get('/search', [ArticleController::class, 'search'])->name('admin.articles.search');

    // Trash listing
    Route::get('/trash', [ArticleController::class, 'trash'])->name('admin.articles.trash');
});

// Article resource routes
Route::resource('articles', ArticleController::class)->names([
    'index' => 'admin.articles.index',
    'create' => 'admin.articles.create',
    'store' => 'admin.articles.store',
    'show' => 'admin.articles.show',
    'edit' => 'admin.articles.edit',
    'update' => 'admin.articles.update',
    'destroy' => 'admin.articles.destroy',
]);
Route::get('articles/{article}/preview', [ArticleController::class, 'preview'])->name('admin.articles.preview');

// Article toggle routes (must be after resource to avoid route conflicts)
Route::post('articles/{article}/toggle-feature', [ArticleController::class, 'toggleFeature'])->name('admin.articles.toggle-feature');
Route::post('articles/{article}/toggle-pin', [ArticleController::class, 'togglePin'])->name('admin.articles.toggle-pin');
Route::post('articles/{article}/duplicate', [ArticleController::class, 'duplicate'])->name('admin.articles.duplicate');
Route::post('articles/{article}/views', [ArticleController::class, 'incrementViews'])->name('admin.articles.increment-views');

// Trashed-article actions (linked from admin.articles.trash)
Route::post('articles/{id}/restore', [ArticleController::class, 'restore'])->name('admin.articles.restore');
Route::delete('articles/{id}/force-delete', [ArticleController::class, 'forceDelete'])->name('admin.articles.force-delete');

// Category Routes
Route::prefix('categories')->group(function (): void {
    // Special endpoints (must be before resource routes to avoid conflicts)
    Route::get('/tree', [CategoryController::class, 'tree'])->name('admin.categories.tree');
    Route::get('/search', [CategoryController::class, 'search'])->name('admin.categories.search');
    Route::post('/bulk-delete', [CategoryController::class, 'bulkDelete'])->name('admin.categories.bulk-delete');
    Route::post('/reorder', [CategoryController::class, 'reorder'])->name('admin.categories.reorder');
});
Route::resource('categories', CategoryController::class)->names([
    'index' => 'admin.categories.index',
    'create' => 'admin.categories.create',
    'store' => 'admin.categories.store',
    'show' => 'admin.categories.show',
    'edit' => 'admin.categories.edit',
    'update' => 'admin.categories.update',
    'destroy' => 'admin.categories.destroy',
]);
// Post-resource routes (with ID parameter)
Route::post('categories/{id}/restore', [CategoryController::class, 'restore'])->name('admin.categories.restore');
Route::post('categories/{id}/duplicate', [CategoryController::class, 'duplicate'])->name('admin.categories.duplicate');

// Tag Routes
Route::prefix('tags')->group(function (): void {
    // Special endpoints (must be before resource routes to avoid conflicts)
    Route::get('/search', [TagController::class, 'search'])->name('admin.tags.search');
    Route::get('/unused', [TagController::class, 'unused'])->name('admin.tags.unused');
    Route::post('/merge', [TagController::class, 'merge'])->name('admin.tags.merge');
    Route::post('/bulk-delete', [TagController::class, 'bulkDelete'])->name('admin.tags.bulk-delete');
});
Route::resource('tags', TagController::class)->names([
    'index' => 'admin.tags.index',
    'create' => 'admin.tags.create',
    'store' => 'admin.tags.store',
    'show' => 'admin.tags.show',
    'edit' => 'admin.tags.edit',
    'update' => 'admin.tags.update',
    'destroy' => 'admin.tags.destroy',
]);

// Series Routes
Route::prefix('series')->group(function (): void {
    Route::post('/bulk-delete', [SeriesController::class, 'bulkDelete'])->name('admin.series.bulk-delete');
    // reorderArticles() takes the series id as its second argument.
    Route::post('/{id}/reorder-articles', [SeriesController::class, 'reorderArticles'])->name('admin.series.reorder-articles');
});
Route::resource('series', SeriesController::class)->names([
    'index' => 'admin.series.index',
    'create' => 'admin.series.create',
    'store' => 'admin.series.store',
    'show' => 'admin.series.show',
    'edit' => 'admin.series.edit',
    'update' => 'admin.series.update',
    'destroy' => 'admin.series.destroy',
]);
