@extends('layouts.admin')

@section('title', 'Categories')

@section('content')
<div class="container mx-auto px-4 py-8" x-data="categoryManager()">
    <!-- Header -->
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">
                Categories
            </h1>
            <p class="mt-2" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                Manage all categories in the system
            </p>
        </div>
        <a href="{{ route('admin.categories.create') }}"
           class="bg-gradient-to-r from-[#0056b3] to-[#218838] text-white px-6 py-3 rounded-lg font-semibold hover:shadow-lg transition-all">
            + Create New
        </a>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="glass-light rounded-lg p-6" :class="darkMode ? 'glass-dark' : ''">
            <p class="text-sm font-semibold" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">Total Categories</p>
            <p class="text-4xl font-bold mt-2 text-[#0056b3]" x-text="stats.total ?? 0"></p>
        </div>
        <div class="glass-light rounded-lg p-6" :class="darkMode ? 'glass-dark' : ''">
            <p class="text-sm font-semibold" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">Blog</p>
            <p class="text-4xl font-bold mt-2 text-blue-600" x-text="stats.blog ?? 0"></p>
        </div>
        <div class="glass-light rounded-lg p-6" :class="darkMode ? 'glass-dark' : ''">
            <p class="text-sm font-semibold" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">Media</p>
            <p class="text-4xl font-bold mt-2 text-green-600" x-text="stats.media ?? 0"></p>
        </div>
        <div class="glass-light rounded-lg p-6" :class="darkMode ? 'glass-dark' : ''">
            <p class="text-sm font-semibold" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">Timeline</p>
            <p class="text-4xl font-bold mt-2 text-teal-600" x-text="stats.timeline ?? 0"></p>
        </div>
    </div>

    <!-- Search & Filters -->
    <div class="glass-light rounded-lg p-6 mb-6" :class="darkMode ? 'glass-dark' : ''">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-semibold mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                    Search
                </label>
                <input type="text"
                       x-model="search"
                       placeholder="Search categories..."
                       @input="debounce(() => loadData(), 500)"
                       class="w-full px-4 py-2 rounded-lg border focus:outline-none focus:ring-2 focus:ring-[#0056b3]"
                       :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
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
                    <option value="blog">Blog</option>
                    <option value="media">Media</option>
                    <option value="timeline">Timeline</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                    Parent
                </label>
                <select x-model="filters.parent_id"
                        @change="loadData()"
                        class="w-full px-4 py-2 rounded-lg border focus:outline-none focus:ring-2 focus:ring-[#0056b3]"
                        :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                    <option value="">All Categories</option>
                    <option value="root">Root Only</option>
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
    <div class="glass-light rounded-lg p-4 mb-6" :class="darkMode ? 'glass-dark' : ''" x-show="selectedCount > 0">
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

    <!-- Categories Table (Tree View) -->
    <div class="glass-light rounded-lg overflow-hidden" :class="darkMode ? 'glass-dark' : ''">
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
                        <th class="px-6 py-3 text-right text-sm font-semibold">
                            <input type="checkbox" @change="toggleSelectAll()" class="rounded">
                        </th>
                        <th class="px-6 py-3 text-right text-sm font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">Name</th>
                        <th class="px-6 py-3 text-right text-sm font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">Type</th>
                        <th class="px-6 py-3 text-right text-sm font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">Children</th>
                        <th class="px-6 py-3 text-right text-sm font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">Articles</th>
                        <th class="px-6 py-3 text-right text-sm font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">Order</th>
                        <th class="px-6 py-3 text-right text-sm font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="item in items" :key="item.id">
                        <tr :class="darkMode ? 'border-gray-700 hover:bg-gray-800' : 'border-gray-200 hover:bg-gray-50'" class="border-t transition">
                            <td class="px-6 py-4">
                                <input type="checkbox" :value="item.id" x-model="selected" @change="updateSelectedCount()" class="rounded">
                            </td>
                            <td class="px-6 py-4 font-semibold" :class="darkMode ? 'text-white' : 'text-gray-900'">
                                <span :style="`margin-right: ${(item.depth ?? 0) * 20}px`"></span>
                                <i :data-lucide="item.icon ?? 'folder'" class="w-4 h-4 inline ml-2"></i>
                                <span x-text="item.name?.en || item.name?.ar || '—'"></span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 rounded-full text-xs font-semibold"
                                      :class="item.type === 'blog' ? 'bg-blue-100 text-blue-800' : item.type === 'media' ? 'bg-green-100 text-green-800' : 'bg-teal-100 text-teal-800'"
                                      x-text="item.type"></span>
                            </td>
                            <td class="px-6 py-4 text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'" x-text="item.children_count ?? 0"></td>
                            <td class="px-6 py-4 text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'" x-text="item.articles_count ?? 0"></td>
                            <td class="px-6 py-4 text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'" x-text="item.display_order ?? 0"></td>
                            <td class="px-6 py-4">
                                <a :href="`/su/portal/v1/admin/categories/${item.id}/edit`"
                                   class="text-[#0056b3] hover:text-blue-700 font-semibold text-sm ml-3">
                                    Edit
                                </a>
                                <a :href="`/su/portal/v1/admin/categories/${item.id}`"
                                   class="text-[#64CCC5] hover:text-teal-700 font-semibold text-sm ml-3">
                                    View
                                </a>
                                <button @click="deleteItem(item.id)"
                                        class="text-red-600 hover:text-red-700 font-semibold text-sm ml-3">
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
                    No categories found
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
function categoryManager() {
    return {
        items: [],
        search: '',
        filters: { type: '', parent_id: '' },
        selected: [],
        selectedCount: 0,
        currentPage: 1,
        perPage: 50,
        totalItems: 0,
        totalPages: 1,
        loading: false,
        stats: { total: 0, blog: 0, media: 0, timeline: 0 },
        darkMode: localStorage.getItem('darkMode') === 'true',

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
                const response = await fetch(`/su/portal/v1/admin/categories?${params}`, {
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
                    this.$nextTick(() => lucide.createIcons());
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
            if (!confirm('Are you sure you want to delete this category?')) return;

            try {
                const response = await fetch(`/su/portal/v1/admin/categories/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                if (response.ok) {
                    this.loadData();
                    alert('Category deleted successfully');
                }
            } catch (error) {
                console.error('Error:', error);
            }
        },

        async bulkDelete() {
            if (!confirm(`Delete ${this.selected.length} categories?`)) return;

            try {
                const response = await fetch(`/su/portal/v1/admin/categories/bulk-delete`, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ ids: this.selected })
                });

                if (response.ok) {
                    this.selected = [];
                    this.selectedCount = 0;
                    this.loadData();
                    alert('Categories deleted successfully');
                }
            } catch (error) {
                console.error('Error:', error);
            }
        },

        debounce(fn, delay) {
            clearTimeout(this.debounceTimer);
            this.debounceTimer = setTimeout(fn, delay);
        }
    };
}
</script>
@endpush
@endsection
