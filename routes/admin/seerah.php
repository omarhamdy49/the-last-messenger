<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\Seerah\SeerahEventController;
use Illuminate\Support\Facades\Route;

// Seerah Event Routes
Route::prefix('seerah-events')->group(function (): void {
    Route::get('/search', [SeerahEventController::class, 'search'])->name('admin.seerah-events.search');
    Route::post('/bulk-delete', [SeerahEventController::class, 'bulkDelete'])->name('admin.seerah-events.bulk-delete');
});
Route::resource('seerah-events', SeerahEventController::class)->names([
    'index' => 'admin.seerah-events.index',
    'create' => 'admin.seerah-events.create',
    'store' => 'admin.seerah-events.store',
    'show' => 'admin.seerah-events.show',
    'edit' => 'admin.seerah-events.edit',
    'update' => 'admin.seerah-events.update',
    'destroy' => 'admin.seerah-events.destroy',
]);
