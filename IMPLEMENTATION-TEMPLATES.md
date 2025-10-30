# Implementation Templates

This document provides copy-paste templates for consistent code patterns across all admin modules.

---

## 1. Controller Template

**File:** `app/Http/Controllers/Admin/{Resource}Controller.php`

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domain\{Domain}\Services\{Resource}Service;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Store{Resource}Request;
use App\Http\Requests\Admin\Update{Resource}Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class {Resource}Controller extends Controller
{
    /**
     * Constructor with dependency injection
     */
    public function __construct(
        protected {Resource}Service $service
    ) {
        // Protect routes - only authenticated admins with permission
        $this->middleware('auth:sanctum');
        $this->middleware('permission:{resource}.view')->only(['index', 'show']);
        $this->middleware('permission:{resource}.create')->only(['create', 'store']);
        $this->middleware('permission:{resource}.update')->only(['edit', 'update']);
        $this->middleware('permission:{resource}.delete')->only(['destroy', 'bulkDelete']);
    }

    /**
     * Display paginated list of resources
     *
     * GET /admin/{resources}
     *
     * @param Request $request
     * @return View|JsonResponse
     */
    public function index(Request $request): View|JsonResponse
    {
        // Get pagination and search parameters
        $perPage = $request->integer('per_page', 50);
        $search = $request->string('search')->trim();

        // Get filters from request
        $filters = $request->only(['status', 'filter_field', 'date_from', 'date_to']);

        // Fetch paginated data with filters applied
        $items = $this->service->paginate(
            perPage: $perPage,
            search: $search,
            filters: $filters
        );

        // Get statistics for stats cards
        $stats = [
            'total' => $this->service->count(),
            'published' => $this->service->countByStatus('published'),
            'draft' => $this->service->countByStatus('draft'),
            'archived' => $this->service->countByStatus('archived'),
        ];

        // Return JSON for AJAX requests, HTML for page loads
        if ($request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
            return response()->json([
                'success' => true,
                'data' => $items->items(),
                'stats' => $stats,
                'meta' => [
                    'current_page' => $items->currentPage(),
                    'per_page' => $items->perPage(),
                    'total' => $items->total(),
                    'last_page' => $items->lastPage(),
                ]
            ]);
        }

        return view('admin.{resources}.index', compact('items', 'stats'));
    }

    /**
     * Show create form
     *
     * GET /admin/{resources}/create
     *
     * @return View
     */
    public function create(): View
    {
        return view('admin.{resources}.create');
    }

    /**
     * Store new resource
     *
     * POST /admin/{resources}
     *
     * @param Store{Resource}Request $request
     * @return JsonResponse|View
     */
    public function store(Store{Resource}Request $request): JsonResponse
    {
        try {
            $item = $this->service->create($request->validated());

            return response()->json([
                'success' => true,
                'message' => '{Resource} created successfully',
                'data' => $item,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create {resource}',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Show edit form
     *
     * GET /admin/{resources}/{id}/edit
     *
     * @param int $id
     * @return View
     */
    public function edit(int $id): View
    {
        $item = $this->service->findOrFail($id);
        return view('admin.{resources}.edit', compact('item'));
    }

    /**
     * Update resource
     *
     * PUT /admin/{resources}/{id}
     *
     * @param Update{Resource}Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Update{Resource}Request $request, int $id): JsonResponse
    {
        try {
            $item = $this->service->update($id, $request->validated());

            return response()->json([
                'success' => true,
                'message' => '{Resource} updated successfully',
                'data' => $item,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update {resource}',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Show resource details
     *
     * GET /admin/{resources}/{id}
     *
     * @param int $id
     * @return View
     */
    public function show(int $id): View
    {
        $item = $this->service->findOrFail($id);
        return view('admin.{resources}.show', compact('item'));
    }

    /**
     * Delete resource
     *
     * DELETE /admin/{resources}/{id}
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->service->delete($id);

            return response()->json([
                'success' => true,
                'message' => '{Resource} deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete {resource}',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Bulk delete multiple resources
     *
     * POST /admin/{resources}/bulk-delete
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function bulkDelete(Request $request): JsonResponse
    {
        $ids = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:{table},id',
        ])['ids'];

        try {
            $count = $this->service->bulkDelete($ids);

            return response()->json([
                'success' => true,
                'message' => "$count {resources} deleted successfully",
                'count' => $count,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete {resources}',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Reorder items (for drag-drop functionality)
     *
     * POST /admin/{resources}/reorder
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function reorder(Request $request): JsonResponse
    {
        $items = $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|integer|exists:{table},id',
            'items.*.order' => 'required|integer|min:0',
        ])['items'];

        try {
            $this->service->reorder($items);

            return response()->json([
                'success' => true,
                'message' => '{Resources} reordered successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reorder {resources}',
                'error' => $e->getMessage(),
            ], 422);
        }
    }
}
```

---

## 2. Form Request Template

**File:** `app/Http/Requests/Admin/Store{Resource}Request.php`

```php
<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class Store{Resource}Request extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request
     */
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasPermissionTo('{resource}.create');
    }

    /**
     * Get the validation rules that apply to the request
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Multi-language fields
            'name.ar' => 'required|string|max:255',
            'name.en' => 'required|string|max:255',
            'name.fr' => 'nullable|string|max:255',
            'name.tr' => 'nullable|string|max:255',
            'name.ur' => 'nullable|string|max:255',

            'description.ar' => 'nullable|string|max:1000',
            'description.en' => 'nullable|string|max:1000',
            'description.fr' => 'nullable|string|max:1000',
            'description.tr' => 'nullable|string|max:1000',
            'description.ur' => 'nullable|string|max:1000',

            // Other fields
            'status' => 'required|in:draft,published,archived',
            'type' => 'required|string',
            'parent_id' => 'nullable|exists:table,id',
            'display_order' => 'nullable|integer|min:0',
        ];
    }

    /**
     * Get custom messages for validator errors
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.ar.required' => 'Arabic name is required',
            'name.en.required' => 'English name is required',
            'status.required' => 'Status is required',
            'status.in' => 'Invalid status value',
        ];
    }

    /**
     * Prepare the data for validation
     */
    protected function prepareForValidation(): void
    {
        // Convert parent_id to null if empty
        if (empty($this->parent_id)) {
            $this->merge(['parent_id' => null]);
        }

        // Set default status if not provided
        if (!$this->has('status')) {
            $this->merge(['status' => 'draft']);
        }
    }
}
```

**File:** `app/Http/Requests/Admin/Update{Resource}Request.php`

```php
<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class Update{Resource}Request extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request
     */
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasPermissionTo('{resource}.update');
    }

    /**
     * Get the validation rules that apply to the request
     */
    public function rules(): array
    {
        return [
            // Same as Store request
            'name.ar' => 'required|string|max:255',
            'name.en' => 'required|string|max:255',
            'name.fr' => 'nullable|string|max:255',
            'name.tr' => 'nullable|string|max:255',
            'name.ur' => 'nullable|string|max:255',

            'description.ar' => 'nullable|string|max:1000',
            'description.en' => 'nullable|string|max:1000',
            'description.fr' => 'nullable|string|max:1000',
            'description.tr' => 'nullable|string|max:1000',
            'description.ur' => 'nullable|string|max:1000',

            'status' => 'required|in:draft,published,archived',
            'type' => 'required|string',
            'parent_id' => 'nullable|exists:table,id',
            'display_order' => 'nullable|integer|min:0',
        ];
    }
}
```

---

## 3. Service Template

**File:** `app/Domain/{Domain}/Services/{Resource}Service.php`

```php
<?php

declare(strict_types=1);

namespace App\Domain\{Domain}\Services;

use App\Domain\{Domain}\Repositories\{Resource}RepositoryInterface;
use App\Domain\Shared\Services\BaseService;
use Illuminate\Pagination\Paginator;

class {Resource}Service extends BaseService
{
    /**
     * Constructor with repository injection
     */
    public function __construct(
        protected {Resource}RepositoryInterface $repository
    ) {
        $this->repository = $repository;
    }

    /**
     * Get paginated items with search and filters
     *
     * @param int $perPage
     * @param string $search
     * @param array $filters
     * @param array $with (relationships to eager load)
     * @return Paginator
     */
    public function paginate(
        int $perPage = 50,
        string $search = '',
        array $filters = [],
        array $with = []
    ): Paginator {
        return $this->repository->paginate(
            perPage: $perPage,
            search: $search,
            filters: $filters,
            with: $with
        );
    }

    /**
     * Create new item
     *
     * @param array $data
     * @return mixed
     */
    public function create(array $data): mixed
    {
        return $this->repository->create($data);
    }

    /**
     * Update item
     *
     * @param int $id
     * @param array $data
     * @return mixed
     */
    public function update(int $id, array $data): mixed
    {
        return $this->repository->update($id, $data);
    }

    /**
     * Delete item
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        return $this->repository->delete($id);
    }

    /**
     * Bulk delete multiple items
     *
     * @param array $ids
     * @return int (number of deleted items)
     */
    public function bulkDelete(array $ids): int
    {
        return $this->repository->bulkDelete($ids);
    }

    /**
     * Find item by ID or throw exception
     *
     * @param int $id
     * @return mixed
     */
    public function findOrFail(int $id): mixed
    {
        return $this->repository->findOrFail($id);
    }

    /**
     * Get total count
     *
     * @return int
     */
    public function count(): int
    {
        return $this->repository->count();
    }

    /**
     * Count items by status
     *
     * @param string $status
     * @return int
     */
    public function countByStatus(string $status): int
    {
        return $this->repository->countByStatus($status);
    }

    /**
     * Reorder items
     *
     * @param array $items (each item: ['id' => int, 'order' => int])
     * @return void
     */
    public function reorder(array $items): void
    {
        foreach ($items as $item) {
            $this->repository->update($item['id'], ['display_order' => $item['order']]);
        }
    }

    /**
     * Additional business logic methods here...
     */
}
```

---

## 4. Repository Interface Template

**File:** `app/Domain/{Domain}/Repositories/{Resource}RepositoryInterface.php`

```php
<?php

declare(strict_types=1);

namespace App\Domain\{Domain}\Repositories;

use App\Domain\Shared\Repositories\RepositoryInterface;
use Illuminate\Pagination\Paginator;

interface {Resource}RepositoryInterface extends RepositoryInterface
{
    /**
     * Get paginated items with filters
     */
    public function paginate(
        int $perPage = 50,
        string $search = '',
        array $filters = [],
        array $with = []
    ): Paginator;

    /**
     * Count items by status
     */
    public function countByStatus(string $status): int;
}
```

---

## 5. Repository Implementation Template

**File:** `app/Domain/{Domain}/Repositories/{Resource}Repository.php`

```php
<?php

declare(strict_types=1);

namespace App\Domain\{Domain}\Repositories;

use App\Domain\{Domain}\Models\{Resource};
use App\Domain\Shared\Repositories\BaseRepository;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Cache;

class {Resource}Repository extends BaseRepository implements {Resource}RepositoryInterface
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->model = {Resource}::class;
    }

    /**
     * Get base query with eager loaded relationships
     *
     * @return mixed
     */
    protected function getBaseQuery(): mixed
    {
        return $this->model::with([
            'relation1',
            'relation2',
            // Add relationships
        ]);
    }

    /**
     * Get paginated items with search and filters
     */
    public function paginate(
        int $perPage = 50,
        string $search = '',
        array $filters = [],
        array $with = []
    ): Paginator {
        $query = $this->getBaseQuery();

        // Apply search
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->whereJsonContains('name->en', $search, 'like')
                  ->orWhereJsonContains('name->ar', $search, 'like')
                  ->orWhereJsonContains('description->en', $search, 'like');
            });
        }

        // Apply filters
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        // Apply additional eager loading
        if (!empty($with)) {
            $query->with($with);
        }

        return $query->paginate($perPage);
    }

    /**
     * Count items by status
     */
    public function countByStatus(string $status): int
    {
        return Cache::tags(['{resource}'])->remember(
            "{resource}:count:$status",
            now()->addMinutes(15),
            fn() => $this->model::where('status', $status)->count()
        );
    }

    /**
     * Create new item
     */
    public function create(array $data): mixed
    {
        $item = $this->model::create($data);
        Cache::tags(['{resource}'])->flush(); // Invalidate cache
        return $item;
    }

    /**
     * Update item
     */
    public function update(int $id, array $data): mixed
    {
        $item = $this->findOrFail($id);
        $item->update($data);
        Cache::tags(['{resource}'])->flush(); // Invalidate cache
        return $item;
    }

    /**
     * Delete item
     */
    public function delete(int $id): bool
    {
        $result = $this->findOrFail($id)->delete();
        Cache::tags(['{resource}'])->flush(); // Invalidate cache
        return $result;
    }

    /**
     * Bulk delete items
     */
    public function bulkDelete(array $ids): int
    {
        $count = $this->model::whereIn('id', $ids)->delete();
        Cache::tags(['{resource}'])->flush(); // Invalidate cache
        return $count;
    }
}
```

---

## 6. Blade View Templates

### 6.1 Index View Template

**File:** `resources/views/admin/{resources}/index.blade.php`

```blade
@extends('layouts.admin')

@section('title', '{Resources}')

@section('content')
<div class="container mx-auto px-4 py-8" x-data="{resourceName}Manager()">
    <!-- Header -->
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">
                {Resources}
            </h1>
            <p class="mt-2" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                Manage all {resource} in the system
            </p>
        </div>
        <a href="{{ route('admin.{resources}.create') }}"
           class="bg-gradient-to-r from-[#0056b3] to-[#218838] text-white px-6 py-3 rounded-lg font-semibold hover:shadow-lg transition-all">
            + Create New
        </a>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="glass rounded-lg p-6" :class="darkMode ? 'bg-gray-900/50' : 'bg-white/80'">
            <p class="text-sm font-semibold" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">Total {Resources}</p>
            <p class="text-4xl font-bold mt-2 text-[#0056b3]" x-text="stats.total ?? 0"></p>
        </div>
        <div class="glass rounded-lg p-6" :class="darkMode ? 'bg-gray-900/50' : 'bg-white/80'">
            <p class="text-sm font-semibold" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">Published</p>
            <p class="text-4xl font-bold mt-2 text-[#218838]" x-text="stats.published ?? 0"></p>
        </div>
        <div class="glass rounded-lg p-6" :class="darkMode ? 'bg-gray-900/50' : 'bg-white/80'">
            <p class="text-sm font-semibold" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">Drafts</p>
            <p class="text-4xl font-bold mt-2 text-yellow-500" x-text="stats.draft ?? 0"></p>
        </div>
        <div class="glass rounded-lg p-6" :class="darkMode ? 'bg-gray-900/50' : 'bg-white/80'">
            <p class="text-sm font-semibold" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">Archived</p>
            <p class="text-4xl font-bold mt-2 text-gray-500" x-text="stats.archived ?? 0"></p>
        </div>
    </div>

    <!-- Search & Filters -->
    <div class="glass rounded-lg p-6 mb-6" :class="darkMode ? 'bg-gray-900/50' : 'bg-white/80'">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-semibold mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                    Search
                </label>
                <input type="text"
                       x-model="search"
                       placeholder="Search {resources}..."
                       @input="debounce(() => loadData(), 500)"
                       class="w-full px-4 py-2 rounded-lg border focus:outline-none focus:ring-2 focus:ring-[#0056b3]"
                       :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
            </div>
            <div>
                <label class="block text-sm font-semibold mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                    Status
                </label>
                <select x-model="filters.status"
                        @change="loadData()"
                        class="w-full px-4 py-2 rounded-lg border focus:outline-none focus:ring-2 focus:ring-[#0056b3]"
                        :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                    <option value="">All Statuses</option>
                    <option value="draft">Draft</option>
                    <option value="published">Published</option>
                    <option value="archived">Archived</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                    Type
                </label>
                <select x-model="filters.type"
                        @change="loadData()"
                        class="w-full px-4 py-2 rounded-lg border focus:outline-none focus:ring-2 focus:ring-[#0056b3]"
                        :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                    <option value="">All Types</option>
                    <option value="type1">Type 1</option>
                    <option value="type2">Type 2</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                    &nbsp;
                </label>
                <button @click="loadData()"
                        class="w-full bg-[#0056b3] text-white px-4 py-2 rounded-lg font-semibold hover:bg-blue-700 transition">
                    Search
                </button>
            </div>
        </div>
    </div>

    <!-- Bulk Actions -->
    <div class="glass rounded-lg p-4 mb-6" :class="darkMode ? 'bg-gray-900/50' : 'bg-white/80'" x-show="selectedCount > 0">
        <div class="flex items-center justify-between">
            <span class="font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                <span x-text="selectedCount"></span> item(s) selected
            </span>
            <button @click="bulkDelete()"
                    class="bg-red-600 text-white px-6 py-2 rounded-lg font-semibold hover:bg-red-700 transition">
                Delete Selected
            </button>
        </div>
    </div>

    <!-- Items Table -->
    <div class="glass rounded-lg overflow-hidden" :class="darkMode ? 'bg-gray-900/50' : 'bg-white/80'">
        <template x-if="loading">
            <div class="p-8">
                <div class="space-y-4">
                    <div class="h-16 bg-gray-200 rounded animate-pulse" :class="darkMode ? 'bg-gray-800' : ''"></div>
                    <div class="h-16 bg-gray-200 rounded animate-pulse" :class="darkMode ? 'bg-gray-800' : ''"></div>
                    <div class="h-16 bg-gray-200 rounded animate-pulse" :class="darkMode ? 'bg-gray-800' : ''"></div>
                </div>
            </div>
        </template>

        <template x-if="!loading && items.length > 0">
            <table class="w-full">
                <thead :class="darkMode ? 'bg-gray-800' : 'bg-gray-100'">
                    <tr>
                        <th class="px-6 py-3 text-left text-sm font-semibold">
                            <input type="checkbox" @change="toggleSelectAll()" class="rounded">
                        </th>
                        <th class="px-6 py-3 text-left text-sm font-semibold">Name</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold">Type</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold">Status</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold">Date</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="item in items" :key="item.id">
                        <tr :class="darkMode ? 'border-gray-700 hover:bg-gray-800' : 'border-gray-200 hover:bg-gray-50'" class="border-t transition">
                            <td class="px-6 py-4">
                                <input type="checkbox" :value="item.id" x-model="selected" @change="updateSelectedCount()" class="rounded">
                            </td>
                            <td class="px-6 py-4 font-semibold" :class="darkMode ? 'text-white' : 'text-gray-900'" x-text="item.name?.en || item.name?.ar || '—'"></td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 rounded-full text-xs font-semibold" :class="'bg-blue-100 text-blue-800'" x-text="item.type"></span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 rounded-full text-xs font-semibold"
                                      :class="item.status === 'published' ? 'bg-green-100 text-green-800' : item.status === 'draft' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800'"
                                      x-text="item.status"></span>
                            </td>
                            <td class="px-6 py-4 text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'" x-text="new Date(item.created_at).toLocaleDateString()"></td>
                            <td class="px-6 py-4">
                                <a :href="`/su/portal/v1/admin/{resources}/${item.id}/edit`"
                                   class="text-[#0056b3] hover:text-blue-700 font-semibold text-sm mr-3">
                                    Edit
                                </a>
                                <button @click="deleteItem(item.id)"
                                        class="text-red-600 hover:text-red-700 font-semibold text-sm">
                                    Delete
                                </button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </template>

        <template x-if="!loading && items.length === 0">
            <div class="p-8 text-center">
                <p :class="darkMode ? 'text-gray-400' : 'text-gray-600'" class="text-lg">
                    No {resources} found
                </p>
            </div>
        </template>
    </div>

    <!-- Pagination -->
    <div class="mt-6 flex justify-between items-center">
        <p :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
            Showing <span x-text="(currentPage - 1) * perPage + 1"></span>
            to <span x-text="Math.min(currentPage * perPage, totalItems)"></span>
            of <span x-text="totalItems"></span> items
        </p>
        <div class="flex gap-2">
            <button @click="previousPage()"
                    :disabled="currentPage === 1"
                    :class="currentPage === 1 ? 'opacity-50 cursor-not-allowed' : ''"
                    class="px-4 py-2 bg-[#0056b3] text-white rounded-lg hover:bg-blue-700 disabled:bg-gray-400 transition">
                Previous
            </button>
            <button @click="nextPage()"
                    :disabled="currentPage >= totalPages"
                    :class="currentPage >= totalPages ? 'opacity-50 cursor-not-allowed' : ''"
                    class="px-4 py-2 bg-[#0056b3] text-white rounded-lg hover:bg-blue-700 disabled:bg-gray-400 transition">
                Next
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script>
function {resourceName}Manager() {
    return {
        items: [],
        search: '',
        filters: { status: '', type: '' },
        selected: [],
        currentPage: 1,
        perPage: 50,
        totalItems: 0,
        totalPages: 1,
        loading: false,
        stats: { total: 0, published: 0, draft: 0, archived: 0 },

        init() {
            this.loadData();
        },

        async loadData() {
            this.loading = true;
            const params = new URLSearchParams({
                page: this.currentPage,
                per_page: this.perPage,
                search: this.search,
                ...this.filters
            });

            try {
                const response = await fetch(`/su/portal/v1/admin/{resources}?${params}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });

                if (response.ok) {
                    const data = await response.json();
                    this.items = data.data || [];
                    this.stats = data.stats || this.stats;
                    this.totalItems = data.meta?.total || 0;
                    this.totalPages = data.meta?.last_page || 1;
                } else {
                    console.error('Failed to load data');
                }
            } catch (error) {
                console.error('Error:', error);
            } finally {
                this.loading = false;
            }
        },

        nextPage() {
            if (this.currentPage < this.totalPages) {
                this.currentPage++;
                this.selected = [];
                this.loadData();
            }
        },

        previousPage() {
            if (this.currentPage > 1) {
                this.currentPage--;
                this.selected = [];
                this.loadData();
            }
        },

        toggleSelectAll() {
            if (this.selected.length === this.items.length) {
                this.selected = [];
            } else {
                this.selected = this.items.map(item => item.id);
            }
            this.updateSelectedCount();
        },

        updateSelectedCount() {
            this.selectedCount = this.selected.length;
        },

        async deleteItem(id) {
            if (!confirm('Are you sure?')) return;

            try {
                const response = await fetch(`/su/portal/v1/admin/{resources}/${id}`, {
                    method: 'DELETE',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });

                if (response.ok) {
                    this.loadData();
                    showNotification('Item deleted successfully');
                }
            } catch (error) {
                console.error('Error:', error);
            }
        },

        async bulkDelete() {
            if (!confirm(`Delete ${this.selected.length} items?`)) return;

            try {
                const response = await fetch(`/su/portal/v1/admin/{resources}/bulk-delete`, {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    body: JSON.stringify({ ids: this.selected })
                });

                if (response.ok) {
                    this.selected = [];
                    this.loadData();
                    showNotification('Items deleted successfully');
                }
            } catch (error) {
                console.error('Error:', error);
            }
        },

        debounce(fn, delay) {
            clearTimeout(this.debounceTimer);
            this.debounceTimer = setTimeout(fn, delay);
        },

        get selectedCount() {
            return this.selected.length;
        }
    };
}
</script>
@endpush
@endsection
```

### 6.2 Create/Edit View Template

**File:** `resources/views/admin/{resources}/create.blade.php` and `edit.blade.php`

```blade
@extends('layouts.admin')

@section('title', 'Create {Resource}')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">
            Create New {Resource}
        </h1>
        <p class="mt-2" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
            Fill in the information below
        </p>
    </div>

    <!-- Form -->
    <form action="{{ route('admin.{resources}.store') }}" method="POST" enctype="multipart/form-data"
          class="glass rounded-lg p-8 max-w-4xl" :class="darkMode ? 'bg-gray-900/50' : 'bg-white/80'">
        @csrf

        <!-- Language Tabs -->
        <div class="flex gap-2 mb-6 border-b" :class="darkMode ? 'border-gray-700' : 'border-gray-200'">
            @foreach(['ar' => 'العربية', 'en' => 'English', 'fr' => 'Français', 'tr' => 'Türkçe', 'ur' => 'اردو'] as $lang => $label)
                <button type="button"
                        @click="activeLanguage = '{{ $lang }}'"
                        :class="activeLanguage === '{{ $lang }}' ? 'border-b-2 border-[#0056b3] font-bold' : ''"
                        class="px-4 py-2 transition"
                        :class="darkMode ? 'text-white hover:text-[#64CCC5]' : 'text-gray-700 hover:text-[#0056b3]'">
                    {{ $label }}
                </button>
            @endforeach
        </div>

        <!-- Multi-language Fields -->
        @foreach(['ar', 'en', 'fr', 'tr', 'ur'] as $lang)
            <div x-show="activeLanguage === '{{ $lang }}'" class="space-y-6">
                <!-- Name Field -->
                <div>
                    <label class="block text-sm font-semibold mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                        Name ({{ strtoupper($lang) }})
                    </label>
                    <input type="text"
                           name="name[{{ $lang }}]"
                           value="{{ old("name.$lang") }}"
                           required
                           class="w-full px-4 py-2 rounded-lg border focus:outline-none focus:ring-2 focus:ring-[#0056b3]"
                           :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                    @error("name.$lang")
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Description Field -->
                <div>
                    <label class="block text-sm font-semibold mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                        Description ({{ strtoupper($lang) }})
                    </label>
                    <textarea name="description[{{ $lang }}]"
                              rows="4"
                              class="w-full px-4 py-2 rounded-lg border focus:outline-none focus:ring-2 focus:ring-[#0056b3]"
                              :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">{{ old("description.$lang") }}</textarea>
                </div>
            </div>
        @endforeach

        <!-- Other Fields -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
            <!-- Status -->
            <div>
                <label class="block text-sm font-semibold mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                    Status
                </label>
                <select name="status"
                        class="w-full px-4 py-2 rounded-lg border focus:outline-none focus:ring-2 focus:ring-[#0056b3]"
                        :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                    <option value="draft">Draft</option>
                    <option value="published">Published</option>
                </select>
            </div>

            <!-- Type -->
            <div>
                <label class="block text-sm font-semibold mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                    Type
                </label>
                <select name="type"
                        class="w-full px-4 py-2 rounded-lg border focus:outline-none focus:ring-2 focus:ring-[#0056b3]"
                        :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                    <option value="">Select Type</option>
                    <option value="blog">Blog</option>
                    <option value="media">Media</option>
                </select>
            </div>
        </div>

        <!-- Buttons -->
        <div class="flex gap-4 mt-8">
            <button type="submit"
                    class="bg-gradient-to-r from-[#0056b3] to-[#218838] text-white px-8 py-3 rounded-lg font-semibold hover:shadow-lg transition">
                Create {Resource}
            </button>
            <a href="{{ route('admin.{resources}.index') }}"
               class="px-8 py-3 rounded-lg font-semibold border transition"
               :class="darkMode ? 'border-gray-700 text-white hover:bg-gray-800' : 'border-gray-300 text-gray-900 hover:bg-gray-100'">
                Cancel
            </a>
        </div>
    </form>
</div>

<script>
function init() {
    return {
        activeLanguage: 'en',
        darkMode: localStorage.getItem('darkMode') !== 'false',
    };
}
</script>
@endsection
```

---

## 7. Routes Template

**File:** `routes/admin.php` (add to existing routes)

```php
Route::prefix('admin')->name('admin.')->middleware(['auth:sanctum', 'admin'])->group(function () {
    // {Resource} Management
    Route::get('{resources}', [{Resource}Controller::class, 'index'])->name('{resources}.index');
    Route::get('{resources}/create', [{Resource}Controller::class, 'create'])->name('{resources}.create');
    Route::post('{resources}', [{Resource}Controller::class, 'store'])->name('{resources}.store');
    Route::get('{resources}/{id}/edit', [{Resource}Controller::class, 'edit'])->name('{resources}.edit');
    Route::put('{resources}/{id}', [{Resource}Controller::class, 'update'])->name('{resources}.update');
    Route::delete('{resources}/{id}', [{Resource}Controller::class, 'destroy'])->name('{resources}.destroy');
    Route::post('{resources}/bulk-delete', [{Resource}Controller::class, 'bulkDelete'])->name('{resources}.bulk-delete');
    Route::post('{resources}/reorder', [{Resource}Controller::class, 'reorder'])->name('{resources}.reorder');
});
```

---

## 8. Sidebar Navigation Template

**File:** `resources/views/admin/partials/sidebar.blade.php` (add to appropriate section)

```blade
<!-- Blog Section -->
<div class="space-y-2">
    <h3 class="px-4 py-2 text-xs font-bold uppercase" :class="darkMode ? 'text-gray-500' : 'text-gray-600'">
        Blog
    </h3>
    <a href="{{ route('admin.articles.index') }}"
       class="flex items-center gap-3 px-4 py-2 rounded-lg transition"
       :class="request()->is('*/admin/articles*') ? 'bg-gradient-to-r from-[#0056b3]/20 to-[#218838]/20 text-[#0056b3]' : (darkMode ? 'text-gray-400 hover:text-white' : 'text-gray-600 hover:text-gray-900')">
        <i data-lucide="file-text" class="w-5 h-5"></i>
        <span>Articles</span>
    </a>
    <a href="{{ route('admin.categories.index') }}"
       class="flex items-center gap-3 px-4 py-2 rounded-lg transition"
       :class="request()->is('*/admin/categories*') ? 'bg-gradient-to-r from-[#0056b3]/20 to-[#218838]/20 text-[#0056b3]' : (darkMode ? 'text-gray-400 hover:text-white' : 'text-gray-600 hover:text-gray-900')">
        <i data-lucide="folder" class="w-5 h-5"></i>
        <span>Categories</span>
    </a>
    <a href="{{ route('admin.tags.index') }}"
       class="flex items-center gap-3 px-4 py-2 rounded-lg transition"
       :class="request()->is('*/admin/tags*') ? 'bg-gradient-to-r from-[#0056b3]/20 to-[#218838]/20 text-[#0056b3]' : (darkMode ? 'text-gray-400 hover:text-white' : 'text-gray-600 hover:text-gray-900')">
        <i data-lucide="tags" class="w-5 h-5"></i>
        <span>Tags</span>
    </a>
    <a href="{{ route('admin.series.index') }}"
       class="flex items-center gap-3 px-4 py-2 rounded-lg transition"
       :class="request()->is('*/admin/series*') ? 'bg-gradient-to-r from-[#0056b3]/20 to-[#218838]/20 text-[#0056b3]' : (darkMode ? 'text-gray-400 hover:text-white' : 'text-gray-600 hover:text-gray-900')">
        <i data-lucide="layers" class="w-5 h-5"></i>
        <span>Series</span>
    </a>
</div>
```

---

## 9. Testing Template

**File:** `tests/Feature/Api/{Resource}ControllerTest.php`

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Domain\{Domain}\Models\{Resource};
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class {Resource}ControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    /** @test */
    public function it_can_list_{resources}()
    {
        {Resource}::factory(5)->create();

        $response = $this->actingAs($this->user)
            ->getJson('/su/portal/v1/admin/{resources}');

        $response->assertStatus(200)
                 ->assertJsonStructure(['data', 'meta', 'stats']);
    }

    /** @test */
    public function it_can_create_{resource}()
    {
        $data = {Resource}::factory()->make()->toArray();

        $response = $this->actingAs($this->user)
            ->postJson('/su/portal/v1/admin/{resources}', $data);

        $response->assertStatus(201)
                 ->assertJsonPath('success', true);

        $this->assertDatabaseHas('{table}', ['name->en' => $data['name']['en']]);
    }

    /** @test */
    public function it_can_update_{resource}()
    {
        ${resource} = {Resource}::factory()->create();
        $data = ['name' => ['en' => 'Updated Name']];

        $response = $this->actingAs($this->user)
            ->putJson("/su/portal/v1/admin/{resources}/{${resource}->id}", $data);

        $response->assertStatus(200)
                 ->assertJsonPath('success', true);
    }

    /** @test */
    public function it_can_delete_{resource}()
    {
        ${resource} = {Resource}::factory()->create();

        $response = $this->actingAs($this->user)
            ->deleteJson("/su/portal/v1/admin/{resources}/{${resource}->id}");

        $response->assertStatus(200)
                 ->assertJsonPath('success', true);

        $this->assertSoftDeleted('{table}', ['id' => ${resource}->id]);
    }

    /** @test */
    public function it_can_bulk_delete_{resources}()
    {
        ${resource}s = {Resource}::factory(3)->create();
        $ids = ${resource}s->pluck('id')->toArray();

        $response = $this->actingAs($this->user)
            ->postJson('/su/portal/v1/admin/{resources}/bulk-delete', ['ids' => $ids]);

        $response->assertStatus(200)
                 ->assertJsonPath('success', true);

        foreach ($ids as $id) {
            $this->assertSoftDeleted('{table}', ['id' => $id]);
        }
    }

    /** @test */
    public function it_validates_required_fields()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/su/portal/v1/admin/{resources}', [
                'name' => ['en' => ''], // Missing required field
            ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['name.en']);
    }

    /** @test */
    public function it_can_search_{resources}()
    {
        {Resource}::factory()->create(['name' => ['en' => 'Test Item']]);
        {Resource}::factory()->create(['name' => ['en' => 'Another Item']]);

        $response = $this->actingAs($this->user)
            ->getJson('/su/portal/v1/admin/{resources}?search=Test');

        $response->assertStatus(200)
                 ->assertJsonCount(1, 'data');
    }
}
```

---

## Implementation Checklist

When implementing a new module, follow this checklist:

- [ ] Create Controller (use template #1)
- [ ] Create Form Requests (use template #2)
- [ ] Create Service (use template #3)
- [ ] Create Repository Interface (use template #4)
- [ ] Create Repository Implementation (use template #5)
- [ ] Create Views (use templates #6.1, #6.2)
- [ ] Add Routes (use template #7)
- [ ] Update Sidebar (use template #8)
- [ ] Create Tests (use template #9)
- [ ] Test all CRUD operations
- [ ] Test AJAX requests
- [ ] Test dark/light mode
- [ ] Test mobile responsiveness
- [ ] Test permissions

---

**Note:** Replace placeholders:
- `{Resource}` → ResourceName (singular, PascalCase: Article, Category, Tag)
- `{resource}` → resource_name (singular, snake_case: article, category, tag)
- `{resources}` → resources_name (plural, snake_case: articles, categories, tags)
- `{Domain}` → DomainName (Blog, Hadith, Media, etc.)
- `{domain}` → domain_name (blog, hadith, media, etc.)
- `{table}` → table_name (articles, categories, tags, etc.)
- `{ResourceName}` → ResourceNameManager (for Alpine.js: ArticleManager, CategoryManager)
