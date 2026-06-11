<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\Sahaba\SahabiController;
use Illuminate\Support\Facades\Route;

// Sahaba Routes
Route::prefix('sahaba')->group(function (): void {
    Route::get('/search', [SahabiController::class, 'search'])->name('admin.sahaba.search');
    Route::post('/bulk-delete', [SahabiController::class, 'bulkDelete'])->name('admin.sahaba.bulk-delete');
});
Route::resource('sahaba', SahabiController::class)->names([
    'index' => 'admin.sahaba.index',
    'create' => 'admin.sahaba.create',
    'store' => 'admin.sahaba.store',
    'show' => 'admin.sahaba.show',
    'edit' => 'admin.sahaba.edit',
    'update' => 'admin.sahaba.update',
    'destroy' => 'admin.sahaba.destroy',
]);
