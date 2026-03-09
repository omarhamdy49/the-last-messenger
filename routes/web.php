<?php

use App\Http\Controllers\LanguageController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('public.home');
})->name('public.home');

Route::get('/su/portal', function () {
    return redirect()->route('admin.dashboard');
})->middleware(['web', 'auth', 'admin', 'throttle:60,1'])
    ->name('portal.entry');

Route::get('/su/portal/v1/admin', function () {
    return redirect()->route('admin.dashboard');
})->middleware(['web', 'auth', 'admin', 'throttle:60,1'])
    ->name('portal.admin.entry');

// Language switching route
Route::post('/language/switch', [LanguageController::class, 'switch'])->name('language.switch');
