{{--
/**
 * DataTable Component
 *
 * A powerful, feature-rich data table with sorting, filtering, pagination, and bulk actions.
 * Netflix-style design with glassmorphism, gradients, and premium UX.
 *
 * Action menu: shared dropdown managed by the DataTable component (not per-row x-teleport).
 * Filter sidebar: connected via filters-changed window event.
 *
 * @props
 * - columns: array (required) - Column definitions
 * - data: Collection|array (required) - Table data
 * - actions: array (optional) - Row actions ['edit', 'delete', 'view', 'duplicate']
 * - bulkActions: array (optional) - Bulk actions ['delete', 'publish', 'export']
 * - searchable: bool (optional) - Enable search functionality
 * - filterable: bool (optional) - Enable filters sidebar
 * - exportable: bool (optional) - Enable export button
 * - perPageOptions: array (optional) - Per page options [10, 25, 50, 100]
 * - emptyTitle: string (optional) - Empty state title
 * - emptyMessage: string (optional) - Empty state message
 * - emptyIcon: string (optional) - Empty state icon
 */
--}}

@props([
    'columns' => [],
    'data' => [],
    'actions' => [],
    'bulkActions' => [],
    'searchable' => true,
    'filterable' => false,
    'exportable' => true,
    'perPageOptions' => [10, 25, 50, 100],
    'emptyTitle' => null,
    'emptyMessage' => null,
    'emptyIcon' => 'inbox',
    'actionRoute' => null,
    'editRoute' => null,
    'deleteRoute' => null,
    'viewRoute' => null
])

@php
    $dataCollection = $data instanceof \Illuminate\Contracts\Pagination\Paginator
        || $data instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator
        ? collect($data->items())
        : collect($data);
    $totalRecords = $dataCollection->count();

    // Generate URL patterns with a placeholder ID replaced in JS
    $routePlaceholder = 9999999;
    try {
        $editRouteUrl    = $editRoute    ? route($editRoute,    $routePlaceholder) : null;
        $viewRouteUrl    = $viewRoute    ? route($viewRoute,    $routePlaceholder) : null;
        $deleteRouteUrl  = $deleteRoute  ? route($deleteRoute,  $routePlaceholder) : null;
    } catch (\Exception $e) {
        $editRouteUrl = $viewRouteUrl = $deleteRouteUrl = null;
    }
@endphp

<div x-data="dataTable()"
     x-init="init()"
     class="w-full"
     @open-row-menu="openRowMenu($event.detail)"
     @open-delete-confirm="openDeleteConfirm($event.detail.row)"
     @filters-changed.window="applyExternalFilters($event.detail)">

    <!-- Table Header: Search, Export -->
    <div class="mb-6 flex flex-col lg:flex-row gap-4 items-start lg:items-center justify-between">

        <!-- Left: Title & Bulk Actions -->
        <div class="flex items-center gap-4 flex-wrap">
            <div>
                <h2 class="text-2xl font-bold gradient-text">
                    {{ $slot->isEmpty() ? __('admin.data_table') : $slot }}
                </h2>
                <p class="text-sm mt-1" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                    <span x-text="filteredData.length"></span> {{ __('admin.records_found') }}
                </p>
            </div>

            <!-- Bulk Actions Toolbar -->
            <div x-show="selectedRows.length > 0"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 class="flex items-center gap-3 px-4 py-2 rounded-lg"
                 :class="darkMode ? 'bg-gray-800/50 border border-gray-700' : 'bg-white/80 border border-gray-300'">

                <span class="text-sm font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                    <span x-text="selectedRows.length"></span> {{ __('admin.selected') }}
                </span>

                @if(in_array('delete', $bulkActions))
                    <button @click="bulkDelete"
                            class="px-3 py-1.5 rounded-lg text-sm font-medium transition-all hover:scale-105
                                   bg-red-500 text-white hover:bg-red-600">
                        <i data-lucide="trash-2" class="w-4 h-4 inline-block me-1"></i>
                        {{ __('admin.delete') }}
                    </button>
                @endif

                @if(in_array('publish', $bulkActions))
                    <button @click="bulkPublish"
                            class="px-3 py-1.5 rounded-lg text-sm font-medium transition-all hover:scale-105
                                   bg-gradient-to-r from-[#0056b3] to-[#218838] text-white">
                        <i data-lucide="check-circle" class="w-4 h-4 inline-block me-1"></i>
                        {{ __('admin.publish') }}
                    </button>
                @endif

                @if(in_array('export', $bulkActions))
                    <button @click="exportSelected"
                            class="px-3 py-1.5 rounded-lg text-sm font-medium transition-all hover:scale-105"
                            :class="darkMode ? 'bg-gray-700 text-white hover:bg-gray-600' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'">
                        <i data-lucide="download" class="w-4 h-4 inline-block me-1"></i>
                        {{ __('admin.export') }}
                    </button>
                @endif

                <button @click="clearSelection"
                        class="px-3 py-1.5 rounded-lg text-sm font-medium transition-all hover:scale-105"
                        :class="darkMode ? 'text-gray-400 hover:text-white' : 'text-gray-600 hover:text-gray-900'">
                    {{ __('admin.clear') }}
                </button>
            </div>
        </div>

        <!-- Right: Search & Export -->
        <div class="flex items-center gap-3 w-full lg:w-auto">

            @if($searchable)
                <div class="relative flex-1 lg:w-80">
                    <i data-lucide="search"
                       class="absolute start-3 top-1/2 -translate-y-1/2 w-5 h-5"
                       :class="darkMode ? 'text-gray-400' : 'text-gray-500'"></i>
                    <input type="text"
                           x-model="searchQuery"
                           @input.debounce.300ms="filterData"
                           placeholder="{{ __('admin.search_placeholder') }}"
                           class="w-full ps-10 pe-10 py-2.5 rounded-xl border-2 transition-all duration-300 focus:ring-2 focus:ring-accent/50"
                           :class="darkMode ?
                               'bg-gray-800 border-gray-700 text-white placeholder-gray-500 focus:border-accent' :
                               'bg-white border-gray-300 text-gray-900 placeholder-gray-400 focus:border-accent'">
                    <button x-show="searchQuery"
                            @click="searchQuery = ''; filterData();"
                            class="absolute end-3 top-1/2 -translate-y-1/2 hover:scale-110 transition-transform"
                            :class="darkMode ? 'text-gray-400 hover:text-white' : 'text-gray-500 hover:text-gray-900'">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>
            @endif

            @if($exportable)
                <div x-data="{ exportOpen: false }" class="relative">
                    <button @click="exportOpen = !exportOpen"
                            class="px-4 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 flex items-center gap-2 whitespace-nowrap
                                   bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-lg hover:shadow-xl">
                        <i data-lucide="download" class="w-5 h-5"></i>
                        <span class="hidden sm:inline">{{ __('admin.export') }}</span>
                        <i data-lucide="chevron-down" class="w-4 h-4"></i>
                    </button>

                    <div x-show="exportOpen"
                         @click.away="exportOpen = false"
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-150"
                         x-transition:leave-start="opacity-100 scale-100"
                         x-transition:leave-end="opacity-0 scale-95"
                         class="absolute end-0 mt-2 w-48 rounded-xl shadow-xl z-50 overflow-hidden border"
                         :class="darkMode ? 'bg-gray-800/95 border-gray-700' : 'bg-white/95 border-gray-200'">

                        <button @click="exportData('csv'); exportOpen = false;"
                                class="w-full px-4 py-3 text-start transition-colors flex items-center gap-3"
                                :class="darkMode ? 'hover:bg-gray-700 text-white' : 'hover:bg-gray-50 text-gray-900'">
                            <i data-lucide="file-text" class="w-5 h-5 text-accent"></i>
                            <span class="font-medium">{{ __('admin.export_csv') }}</span>
                        </button>

                        <button @click="exportData('excel'); exportOpen = false;"
                                class="w-full px-4 py-3 text-start transition-colors flex items-center gap-3"
                                :class="darkMode ? 'hover:bg-gray-700 text-white' : 'hover:bg-gray-50 text-gray-900'">
                            <i data-lucide="file-spreadsheet" class="w-5 h-5 text-green-500"></i>
                            <span class="font-medium">{{ __('admin.export_excel') }}</span>
                        </button>

                        <button @click="exportData('pdf'); exportOpen = false;"
                                class="w-full px-4 py-3 text-start transition-colors flex items-center gap-3"
                                :class="darkMode ? 'hover:bg-gray-700 text-white' : 'hover:bg-gray-50 text-gray-900'">
                            <i data-lucide="file" class="w-5 h-5 text-red-500"></i>
                            <span class="font-medium">{{ __('admin.export_pdf') }}</span>
                        </button>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Table Container -->
    <div class="rounded-2xl shadow-xl border relative"
         :class="darkMode ? 'bg-gray-900 border-gray-800' : 'bg-white border-gray-200'"
         style="overflow: clip;">

        <!-- Loading Overlay -->
        <div x-show="loading"
             x-transition
             class="absolute inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center rounded-2xl">
            <div class="text-center">
                <div class="inline-block animate-spin rounded-full h-12 w-12 border-4 border-accent border-t-transparent"></div>
                <p class="mt-4 text-white font-semibold">{{ __('admin.loading') }}</p>
            </div>
        </div>

        <div class="overflow-x-auto custom-scrollbar">
            <table class="w-full">

                <!-- Table Header -->
                <x-admin.table-header
                    :columns="$columns"
                    :has-actions="!empty($actions)"
                    :has-bulk-select="!empty($bulkActions)"
                />

                <!-- Table Body -->
                <tbody>
                    <!-- Loading skeletons -->
                    <template x-if="loading">
                        @for($i = 0; $i < 5; $i++)
                            <tr class="border-b" :class="darkMode ? 'border-gray-800' : 'border-gray-200'">
                                @if(!empty($bulkActions))
                                    <td class="px-6 py-4">
                                        <div class="h-5 w-5 bg-gray-300 dark:bg-gray-700 rounded animate-pulse"></div>
                                    </td>
                                @endif
                                @foreach($columns as $column)
                                    <td class="px-6 py-4">
                                        <div class="h-4 bg-gray-300 dark:bg-gray-700 rounded animate-pulse w-3/4"></div>
                                    </td>
                                @endforeach
                                @if(!empty($actions))
                                    <td class="px-6 py-4">
                                        <div class="h-8 w-8 bg-gray-300 dark:bg-gray-700 rounded animate-pulse"></div>
                                    </td>
                                @endif
                            </tr>
                        @endfor
                    </template>

                    <!-- Actual data rows -->
                    <template x-if="!loading && paginatedData.length > 0">
                        <template x-for="(row, index) in paginatedData" :key="row.id || index">
                            <x-admin.table-row
                                :columns="$columns"
                                :actions="$actions"
                                :has-bulk-select="!empty($bulkActions)"
                                :edit-route-url="$editRouteUrl"
                                :delete-route-url="$deleteRouteUrl"
                                :view-route-url="$viewRouteUrl"
                                :route-placeholder="$routePlaceholder"
                            />
                        </template>
                    </template>

                    <!-- Empty State -->
                    <template x-if="!loading && paginatedData.length === 0">
                        <tr>
                            <td :colspan="columnsCount" class="px-6 py-16">
                                <div class="text-center">
                                    <div class="inline-flex items-center justify-center w-20 h-20 rounded-full mb-4"
                                         :class="darkMode ? 'bg-gray-800' : 'bg-gray-100'">
                                        <i data-lucide="{{ $emptyIcon }}"
                                           class="w-10 h-10"
                                           :class="darkMode ? 'text-gray-600' : 'text-gray-400'"></i>
                                    </div>
                                    <h3 class="text-xl font-bold mb-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                                        {{ $emptyTitle ?? __('admin.no_data') }}
                                    </h3>
                                    <p :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                                        {{ $emptyMessage ?? __('admin.no_data_message') }}
                                    </p>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <!-- Pagination Footer -->
        <div class="border-t px-6 py-4"
             :class="darkMode ? 'border-gray-800 bg-gray-900/50' : 'border-gray-200 bg-gray-50'">

            <div class="flex flex-col sm:flex-row items-center justify-between gap-4">

                <div class="flex items-center gap-3">
                    <label class="text-sm font-medium" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                        {{ __('admin.rows_per_page') }}
                    </label>
                    <select x-model="perPage"
                            @change="changePage(1)"
                            class="px-3 py-1.5 rounded-lg border text-sm font-medium transition-all focus:ring-2 focus:ring-accent/50"
                            :class="darkMode ?
                                'bg-gray-800 border-gray-700 text-white' :
                                'bg-white border-gray-300 text-gray-900'">
                        @foreach($perPageOptions as $option)
                            <option value="{{ $option }}">{{ $option }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="text-sm font-medium" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                    {{ __('admin.showing') }}
                    <span class="font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'" x-text="startIndex + 1"></span>
                    {{ __('admin.to') }}
                    <span class="font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'" x-text="endIndex"></span>
                    {{ __('admin.of') }}
                    <span class="font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'" x-text="filteredData.length"></span>
                </div>

                <div class="flex items-center gap-1 sm:gap-2">
                    <button @click="changePage(1)" :disabled="currentPage === 1"
                            class="p-1.5 sm:px-3 sm:py-1.5 rounded-lg text-sm font-medium transition-all disabled:opacity-50 disabled:cursor-not-allowed hover:scale-105"
                            :class="darkMode ? 'bg-gray-800 text-white hover:bg-gray-700' : 'bg-gray-200 text-gray-900 hover:bg-gray-300'">
                        <i data-lucide="{{ in_array(app()->getLocale(), ['ar','ur']) ? 'chevrons-right' : 'chevrons-left' }}" class="w-4 h-4"></i>
                    </button>

                    <button @click="changePage(currentPage - 1)" :disabled="currentPage === 1"
                            class="p-1.5 sm:px-3 sm:py-1.5 rounded-lg text-sm font-medium transition-all disabled:opacity-50 disabled:cursor-not-allowed hover:scale-105"
                            :class="darkMode ? 'bg-gray-800 text-white hover:bg-gray-700' : 'bg-gray-200 text-gray-900 hover:bg-gray-300'">
                        <i data-lucide="{{ in_array(app()->getLocale(), ['ar','ur']) ? 'chevron-right' : 'chevron-left' }}" class="w-4 h-4"></i>
                    </button>

                    <span class="sm:hidden text-sm font-semibold px-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                        <span x-text="currentPage"></span> / <span x-text="totalPages"></span>
                    </span>

                    <template x-for="page in pageNumbers" :key="page">
                        <button @click="changePage(page)"
                                class="hidden sm:block px-3 py-1.5 rounded-lg text-sm font-bold transition-all hover:scale-105"
                                :class="page === currentPage ?
                                    'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-lg' :
                                    (darkMode ? 'bg-gray-800 text-white hover:bg-gray-700' : 'bg-gray-200 text-gray-900 hover:bg-gray-300')"
                                x-text="page">
                        </button>
                    </template>

                    <button @click="changePage(currentPage + 1)" :disabled="currentPage === totalPages"
                            class="p-1.5 sm:px-3 sm:py-1.5 rounded-lg text-sm font-medium transition-all disabled:opacity-50 disabled:cursor-not-allowed hover:scale-105"
                            :class="darkMode ? 'bg-gray-800 text-white hover:bg-gray-700' : 'bg-gray-200 text-gray-900 hover:bg-gray-300'">
                        <i data-lucide="{{ in_array(app()->getLocale(), ['ar','ur']) ? 'chevron-left' : 'chevron-right' }}" class="w-4 h-4"></i>
                    </button>

                    <button @click="changePage(totalPages)" :disabled="currentPage === totalPages"
                            class="p-1.5 sm:px-3 sm:py-1.5 rounded-lg text-sm font-medium transition-all disabled:opacity-50 disabled:cursor-not-allowed hover:scale-105"
                            :class="darkMode ? 'bg-gray-800 text-white hover:bg-gray-700' : 'bg-gray-200 text-gray-900 hover:bg-gray-300'">
                        <i data-lucide="{{ in_array(app()->getLocale(), ['ar','ur']) ? 'chevrons-left' : 'chevrons-right' }}" class="w-4 h-4"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================================
         SHARED ROW ACTIONS DROPDOWN (teleported once, outside x-for)
         ============================================================ -->
    @if(!empty($actions))
        <template x-teleport="body">
            <div x-show="activeMenuRow !== null"
                 x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="opacity-0 scale-95 translate-y-1"
                 x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-100"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 @click.outside="closeRowMenu()"
                 :style="`position: fixed; top: ${menuTop}px; left: ${menuLeft}px; width: 192px; z-index: 9999;`"
                 class="rounded-xl shadow-2xl overflow-hidden border"
                 :class="darkMode ? 'bg-gray-800 border-gray-700' : 'bg-white border-gray-200'">

                @if(in_array('view', $actions) && $viewRouteUrl)
                    <a @click.prevent="window.location.href = '{{ $viewRouteUrl }}'.replace('{{ $routePlaceholder }}', activeMenuRow?.id)"
                       href="#"
                       class="flex items-center gap-3 px-4 py-3 transition-colors group/item cursor-pointer"
                       :class="darkMode ? 'hover:bg-gray-700 text-white' : 'hover:bg-gray-50 text-gray-900'">
                        <i data-lucide="eye" class="w-5 h-5 text-blue-500 group-hover/item:scale-110 transition-transform"></i>
                        <span class="font-medium text-sm">{{ __('admin.view') }}</span>
                    </a>
                @endif

                @if(in_array('edit', $actions) && $editRouteUrl)
                    <a @click.prevent="window.location.href = '{{ $editRouteUrl }}'.replace('{{ $routePlaceholder }}', activeMenuRow?.id)"
                       href="#"
                       class="flex items-center gap-3 px-4 py-3 transition-colors group/item cursor-pointer"
                       :class="darkMode ? 'hover:bg-gray-700 text-white' : 'hover:bg-gray-50 text-gray-900'">
                        <i data-lucide="edit" class="w-5 h-5 text-accent group-hover/item:scale-110 transition-transform"></i>
                        <span class="font-medium text-sm">{{ __('admin.edit') }}</span>
                    </a>
                @endif

                @if(in_array('duplicate', $actions))
                    <button @click="duplicateRow(activeMenuRow?.id); closeRowMenu();"
                            class="w-full flex items-center gap-3 px-4 py-3 transition-colors group/item"
                            :class="darkMode ? 'hover:bg-gray-700 text-white' : 'hover:bg-gray-50 text-gray-900'">
                        <i data-lucide="copy" class="w-5 h-5 text-green-500 group-hover/item:scale-110 transition-transform"></i>
                        <span class="font-medium text-sm">{{ __('admin.create') }}</span>
                    </button>
                @endif

                @if(in_array('delete', $actions) && $deleteRouteUrl)
                    <div class="border-t" :class="darkMode ? 'border-gray-700' : 'border-gray-200'"></div>
                    <button @click="openDeleteConfirm(activeMenuRow); closeRowMenu();"
                            class="w-full flex items-center gap-3 px-4 py-3 transition-colors group/item"
                            :class="darkMode ? 'hover:bg-red-900/30 text-red-400 hover:text-red-300' : 'hover:bg-red-50 text-red-600 hover:text-red-700'">
                        <i data-lucide="trash-2" class="w-5 h-5 group-hover/item:scale-110 transition-transform"></i>
                        <span class="font-medium text-sm">{{ __('admin.delete') }}</span>
                    </button>
                @endif
            </div>
        </template>
    @endif

    <!-- ============================================================
         SHARED DELETE CONFIRMATION MODAL (teleported once)
         ============================================================ -->
    @if(in_array('delete', $actions) && $deleteRouteUrl)
        <template x-teleport="body">
            <div x-show="deleteConfirmRow !== null"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-black/50 backdrop-blur-sm z-[9998] flex items-center justify-center p-4"
                 @click.self="closeDeleteConfirm()">

                <div class="rounded-2xl p-6 max-w-md w-full shadow-2xl"
                     :class="darkMode ? 'bg-gray-900 border border-gray-800' : 'bg-white'"
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100">

                    <div class="flex items-center justify-center w-16 h-16 rounded-full bg-red-100 mx-auto mb-4">
                        <i data-lucide="alert-triangle" class="w-8 h-8 text-red-600"></i>
                    </div>

                    <h3 class="text-xl font-bold text-center mb-1" :class="darkMode ? 'text-white' : 'text-gray-900'">
                        {{ __('admin.confirm_delete') }}
                    </h3>
                    <p class="text-sm text-center mb-6" :class="darkMode ? 'text-gray-400' : 'text-gray-500'"
                       x-text="deleteConfirmRow?.title || deleteConfirmRow?.name || ''">
                    </p>

                    <div class="flex gap-3">
                        <button @click="closeDeleteConfirm()"
                                class="flex-1 px-4 py-2.5 rounded-xl font-semibold transition-all hover:scale-105"
                                :class="darkMode ? 'bg-gray-800 text-white hover:bg-gray-700' : 'bg-gray-200 text-gray-900 hover:bg-gray-300'">
                            {{ __('admin.cancel') }}
                        </button>
                        <button @click="handleDelete()"
                                class="flex-1 px-4 py-2.5 rounded-xl font-semibold transition-all hover:scale-105
                                       bg-red-600 text-white hover:bg-red-700 shadow-lg">
                            {{ __('admin.delete') }}
                        </button>
                    </div>
                </div>
            </div>
        </template>
    @endif

</div>

<script>
function dataTable() {
    return {
        // Data
        rawData: @json($dataCollection),
        filteredData: [],
        paginatedData: [],

        // Selection
        selectedRows: [],
        selectAll: false,

        // Sorting
        sortColumn: null,
        sortDirection: 'asc',

        // Pagination
        currentPage: 1,
        perPage: {{ $perPageOptions[0] }},
        totalPages: 1,

        // Search & Filters
        searchQuery: '',
        externalFilters: {},

        // UI State
        loading: false,
        columnsCount: {{ count($columns) + (empty($actions) ? 0 : 1) + (empty($bulkActions) ? 0 : 1) }},

        // Row action menu
        activeMenuRow: null,
        menuTop: 0,
        menuLeft: 0,

        // Delete confirmation
        deleteConfirmRow: null,

        init() {
            this.filteredData = [...this.rawData];
            this.updatePagination();
            this.$nextTick(() => lucide.createIcons());
        },

        // ── ROW MENU ─────────────────────────────────────────────
        openRowMenu({ row, rect }) {
            const menuWidth = 192;
            const spaceRight = window.innerWidth - rect.right;
            this.menuTop  = rect.bottom + 8;
            this.menuLeft = spaceRight >= menuWidth ? rect.left : rect.right - menuWidth;
            this.activeMenuRow = row;

            const close = () => {
                this.closeRowMenu();
                window.removeEventListener('scroll', close, { passive: true });
            };
            window.addEventListener('scroll', close, { passive: true });

            this.$nextTick(() => lucide.createIcons());
        },

        closeRowMenu() {
            this.activeMenuRow = null;
        },

        // ── DELETE CONFIRMATION ──────────────────────────────────
        openDeleteConfirm(row) {
            this.deleteConfirmRow = row;
            this.$nextTick(() => lucide.createIcons());
        },

        closeDeleteConfirm() {
            this.deleteConfirmRow = null;
        },

        handleDelete() {
            if (!this.deleteConfirmRow) return;
            @if($deleteRouteUrl)
            const url = '{{ $deleteRouteUrl }}'.replace('{{ $routePlaceholder }}', this.deleteConfirmRow.id);
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = url;
            const csrf = document.querySelector('meta[name=csrf-token]');
            form.innerHTML = '<input type="hidden" name="_token" value="' + (csrf ? csrf.content : '') + '">'
                           + '<input type="hidden" name="_method" value="DELETE">';
            document.body.appendChild(form);
            form.submit();
            @endif
        },

        // ── FILTERING ─────────────────────────────────────────────
        applyExternalFilters(filters) {
            this.externalFilters = filters || {};
            this.filterData();
        },

        filterData() {
            this.loading = true;

            setTimeout(() => {
                let data = [...this.rawData];

                // Text search
                if (this.searchQuery) {
                    const query = this.searchQuery.toLowerCase();
                    data = data.filter(row =>
                        Object.values(row).some(value =>
                            String(value).toLowerCase().includes(query)
                        )
                    );
                }

                // Category filter
                if (this.externalFilters.category_id) {
                    data = data.filter(row => row.category_id == this.externalFilters.category_id);
                }

                // Status filter (array of selected statuses)
                if (this.externalFilters.status && this.externalFilters.status.length > 0) {
                    data = data.filter(row => this.externalFilters.status.includes(row.status));
                }

                // Author/writer filter
                if (this.externalFilters.author_id) {
                    data = data.filter(row => row.writer_id == this.externalFilters.author_id);
                }

                // Date range filter
                if (this.externalFilters.date_from) {
                    data = data.filter(row =>
                        row.created_at && new Date(row.created_at) >= new Date(this.externalFilters.date_from)
                    );
                }
                if (this.externalFilters.date_to) {
                    data = data.filter(row =>
                        row.created_at && new Date(row.created_at) <= new Date(this.externalFilters.date_to)
                    );
                }

                // Feature toggles
                if (this.externalFilters.is_featured) {
                    data = data.filter(row => row.is_featured);
                }
                if (this.externalFilters.is_pinned) {
                    data = data.filter(row => row.is_pinned);
                }

                this.filteredData = data;
                this.currentPage = 1;
                this.updatePagination();
                this.loading = false;
                this.$nextTick(() => lucide.createIcons());
            }, 150);
        },

        // ── SORTING ──────────────────────────────────────────────
        sortBy(column) {
            if (this.sortColumn === column) {
                this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
            } else {
                this.sortColumn = column;
                this.sortDirection = 'asc';
            }

            this.filteredData.sort((a, b) => {
                let aVal = a[column];
                let bVal = b[column];

                if (aVal === null || aVal === undefined) return 1;
                if (bVal === null || bVal === undefined) return -1;

                if (typeof aVal === 'number' && typeof bVal === 'number') {
                    return this.sortDirection === 'asc' ? aVal - bVal : bVal - aVal;
                }

                aVal = String(aVal).toLowerCase();
                bVal = String(bVal).toLowerCase();
                if (this.sortDirection === 'asc') return aVal > bVal ? 1 : -1;
                return aVal < bVal ? 1 : -1;
            });

            this.updatePagination();
        },

        // ── PAGINATION ───────────────────────────────────────────
        updatePagination() {
            this.totalPages = Math.max(1, Math.ceil(this.filteredData.length / this.perPage));
            this.paginatedData = this.filteredData.slice(
                (this.currentPage - 1) * this.perPage,
                this.currentPage * this.perPage
            );
        },

        changePage(page) {
            if (page < 1 || page > this.totalPages) return;
            this.currentPage = page;
            this.updatePagination();
            this.$nextTick(() => lucide.createIcons());
        },

        get pageNumbers() {
            const pages = [];
            const maxVisible = 5;
            let start = Math.max(1, this.currentPage - Math.floor(maxVisible / 2));
            let end   = Math.min(this.totalPages, start + maxVisible - 1);
            if (end - start < maxVisible - 1) start = Math.max(1, end - maxVisible + 1);
            for (let i = start; i <= end; i++) pages.push(i);
            return pages;
        },

        get startIndex() { return (this.currentPage - 1) * this.perPage; },
        get endIndex()   { return Math.min(this.startIndex + this.perPage, this.filteredData.length); },

        // ── SELECTION ─────────────────────────────────────────────
        toggleSelectAll() {
            this.selectedRows = this.selectAll ? this.paginatedData.map(row => row.id) : [];
        },

        toggleRowSelection(id) {
            const idx = this.selectedRows.indexOf(id);
            if (idx > -1) this.selectedRows.splice(idx, 1);
            else this.selectedRows.push(id);
            this.selectAll = this.selectedRows.length === this.paginatedData.length;
        },

        clearSelection() {
            this.selectedRows = [];
            this.selectAll = false;
        },

        // ── BULK ACTIONS ──────────────────────────────────────────
        bulkDelete() {
            showConfirm({
                title: '{{ __("admin.confirm_delete") }}',
                message: '{{ __("admin.delete_warning") }}',
                type: 'danger',
                onConfirm: () => {
                    this.loading = true;
                    setTimeout(() => {
                        this.rawData = this.rawData.filter(row => !this.selectedRows.includes(row.id));
                        this.filterData();
                        this.clearSelection();
                    }, 800);
                }
            });
        },

        bulkPublish() {
            this.loading = true;
            setTimeout(() => {
                showToast(`${this.selectedRows.length} {{ __("admin.items_published") }}`, 'success');
                this.clearSelection();
                this.loading = false;
            }, 800);
        },

        // ── EXPORT ───────────────────────────────────────────────
        exportSelected() {
            const selectedData = this.rawData.filter(row => this.selectedRows.includes(row.id));
            this.downloadCSV(selectedData, 'selected-data.csv');
        },

        exportData(format) {
            switch (format) {
                case 'csv':   this.downloadCSV(this.filteredData, 'data-export.csv'); break;
                case 'excel': showToast('Excel export coming soon!', 'info'); break;
                case 'pdf':   showToast('PDF export coming soon!', 'info'); break;
            }
        },

        duplicateRow(id) {
            if (!id) id = this.activeMenuRow?.id;
            const row = this.rawData.find(r => r.id === id);
            if (row) {
                const newRow = { ...row, id: Date.now() };
                this.rawData.push(newRow);
                this.filterData();
            }
        },

        downloadCSV(data, filename) {
            const columns = @json(array_column($columns, 'key'));
            const headers = @json(array_column($columns, 'label'));

            let csv = headers.join(',') + '\n';
            data.forEach(row => {
                const values = columns.map(col => {
                    const value = row[col] || '';
                    return `"${String(value).replace(/"/g, '""')}"`;
                });
                csv += values.join(',') + '\n';
            });

            const blob = new Blob([csv], { type: 'text/csv' });
            const url  = window.URL.createObjectURL(blob);
            const a    = document.createElement('a');
            a.href = url;
            a.download = filename;
            a.click();
            window.URL.revokeObjectURL(url);
        }
    };
}
</script>
