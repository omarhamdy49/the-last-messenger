<?php

use App\Domain\Blog\Exceptions\ArticleNotFoundException;
use App\Domain\Blog\Exceptions\CategoryNotFoundException;
use App\Domain\Sahaba\Exceptions\SahabiNotFoundException;
use App\Domain\Seerah\Exceptions\SeerahEventNotFoundException;
use App\Domain\Shared\Exceptions\DomainException;
use App\Domain\Shared\Exceptions\EntityNotFoundException;
use App\Http\Middleware\AddRequestId;
use App\Http\Middleware\AdminAuthMiddleware;
use App\Http\Middleware\AuthenticateApi;
use App\Http\Middleware\CacheGuestPages;
use App\Http\Middleware\LogApiRequest;
use App\Http\Middleware\SecureApiResponseHeaders;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\HandleCors;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Http\Middleware\CheckAbilities;
use Laravel\Sanctum\Http\Middleware\CheckForAnyAbility;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (): void {
            Route::middleware('web')
                ->group(base_path('routes/admin.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => AdminAuthMiddleware::class,
            'auth.api' => AuthenticateApi::class,
            'permission' => PermissionMiddleware::class,
            'role' => RoleMiddleware::class,
            'abilities' => CheckAbilities::class,
            'ability' => CheckForAnyAbility::class,
        ]);

        $middleware->api(prepend: [
            HandleCors::class,
            AddRequestId::class,
        ]);

        $middleware->api(append: [
            SetLocale::class,
            LogApiRequest::class,
            SecureApiResponseHeaders::class,
        ]);

        // AddRequestId also runs on `web`, not just `api` — the admin panel and public
        // site are the bulk of traffic, and without this their log lines carry no
        // correlation id at all. Appended rather than prepended so CacheGuestPages keeps
        // the outermost response position it deliberately claims below.
        $middleware->web(append: [
            AddRequestId::class,
            SetLocale::class,
        ]);

        // Prepended so its response phase runs OUTERMOST (after StartSession adds
        // the session cookie) — lets it edge-cache the guest homepage and strip
        // per-user cookies, while forcing authenticated/admin responses to no-store.
        $middleware->web(prepend: [
            CacheGuestPages::class,
        ]);

        // Unauthenticated visitors: send admin-area requests to the admin login, but
        // public-site requests (e.g. /profile, /favorites) to the public login — not the
        // admin portal. JSON requests fall through to a 401 (return null).
        $middleware->redirectGuestsTo(function (Request $request): ?string {
            if ($request->expectsJson()) {
                return null;
            }

            if ($request->is('su/portal', 'su/portal/*') || $request->routeIs('admin.*')) {
                return route('login');
            }

            return route('public.login');
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Map EntityNotFoundException (404 not-found domain errors) to a 404 JSON response.
        $exceptions->render(function (
            EntityNotFoundException $e,
            Request $request,
        ) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => class_basename($e),
                        'message' => __('errors.resource_not_found'),
                    ],
                ], 404);
            }
        });

        // Map ArticleNotFoundException (a DomainException subtype) to a 404 JSON response.
        $exceptions->render(function (
            ArticleNotFoundException $e,
            Request $request,
        ) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'ARTICLE_NOT_FOUND',
                        'message' => __('errors.resource_not_found'),
                    ],
                ], 404);
            }
            abort(404, $e->getMessage());
        });

        // Map CategoryNotFoundException to a 404 JSON response or 404 abort for web.
        $exceptions->render(function (
            CategoryNotFoundException $e,
            Request $request,
        ) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'CATEGORY_NOT_FOUND',
                        'message' => __('errors.resource_not_found'),
                    ],
                ], 404);
            }
            abort(404, $e->getMessage());
        });

        // Map SeerahEventNotFoundException to a 404 JSON response or 404 abort for web.
        $exceptions->render(function (
            SeerahEventNotFoundException $e,
            Request $request,
        ) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'SEERAH_EVENT_NOT_FOUND',
                        'message' => __('errors.resource_not_found'),
                    ],
                ], 404);
            }
            abort(404, $e->getMessage());
        });

        // Map SahabiNotFoundException to a 404 JSON response or 404 abort for web.
        $exceptions->render(function (
            SahabiNotFoundException $e,
            Request $request,
        ) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'SAHABI_NOT_FOUND',
                        'message' => __('errors.resource_not_found'),
                    ],
                ], 404);
            }
            abort(404, $e->getMessage());
        });

        // Map all remaining DomainException subclasses to a 422 JSON response with a
        // generic user-facing message so that internal domain details are never leaked.
        $exceptions->render(function (
            DomainException $e,
            Request $request,
        ) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => class_basename($e),
                        'message' => __('errors.general'),
                    ],
                ], 422);
            }
        });
    })->create();
