@extends('layouts.admin')

@section('title', 'Media Categories')

@section('content')
<div class="container mx-auto px-4 py-8" x-data="mediaCategoryManager()" x-init="init()">
    <!-- Header -->
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">
                Media Categories
            </h1>
            <p class="mt-2" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                Manage media program categories
            </p>
        </div>
        <a href="{{ route('admin.media-categories.create') }}"
           class="bg-gradient-to-r from-[#0056b3] to-[#218838] text-white px-6 py-3 rounded-lg font-semibold hover:shadow-lg transition-all flex items-center gap-2">
            <i data-lucide="plus-circle" class="w-5 h-5"></i>
            <span>Create New Category</span>
        </a>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        <div class="glass rounded-lg p-6 transition-colors" :class="darkMode ? 'bg-gray-900/50' : 'bg-white/80'">
            <p class="text-sm font-semibold" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">Total Categories</p>
            <p class="text-4xl font-bold mt-2 text-[#0056b3]" x-text="stats.total ?? 0"></p>
        </div>
        <div class="glass rounded-lg p-6 transition-colors" :class="darkMode ? 'bg-gray-900/50' : 'bg-white/80'">
            <p class="text-sm font-semibold" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">Total Programs</p>
            <p class="text-4xl font-bold mt-2 text-[#218838]" x-text="stats.total_programs ?? 0"></p>
        </div>
    </div>

    <!-- Search & Filters -->
    <div class="glass rounded-lg p-6 mb-6 transition-colors" :class="darkMode ? 'bg-gray-900/50' : 'bg-white/80'">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="md:col-span-2">
                <label class="block text-sm font-semibold mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                    Search
                </label>
                <input type="text"
                       x-model="search"
                       @input.debounce.500ms="loadData()"
                       placeholder="Search categories by name..."
                       class="w-full px-4 py-2 rounded-lg border focus:outline-none focus:ring-2 focus:ring-[#0056b3]"
                       :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
            </div>
            <div>
                <label class="block text-sm font-semibold mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                    Sort By
                </label>
                <select x-model="sortBy"
                        @change="loadData()"
                        class="w-full px-4 py-2 rounded-lg border focus:outline-none focus:ring-2 focus:ring-[#0056b3]"
                        :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                    <option value="created_at">Created Date</option>
                    <option value="name">Name</option>
                    <option value="program_count">Program Count</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Bulk Actions -->
    <div class="glass rounded-lg p-4 mb-6 transition-colors" :class="darkMode ? 'bg-gray-900/50' : 'bg-white/80'" x-show="selected.length > 0">
        <div class="flex items-center justify-between">
            <span class="font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                <span x-text="selected.length"></span> category(ies) selected
            </span>
            <button @click="bulkDelete()"
                    class="bg-red-600 text-white px-6 py-2 rounded-lg font-semibold hover:bg-red-700 transition">
                Delete Selected
            </button>
        </div>
    </div>

    <!-- Categories Table -->
    <div class="glass rounded-lg overflow-hidden transition-colors" :class="darkMode ? 'bg-gray-900/50' : 'bg-white/80'">
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
                        <th class="px-6 py-3 text-left text-sm font-semibold">Icon</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold">Programs</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold">Created</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="item in items" :key="item.id">
                        <tr :class="darkMode ? 'border-gray-700 hover:bg-gray-800' : 'border-gray-200 hover:bg-gray-50'" class="border-t transition">
                            <td class="px-6 py-4">
                                <input type="checkbox" :value="item.id" x-model="selected" class="rounded">
                            </td>
                            <td class="px-6 py-4 font-semibold" :class="darkMode ? 'text-white' : 'text-gray-900'" x-text="item.name?.en || item.name?.ar || '—'"></td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <i :data-lucide="item.icon || 'video'" class="w-5 h-5 text-[#0056b3]"></i>
                                    <span class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'" x-text="item.icon || 'video'"></span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-3 py-1 rounded-full text-sm font-semibold bg-blue-100 text-blue-800" x-text="item.programs_count || 0"></span>
                            </td>
                            <td class="px-6 py-4 text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'" x-text="new Date(item.created_at).toLocaleDateString()"></td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <a :href="`/su/portal/v1/admin/media-categories/${item.id}`"
                                       class="text-[#64CCC5] hover:text-[#0056b3] font-semibold text-sm"
                                       title="View">
                                        <i data-lucide="eye" class="w-4 h-4"></i>
                                    </a>
                                    <a :href="`/su/portal/v1/admin/media-categories/${item.id}/edit`"
                                       class="text-[#0056b3] hover:text-blue-700 font-semibold text-sm"
                                       title="Edit">
                                        <i data-lucide="edit" class="w-4 h-4"></i>
                                    </a>
                                    <button @click="deleteItem(item.id)"
                                            class="text-red-600 hover:text-red-700 font-semibold text-sm"
                                            title="Delete">
                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </template>

        <template x-if="!loading && items.length === 0">
            <div class="p-8 text-center">
                <i data-lucide="video" class="w-16 h-16 mx-auto mb-4" :class="darkMode ? 'text-gray-600' : 'text-gray-400'"></i>
                <p :class="darkMode ? 'text-gray-400' : 'text-gray-600'" class="text-lg">
                    No media categories found
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
function mediaCategoryManager() {
    return {
        items: [],
        search: '',
        sortBy: 'created_at',
        selected: [],
        currentPage: 1,
        perPage: 15,
        totalItems: 0,
        totalPages: 1,
        loading: true,
        stats: { total: 0, total_programs: 0 },
        darkMode: localStorage.getItem('darkMode') === 'true',

        async init() {
            await this.loadData();
            this.$nextTick(() => lucide.createIcons());
        },

        async loadData() {
            this.loading = true;
            const params = new URLSearchParams({
                page: this.currentPage,
                per_page: this.perPage,
                search: this.search,
                sort_by: this.sortBy
            });

            try {
                const response = await fetch(`/su/portal/v1/admin/media-categories?${params}`, {
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
                }
            } catch (error) {
                console.error('Error loading media categories:', error);
            } finally {
                this.loading = false;
                this.$nextTick(() => lucide.createIcons());
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
        },

        async deleteItem(id) {
            if (!confirm('Are you sure you want to delete this category?')) return;

            try {
                const response = await fetch(`/su/portal/v1/admin/media-categories/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                if (response.ok) {
                    await this.loadData();
                }
            } catch (error) {
                console.error('Error deleting category:', error);
            }
        },

        async bulkDelete() {
            if (!confirm(`Delete ${this.selected.length} categories?`)) return;

            try {
                const response = await fetch('/su/portal/v1/admin/media-categories/bulk-delete', {
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
                    await this.loadData();
                }
            } catch (error) {
                console.error('Error bulk deleting:', error);
            }
        }
    };
}
</script>
@endpush
@endsection
