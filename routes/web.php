<?php

use App\Http\Controllers\LanguageController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/su/portal/v1/admin');
});

// Language switching route
Route::post('/language/switch', [LanguageController::class, 'switch'])->name('language.switch');
