@extends('layouts.admin')

@section('content')
<div x-data="categoryManagement()">

    <!-- Page Header with Stats -->
    <div class="mb-8">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6">
            <div>
                <h1 class="text-3xl font-bold gradient-text mb-2">Categories Management</h1>
                <p class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                    Organize your content with hierarchical categories
                </p>
            </div>

            <div class="flex items-center gap-3 flex-wrap">
                <button @click="expandAll()"
                        class="px-4 py-2.5 rounded-lg font-semibold transition-all hover:scale-105 flex items-center gap-2"
                        :class="darkMode ? 'bg-gray-800 text-white hover:bg-gray-700' : 'bg-gray-100 text-gray-900 hover:bg-gray-200'">
                    <i data-lucide="chevrons-down" class="w-5 h-5"></i>
                    <span>Expand All</span>
                </button>

                <button @click="collapseAll()"
                        class="px-4 py-2.5 rounded-lg font-semibold transition-all hover:scale-105 flex items-center gap-2"
                        :class="darkMode ? 'bg-gray-800 text-white hover:bg-gray-700' : 'bg-gray-100 text-gray-900 hover:bg-gray-200'">
                    <i data-lucide="chevrons-up" class="w-5 h-5"></i>
                    <span>Collapse All</span>
                </button>

                <button @click="loadCategories()"
                        class="px-4 py-2.5 rounded-lg font-semibold transition-all hover:scale-105 flex items-center gap-2"
                        :class="darkMode ? 'bg-gray-800 text-white hover:bg-gray-700' : 'bg-gray-100 text-gray-900 hover:bg-gray-200'">
                    <i data-lucide="refresh-cw" class="w-5 h-5" :class="{ 'animate-spin': loading }"></i>
                    <span>Refresh</span>
                </button>

                <a href="{{ route('admin.categories.create') }}"
                   class="px-6 py-2.5 rounded-lg font-semibold bg-gradient-to-r from-[#0056b3] to-[#218838] text-white hover:scale-105 transition-all shadow-lg hover:shadow-xl flex items-center gap-2">
                    <i data-lucide="plus" class="w-5 h-5"></i>
                    <span>New Category</span>
                </a>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <x-admin.stats-card
                title="{{ __('admin.total_categories') }}"
                :value="$stats['total'] ?? 0"
                icon="folder"
                color="blue"
                :trend="null"
            />
            <x-admin.stats-card
                title="{{ __('admin.blog') }}"
                :value="$stats['blog'] ?? 0"
                icon="check-circle"
                color="green"
                :trend="null"
            />
            <x-admin.stats-card
                title="{{ __('admin.media') }}"
                :value="$stats['media'] ?? 0"
                icon="layers"
                color="purple"
                :trend="null"
            />
            <x-admin.stats-card
                title="{{ __('admin.timeline') }}"
                :value="$stats['timeline'] ?? 0"
                icon="clock"
                color="teal"
                :trend="null"
            />
        </div>
    </div>

    <!-- Search and Filters -->
    <div class="mb-6 flex flex-col lg:flex-row gap-4">
        <!-- Search -->
        <div class="flex-1 relative">
            <i data-lucide="search"
               class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5"
               :class="darkMode ? 'text-gray-400' : 'text-gray-500'"></i>
            <input type="text"
                   x-model="searchQuery"
                   @input.debounce.300ms="filterCategories()"
                   placeholder="Search categories..."
                   class="w-full pl-10 pr-10 py-3 rounded-xl border-2 transition-all duration-300 focus:ring-2 focus:ring-accent/50"
                   :class="darkMode ?
                       'bg-gray-800 border-gray-700 text-white placeholder-gray-500 focus:border-accent' :
                       'bg-white border-gray-300 text-gray-900 placeholder-gray-400 focus:border-accent'">
            <button x-show="searchQuery"
                    @click="searchQuery = ''; filterCategories();"
                    class="absolute right-3 top-1/2 -translate-y-1/2 hover:scale-110 transition-transform"
                    :class="darkMode ? 'text-gray-400 hover:text-white' : 'text-gray-500 hover:text-gray-900'">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <!-- Status Filter -->
        <select x-model="statusFilter"
                @change="filterCategories()"
                class="px-4 py-3 rounded-xl border-2 transition-all focus:ring-2 focus:ring-accent/50 w-full lg:w-48"
                :class="darkMode ?
                    'bg-gray-800 border-gray-700 text-white focus:border-accent' :
                    'bg-white border-gray-300 text-gray-900 focus:border-accent'">
            <option value="all">All Status</option>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
        </select>

        <!-- Depth Filter -->
        <select x-model="depthFilter"
                @change="filterCategories()"
                class="px-4 py-3 rounded-xl border-2 transition-all focus:ring-2 focus:ring-accent/50 w-full lg:w-48"
                :class="darkMode ?
                    'bg-gray-800 border-gray-700 text-white focus:border-accent' :
                    'bg-white border-gray-300 text-gray-900 focus:border-accent'">
            <option value="all">All Levels</option>
            <option value="0">Root Level</option>
            <option value="1">Level 1</option>
            <option value="2">Level 2</option>
            <option value="3">Level 3+</option>
        </select>
    </div>

    <!-- Categories List -->
    <div class="rounded-2xl overflow-hidden shadow-xl border"
         :class="darkMode ? 'bg-gray-900 border-gray-800' : 'bg-white border-gray-200'">

        <!-- Table View -->
        @if($categories->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr :class="darkMode ? 'bg-gray-800' : 'bg-gray-100' ">
                            <th class="px-6 py-4 text-start font-semibold">{{ __('admin.name') }}</th>
                            <th class="px-6 py-4 text-start font-semibold">{{ __('admin.type') }}</th>
                            <th class="px-6 py-4 text-start font-semibold">{{ __('admin.articles_count') }}</th>
                            <th class="px-6 py-4 text-start font-semibold">{{ __('admin.status') }}</th>
                            <th class="px-6 py-4 text-end font-semibold">{{ __('admin.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y" :class="darkMode ? 'divide-gray-800' : 'divide-gray-200'">
                        @foreach($categories as $category)
                            <tr :class="darkMode ? 'hover:bg-gray-800' : 'hover:bg-gray-50'">
                                <td class="px-6 py-4">
                                    <div>
                                        <p class="font-semibold" :class="darkMode ? 'text-white' : 'text-gray-900'">
                                            {{ $category->getTranslation('name', app()->getLocale()) ?? $category->getTranslation('name', 'ar') }}
                                        </p>
                                        <p class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                                            {{ $category->slug }}
                                        </p>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold"
                                          :class="'{{ $category->type->value }}' === 'blog' ? 'bg-blue-100 text-blue-800' :
                                                   '{{ $category->type->value }}' === 'media' ? 'bg-purple-100 text-purple-800' :
                                                   'bg-teal-100 text-teal-800'">
                                        {{ ucfirst($category->type->value) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span :class="darkMode ? 'text-gray-300' : 'text-gray-600'">
                                        {{ $category->articles_count ?? 0 }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold"
                                          :class="$category->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'">
                                        {{ ucfirst($category->status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('admin.categories.edit', $category->id) }}"
                                           class="p-2 rounded-lg transition-colors"
                                           :class="darkMode ? 'hover:bg-gray-800 text-gray-400 hover:text-white' : 'hover:bg-gray-100 text-gray-600 hover:text-gray-900'">
                                            <i data-lucide="pencil" class="w-5 h-5"></i>
                                        </a>
                                        <button @click="deleteCategory({{ $category->id }})"
                                                class="p-2 rounded-lg transition-colors text-red-500 hover:bg-red-100 dark:hover:bg-red-900/30">
                                            <i data-lucide="trash-2" class="w-5 h-5"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($categories->hasPages())
                <div class="px-6 py-4 border-t" :class="darkMode ? 'border-gray-800 bg-gray-900' : 'border-gray-200 bg-gray-50'">
                    {{ $categories->links() }}
                </div>
            @endif
        @else
            <!-- Empty State -->
            <div class="p-16 text-center">
                <div class="inline-flex items-center justify-center w-20 h-20 rounded-full mb-4"
                     :class="darkMode ? 'bg-gray-800' : 'bg-gray-100'">
                    <i data-lucide="folder-x" class="w-10 h-10"
                       :class="darkMode ? 'text-gray-600' : 'text-gray-400'"></i>
                </div>
                <h3 class="text-xl font-bold mb-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                    {{ __('admin.no_categories_found') }}
                </h3>
                <p :class="darkMode ? 'text-gray-400' : 'text-gray-600'" class="mb-6">
                    {{ __('admin.get_started_by_creating_category') }}
                </p>
                <a href="{{ route('admin.categories.create') }}"
                   class="inline-flex items-center gap-2 px-6 py-3 rounded-lg font-semibold bg-gradient-to-r from-[#0056b3] to-[#218838] text-white hover:scale-105 transition-all shadow-lg">
                    <i data-lucide="plus" class="w-5 h-5"></i>
                    <span>{{ __('admin.create_category') }}</span>
                </a>
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
function categoryManagement() {
    return {
        loading: false,
        searchQuery: '',
        statusFilter: 'all',
        depthFilter: 'all',

        init() {
            this.$nextTick(() => lucide.createIcons());
        },

        expandAll() {
            // Expand all category trees
            document.querySelectorAll('[data-expandable]').forEach(el => {
                el.classList.remove('hidden');
            });
        },

        collapseAll() {
            // Collapse all category trees
            document.querySelectorAll('[data-expandable]').forEach(el => {
                el.classList.add('hidden');
            });
        },

        async loadCategories() {
            // Reload the page with current filters
            window.location.href = '{{ route("admin.categories.index") }}' +
                '?search=' + this.searchQuery +
                '&status=' + this.statusFilter +
                '&type=' + this.depthFilter;
        },

        filterCategories() {
            // Submit form with filters (server-side filtering)
            this.loadCategories();
        },

        deleteCategory(categoryId) {
            if (!confirm('{{ __("admin.confirm_delete") }}')) {
                return;
            }

            const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

            fetch(`/su/portal/v1/admin/categories/${categoryId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                if (response.ok) {
                    window.location.reload();
                } else {
                    alert('{{ __("admin.error_occurred") }}');
                }
            })
            .catch(error => {
                console.error('Delete error:', error);
                alert('{{ __("admin.error_occurred") }}');
            });
        }
    };
}
</script>
@endpush
