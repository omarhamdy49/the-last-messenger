<?php

use App\Http\Middleware\AdminAuthMiddleware;
use App\Http\Middleware\AuthenticateApi;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

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
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
        ]);

        $middleware->api(prepend: [
            \Illuminate\Http\Middleware\HandleCors::class,
            \App\Http\Middleware\AddRequestId::class,
        ]);

        $middleware->api(append: [
            \App\Http\Middleware\SetLocale::class,
            \App\Http\Middleware\LogApiRequest::class,
        ]);

        $middleware->web(append: [
            \App\Http\Middleware\SetLocale::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Map EntityNotFoundException (404 not-found domain errors) to a 404 JSON response.
        $exceptions->render(function (
            \App\Domain\Shared\Exceptions\EntityNotFoundException $e,
            \Illuminate\Http\Request $request,
        ) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code'    => class_basename($e),
                        'message' => __('errors.resource_not_found'),
                    ],
                ], 404);
            }
        });

        // Map ArticleNotFoundException (a DomainException subtype) to a 404 JSON response.
        $exceptions->render(function (
            \App\Domain\Blog\Exceptions\ArticleNotFoundException $e,
            \Illuminate\Http\Request $request,
        ) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code'    => 'ARTICLE_NOT_FOUND',
                        'message' => __('errors.resource_not_found'),
                    ],
                ], 404);
            }
            abort(404, $e->getMessage());
        });

        // Map CategoryNotFoundException to a 404 JSON response or 404 abort for web.
        $exceptions->render(function (
            \App\Domain\Blog\Exceptions\CategoryNotFoundException $e,
            \Illuminate\Http\Request $request,
        ) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code'    => 'CATEGORY_NOT_FOUND',
                        'message' => __('errors.resource_not_found'),
                    ],
                ], 404);
            }
            abort(404, $e->getMessage());
        });

        // Map SeerahEventNotFoundException to a 404 JSON response or 404 abort for web.
        $exceptions->render(function (
            \App\Domain\Seerah\Exceptions\SeerahEventNotFoundException $e,
            \Illuminate\Http\Request $request,
        ) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code'    => 'SEERAH_EVENT_NOT_FOUND',
                        'message' => __('errors.resource_not_found'),
                    ],
                ], 404);
            }
            abort(404, $e->getMessage());
        });

        // Map SahabiNotFoundException to a 404 JSON response or 404 abort for web.
        $exceptions->render(function (
            \App\Domain\Sahaba\Exceptions\SahabiNotFoundException $e,
            \Illuminate\Http\Request $request,
        ) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code'    => 'SAHABI_NOT_FOUND',
                        'message' => __('errors.resource_not_found'),
                    ],
                ], 404);
            }
            abort(404, $e->getMessage());
        });

        // Map all remaining DomainException subclasses to a 422 JSON response with a
        // generic user-facing message so that internal domain details are never leaked.
        $exceptions->render(function (
            \App\Domain\Shared\Exceptions\DomainException $e,
            \Illuminate\Http\Request $request,
        ) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code'    => class_basename($e),
                        'message' => __('errors.general'),
                    ],
                ], 422);
            }
        });
    })->create();
