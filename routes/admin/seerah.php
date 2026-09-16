<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\Seerah\SeerahEventController;
use App\Http\Controllers\Admin\Seerah\SeerahEventTypeController;
use App\Http\Controllers\Admin\Seerah\SeerahMilestoneController;
use Illuminate\Support\Facades\Route;

// Seerah Event Routes
Route::prefix('seerah-events')->group(function (): void {
    Route::get('/articles', [SeerahEventController::class, 'articles'])->name('admin.seerah-events.articles');
    Route::get('/search', [SeerahEventController::class, 'search'])->name('admin.seerah-events.search');
    Route::post('/bulk-delete', [SeerahEventController::class, 'bulkDelete'])->name('admin.seerah-events.bulk-delete');
    Route::post('/bulk-status', [SeerahEventController::class, 'bulkUpdateStatus'])->name('admin.seerah-events.bulk-status');
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

// Seerah Event Type Routes (DB-driven taxonomy)
Route::prefix('seerah-event-types')->group(function (): void {
    Route::post('/bulk-delete', [SeerahEventTypeController::class, 'bulkDelete'])->name('admin.seerah-event-types.bulk-delete');
    Route::post('/{id}/toggle-active', [SeerahEventTypeController::class, 'toggleActive'])->name('admin.seerah-event-types.toggle-active');
});
Route::resource('seerah-event-types', SeerahEventTypeController::class)->names([
    'index' => 'admin.seerah-event-types.index',
    'create' => 'admin.seerah-event-types.create',
    'store' => 'admin.seerah-event-types.store',
    'show' => 'admin.seerah-event-types.show',
    'edit' => 'admin.seerah-event-types.edit',
    'update' => 'admin.seerah-event-types.update',
    'destroy' => 'admin.seerah-event-types.destroy',
]);

// Seerah Milestone Routes (الحِقَب — chronological eras grouping events)
Route::prefix('seerah-milestones')->group(function (): void {
    Route::post('/bulk-delete', [SeerahMilestoneController::class, 'bulkDelete'])->name('admin.seerah-milestones.bulk-delete');
    Route::post('/{id}/toggle-active', [SeerahMilestoneController::class, 'toggleActive'])->name('admin.seerah-milestones.toggle-active');
});
Route::resource('seerah-milestones', SeerahMilestoneController::class)->names([
    'index' => 'admin.seerah-milestones.index',
    'create' => 'admin.seerah-milestones.create',
    'store' => 'admin.seerah-milestones.store',
    'show' => 'admin.seerah-milestones.show',
    'edit' => 'admin.seerah-milestones.edit',
    'update' => 'admin.seerah-milestones.update',
    'destroy' => 'admin.seerah-milestones.destroy',
]);
