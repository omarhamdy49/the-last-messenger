<?php

use App\Http\Controllers\LanguageController;
use App\Http\Controllers\Site\ArticleController;
use App\Http\Controllers\Site\AuthController;
use App\Http\Controllers\Site\ContactController;
use App\Http\Controllers\Site\FaqController;
use App\Http\Controllers\Site\FavoriteController;
use App\Http\Controllers\Site\GenealogyController;
use App\Http\Controllers\Site\HadithController;
use App\Http\Controllers\Site\HomeController;
use App\Http\Controllers\Site\MediaController;
use App\Http\Controllers\Site\PageController;
use App\Http\Controllers\Site\PasswordResetController;
use App\Http\Controllers\Site\ProfileController;
use App\Http\Controllers\Site\QuranController;
use App\Http\Controllers\Site\SahabaController;
use App\Http\Controllers\Site\SearchController;
use App\Http\Controllers\Site\SeerahController;
use App\Http\Controllers\Site\SocialAuthController;
use App\Http\Controllers\Site\TopicController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('public.home');

// Quran public pages
Route::get('/quran', [QuranController::class, 'index'])->name('public.quran');
Route::get('/quran/surahs', [QuranController::class, 'surahsJson'])->name('public.quran.surahs.json');
Route::get('/quran/reciters', [QuranController::class, 'reciters'])->name('public.quran.reciters');
Route::get('/quran/live', [QuranController::class, 'live'])->name('public.quran.live');
Route::get('/quran/asbab-al-nuzul', [QuranController::class, 'asbab'])->name('public.quran.asbab');
// "Ask the Quran" inquiry search (canonical verse retrieval, no LLM).
Route::get('/quran/ask', [QuranController::class, 'ask'])->name('public.quran.ask');
Route::get('/quran/ask/search', [QuranController::class, 'askSearch'])
    ->middleware('throttle:search')->name('public.quran.ask.search');
// AI answer (DeepSeek + quran.ai MCP): registered users only, monthly-quota
// limited (enforced in the handler) with a per-minute burst throttle keyed by user.
Route::post('/quran/ask', [QuranController::class, 'askAi'])
    ->middleware(['auth', 'throttle:ai_inquiry'])->name('public.quran.ask.submit');
// Streaming (SSE) variant of the AI answer — same auth + monthly-quota policy.
Route::post('/quran/ask/stream', [QuranController::class, 'askAiStream'])
    ->middleware(['auth', 'throttle:ai_inquiry'])->name('public.quran.ask.stream');
// One past inquiry (Q&A) for the history aside — auth + IDOR-safe (scoped to user).
Route::get('/quran/ask/history/{inquiry}', [QuranController::class, 'askHistory'])
    ->whereNumber('inquiry')->middleware('auth')->name('public.quran.ask.history');
Route::get('/quran/surah/{surahNumber}', [QuranController::class, 'surah'])
    ->whereNumber('surahNumber')->name('public.quran.surah');
// Lazy editions (keep the surah page light): tafsir is fetched per verse on demand;
// a non-default translation edition is fetched once per surah when selected.
Route::get('/quran/verse/{verseId}/tafsir/{resourceId}', [QuranController::class, 'verseTafsir'])
    ->whereNumber('verseId')->whereNumber('resourceId')->name('public.quran.verse.tafsir');
Route::get('/quran/surah/{surahNumber}/translation/{resourceId}', [QuranController::class, 'surahTranslation'])
    ->whereNumber('surahNumber')->whereNumber('resourceId')->name('public.quran.surah.translation');
Route::get('/quran/juz/{juz}', [QuranController::class, 'juz'])
    ->whereNumber('juz')->name('public.quran.juz');
Route::get('/quran/hizb/{hizb}', [QuranController::class, 'hizb'])
    ->whereNumber('hizb')->name('public.quran.hizb');
Route::get('/quran/page/{page}', [QuranController::class, 'page'])
    ->whereNumber('page')->name('public.quran.page');
// Short surah URL (e.g. /quran/2). Numeric-only so it never shadows the named
// routes above (reciters, live, juz, hizb, page).
Route::get('/quran/{surahNumber}', [QuranController::class, 'surah'])
    ->whereNumber('surahNumber')->name('public.quran.surah.short');

// Articles / Blog
Route::get('/articles', [ArticleController::class, 'index'])->name('public.articles');
Route::get('/articles/{slug}', [ArticleController::class, 'show'])->name('public.articles.show');

// Hadith
Route::get('/hadith', [HadithController::class, 'index'])->name('public.hadith');
Route::get('/hadith/explorer', [HadithController::class, 'explorer'])->name('public.hadith.explorer');
Route::get('/hadith/translations', [HadithController::class, 'translations'])
    ->middleware('throttle:120,1')->name('public.hadith.translations');
Route::get('/hadith/book/{slug}', [HadithController::class, 'book'])->name('public.hadith.book');
Route::get('/hadith/book/{slug}/chapter/{chapter}', [HadithController::class, 'chapter'])
    ->whereNumber('chapter')->name('public.hadith.chapter');
Route::get('/hadith/{id}/narrations', [HadithController::class, 'narrations'])
    ->whereNumber('id')->name('public.hadith.narrations');
Route::get('/hadith/{id}', [HadithController::class, 'show'])->whereNumber('id')->name('public.hadith.show');

// Seerah
Route::get('/seerah', [SeerahController::class, 'index'])->name('public.seerah');
Route::get('/seerah/events', [SeerahController::class, 'events'])->name('public.seerah.events');
Route::get('/seerah/event/{slug}', [SeerahController::class, 'event'])->name('public.seerah.event');

// Sahaba
Route::get('/sahaba', [SahabaController::class, 'index'])->name('public.sahaba');
Route::get('/sahaba/{slug}', [SahabaController::class, 'show'])->name('public.sahaba.show');

// Genealogy
Route::get('/genealogy', [GenealogyController::class, 'index'])->name('public.genealogy');

// Media / Programs
Route::get('/media', [MediaController::class, 'index'])->name('public.media');
// Live channel single page — registered before the {slug} program route so the
// literal "live" segment is never swallowed as a program slug.
Route::get('/media/live/{slug}', [MediaController::class, 'live'])->name('public.media.live');
Route::get('/media/{slug}', [MediaController::class, 'show'])->name('public.media.program');
Route::get('/media/{slug}/episodes/{episode}', [MediaController::class, 'episode'])
    ->whereNumber('episode')->name('public.media.episode');

// Topics (cross-module aggregation: verses + hadiths + articles per theme)
Route::get('/topics', [TopicController::class, 'index'])->name('public.topics');
Route::get('/topics/{slug}', [TopicController::class, 'show'])->name('public.topics.show');

// Unified Search
Route::get('/search', [SearchController::class, 'index'])->name('public.search');

// Static pages / Contact / FAQ (Content domain).
// `public.about|privacy|terms` aliases match MenuSeeder's footer targets so the
// DB-managed footer links resolve; all serve StaticPageService-backed pages.
Route::get('/pages/{slug}', [PageController::class, 'show'])
    ->where('slug', '[a-z0-9\-]+')->name('public.pages.show');
Route::get('/about', [PageController::class, 'show'])
    ->defaults('slug', 'about')->name('public.about');
Route::get('/privacy', [PageController::class, 'show'])
    ->defaults('slug', 'privacy')->name('public.privacy');
Route::get('/terms', [PageController::class, 'show'])
    ->defaults('slug', 'terms')->name('public.terms');
Route::get('/contact', [ContactController::class, 'show'])->name('public.contact');
Route::post('/contact', [ContactController::class, 'store'])
    ->middleware('throttle:10,1')->name('public.contact.submit');
Route::get('/faq', [FaqController::class, 'index'])->name('public.faq');

// Public site authentication (session/cookie, `web` guard).
// Named `public.*` to avoid colliding with the admin portal's `login` route,
// which AdminAuthMiddleware/AuthenticateApi redirect to. The Sanctum token API
// at /api/v1/auth/* is separate and untouched.
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('public.login');
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:5,1')->name('public.login.submit');

    Route::get('/register', [AuthController::class, 'showRegister'])->name('public.register');
    Route::post('/register', [AuthController::class, 'register'])
        ->middleware('throttle:5,1')->name('public.register.submit');

    // Forgot / reset password (Laravel `users` password broker). Standard
    // `password.*` route names so the login page's "forgot password?" link
    // and the default ResetPassword notification resolve correctly.
    Route::get('/forgot-password', [PasswordResetController::class, 'showLinkRequest'])
        ->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink'])
        ->middleware('throttle:5,1')->name('password.email');

    Route::get('/reset-password/{token}', [PasswordResetController::class, 'showReset'])
        ->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'resetPassword'])
        ->middleware('throttle:5,1')->name('password.update');
});

// Social login (Socialite, Google + Facebook). Provider whitelist at the route
// level — anything else 404s. Throttled; guest-only like the form login.
Route::middleware(['guest', 'throttle:10,1'])->whereIn('provider', ['google', 'facebook'])->group(function () {
    Route::get('/auth/{provider}/redirect', [SocialAuthController::class, 'redirect'])
        ->name('public.social.redirect');
    Route::get('/auth/{provider}/callback', [SocialAuthController::class, 'callback'])
        ->name('public.social.callback');
});

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')->name('public.logout');

// Authenticated visitor area: profile dashboard + bookmarks (session/web guard).
// Every action is scoped to the acting user inside the services (IDOR-safe).
Route::middleware('auth')->group(function () {
    // Profile dashboard + account/password updates.
    Route::get('/profile', [ProfileController::class, 'show'])->name('public.profile');
    Route::match(['put', 'patch'], '/profile', [ProfileController::class, 'updateProfile'])
        ->name('public.profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])
        ->name('public.profile.password');

    // Avatar upload (plain file on the public disk; replaces previous file).
    Route::post('/profile/avatar', [ProfileController::class, 'updateAvatar'])
        ->middleware('throttle:10,1')->name('public.profile.avatar');

    // Account deletion (password re-checked in the FormRequest).
    Route::delete('/profile', [ProfileController::class, 'destroy'])
        ->middleware('throttle:5,1')->name('public.profile.destroy');

    // Email verification (notice + signed verify link + throttled resend).
    Route::get('/email/verify', [AuthController::class, 'showVerificationNotice'])
        ->name('public.verification.notice');
    Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])
        ->middleware(['signed', 'throttle:6,1'])->name('public.verification.verify');
    Route::post('/email/verification-notification', [AuthController::class, 'resendVerificationEmail'])
        ->middleware('throttle:6,1')->name('public.verification.send');

    // Bookmarks (favorites).
    Route::get('/favorites', [FavoriteController::class, 'index'])->name('public.favorites');
    Route::post('/favorites', [FavoriteController::class, 'store'])
        ->middleware('throttle:30,1')->name('public.favorites.store');
    Route::delete('/favorites', [FavoriteController::class, 'destroy'])
        ->middleware('throttle:30,1')->name('public.favorites.destroy');
});

// Admin portal entry redirects
Route::get('/su/portal', function () {
    return redirect()->route('admin.dashboard');
})->middleware(['web', 'auth:admin', 'admin', 'role:super_admin|admin|editor,admin', 'throttle:60,1'])
    ->name('portal.entry');

Route::get('/su/portal/v1/admin', function () {
    return redirect()->route('admin.dashboard');
})->middleware(['web', 'auth:admin', 'admin', 'role:super_admin|admin|editor,admin', 'throttle:60,1'])
    ->name('portal.admin.entry');

// Language switching route
Route::post('/language/switch', [LanguageController::class, 'switch'])->name('language.switch');
