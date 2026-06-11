<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\Genealogy\GenealogyController;
use Illuminate\Support\Facades\Route;

// Genealogy Routes
Route::prefix('genealogy')->group(function (): void {
    Route::get('/search', [GenealogyController::class, 'search'])->name('admin.genealogy.search');
    Route::post('/bulk-delete', [GenealogyController::class, 'bulkDelete'])->name('admin.genealogy.bulk-delete');
});
Route::resource('genealogy', GenealogyController::class)->names([
    'index' => 'admin.genealogy.index',
    'create' => 'admin.genealogy.create',
    'store' => 'admin.genealogy.store',
    'show' => 'admin.genealogy.show',
    'edit' => 'admin.genealogy.edit',
    'update' => 'admin.genealogy.update',
    'destroy' => 'admin.genealogy.destroy',
]);
