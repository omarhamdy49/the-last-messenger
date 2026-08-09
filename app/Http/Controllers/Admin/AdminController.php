<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Traits\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Session;

/**
 * Base Admin Controller
 *
 * Provides common functionality for all admin controllers:
 * - Authorization checks
 * - Request validation helpers
 * - Response formatting
 * - Activity logging
 * - Error handling
 */
abstract class AdminController extends Controller
{
    use ApiResponse;
    use AuthorizesRequests;

    /**
     * Items per page for pagination
     */
    protected int $perPage = 15;

    /**
     * Constructor
     */
    public function __construct()
    {
        // Ensure user is authenticated on the admin guard and has admin access
        $this->middleware(['auth:admin', 'admin']);
    }

    /**
     * Authorize admin action
     *
     * Check if the authenticated user has the required permission
     *
     * @param  string  $permission  Permission name (e.g., 'edit articles')
     * @param  mixed  $model  Optional model instance for model-based permissions
     */
    protected function authorizeAdmin(string $permission, mixed $model = null): bool
    {
        $user = auth()->user();

        // Super admins have unrestricted access
        if ($user->hasRole('super_admin')) {
            return true;
        }

        // Check if user has the specific permission
        if (! $user->hasPermissionTo($permission)) {
            abort(403, 'You do not have permission to perform this action.');
        }

        return true;
    }

    /**
     * Validate request with custom error handling
     *
     * @param  array  $rules  Validation rules
     * @param  array  $messages  Custom error messages
     * @return array Validated data
     */
    protected function validateRequest(Request $request, array $rules, array $messages = []): array
    {
        try {
            return $request->validate($rules, $messages);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // For API requests, return JSON response
            if ($request->expectsJson()) {
                throw $e;
            }

            // For web requests, redirect back with errors
            throw $e;
        }
    }

    /**
     * Get pagination parameters from request
     *
     * `sort` is returned verbatim: only the repository knows which columns its
     * table actually has, so the allow-list lives at the point of use (see
     * ArticleRepository::SORTABLE_COLUMNS). `order` and `per_page` are safe to
     * constrain here.
     *
     * @return array{per_page: int, page: int, sort: string, order: string}
     */
    protected function getPaginationParams(Request $request): array
    {
        return [
            'per_page' => min(max($request->integer('per_page', $this->perPage), 1), 100),
            'page' => max($request->integer('page', 1), 1),
            'sort' => $request->string('sort', 'created_at')->toString(),
            'order' => strtolower($request->string('order', 'desc')->toString()) === 'asc' ? 'asc' : 'desc',
        ];
    }

    /**
     * Get search query from request
     *
     * @param  string  $param  Query parameter name (default: 'q')
     */
    protected function getSearchQuery(Request $request, string $param = 'q'): ?string
    {
        $query = $request->string($param)->toString();

        return ! empty($query) ? $query : null;
    }

    /**
     * Get filter parameters from request
     *
     * @param  array  $allowedFilters  List of allowed filter keys
     */
    protected function getFilters(Request $request, array $allowedFilters = []): array
    {
        $filters = [];

        foreach ($allowedFilters as $filter) {
            if ($request->has($filter) && $request->get($filter) !== null) {
                $filters[$filter] = $request->get($filter);
            }
        }

        return $filters;
    }

    /**
     * Log admin activity
     *
     * @param  string  $action  Action performed (e.g., 'created', 'updated', 'deleted')
     * @param  string  $model  Model name (e.g., 'Article', 'User')
     * @param  mixed  $modelId  Model ID
     * @param  array  $metadata  Additional metadata
     */
    protected function logActivity(string $action, string $model, mixed $modelId, array $metadata = []): void
    {
        // Log activity using Laravel's logging system
        \Log::info('admin.action', [
            'action' => $action,
            'user_id' => auth()->id(),
            'user_email' => auth()->user()->email,
            'model' => $model,
            'model_id' => $modelId,
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'metadata' => $metadata,
            'timestamp' => now()->toDateTimeString(),
        ]);
    }

    /**
     * Handle bulk operations
     *
     * @param  string  $action  Bulk action to perform
     * @param  callable  $callback  Callback to execute for each item
     * @return array{success: int, failed: int, errors: array}
     */
    protected function handleBulkOperation(Request $request, string $action, callable $callback): array
    {
        $ids = $request->input('ids', []);

        if (empty($ids)) {
            abort(422, 'No items selected for bulk operation.');
        }

        $results = [
            'success' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        foreach ($ids as $id) {
            try {
                $callback($id);
                $results['success']++;
            } catch (\Throwable $e) {
                $results['failed']++;
                $results['errors'][$id] = $e->getMessage();
            }
        }

        $this->logActivity("bulk_{$action}", 'Multiple', $ids, [
            'success' => $results['success'],
            'failed' => $results['failed'],
        ]);

        return $results;
    }

    /**
     * Format response for datatables
     *
     * @param  mixed  $data  Query builder or collection
     * @param  callable|null  $transformer  Optional data transformer
     */
    protected function datatableResponse($data, ?callable $transformer = null): array
    {
        if ($transformer) {
            $data = $data->map($transformer);
        }

        return [
            'data' => $data->items(),
            'recordsTotal' => $data->total(),
            'recordsFiltered' => $data->total(),
            'draw' => request()->integer('draw', 1),
        ];
    }

    /**
     * Check if request expects JSON
     */
    protected function expectsJson(Request $request): bool
    {
        return $request->expectsJson() || $request->wantsJson();
    }

    /**
     * Handle successful operation
     *
     * @param  string  $message  Success message
     * @param  mixed  $data  Response data
     * @param  string  $redirectRoute  Route to redirect (for web requests)
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    protected function success(string $message, mixed $data = null, ?string $redirectRoute = null)
    {
        if ($this->expectsJson(request())) {
            return $this->successResponse($data, $message);
        }

        return redirect()->route($redirectRoute ?? 'admin.dashboard.index')
            ->with('success', $message);
    }

    /**
     * Handle failed operation
     *
     * @param  string  $message  Error message
     * @param  int  $code  HTTP status code
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    protected function failure(string $message, int $code = 400)
    {
        if ($this->expectsJson(request())) {
            return $this->errorResponse($message, $code);
        }

        return back()->withErrors(['error' => $message])->withInput();
    }

    /**
     * Switch application locale
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function switchLocale(Request $request)
    {
        $locale = $request->input('locale', 'ar');

        // Validate locale
        $supportedLocales = ['ar', 'en', 'fr', 'tr', 'ur'];
        if (! in_array($locale, $supportedLocales)) {
            return $this->failure(__('admin.locale_unsupported'), 400);
        }

        // Store locale in session
        Session::put('locale', $locale);

        // Update user preference if authenticated
        if (auth()->check() && auth()->user()) {
            auth()->user()->update(['language' => $locale]);
        }

        // Set application locale
        app()->setLocale($locale);
        Carbon::setLocale($locale);

        if ($this->expectsJson($request)) {
            return $this->successResponse([
                'locale' => $locale,
                'direction' => get_text_direction($locale),
            ], __('admin.locale_changed'));
        }

        return back()->with('success', __('admin.locale_changed'));
    }

    /**
     * Get current locale information
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getLocaleInfo(Request $request)
    {
        $locale = app()->getLocale();

        return $this->successResponse([
            'locale' => $locale,
            'direction' => get_text_direction($locale),
            'is_rtl' => is_rtl_locale($locale),
            'supported_locales' => [
                ['code' => 'ar', 'name' => 'العربية', 'native' => 'العربية'],
                ['code' => 'en', 'name' => 'English', 'native' => 'English'],
                ['code' => 'fr', 'name' => 'Français', 'native' => 'Français'],
                ['code' => 'tr', 'name' => 'Türkçe', 'native' => 'Türkçe'],
                ['code' => 'ur', 'name' => 'اردو', 'native' => 'اردو'],
            ],
        ]);
    }
}
