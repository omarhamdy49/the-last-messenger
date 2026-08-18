<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\LocationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Shared module admin routes
|--------------------------------------------------------------------------
|
| Cross-cutting reference data consumed by more than one domain. Locations is
| the first: Seerah, People and Quran all point at it.
|
*/

// Static segments MUST be registered before Route::resource, or /search and
// /bulk-delete resolve as the {location} parameter (see CLAUDE.md).
Route::prefix('locations')->group(function (): void {
    Route::get('/search', [LocationController::class, 'search'])->name('admin.locations.search');
    Route::post('/bulk-delete', [LocationController::class, 'bulkDelete'])->name('admin.locations.bulk-delete');
    Route::post('/{location}/toggle-active', [LocationController::class, 'toggleActive'])
        ->whereNumber('location')
        ->name('admin.locations.toggle-active');
});

Route::resource('locations', LocationController::class)
    ->whereNumber('location')
    ->names([
        'index' => 'admin.locations.index',
        'create' => 'admin.locations.create',
        'store' => 'admin.locations.store',
        'show' => 'admin.locations.show',
        'edit' => 'admin.locations.edit',
        'update' => 'admin.locations.update',
        'destroy' => 'admin.locations.destroy',
    ]);
