<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\Genealogy\GenealogyController;
use Illuminate\Support\Facades\Route;

// Genealogy Routes
Route::prefix('genealogy')->group(function (): void {
    Route::get('/search', [GenealogyController::class, 'search'])->name('admin.genealogy.search');
    Route::post('/bulk-delete', [GenealogyController::class, 'bulkDelete'])->name('admin.genealogy.bulk-delete');
    Route::post('/{person}/relationships', [GenealogyController::class, 'storeRelationship'])->name('admin.genealogy.relationships.store');
    Route::put('/relationships/{relationship}', [GenealogyController::class, 'updateRelationship'])->name('admin.genealogy.relationships.update');
    Route::delete('/relationships/{relationship}', [GenealogyController::class, 'destroyRelationship'])->name('admin.genealogy.relationships.destroy');
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
