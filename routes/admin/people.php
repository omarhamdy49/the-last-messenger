<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\MuslimController;
use Illuminate\Support\Facades\Route;

// Static segments before the resource routes so `picker` and `roles` can never
// be read as a {muslim} binding.
Route::prefix('muslims')->group(function (): void {
    Route::get('/picker', [MuslimController::class, 'picker'])
        ->middleware('throttle:search')
        ->name('admin.muslims.picker');
    Route::get('/roles', [MuslimController::class, 'roles'])->name('admin.muslims.roles');
});

Route::resource('muslims', MuslimController::class)
    ->except(['show'])
    ->names([
        'index' => 'admin.muslims.index',
        'create' => 'admin.muslims.create',
        'store' => 'admin.muslims.store',
        'edit' => 'admin.muslims.edit',
        'update' => 'admin.muslims.update',
        'destroy' => 'admin.muslims.destroy',
    ]);
