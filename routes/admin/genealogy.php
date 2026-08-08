<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\GenealogyController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Genealogy
|--------------------------------------------------------------------------
|
| Descent (father/mother) and non-descent edges over the `muslims` identity.
| The bound parameter is `muslim` throughout — genealogy has no records of its
| own, it edits the person rows and the `muslim_relationships` edges between
| them.
|
*/

Route::prefix('genealogy')->name('admin.genealogy.')->group(function (): void {
    Route::get('/', [GenealogyController::class, 'index'])->name('index');

    // Nested under the person so a relationship can never be edited without
    // naming which side of it the editor came from.
    Route::prefix('{muslim}')->group(function (): void {
        Route::get('/', [GenealogyController::class, 'show'])->name('show');
        Route::match(['put', 'patch'], '/', [GenealogyController::class, 'update'])->name('update');

        Route::post('/relationships', [GenealogyController::class, 'storeRelationship'])
            ->name('relationships.store');
        Route::match(['put', 'patch'], '/relationships/{relationship}', [GenealogyController::class, 'updateRelationship'])
            ->name('relationships.update');
        Route::delete('/relationships/{relationship}', [GenealogyController::class, 'destroyRelationship'])
            ->name('relationships.destroy');
    });
});
