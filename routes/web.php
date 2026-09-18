<?php

use App\Http\Controllers\LanguageController;
use App\Http\Controllers\Site\ArticleController;
use App\Http\Controllers\Site\ArticleMediaController;
use App\Http\Controllers\Site\AuthController;
use App\Http\Controllers\Site\ContactController;
use App\Http\Controllers\Site\FaqController;
use App\Http\Controllers\Site\FavoriteController;
use App\Http\Controllers\Site\GenealogyController;
use App\Http\Controllers\Site\HadithController;
use App\Http\Controllers\Site\HomeController;
use App\Http\Controllers\Site\HomeModuleCardImageController;
use App\Http\Controllers\Site\MediaController;
use App\Http\Controllers\Site\PageController;
use App\Http\Controllers\Site\PasswordResetController;
use App\Http\Controllers\Site\ProfileController;
use App\Http\Controllers\Site\Quran\AskController as QuranAskController;
use App\Http\Controllers\Site\Quran\NavigationController as QuranNavigationController;
use App\Http\Controllers\Site\Quran\ReaderController as QuranReaderController;
use App\Http\Controllers\Site\Quran\ReciterController as QuranReciterController;
use App\Http\Controllers\Site\Quran\SearchController as QuranSearchController;
use App\Http\Controllers\Site\QuranPlaylistController;
use App\Http\Controllers\Site\SahabaController;
use App\Http\Controllers\Site\SearchController;
use App\Http\Controllers\Site\SeerahController;
use App\Http\Controllers\Site\SeriesController;
use App\Http\Controllers\Site\SitemapController;
use App\Http\Controllers\Site\SocialAuthController;
use App\Http\Controllers\Site\TopicController;
use App\Http\Controllers\Site\WatchProgressController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('public.home');
Route::get('/home-card-images/{media}', HomeModuleCardImageController::class)
    ->whereNumber('media')
    ->name('public.home-card-image');

// Quran public pages (006: served from local Quran Foundation data)
Route::get('/quran', [QuranReaderController::class, 'index'])->name('public.quran');
Route::get('/quran/surahs', [QuranReaderController::class, 'surahsJson'])->name('public.quran.surahs.json');
Route::get('/quran/reciters', [QuranReciterController::class, 'index'])->name('public.quran.reciters');
Route::get('/quran/reciters/{slug}', [QuranReciterController::class, 'show'])->name('public.quran.reciter');
Route::get('/quran/search', [QuranSearchController::class, 'page'])->middleware('throttle:search')->name('public.quran.search');
Route::get('/quran/search/quick', [QuranSearchController::class, 'quick'])->middleware('throttle:search')->name('public.quran.search.quick');
Route::get('/quran/random', [QuranReaderController::class, 'random'])->name('public.quran.random');
Route::get('/quran/verse/{key}', [QuranReaderController::class, 'verse'])->where('key', '\d{1,3}:\d{1,3}')->name('public.quran.verse');
Route::get('/quran/translation/{resource}', [QuranReaderController::class, 'translationResource'])->whereNumber('resource')->name('public.quran.translation');
Route::get('/quran/tafsir/{resource}', [QuranReaderController::class, 'tafsirResource'])->whereNumber('resource')->name('public.quran.tafsir');
Route::get('/quran/tafsir/{resource}/chapter/{surahNumber}', [QuranReaderController::class, 'chapterTafsir'])
    ->whereNumber(['resource', 'surahNumber'])->name('public.quran.tafsir.chapter');
// Reciter playlists. Static segments (new, preview, audio) are declared before
// `{slug}`, and the slug is constrained to the 12-char random form.
Route::get('/quran/playlists', [QuranPlaylistController::class, 'index'])->name('public.quran.playlists.index');
Route::get('/quran/playlists/new', [QuranPlaylistController::class, 'create'])->middleware('auth')->name('public.quran.playlists.create');
Route::post('/quran/playlists/preview', [QuranPlaylistController::class, 'preview'])->middleware(['auth', 'throttle:30,1'])->name('public.quran.playlists.preview');
Route::get('/quran/playlists/audio/{surah}/{recording}', [QuranPlaylistController::class, 'audio'])
    ->whereNumber(['surah', 'recording'])->name('public.quran.playlists.audio');
Route::post('/quran/playlists', [QuranPlaylistController::class, 'store'])->middleware('auth')->name('public.quran.playlists.store');
Route::prefix('/quran/playlists/{slug}')->where(['slug' => '[a-z0-9]{12}'])->controller(QuranPlaylistController::class)->group(function (): void {
    Route::get('/', 'show')->name('public.quran.playlists.show');
    Route::post('/plays', 'plays')->middleware('throttle:10,1')->name('public.quran.playlists.plays');
    Route::middleware('auth')->group(function (): void {
        Route::get('/edit', 'edit')->name('public.quran.playlists.edit');
        Route::put('/', 'update')->name('public.quran.playlists.update');
        Route::delete('/', 'destroy')->name('public.quran.playlists.destroy');
        Route::patch('/visibility', 'visibility')->name('public.quran.playlists.visibility');
    });
});
// Live channels belong to the Media module now.
Route::redirect('/quran/live', '/media/live', 301);
// "Ask the Quran" inquiry search (canonical verse retrieval, no LLM).
Route::get('/quran/ask', [QuranAskController::class, 'ask'])->name('public.quran.ask');
Route::get('/quran/ask/search', [QuranAskController::class, 'askSearch'])
    ->middleware('throttle:search')->name('public.quran.ask.search');
// AI answer (DeepSeek + quran.ai MCP): registered users only, monthly-quota
// limited (enforced in the handler) with a per-minute burst throttle keyed by user.
Route::post('/quran/ask', [QuranAskController::class, 'askAi'])
    ->middleware(['auth', 'throttle:ai_inquiry'])->name('public.quran.ask.submit');
// Streaming (SSE) variant of the AI answer — same auth + monthly-quota policy.
Route::post('/quran/ask/stream', [QuranAskController::class, 'askAiStream'])
    ->middleware(['auth', 'throttle:ai_inquiry'])->name('public.quran.ask.stream');
// One past inquiry (Q&A) for the history aside — auth + IDOR-safe (scoped to user).
Route::get('/quran/ask/history/{inquiry}', [QuranAskController::class, 'askHistory'])
    ->whereNumber('inquiry')->middleware('auth')->name('public.quran.ask.history');
Route::get('/quran/surah/{surahNumber}', [QuranReaderController::class, 'surah'])
    ->whereNumber('surahNumber')->name('public.quran.surah');
// Lazy content (keeps the surah page light): tafsir per verse on demand; a
// non-default translation once per surah when selected.
Route::get('/quran/verse/{verseId}/tafsir/{resourceId}', [QuranReaderController::class, 'verseTafsir'])
    ->whereNumber('verseId')->whereNumber('resourceId')->name('public.quran.verse.tafsir');
Route::get('/quran/verse/{verseId}/words', [QuranReaderController::class, 'verseWords'])
    ->whereNumber('verseId')->name('public.quran.verse.words');
Route::get('/quran/surah/{surahNumber}/info', [QuranReaderController::class, 'surahInfo'])
    ->whereNumber('surahNumber')->name('public.quran.surah.info');
Route::get('/quran/surah/{surahNumber}/translation/{resourceId}', [QuranReaderController::class, 'surahTranslation'])
    ->whereNumber('surahNumber')->whereNumber('resourceId')->name('public.quran.surah.translation');
// Verse timings of a chapter reciter's file, so the player can highlight the ayah being recited.
Route::get('/quran/surah/{surahNumber}/timings/{reciterId}', [QuranReaderController::class, 'surahTimings'])
    ->whereNumber('surahNumber')->whereNumber('reciterId')->name('public.quran.surah.timings');
Route::get('/quran/juz/{juz}', [QuranNavigationController::class, 'juz'])->whereNumber('juz')->name('public.quran.juz');
Route::get('/quran/hizb/{hizb}', [QuranNavigationController::class, 'hizb'])->whereNumber('hizb')->name('public.quran.hizb');
Route::get('/quran/rub/{rub}', [QuranNavigationController::class, 'rub'])->whereNumber('rub')->name('public.quran.rub');
Route::get('/quran/manzil/{manzil}', [QuranNavigationController::class, 'manzil'])->whereNumber('manzil')->name('public.quran.manzil');
Route::get('/quran/ruku/{ruku}', [QuranNavigationController::class, 'ruku'])->whereNumber('ruku')->name('public.quran.ruku');
Route::get('/quran/page/{page}', [QuranNavigationController::class, 'page'])->whereNumber('page')->name('public.quran.page');
// Short surah URL (e.g. /quran/2). Numeric-only so it never shadows the named
// routes above (reciters, search, juz, hizb, page).
Route::get('/quran/{surahNumber}', [QuranReaderController::class, 'surah'])
    ->whereNumber('surahNumber')->name('public.quran.surah.short');

// Hadith
// Static segments are declared BEFORE the dynamic canonical route so `search`
// and `urn` can never be read as a collection code. Every dynamic segment is
// constrained: `number` allows `1a`, which the source really uses.
// bookNumber allows `35b`, `introduction` and `letter`, which the source uses.
Route::get('/hadith', [HadithController::class, 'index'])->name('public.hadith');
// throttle:search — HadithBrowseService runs MATCH…AGAINST twice (WHERE + ORDER BY) over
// ~38k rows per request, uncached. Every other search surface was already throttled; this
// one was not.
Route::get('/hadith/search', [HadithController::class, 'search'])
    ->middleware('throttle:search')->name('public.hadith.search');
// A single hadith is `{collection}:{referenceKey}` — the colon keeps it out of
// the `/hadith/{slug}/{bookNumber}` namespace, where `/hadith/bukhari/12` would
// be ambiguous between book 12 and reference key 12. Declared before the
// one-segment collection route so a colon is never read as a slug.
Route::get('/hadith/{reference}', [HadithController::class, 'show'])
    ->where('reference', '[a-z0-9-]+:[A-Za-z0-9._-]+')->name('public.hadith.show');
Route::get('/hadith/{slug}', [HadithController::class, 'collection'])
    ->where('slug', '[a-z0-9-]+')->name('public.hadith.collection');
Route::get('/hadith/{slug}/{bookNumber}', [HadithController::class, 'book'])
    ->where('slug', '[a-z0-9-]+')->where('bookNumber', '[A-Za-z0-9._-]+')->name('public.hadith.book');

// XML sitemap (articles surface)
Route::get('/sitemap.xml', [SitemapController::class, 'articles'])->name('sitemap.articles');

// robots.txt served dynamically so the Sitemap directive uses the real request host.
Route::get('/robots.txt', function () {
    // Allow-list: named search engines and AI agents may crawl; everyone else is denied.
    // Agent lists and closed paths live in config/robots.php.
    //
    // robots.txt is advisory — compliant crawlers honour it, scrapers ignore it entirely.
    // Actual protection against abusive traffic is nginx's limit_req/limit_conn plus
    // Cloudflare's bot rules, not this file.
    $allowed = [
        ...config('robots.search_engines', []),
        ...config('robots.ai_agents', []),
    ];
    $disallowedPaths = config('robots.disallowed_paths', []);

    $lines = [];

    foreach ($allowed as $agent) {
        $lines[] = "User-agent: {$agent}";
    }

    if ($allowed !== []) {
        // Only Disallow lines. Anything not matched by one is permitted by default, so an
        // additional empty `Disallow:` would contradict the rules above it — parsers differ
        // on how they resolve that, and it is not needed to express "allow the rest".
        foreach ($disallowedPaths as $path) {
            $lines[] = "Disallow: {$path}";
        }

        $lines[] = '';
    }

    // Everything not named above.
    $lines[] = 'User-agent: *';
    $lines[] = 'Disallow: /';
    $lines[] = '';
    $lines[] = 'Sitemap: '.url('/sitemap.xml');

    return response(implode("\n", $lines)."\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
})->name('robots');

// Articles / Blog
Route::get('/articles', [ArticleController::class, 'index'])->name('public.articles');
Route::post('/articles/{id}/view', [ArticleController::class, 'registerView'])
    ->whereNumber('id')->middleware('throttle:30,1')->name('public.articles.view');
// Registered before /articles/{slug} so "media" is never read as a slug.
Route::get('/articles/media/{media:public_key}', [ArticleMediaController::class, 'stream'])
    ->name('public.articles.media');
Route::get('/articles/{slug}', [ArticleController::class, 'show'])->name('public.articles.show');

// Article series (ordered reading paths; replaces Topics in the header nav)
Route::get('/series', [SeriesController::class, 'index'])->name('public.series');
Route::get('/series/{slug}', [SeriesController::class, 'show'])->name('public.series.show');

// Seerah
Route::get('/seerah', [SeerahController::class, 'index'])->name('public.seerah');
// `?q=` runs a leading-wildcard LIKE over the event translations on an
// anonymous request, and the response cache is keyed by the term — so a varied
// term always reaches the DB. Rate limited like the other search surfaces.
Route::get('/seerah/events', [SeerahController::class, 'events'])
    ->middleware('throttle:search')->name('public.seerah.events');
Route::get('/seerah/event/{slug}', [SeerahController::class, 'event'])->name('public.seerah.event');
// Viewport lazy-loading feeds for the journey timeline (JSON, cached, anonymous).
Route::get('/seerah/journey/{slug}/events', [SeerahController::class, 'journeyEvents'])
    ->middleware('throttle:search')->name('public.seerah.journey.events');
Route::get('/seerah/articles-feed', [SeerahController::class, 'articlesFeed'])
    ->middleware('throttle:search')->name('public.seerah.articles.feed');

// Sahaba
// Sahaba — the public companions surface, backed by the internal `muslims`
// identity. Static segments first so `tree` can never be read as a slug.
// `?q=` reaches MuslimRepository's LIKE/MATCH branches unauthenticated, so this
// listing carries the same budget as /search.
Route::get('/sahaba', [SahabaController::class, 'index'])
    ->middleware('throttle:search')->name('public.sahaba');
Route::get('/sahaba/{slug}/tree', [SahabaController::class, 'tree'])
    ->where('slug', '[a-z0-9-]+')->name('public.sahaba.tree');
Route::get('/sahaba/{slug}', [SahabaController::class, 'show'])
    ->where('slug', '[a-z0-9-]+')->name('public.sahaba.show');

// Genealogy — the family-tree explorer, rebuilt on `muslims`. `?root=` picks
// the person the tree is drawn from; any muslim is a valid root.
// `?root=` bypasses the 30-min cache entirely (GenealogyController::index caches only the
// default root) and runs recursive CTEs at LINEAGE_DEPTH=40 plus the sibling/uncle/cousin
// queries. Unauthenticated and, until now, unthrottled: one host looping distinct slugs
// saturates MySQL. The slug pattern also bounds the cache-key space.
Route::get('/genealogy', [GenealogyController::class, 'index'])
    ->middleware('throttle:search')
    ->where('root', '[A-Za-z0-9\\-]{0,120}')
    ->name('public.genealogy');
Route::get('/genealogy/people', [GenealogyController::class, 'people'])
    ->middleware('throttle:search')->name('public.genealogy.people');

// Genealogy
// Media / Programs
Route::get('/media', [MediaController::class, 'index'])->name('public.media');
// Live channel routes — registered before the {slug} program route so the
// literal "live" segment is never swallowed as a program slug.
// Static segment registered before /media/{slug} so it never resolves as a slug.
// Per-user JSON for the lazy-loaded Continue Watching row on the hub.
Route::get('/media/continue-watching', [MediaController::class, 'continueWatching'])
    ->middleware('auth')->name('public.media.continue-watching');
Route::get('/media/live', [MediaController::class, 'liveIndex'])->name('public.media.live.index');
Route::get('/media/live/{slug}', [MediaController::class, 'live'])->name('public.media.live');
Route::get('/media/{slug}', [MediaController::class, 'show'])->name('public.media.program');
Route::get('/media/{slug}/episodes/{episode}', [MediaController::class, 'episode'])
    ->whereNumber('episode')->name('public.media.episode');

// Topics (cross-module aggregation: verses + hadiths + articles per theme)
Route::get('/topics', [TopicController::class, 'index'])
    ->middleware('throttle:search')->name('public.topics');
Route::get('/topics/{slug}', [TopicController::class, 'show'])
    ->middleware('throttle:search')->name('public.topics.show');

// Unified Search
// Throttled like the other search surfaces: this one fans out LIKE/fulltext
// queries across every public module on an anonymous request.
Route::get('/search', [SearchController::class, 'index'])
    ->middleware('throttle:search')->name('public.search');

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

    // Episode watch-progress beats from the player (~1 per 15s per viewer,
    // plus pause/unload beacons — 60/min leaves headroom for several tabs).
    Route::post('/media/progress', [WatchProgressController::class, 'store'])
        ->middleware('throttle:60,1')->name('public.media.progress');
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
