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
                title="Total Categories"
                :value="$totalCategories"
                icon="folder"
                color="blue"
                :trend="null"
            />
            <x-admin.stats-card
                title="Active"
                :value="$activeCategories"
                icon="check-circle"
                color="green"
                :trend="null"
            />
            <x-admin.stats-card
                title="Inactive"
                :value="$inactiveCategories"
                icon="x-circle"
                color="gray"
                :trend="null"
            />
            <x-admin.stats-card
                title="Max Depth"
                :value="$maxDepth"
                icon="layers"
                color="purple"
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

    <!-- Category Tree -->
    <div class="rounded-2xl overflow-hidden shadow-xl border"
         :class="darkMode ? 'bg-gray-900 border-gray-800' : 'bg-white border-gray-200'">

        <!-- Loading State -->
        <div x-show="loading" class="p-8">
            <div class="space-y-4">
                <template x-for="i in 5" :key="i">
                    <div class="flex items-center gap-4">
                        <div class="h-12 w-12 rounded-lg animate-pulse"
                             :class="darkMode ? 'bg-gray-800' : 'bg-gray-200'"></div>
                        <div class="flex-1 space-y-2">
                            <div class="h-4 rounded animate-pulse w-1/4"
                                 :class="darkMode ? 'bg-gray-800' : 'bg-gray-200'"></div>
                            <div class="h-3 rounded animate-pulse w-1/3"
                                 :class="darkMode ? 'bg-gray-800' : 'bg-gray-200'"></div>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <!-- Tree View -->
        <div x-show="!loading && filteredCategories.length > 0" class="divide-y"
             :class="darkMode ? 'divide-gray-800' : 'divide-gray-200'">
            <template x-for="category in filteredCategories" :key="category.id">
                <x-admin.category-tree-item :category="category" />
            </template>
        </div>

        <!-- Empty State -->
        <div x-show="!loading && filteredCategories.length === 0" class="p-16 text-center">
            <div class="inline-flex items-center justify-center w-20 h-20 rounded-full mb-4"
                 :class="darkMode ? 'bg-gray-800' : 'bg-gray-100'">
                <i data-lucide="folder-x" class="w-10 h-10"
                   :class="darkMode ? 'text-gray-600' : 'text-gray-400'"></i>
            </div>
            <h3 class="text-xl font-bold mb-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                No Categories Found
            </h3>
            <p :class="darkMode ? 'text-gray-400' : 'text-gray-600'" class="mb-6">
                <template x-if="searchQuery || statusFilter !== 'all' || depthFilter !== 'all'">
                    <span>Try adjusting your filters or search query</span>
                </template>
                <template x-if="!searchQuery && statusFilter === 'all' && depthFilter === 'all'">
                    <span>Get started by creating your first category</span>
                </template>
            </p>
            <a href="{{ route('admin.categories.create') }}"
               class="inline-flex items-center gap-2 px-6 py-3 rounded-lg font-semibold bg-gradient-to-r from-[#0056b3] to-[#218838] text-white hover:scale-105 transition-all shadow-lg">
                <i data-lucide="plus" class="w-5 h-5"></i>
                <span>Create Category</span>
            </a>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function categoryManagement() {
    return {
        loading: false,
        categories: @json($categories),
        filteredCategories: [],
        expandedCategories: [],
        searchQuery: '',
        statusFilter: 'all',
        depthFilter: 'all',

        init() {
            this.filteredCategories = this.categories;
            this.$nextTick(() => lucide.createIcons());
        },

        async loadCategories() {
            this.loading = true;
            try {
                const response = await fetch('{{ route("admin.categories.index") }}?ajax=1', {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });
                const data = await response.json();
                this.categories = data.categories;
                this.filterCategories();
            } catch (error) {
                console.error('Failed to load categories:', error);
            } finally {
                this.loading = false;
                this.$nextTick(() => lucide.createIcons());
            }
        },

        filterCategories() {
            let filtered = [...this.categories];

            // Search filter
            if (this.searchQuery) {
                const query = this.searchQuery.toLowerCase();
                filtered = filtered.filter(cat =>
                    this.searchInCategory(cat, query)
                );
            }

            // Status filter
            if (this.statusFilter !== 'all') {
                filtered = this.filterByStatus(filtered, this.statusFilter);
            }

            // Depth filter
            if (this.depthFilter !== 'all') {
                const depth = parseInt(this.depthFilter);
                filtered = this.filterByDepth(filtered, depth);
            }

            this.filteredCategories = filtered;
            this.$nextTick(() => lucide.createIcons());
        },

        searchInCategory(category, query) {
            // Search in name
            if (category.name && category.name.toLowerCase().includes(query)) {
                return true;
            }
            // Search in children
            if (category.children && category.children.length > 0) {
                return category.children.some(child => this.searchInCategory(child, query));
            }
            return false;
        },

        filterByStatus(categories, status) {
            return categories.filter(cat => {
                const matchesStatus = cat.is_active === (status === 'active');
                if (cat.children && cat.children.length > 0) {
                    cat.children = this.filterByStatus(cat.children, status);
                }
                return matchesStatus || (cat.children && cat.children.length > 0);
            });
        },

        filterByDepth(categories, targetDepth) {
            if (targetDepth === 3) {
                // Show categories at depth 3 or more
                return categories.filter(cat => cat.depth >= 3);
            }
            return categories.filter(cat => cat.depth === targetDepth);
        },

        expandAll() {
            this.expandedCategories = this.getAllCategoryIds(this.filteredCategories);
            window.dispatchEvent(new CustomEvent('expand-all-categories'));
        },

        collapseAll() {
            this.expandedCategories = [];
            window.dispatchEvent(new CustomEvent('collapse-all-categories'));
        },

        getAllCategoryIds(categories) {
            let ids = [];
            categories.forEach(cat => {
                ids.push(cat.id);
                if (cat.children && cat.children.length > 0) {
                    ids = ids.concat(this.getAllCategoryIds(cat.children));
                }
            });
            return ids;
        },

        deleteCategory(categoryId) {
            window.showConfirm({
                title: '{{ __("admin.confirm_delete") }}',
                message: 'هل أنت متأكد من حذف هذا التصنيف؟ سيتم حذف جميع التصنيفات الفرعية أيضاً.',
                type: 'danger',
                onConfirm: async () => {
                    try {
                        const response = await fetch(`/admin/categories/${categoryId}`, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        });

                        if (response.ok) {
                            await this.loadCategories();
                            showToast('{{ __("admin.deleted_successfully") }}', 'success');
                        }
                    } catch (error) {
                        console.error('Failed to delete category:', error);
                        showToast('{{ __("admin.error_occurred") }}', 'error');
                    }
                }
            });
        }
    };
}
</script>
@endpush
