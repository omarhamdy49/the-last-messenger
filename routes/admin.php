<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\AnalyticsController;
use App\Http\Controllers\Admin\AuditController;
use App\Http\Controllers\Admin\BulkOperationController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ExportController;
use App\Http\Controllers\Admin\Hadith\HadithBookController;
use App\Http\Controllers\Admin\Hadith\HadithChapterController;
use App\Http\Controllers\Admin\Hadith\HadithCollectionController;
use App\Http\Controllers\Admin\Hadith\HadithController;
use App\Http\Controllers\Admin\ImportController;
use App\Http\Controllers\Admin\LanguageSettingsController;
use App\Http\Controllers\Admin\MediaController;
// use App\Http\Controllers\Admin\MediaCategoryController;
// use App\Http\Controllers\Admin\MediaProgramController;
use App\Http\Controllers\Admin\MediaSettingsController;
// use App\Http\Controllers\Admin\MediaUploadController;
// use App\Http\Controllers\Admin\MediaLibraryController;
// use App\Http\Controllers\Admin\MediaFolderController;
// use App\Http\Controllers\Admin\MediaTagController;
// use App\Http\Controllers\Admin\MediaSearchController;
// use App\Http\Controllers\Admin\MediaBulkController;
// use App\Http\Controllers\Admin\MediaCropController;
// use App\Http\Controllers\Admin\MediaUsageController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SchedulerController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\VersionController;
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

// Admin Web Login Routes (no auth required).
// Credential + role checks live in AdminAuthController, which only establishes a
// session for admin-tier users so a public account can never reach the portal.
Route::middleware(['web'])->group(function () {
    Route::get('su/portal/v1/admin/login', [AdminAuthController::class, 'showLogin'])
        ->name('login'); // Named 'login' for auth middleware redirect

    Route::post('su/portal/v1/admin/login', [AdminAuthController::class, 'login'])
        ->middleware('throttle:5,1')
        ->name('admin.web.login.submit');

    Route::post('su/portal/v1/admin/logout', [AdminAuthController::class, 'logout'])
        ->name('admin.logout');
});

// Main Dashboard Route (must be defined before the group to avoid override)
Route::get('su/portal/v1/admin/dashboard', [DashboardController::class, 'index'])
    ->middleware(['web', 'auth:admin', 'admin', 'role:super_admin|admin|editor,admin', 'throttle:60,1'])
    ->name('admin.dashboard');

// Analytics Route Alias
Route::get('su/portal/v1/admin/analytics', [AnalyticsController::class, 'dashboard'])
    ->middleware(['web', 'auth:admin', 'admin', 'role:super_admin|admin|editor,admin', 'throttle:60,1'])
    ->name('admin.analytics');

// Settings Route Placeholder (coming soon)
Route::get('su/portal/v1/admin/settings', function () {
    return view('admin.coming-soon', ['module' => 'Settings']);
})->middleware(['web', 'auth:admin', 'admin', 'role:super_admin|admin|editor,admin', 'throttle:60,1'])
    ->name('admin.settings');

// Programs & Episodes — now handled by full domain routes below

// Help Route
Route::get('su/portal/v1/admin/help', function () {
    return view('admin.help');
})->middleware(['web', 'auth:admin', 'admin', 'role:super_admin|admin|editor,admin', 'throttle:60,1'])
    ->name('admin.help');

// Profile Route
Route::get('su/portal/v1/admin/profile', function () {
    return view('admin.coming-soon', ['module' => 'Profile']);
})->middleware(['web', 'auth:admin', 'admin', 'role:super_admin|admin|editor,admin', 'throttle:60,1'])
    ->name('admin.profile');

Route::prefix('su/portal/v1/admin')
    ->middleware(['web', 'auth:admin', 'admin', 'role:super_admin|admin|editor,admin', 'throttle:60,1'])
    ->group(function (): void {

        // Dashboard Routes
        Route::prefix('dashboard')->name('admin.dashboard.')->group(function (): void {
            // Note: Main dashboard route (/) is defined above as 'admin.dashboard' for convenience
            // Route::get('/', [DashboardController::class, 'index'])->name('index');
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

        Route::get('hadith', [HadithController::class, 'index'])->name('admin.hadith.index');
        Route::get('hadith/{hadith}/edit', [HadithController::class, 'edit'])->name('admin.hadith.edit');
        Route::put('hadith/{hadith}', [HadithController::class, 'update'])->name('admin.hadith.update');

        Route::get('hadith-collections', [HadithCollectionController::class, 'index'])->name('admin.hadith-collections.index');
        Route::get('hadith-collections/{collection}', [HadithCollectionController::class, 'show'])->name('admin.hadith-collections.show');
        Route::put('hadith-collections/{collection}', [HadithCollectionController::class, 'update'])->name('admin.hadith-collections.update');

        Route::get('hadith-books', [HadithBookController::class, 'index'])->name('admin.hadith-books.index');
        Route::get('hadith-books/{book}/edit', [HadithBookController::class, 'edit'])->name('admin.hadith-books.edit');
        Route::put('hadith-books/{book}', [HadithBookController::class, 'update'])->name('admin.hadith-books.update');

        Route::get('hadith-chapters', [HadithChapterController::class, 'index'])->name('admin.hadith-chapters.index');
        Route::get('hadith-chapters/{chapter}/edit', [HadithChapterController::class, 'edit'])->name('admin.hadith-chapters.edit');
        Route::put('hadith-chapters/{chapter}', [HadithChapterController::class, 'update'])->name('admin.hadith-chapters.update');

        // Analytics Routes
        Route::prefix('analytics')->name('admin.analytics.')->group(function (): void {
            Route::get('/', [AnalyticsController::class, 'dashboard'])->name('index');
            Route::get('/stats', [AnalyticsController::class, 'getStats'])->name('stats');
            Route::get('/charts/{type}', [AnalyticsController::class, 'getChartData'])->name('charts');
            Route::get('/engagement', [AnalyticsController::class, 'getEngagement'])->name('engagement');
            Route::get('/trends', [AnalyticsController::class, 'getTrends'])->name('trends');
            Route::get('/top-content', [AnalyticsController::class, 'getTopContent'])->name('top-content');
            Route::get('/module/{module}', [AnalyticsController::class, 'getModuleAnalytics'])->name('module');
            Route::get('/comparison', [AnalyticsController::class, 'getComparison'])->name('comparison');
            Route::get('/recent-activity', [AnalyticsController::class, 'getRecentActivity'])->name('recent-activity');
            Route::get('/performance', [AnalyticsController::class, 'getPerformance'])->name('performance');
            Route::get('/export', [AnalyticsController::class, 'exportAnalytics'])->name('export');
            Route::post('/cache/clear', [AnalyticsController::class, 'clearCache'])->name('cache.clear');
        });

        // Components Demo Route (for development)
        Route::get('/components-demo', function () {
            return view('admin.components-demo');
        })->name('admin.components.demo');

        Route::prefix('media-library')->name('admin.media-library.')->group(function (): void {
            Route::get('/', [MediaController::class, 'index'])->name('index');
            Route::post('/', [MediaController::class, 'store'])->name('store');
            Route::post('/bulk-upload', [MediaController::class, 'bulkUpload'])->name('bulk-upload');
            Route::post('/bulk-delete', [MediaController::class, 'bulkDestroy'])->name('bulk-delete');
            Route::post('/bulk-update-tags', [MediaController::class, 'updateTags'])->name('bulk-update-tags');
            Route::get('/statistics', [MediaController::class, 'statistics'])->name('statistics');
            Route::get('/collections', [MediaController::class, 'collections'])->name('collections');
            Route::get('/tags', [MediaController::class, 'tags'])->name('tags');
            Route::get('/search', [MediaController::class, 'search'])->name('search');
            Route::get('/settings', [MediaSettingsController::class, 'index'])->name('settings');
            Route::post('/settings/presets', [MediaSettingsController::class, 'storePreset'])->name('settings.presets.store');
            Route::put('/settings/presets/{preset}', [MediaSettingsController::class, 'updatePreset'])->name('settings.presets.update');
            Route::delete('/settings/presets/{preset}', [MediaSettingsController::class, 'destroyPreset'])->name('settings.presets.destroy');
            Route::post('/settings/presets/reorder', [MediaSettingsController::class, 'reorder'])->name('settings.presets.reorder');
            Route::get('/files/{media:public_key}', [MediaController::class, 'file'])->name('file');
            Route::get('/files/{media:public_key}/variants/{variant}', [MediaController::class, 'variant'])->name('variant');
            Route::get('/{media}', [MediaController::class, 'show'])->name('show');
            Route::put('/{media}', [MediaController::class, 'update'])->name('update');
            Route::delete('/{media}', [MediaController::class, 'destroy'])->name('destroy');
            Route::get('/{media}/usage', [MediaController::class, 'usage'])->name('usage');
            Route::post('/{media}/optimize', [MediaController::class, 'optimize'])->name('optimize');
            Route::post('/{media}/crop', [MediaController::class, 'crop'])->name('crop');
            Route::post('/{media}/regenerate-variants', [MediaController::class, 'regenerateVariants'])->name('regenerate-variants');
            Route::get('/{media:public_key}/download', [MediaController::class, 'download'])->name('download');
        });

        // DISABLED: Media Category Routes
        // Route::prefix('media-categories')->group(function (): void {
        //     Route::post('/bulk-delete', [MediaCategoryController::class, 'bulkDelete'])->name('admin.media-categories.bulk-delete');
        //     Route::post('/reorder', [MediaCategoryController::class, 'reorder'])->name('admin.media-categories.reorder');
        // });
        // Route::resource('media-categories', MediaCategoryController::class)->names([
        //     'index' => 'admin.media-categories.index',
        //     'create' => 'admin.media-categories.create',
        //     'store' => 'admin.media-categories.store',
        //     'show' => 'admin.media-categories.show',
        //     'edit' => 'admin.media-categories.edit',
        //     'update' => 'admin.media-categories.update',
        //     'destroy' => 'admin.media-categories.destroy',
        // ]);

        // DISABLED: Media Program Routes
        // Route::prefix('media-programs')->group(function (): void {
        //     Route::get('/search', [MediaProgramController::class, 'search'])->name('admin.media-programs.search');
        //     Route::post('/bulk-delete', [MediaProgramController::class, 'bulkDelete'])->name('admin.media-programs.bulk-delete');
        // });
        // Route::resource('media-programs', MediaProgramController::class)->names([
        //     'index' => 'admin.media-programs.index',
        //     'create' => 'admin.media-programs.create',
        //     'store' => 'admin.media-programs.store',
        //     'show' => 'admin.media-programs.show',
        //     'edit' => 'admin.media-programs.edit',
        //     'update' => 'admin.media-programs.update',
        //     'destroy' => 'admin.media-programs.destroy',
        // ]);

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
        Route::prefix('activity-logs')->name('admin.activity-logs.')->group(function (): void {
            Route::get('/', [ActivityLogController::class, 'index'])->name('index');
            Route::get('/user/{userId}', [ActivityLogController::class, 'userActivity'])->name('user');
            Route::get('/model/{type}/{id}', [ActivityLogController::class, 'modelHistory'])->name('model');
            Route::get('/{id}', [ActivityLogController::class, 'show'])->name('show');
        });

        // Language Settings Routes
        Route::prefix('settings/languages')->name('admin.settings.languages.')->group(function (): void {
            Route::get('/', [LanguageSettingsController::class, 'index'])->name('index');
            Route::get('/list', [LanguageSettingsController::class, 'list'])->name('list');
            Route::post('/', [LanguageSettingsController::class, 'store'])->name('store');
            Route::put('/{code}', [LanguageSettingsController::class, 'update'])->name('update');
            Route::delete('/{code}', [LanguageSettingsController::class, 'destroy'])->name('destroy');
            Route::post('/{code}/set-default', [LanguageSettingsController::class, 'setDefault'])->name('set-default');
            Route::post('/{code}/toggle', [LanguageSettingsController::class, 'toggle'])->name('toggle');
            Route::post('/reorder', [LanguageSettingsController::class, 'reorder'])->name('reorder');
        });

        // Export Routes
        Route::prefix('export')->name('admin.export.')->group(function (): void {
            Route::post('/', [ExportController::class, 'export'])->name('export');
            Route::get('/{module}/columns', [ExportController::class, 'getColumns'])->name('columns');
            Route::get('/history', [ExportController::class, 'history'])->name('history');
            Route::get('/stats', [ExportController::class, 'stats'])->name('stats');
            Route::delete('/cleanup', [ExportController::class, 'cleanup'])->name('cleanup');
        });

        // Import Routes
        Route::prefix('import')->name('admin.import.')->group(function (): void {
            Route::get('/{module}', [ImportController::class, 'importForm'])->name('form');
            Route::post('/{module}/parse', [ImportController::class, 'parseFile'])->name('parse');
            Route::post('/{module}/validate', [ImportController::class, 'validateData'])->name('validate');
            Route::post('/{module}/execute', [ImportController::class, 'execute'])->name('execute');
            Route::get('/{importId}/preview', [ImportController::class, 'preview'])->name('preview');
            Route::get('/{importId}/status', [ImportController::class, 'status'])->name('status');
            Route::get('/history', [ImportController::class, 'history'])->name('history');
            Route::get('/{importId}/errors', [ImportController::class, 'errors'])->name('errors');
            Route::delete('/{importId}', [ImportController::class, 'destroy'])->name('destroy');
        });

        // Content Scheduler Routes
        Route::prefix('scheduler')->name('admin.scheduler.')->group(function (): void {
            Route::get('/', [SchedulerController::class, 'index'])->name('index');
            Route::get('/create', [SchedulerController::class, 'create'])->name('create');
            Route::post('/', [SchedulerController::class, 'store'])->name('store');
            Route::get('/{id}', [SchedulerController::class, 'show'])->name('show');
            Route::get('/{id}/edit', [SchedulerController::class, 'edit'])->name('edit');
            Route::put('/{id}', [SchedulerController::class, 'update'])->name('update');
            Route::delete('/{id}', [SchedulerController::class, 'destroy'])->name('destroy');
            Route::get('/{type}/{id}/history', [SchedulerController::class, 'history'])->name('history');
        });

        // Bulk Operations Routes
        Route::prefix('bulk-operations')->name('admin.bulk-operations.')->group(function (): void {
            Route::post('/', [BulkOperationController::class, 'store'])->name('store');
            Route::get('/', [BulkOperationController::class, 'index'])->name('index');
            Route::get('/stats', [BulkOperationController::class, 'stats'])->name('stats');
            Route::get('/{id}/status', [BulkOperationController::class, 'status'])->name('status');
            Route::get('/{id}/download', [BulkOperationController::class, 'download'])->name('download');
            Route::delete('/{id}', [BulkOperationController::class, 'destroy'])->name('destroy');
        });

        // Version Control Routes
        Route::prefix('versions')->name('admin.versions.')->group(function (): void {
            Route::get('/stats', [VersionController::class, 'stats'])->name('stats');
            Route::get('/{type}/{id}', [VersionController::class, 'getVersions'])->name('list');
            Route::get('/{versionId}', [VersionController::class, 'getVersion'])->name('show');
            Route::get('/{v1}/{v2}/compare', [VersionController::class, 'compare'])->name('compare');
            Route::post('/revert', [VersionController::class, 'revert'])->name('revert');
            Route::get('/{type}/{id}/timeline', [VersionController::class, 'getTimeline'])->name('timeline');
            Route::post('/{type}/{id}/prune', [VersionController::class, 'prune'])->name('prune');
        });

        // Audit Log Routes
        Route::prefix('audit')->name('admin.audit.')->group(function (): void {
            Route::get('/', [AuditController::class, 'index'])->name('index');
            Route::get('/stats', [AuditController::class, 'stats'])->name('stats');
            Route::get('/export', [AuditController::class, 'export'])->name('export');
            Route::get('/activity-report', [AuditController::class, 'activityReport'])->name('activity-report');
            Route::get('/heatmap', [AuditController::class, 'heatmap'])->name('heatmap');
            Route::post('/generate-report', [AuditController::class, 'generateReport'])->name('generate-report');
            Route::post('/prune', [AuditController::class, 'prune'])->name('prune');
            Route::get('/{id}', [AuditController::class, 'show'])->name('show');
            Route::get('/user/{userId}', [AuditController::class, 'userActivity'])->name('user-activity');
            Route::get('/user/{userId}/report', [AuditController::class, 'userActivityReport'])->name('user-activity-report');
            Route::get('/ip/{ipAddress}', [AuditController::class, 'logsByIp'])->name('logs-by-ip');
            Route::get('/action/{action}', [AuditController::class, 'logsByAction'])->name('logs-by-action');
        });

        // Domain route files
        require __DIR__.'/admin/blog.php';
        require __DIR__.'/admin/quran.php';
        require __DIR__.'/admin/seerah.php';
        require __DIR__.'/admin/people.php';
        require __DIR__.'/admin/sahaba.php';
        require __DIR__.'/admin/genealogy.php';
        require __DIR__.'/admin/media.php';
        require __DIR__.'/admin/content.php';
    });
