<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\Content\ContactMessageController;
use App\Http\Controllers\Admin\Content\FaqController;
use App\Http\Controllers\Admin\Content\HomeModuleCardController;
use App\Http\Controllers\Admin\Content\HomeSectionController;
use App\Http\Controllers\Admin\Content\MenuItemController;
use App\Http\Controllers\Admin\Content\QuickFilterChipController;
use App\Http\Controllers\Admin\Content\SiteSettingController;
use App\Http\Controllers\Admin\Content\StaticPageController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin — Content domain (site chrome, pages, FAQs, inbox, imports)
|--------------------------------------------------------------------------
| Included from routes/admin.php inside the
| ['web', 'auth', 'admin', 'throttle:60,1'] su/portal/v1/admin group.
*/

// Site Settings (grouped editor)
Route::get('site-settings', [SiteSettingController::class, 'index'])->name('admin.content.settings.index');
Route::put('site-settings/{group}', [SiteSettingController::class, 'update'])
    ->where('group', '[a-z0-9_-]+')
    ->name('admin.content.settings.update');

// Menus (per MenuLocation)
Route::prefix('menus')->name('admin.content.menus.')->group(function (): void {
    Route::get('/', [MenuItemController::class, 'index'])->name('index');
    Route::get('/create', [MenuItemController::class, 'create'])->name('create');
    Route::post('/', [MenuItemController::class, 'store'])->name('store');
    Route::get('/{menu}/edit', [MenuItemController::class, 'edit'])->whereNumber('menu')->name('edit');
    Route::put('/{menu}', [MenuItemController::class, 'update'])->whereNumber('menu')->name('update');
    Route::delete('/{menu}', [MenuItemController::class, 'destroy'])->whereNumber('menu')->name('destroy');
    Route::post('/{menu}/move', [MenuItemController::class, 'move'])->whereNumber('menu')->name('move');
});

// Home page sections
Route::prefix('home-sections')->name('admin.content.home-sections.')->group(function (): void {
    Route::get('/', [HomeSectionController::class, 'index'])->name('index');
    Route::post('/reorder', [HomeSectionController::class, 'reorder'])->name('reorder');
    Route::get('/{home_section}/edit', [HomeSectionController::class, 'edit'])->whereNumber('home_section')->name('edit');
    Route::put('/{home_section}', [HomeSectionController::class, 'update'])->whereNumber('home_section')->name('update');
    Route::patch('/{home_section}/active', [HomeSectionController::class, 'toggle'])->whereNumber('home_section')->name('toggle');
});

// Home sections — module cards
Route::prefix('home-cards')->name('admin.content.home-cards.')->group(function (): void {
    Route::get('/', [HomeModuleCardController::class, 'index'])->name('index');
    Route::get('/create', [HomeModuleCardController::class, 'create'])->name('create');
    Route::post('/', [HomeModuleCardController::class, 'store'])->name('store');
    Route::get('/{home_card}/edit', [HomeModuleCardController::class, 'edit'])->whereNumber('home_card')->name('edit');
    Route::put('/{home_card}', [HomeModuleCardController::class, 'update'])->whereNumber('home_card')->name('update');
    Route::delete('/{home_card}', [HomeModuleCardController::class, 'destroy'])->whereNumber('home_card')->name('destroy');
});

// Home sections — quick-filter chips
Route::prefix('quick-filters')->name('admin.content.quick-filters.')->group(function (): void {
    Route::get('/', [QuickFilterChipController::class, 'index'])->name('index');
    Route::get('/create', [QuickFilterChipController::class, 'create'])->name('create');
    Route::post('/', [QuickFilterChipController::class, 'store'])->name('store');
    Route::get('/{quick_filter}/edit', [QuickFilterChipController::class, 'edit'])->whereNumber('quick_filter')->name('edit');
    Route::put('/{quick_filter}', [QuickFilterChipController::class, 'update'])->whereNumber('quick_filter')->name('update');
    Route::delete('/{quick_filter}', [QuickFilterChipController::class, 'destroy'])->whereNumber('quick_filter')->name('destroy');
});

// Static pages
Route::prefix('static-pages')->name('admin.content.static-pages.')->group(function (): void {
    Route::get('/', [StaticPageController::class, 'index'])->name('index');
    Route::get('/create', [StaticPageController::class, 'create'])->name('create');
    Route::post('/', [StaticPageController::class, 'store'])->name('store');
    Route::get('/{static_page}/edit', [StaticPageController::class, 'edit'])->whereNumber('static_page')->name('edit');
    Route::put('/{static_page}', [StaticPageController::class, 'update'])->whereNumber('static_page')->name('update');
    Route::delete('/{static_page}', [StaticPageController::class, 'destroy'])->whereNumber('static_page')->name('destroy');
});

// FAQs
Route::prefix('faqs')->name('admin.content.faqs.')->group(function (): void {
    Route::get('/', [FaqController::class, 'index'])->name('index');
    Route::get('/create', [FaqController::class, 'create'])->name('create');
    Route::post('/', [FaqController::class, 'store'])->name('store');
    Route::get('/{faq}/edit', [FaqController::class, 'edit'])->whereNumber('faq')->name('edit');
    Route::put('/{faq}', [FaqController::class, 'update'])->whereNumber('faq')->name('update');
    Route::delete('/{faq}', [FaqController::class, 'destroy'])->whereNumber('faq')->name('destroy');
});

// Contact messages (read-only inbox + triage)
Route::prefix('contact-messages')->name('admin.content.contact-messages.')->group(function (): void {
    Route::get('/', [ContactMessageController::class, 'index'])->name('index');
    Route::get('/{contact_message}', [ContactMessageController::class, 'show'])->whereNumber('contact_message')->name('show');
    Route::post('/{contact_message}/mark-read', [ContactMessageController::class, 'markRead'])->whereNumber('contact_message')->name('mark-read');
    Route::post('/{contact_message}/mark-unread', [ContactMessageController::class, 'markUnread'])->whereNumber('contact_message')->name('mark-unread');
    Route::delete('/{contact_message}', [ContactMessageController::class, 'destroy'])->whereNumber('contact_message')->name('destroy');
});

// Import status (read-only dashboards)
