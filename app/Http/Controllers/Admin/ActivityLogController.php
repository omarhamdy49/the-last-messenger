<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domain\Shared\Repositories\AdminLogRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Admin Activity Log Controller
 * Handles activity log viewing and filtering for admin portal
 */
class ActivityLogController extends Controller
{
    /**
     * Constructor
     */
    public function __construct(
        protected AdminLogRepositoryInterface $logRepository
    ) {}

    /**
     * Display a listing of all activity logs with filters
     *
     * GET /su/portal/v1/admin/activity-logs
     */
    public function index(Request $request): View|JsonResponse
    {
        // Build filters array
        $filters = [
            'user_id' => $request->input('user_id'),
            'action' => $request->input('action'),
            'model_type' => $request->input('model_type'),
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
            'ip_address' => $request->input('ip_address'),
            'per_page' => $request->integer('per_page', 50),
        ];

        // Get logs with filters or all logs
        $hasFilters = array_filter($filters, fn ($value) => ! is_null($value) && $value !== '');
        $logs = ! empty($hasFilters)
            ? $this->logRepository->search($filters)
            : $this->logRepository->getAll($filters['per_page']);

        // Calculate stats
        $stats = [
            'today' => \App\Domain\Shared\Models\AdminLog::whereDate('created_at', today())->count(),
            'week' => \App\Domain\Shared\Models\AdminLog::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            'users' => \App\Domain\Shared\Models\AdminLog::whereDate('created_at', today())
                ->distinct('user_id')
                ->count('user_id'),
            'topAction' => \App\Domain\Shared\Models\AdminLog::whereDate('created_at', today())
                ->groupBy('action')
                ->selectRaw('action, COUNT(*) as count')
                ->orderByDesc('count')
                ->first()
                ->action ?? 'لا يوجد',
        ];

        // Return JSON for AJAX requests
        if ($request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
            return response()->json([
                'success' => true,
                'data' => $logs->items(),
                'stats' => $stats,
                'meta' => [
                    'current_page' => $logs->currentPage(),
                    'per_page' => $logs->perPage(),
                    'total' => $logs->total(),
                    'last_page' => $logs->lastPage(),
                ],
            ]);
        }

        // Get all users for filter dropdown
        $users = User::select('id', 'name', 'email')->orderBy('name')->get();

        // Get unique actions for filter
        $actions = [
            'created' => 'إنشاء',
            'updated' => 'تحديث',
            'deleted' => 'حذف',
            'login' => 'تسجيل دخول',
            'logout' => 'تسجيل خروج',
            'bulk_delete' => 'حذف جماعي',
            'bulk_publish' => 'نشر جماعي',
            'bulk_archive' => 'أرشفة جماعية',
        ];

        // Get unique model types for filter
        $modelTypes = [
            'App\Domain\Blog\Models\Article' => 'مقال',
            'App\Domain\Media\Models\MediaProgram' => 'برنامج إعلامي',
            'App\Domain\Seerah\Models\SeerahEvent' => 'حدث سيرة',
            'App\Domain\People\Models\Muslim' => 'صحابي',
            'App\Domain\Quran\Models\QuranVerse' => 'آية قرآنية',
            'App\Domain\People\Models\Muslim2' => 'شخصية',
            'App\Models\User' => 'مستخدم',
        ];

        return view('admin.activity-logs.index', compact(
            'logs',
            'stats',
            'users',
            'actions',
            'modelTypes',
            'filters'
        ));
    }

    /**
     * Display the specified activity log
     *
     * GET /su/portal/v1/admin/activity-logs/{id}
     */
    public function show(int $id): View
    {
        $log = $this->logRepository->findById($id);

        if (! $log) {
            abort(404, 'Activity log not found');
        }

        // Check if the model still exists
        $modelExists = false;
        $modelUrl = null;

        if ($log->model_type && $log->model_id) {
            try {
                $modelClass = $log->model_type;
                if (class_exists($modelClass)) {
                    $model = $modelClass::find($log->model_id);
                    $modelExists = $model !== null;

                    // Generate URL based on model type
                    if ($modelExists) {
                        $modelUrl = $this->getModelUrl($log->model_type, $log->model_id);
                    }
                }
            } catch (\Throwable $e) {
                // Model doesn't exist or can't be loaded
                $modelExists = false;
            }
        }

        return view('admin.activity-logs.show', compact('log', 'modelExists', 'modelUrl'));
    }

    /**
     * Display activity logs for a specific user
     *
     * GET /su/portal/v1/admin/activity-logs/user/{userId}
     */
    public function userActivity(int $userId): View
    {
        $user = User::findOrFail($userId);
        $logs = $this->logRepository->getUserActivity($userId);

        return view('admin.activity-logs.user', compact('logs', 'user'));
    }

    /**
     * Display history for a specific model
     *
     * GET /su/portal/v1/admin/activity-logs/model/{type}/{id}
     */
    public function modelHistory(string $type, int $id): View
    {
        // Decode the model type (URL encoded)
        $modelType = urldecode($type);

        $logs = $this->logRepository->getModelHistory($modelType, $id);

        // Get model name for display
        $modelName = $this->getModelName($modelType);

        return view('admin.activity-logs.model', compact('logs', 'modelType', 'modelName', 'id'));
    }

    /**
     * Get model URL for linking
     */
    protected function getModelUrl(string $modelType, int $modelId): ?string
    {
        $routes = [
            'App\Domain\Blog\Models\Article' => 'admin.articles.show',
            'App\Domain\Media\Models\MediaProgram' => 'admin.media-programs.show',
            'App\Domain\Seerah\Models\SeerahEvent' => 'admin.seerah-events.show',
            'App\Domain\People\Models\Muslim' => 'admin.muslims.edit',
            'App\Domain\Quran\Models\QuranVerse' => 'admin.quran.show',
            'App\Domain\People\Models\Muslim2' => 'admin.genealogy.show',
            'App\Models\User' => 'admin.users.show',
        ];

        if (isset($routes[$modelType])) {
            try {
                return route($routes[$modelType], $modelId);
            } catch (\Throwable $e) {
                return null;
            }
        }

        return null;
    }

    /**
     * Get human-readable model name
     */
    protected function getModelName(string $modelType): string
    {
        $names = [
            'App\Domain\Blog\Models\Article' => 'مقال',
            'App\Domain\Media\Models\MediaProgram' => 'برنامج إعلامي',
            'App\Domain\Seerah\Models\SeerahEvent' => 'حدث سيرة',
            'App\Domain\People\Models\Muslim' => 'صحابي',
            'App\Domain\Quran\Models\QuranVerse' => 'آية قرآنية',
            'App\Domain\People\Models\Muslim2' => 'شخصية',
            'App\Models\User' => 'مستخدم',
        ];

        return $names[$modelType] ?? 'غير معروف';
    }
}
