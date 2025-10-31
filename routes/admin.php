<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\AnalyticsController;
use App\Http\Controllers\Admin\ArticleController;
use App\Http\Controllers\Admin\AuditController;
use App\Http\Controllers\Admin\BulkOperationController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ExportController;
use App\Http\Controllers\Admin\GenealogyController;
use App\Http\Controllers\Admin\HadithController;
use App\Http\Controllers\Admin\HadithNarratorController;
use App\Http\Controllers\Admin\ImportController;
use App\Http\Controllers\Admin\LanguageSettingsController;
use App\Http\Controllers\Admin\MediaCategoryController;
use App\Http\Controllers\Admin\MediaProgramController;
use App\Http\Controllers\Admin\MediaUploadController;
use App\Http\Controllers\Admin\QuranController;
use App\Http\Controllers\Admin\ReciterController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SahabiController;
use App\Http\Controllers\Admin\SchedulerController;
use App\Http\Controllers\Admin\SeerahEventController;
use App\Http\Controllers\Admin\SeriesController;
use App\Http\Controllers\Admin\SurahController;
use App\Http\Controllers\Admin\TafseerController;
use App\Http\Controllers\Admin\TagController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\VerseController;
use App\Http\Controllers\Admin\VersionController;
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

        // Category Routes
        Route::prefix('categories')->group(function (): void {
            Route::post('/bulk-delete', [CategoryController::class, 'bulkDelete'])->name('admin.categories.bulk-delete');
            Route::post('/reorder', [CategoryController::class, 'reorder'])->name('admin.categories.reorder');
        });
        Route::resource('categories', CategoryController::class)->names([
            'index' => 'admin.categories.index',
            'create' => 'admin.categories.create',
            'store' => 'admin.categories.store',
            'show' => 'admin.categories.show',
            'edit' => 'admin.categories.edit',
            'update' => 'admin.categories.update',
            'destroy' => 'admin.categories.destroy',
        ]);

        // Tag Routes
        Route::prefix('tags')->group(function (): void {
            Route::post('/merge', [TagController::class, 'merge'])->name('admin.tags.merge');
            Route::post('/bulk-delete', [TagController::class, 'bulkDelete'])->name('admin.tags.bulk-delete');
        });
        Route::resource('tags', TagController::class)->names([
            'index' => 'admin.tags.index',
            'create' => 'admin.tags.create',
            'store' => 'admin.tags.store',
            'show' => 'admin.tags.show',
            'edit' => 'admin.tags.edit',
            'update' => 'admin.tags.update',
            'destroy' => 'admin.tags.destroy',
        ]);

        // Series Routes
        Route::prefix('series')->group(function (): void {
            Route::post('/bulk-delete', [SeriesController::class, 'bulkDelete'])->name('admin.series.bulk-delete');
            Route::post('/reorder-articles', [SeriesController::class, 'reorderArticles'])->name('admin.series.reorder-articles');
        });
        Route::resource('series', SeriesController::class)->names([
            'index' => 'admin.series.index',
            'create' => 'admin.series.create',
            'store' => 'admin.series.store',
            'show' => 'admin.series.show',
            'edit' => 'admin.series.edit',
            'update' => 'admin.series.update',
            'destroy' => 'admin.series.destroy',
        ]);

        // Media Category Routes
        Route::prefix('media-categories')->group(function (): void {
            Route::post('/bulk-delete', [MediaCategoryController::class, 'bulkDelete'])->name('admin.media-categories.bulk-delete');
            Route::post('/reorder', [MediaCategoryController::class, 'reorder'])->name('admin.media-categories.reorder');
        });
        Route::resource('media-categories', MediaCategoryController::class)->names([
            'index' => 'admin.media-categories.index',
            'create' => 'admin.media-categories.create',
            'store' => 'admin.media-categories.store',
            'show' => 'admin.media-categories.show',
            'edit' => 'admin.media-categories.edit',
            'update' => 'admin.media-categories.update',
            'destroy' => 'admin.media-categories.destroy',
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

        // Hadith Narrator Routes
        Route::prefix('hadith-narrators')->group(function (): void {
            Route::get('/search', [HadithNarratorController::class, 'search'])->name('admin.hadith-narrators.search');
            Route::post('/bulk-delete', [HadithNarratorController::class, 'bulkDelete'])->name('admin.hadith-narrators.bulk-delete');
            Route::get('/by-grade/{grade}', [HadithNarratorController::class, 'byGrade'])->name('admin.hadith-narrators.by-grade');
            Route::get('/by-era/{era}', [HadithNarratorController::class, 'byEra'])->name('admin.hadith-narrators.by-era');
            Route::get('/by-country/{country}', [HadithNarratorController::class, 'byCountry'])->name('admin.hadith-narrators.by-country');
            Route::get('/{id}/teachers', [HadithNarratorController::class, 'teachers'])->name('admin.hadith-narrators.teachers');
            Route::get('/{id}/students', [HadithNarratorController::class, 'students'])->name('admin.hadith-narrators.students');
        });
        Route::resource('hadith-narrators', HadithNarratorController::class)->names([
            'index' => 'admin.hadith-narrators.index',
            'create' => 'admin.hadith-narrators.create',
            'store' => 'admin.hadith-narrators.store',
            'show' => 'admin.hadith-narrators.show',
            'edit' => 'admin.hadith-narrators.edit',
            'update' => 'admin.hadith-narrators.update',
            'destroy' => 'admin.hadith-narrators.destroy',
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

        // Quran Routes (main entry point for Quran management)
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

        // Quran Surah Routes (no create/store - 114 surahs are fixed)
        Route::prefix('surahs')->group(function (): void {
            Route::get('/search', [SurahController::class, 'search'])->name('admin.surahs.search');
        });
        Route::resource('surahs', SurahController::class)->except(['create', 'store', 'destroy'])->names([
            'index' => 'admin.surahs.index',
            'show' => 'admin.surahs.show',
            'edit' => 'admin.surahs.edit',
            'update' => 'admin.surahs.update',
        ]);

        // Quran Verse Routes (full CRUD)
        Route::prefix('verses')->group(function (): void {
            Route::get('/search', [VerseController::class, 'search'])->name('admin.verses.search');
            Route::post('/bulk-delete', [VerseController::class, 'bulkDelete'])->name('admin.verses.bulk-delete');
        });
        Route::resource('verses', VerseController::class)->names([
            'index' => 'admin.verses.index',
            'create' => 'admin.verses.create',
            'store' => 'admin.verses.store',
            'show' => 'admin.verses.show',
            'edit' => 'admin.verses.edit',
            'update' => 'admin.verses.update',
            'destroy' => 'admin.verses.destroy',
        ]);

        // Reciter Routes
        Route::prefix('reciters')->group(function (): void {
            Route::get('/search', [ReciterController::class, 'search'])->name('admin.reciters.search');
            Route::post('/bulk-delete', [ReciterController::class, 'bulkDelete'])->name('admin.reciters.bulk-delete');
            Route::post('/reorder', [ReciterController::class, 'reorder'])->name('admin.reciters.reorder');
            Route::post('/{id}/toggle-featured', [ReciterController::class, 'toggleFeatured'])->name('admin.reciters.toggle-featured');
        });
        Route::resource('reciters', ReciterController::class)->names([
            'index' => 'admin.reciters.index',
            'create' => 'admin.reciters.create',
            'store' => 'admin.reciters.store',
            'show' => 'admin.reciters.show',
            'edit' => 'admin.reciters.edit',
            'update' => 'admin.reciters.update',
            'destroy' => 'admin.reciters.destroy',
        ]);

        // Tafseer Routes
        Route::prefix('tafseer')->group(function (): void {
            Route::get('/search', [TafseerController::class, 'search'])->name('admin.tafseer.search');
            Route::post('/bulk-delete', [TafseerController::class, 'bulkDelete'])->name('admin.tafseer.bulk-delete');
        });
        Route::resource('tafseer', TafseerController::class)->names([
            'index' => 'admin.tafseer.index',
            'create' => 'admin.tafseer.create',
            'store' => 'admin.tafseer.store',
            'show' => 'admin.tafseer.show',
            'edit' => 'admin.tafseer.edit',
            'update' => 'admin.tafseer.update',
            'destroy' => 'admin.tafseer.destroy',
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

        // Export Routes
        Route::prefix('export')->name('export.')->group(function (): void {
            Route::post('/', [ExportController::class, 'export'])->name('export');
            Route::get('/{module}/columns', [ExportController::class, 'getColumns'])->name('columns');
            Route::get('/history', [ExportController::class, 'history'])->name('history');
            Route::get('/stats', [ExportController::class, 'stats'])->name('stats');
            Route::delete('/cleanup', [ExportController::class, 'cleanup'])->name('cleanup');
        });

        // Import Routes
        Route::prefix('import')->name('import.')->group(function (): void {
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
        Route::prefix('scheduler')->name('scheduler.')->group(function (): void {
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
        Route::prefix('bulk-operations')->name('bulk-operations.')->group(function (): void {
            Route::post('/', [BulkOperationController::class, 'store'])->name('store');
            Route::get('/', [BulkOperationController::class, 'index'])->name('index');
            Route::get('/stats', [BulkOperationController::class, 'stats'])->name('stats');
            Route::get('/{id}/status', [BulkOperationController::class, 'status'])->name('status');
            Route::get('/{id}/download', [BulkOperationController::class, 'download'])->name('download');
            Route::delete('/{id}', [BulkOperationController::class, 'destroy'])->name('destroy');
        });

        // Version Control Routes
        Route::prefix('versions')->name('versions.')->group(function (): void {
            Route::get('/stats', [VersionController::class, 'stats'])->name('stats');
            Route::get('/{type}/{id}', [VersionController::class, 'getVersions'])->name('list');
            Route::get('/{versionId}', [VersionController::class, 'getVersion'])->name('show');
            Route::get('/{v1}/{v2}/compare', [VersionController::class, 'compare'])->name('compare');
            Route::post('/revert', [VersionController::class, 'revert'])->name('revert');
            Route::get('/{type}/{id}/timeline', [VersionController::class, 'getTimeline'])->name('timeline');
            Route::post('/{type}/{id}/prune', [VersionController::class, 'prune'])->name('prune');
        });

        // Audit Log Routes
        Route::prefix('audit')->name('audit.')->group(function (): void {
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
    });
