<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\SahabiController;
use Illuminate\Support\Facades\Route;

// Static segments before the resource routes so `picker` and `roles` can never
// be read as a {muslim} binding.
Route::prefix('sahaba')->group(function (): void {
    Route::get('/picker', [SahabiController::class, 'picker'])
        ->middleware('throttle:search')
        ->name('admin.sahaba.picker');
    // Paginated browser behind the parent-picker modal.
    Route::get('/browse', [SahabiController::class, 'browse'])
        ->middleware('throttle:search')
        ->name('admin.sahaba.browse');
    Route::get('/roles', [SahabiController::class, 'roles'])->name('admin.sahaba.roles');
    // Bulk actions the existing index view posts to.
    Route::post('/bulk-delete', [SahabiController::class, 'bulkDelete'])->name('admin.sahaba.bulk-delete');
    Route::post('/bulk-featured', [SahabiController::class, 'bulkSetFeatured'])->name('admin.sahaba.bulk-featured');
    Route::post('/bulk-status', [SahabiController::class, 'bulkSetPublished'])->name('admin.sahaba.bulk-status');

    // Single-row flag toggles from the list and the profile header. PATCH with
    // a real form method, not a spoofed one in a JSON body — Symfony reads the
    // form bag, so a JSON `_method` stays POST and 405s.
    Route::patch('/{muslim}/toggle/{flag}', [SahabiController::class, 'toggle'])
        ->whereNumber('muslim')
        ->whereIn('flag', ['published', 'featured', 'tree'])
        ->name('admin.sahaba.toggle');
});

// The URL segment is `sahaba` (the public-facing concept) but the bound model
// is a Muslim — `muslims` is the internal identity table and a companion is a
// muslim carrying the `sahabi` role. Naming the parameter explicitly keeps the
// controller signatures and FormRequest lookups on `muslim`.
Route::resource('sahaba', SahabiController::class)
    ->parameters(['sahaba' => 'muslim'])
    ->names([
        'index' => 'admin.sahaba.index',
        'create' => 'admin.sahaba.create',
        'store' => 'admin.sahaba.store',
        'show' => 'admin.sahaba.show',
        'edit' => 'admin.sahaba.edit',
        'update' => 'admin.sahaba.update',
        'destroy' => 'admin.sahaba.destroy',
    ]);
