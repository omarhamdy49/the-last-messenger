<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\Blog\ArticleController;
use App\Http\Controllers\Admin\Blog\CategoryController;
use App\Http\Controllers\Admin\Blog\SeriesController;
use App\Http\Controllers\Admin\Blog\TagController;
use Illuminate\Support\Facades\Route;

// Article Routes
Route::prefix('articles')->group(function (): void {
    // API endpoints (must be before resource routes to avoid conflicts)
    Route::get('/api', [ArticleController::class, 'api'])->name('admin.articles.api');
    Route::get('/stats', [ArticleController::class, 'stats'])->name('admin.articles.stats');

    // Bulk actions
    Route::post('/bulk-action', [ArticleController::class, 'bulkAction'])->name('admin.articles.bulk-action');
    Route::post('/bulk-delete', [ArticleController::class, 'bulkDelete'])->name('admin.articles.bulk-delete');
    Route::post('/bulk-publish', [ArticleController::class, 'bulkPublish'])->name('admin.articles.bulk-publish');
    Route::post('/bulk-archive', [ArticleController::class, 'bulkArchive'])->name('admin.articles.bulk-archive');

    // Search
    Route::get('/search', [ArticleController::class, 'search'])->name('admin.articles.search');
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

// Article trash route
Route::get('articles/trash', [ArticleController::class, 'trash'])->name('admin.articles.trash');

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
    Route::post('/reorder-articles', [SeriesController::class, 'reorderArticles'])->name('admin.series.reorder-articles');
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
