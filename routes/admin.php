<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\ArticleController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\GenealogyController;
use App\Http\Controllers\Admin\HadithController;
use App\Http\Controllers\Admin\LanguageSettingsController;
use App\Http\Controllers\Admin\MediaProgramController;
use App\Http\Controllers\Admin\MediaUploadController;
use App\Http\Controllers\Admin\QuranController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SahabiController;
use App\Http\Controllers\Admin\SeerahEventController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Portal Routes
|--------------------------------------------------------------------------
|
| All admin routes are prefixed with /su/portal/v1/admin
| Protected by auth:sanctum and admin middleware
| Rate limited to 60 requests per minute
|
*/

// Admin Web Login Routes (no auth required)
// Apply web middleware for session and CSRF support
Route::middleware(['web'])->group(function () {
    Route::get('su/portal/v1/admin/login', function () {
        return view('admin.auth.login');
    })->name('login'); // Named 'login' for auth middleware redirect

    Route::post('su/portal/v1/admin/login', function (\Illuminate\Http\Request $request) {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            // Check if user has admin role
            if (! auth()->user()->hasAnyRole(['admin', 'super_admin', 'editor'])) {
                Auth::logout();

                return redirect()->back()->withErrors(['email' => 'ليس لديك صلاحية الوصول للوحة التحكم.']);
            }

            return redirect()->intended('/su/portal/v1/admin/dashboard');
        }

        return redirect()->back()->withErrors([
            'email' => 'بيانات الدخول غير صحيحة. يرجى المحاولة مرة أخرى.',
        ])->withInput($request->only('email'));
    })->name('admin.web.login.submit');

    Route::post('su/portal/v1/admin/logout', function (\Illuminate\Http\Request $request) {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    })->name('admin.logout');
});

Route::prefix('su/portal/v1/admin')
    ->middleware(['web', 'auth', 'admin', 'throttle:60,1'])
    ->group(function (): void {

        // Dashboard Routes
        Route::prefix('dashboard')->name('admin.dashboard.')->group(function (): void {
            Route::get('/', [DashboardController::class, 'index'])->name('index');
            Route::get('/statistics', [DashboardController::class, 'statistics'])->name('statistics');
            Route::get('/content-growth', [DashboardController::class, 'contentGrowth'])->name('content-growth');
            Route::get('/module-distribution', [DashboardController::class, 'moduleDistribution'])->name('module-distribution');
            Route::get('/activity-heatmap', [DashboardController::class, 'activityHeatmap'])->name('activity-heatmap');
            Route::get('/recent-activity', [DashboardController::class, 'recentActivity'])->name('recent-activity');
            Route::get('/quick-analytics', [DashboardController::class, 'quickAnalytics'])->name('quick-analytics');
            Route::get('/module-health', [DashboardController::class, 'moduleHealth'])->name('module-health');
            Route::get('/recent-content', [DashboardController::class, 'recentContent'])->name('recent-content');
            Route::get('/performance-metrics', [DashboardController::class, 'performanceMetrics'])->name('performance-metrics');

            // Legacy endpoints (for backward compatibility)
            Route::get('/growth', [DashboardController::class, 'growth'])->name('growth');
            Route::get('/popular', [DashboardController::class, 'popular'])->name('popular');
            Route::post('/cache/clear', [DashboardController::class, 'clearCache'])->name('cache.clear');
        });

        // Components Demo Route (for development)
        Route::get('/components-demo', function () {
            return view('admin.components-demo');
        })->name('admin.components.demo');

        // Media Upload Routes
        Route::prefix('media')->name('media.')->group(function (): void {
            Route::post('/upload', [MediaUploadController::class, 'upload'])->name('upload');
            Route::delete('/{id}', [MediaUploadController::class, 'delete'])->name('delete');
            Route::get('/list', [MediaUploadController::class, 'list'])->name('list');
            Route::post('/reorder', [MediaUploadController::class, 'reorder'])->name('reorder');
        });

        // Article Routes
        Route::prefix('articles')->group(function (): void {
            Route::get('/search', [ArticleController::class, 'search'])->name('admin.articles.search');
            Route::post('/bulk-delete', [ArticleController::class, 'bulkDelete'])->name('admin.articles.bulk-delete');
            Route::post('/bulk-publish', [ArticleController::class, 'bulkPublish'])->name('admin.articles.bulk-publish');
            Route::post('/bulk-archive', [ArticleController::class, 'bulkArchive'])->name('admin.articles.bulk-archive');
        });
        Route::resource('articles', ArticleController::class)->names([
            'index' => 'admin.articles.index',
            'create' => 'admin.articles.create',
            'store' => 'admin.articles.store',
            'show' => 'admin.articles.show',
            'edit' => 'admin.articles.edit',
            'update' => 'admin.articles.update',
            'destroy' => 'admin.articles.destroy',
        ]);

        // Hadith Routes
        Route::prefix('hadith')->group(function (): void {
            Route::get('/search', [HadithController::class, 'search'])->name('admin.hadith.search');
            Route::post('/bulk-delete', [HadithController::class, 'bulkDelete'])->name('admin.hadith.bulk-delete');
        });
        Route::resource('hadith', HadithController::class)->names([
            'index' => 'admin.hadith.index',
            'create' => 'admin.hadith.create',
            'store' => 'admin.hadith.store',
            'show' => 'admin.hadith.show',
            'edit' => 'admin.hadith.edit',
            'update' => 'admin.hadith.update',
            'destroy' => 'admin.hadith.destroy',
        ]);

        // Media Program Routes
        Route::prefix('media-programs')->group(function (): void {
            Route::get('/search', [MediaProgramController::class, 'search'])->name('admin.media-programs.search');
            Route::post('/bulk-delete', [MediaProgramController::class, 'bulkDelete'])->name('admin.media-programs.bulk-delete');
        });
        Route::resource('media-programs', MediaProgramController::class)->names([
            'index' => 'admin.media-programs.index',
            'create' => 'admin.media-programs.create',
            'store' => 'admin.media-programs.store',
            'show' => 'admin.media-programs.show',
            'edit' => 'admin.media-programs.edit',
            'update' => 'admin.media-programs.update',
            'destroy' => 'admin.media-programs.destroy',
        ]);

        // Quran Routes
        Route::prefix('quran')->group(function (): void {
            Route::get('/search', [QuranController::class, 'search'])->name('admin.quran.search');
            Route::post('/bulk-delete', [QuranController::class, 'bulkDelete'])->name('admin.quran.bulk-delete');
        });
        Route::resource('quran', QuranController::class)->names([
            'index' => 'admin.quran.index',
            'create' => 'admin.quran.create',
            'store' => 'admin.quran.store',
            'show' => 'admin.quran.show',
            'edit' => 'admin.quran.edit',
            'update' => 'admin.quran.update',
            'destroy' => 'admin.quran.destroy',
        ]);

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

        // User Management Routes (Super Admin Only)
        Route::prefix('users')->group(function (): void {
            Route::get('/search', [UserController::class, 'search'])->name('admin.users.search');
            Route::post('/bulk-delete', [UserController::class, 'bulkDelete'])->name('admin.users.bulk-delete');
            Route::post('/{id}/roles', [UserController::class, 'assignRoles'])->name('admin.users.assign-roles');
        });
        Route::resource('users', UserController::class)->names([
            'index' => 'admin.users.index',
            'create' => 'admin.users.create',
            'store' => 'admin.users.store',
            'show' => 'admin.users.show',
            'edit' => 'admin.users.edit',
            'update' => 'admin.users.update',
            'destroy' => 'admin.users.destroy',
        ]);

        // Role & Permission Management Routes (Super Admin Only)
        Route::prefix('roles')->group(function (): void {
            Route::post('/{id}/permissions', [RoleController::class, 'assignPermissions'])->name('admin.roles.assign-permissions');
        });
        Route::get('/permissions', [RoleController::class, 'permissions'])->name('admin.permissions.index');
        Route::resource('roles', RoleController::class)->names([
            'index' => 'admin.roles.index',
            'create' => 'admin.roles.create',
            'store' => 'admin.roles.store',
            'show' => 'admin.roles.show',
            'edit' => 'admin.roles.edit',
            'update' => 'admin.roles.update',
            'destroy' => 'admin.roles.destroy',
        ]);

        // Activity Log Routes
        Route::prefix('activity-logs')->name('activity-logs.')->group(function (): void {
            Route::get('/', [ActivityLogController::class, 'index'])->name('index');
            Route::get('/user/{userId}', [ActivityLogController::class, 'userActivity'])->name('user');
            Route::get('/model/{type}/{id}', [ActivityLogController::class, 'modelHistory'])->name('model');
            Route::get('/{id}', [ActivityLogController::class, 'show'])->name('show');
        });

        // Language Settings Routes
        Route::prefix('settings/languages')->name('settings.languages.')->group(function (): void {
            Route::get('/', [LanguageSettingsController::class, 'index'])->name('index');
            Route::get('/list', [LanguageSettingsController::class, 'list'])->name('list');
            Route::post('/', [LanguageSettingsController::class, 'store'])->name('store');
            Route::put('/{code}', [LanguageSettingsController::class, 'update'])->name('update');
            Route::delete('/{code}', [LanguageSettingsController::class, 'destroy'])->name('destroy');
            Route::post('/{code}/set-default', [LanguageSettingsController::class, 'setDefault'])->name('set-default');
            Route::post('/{code}/toggle', [LanguageSettingsController::class, 'toggle'])->name('toggle');
            Route::post('/reorder', [LanguageSettingsController::class, 'reorder'])->name('reorder');
        });
    });
