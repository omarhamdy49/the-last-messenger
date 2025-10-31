@extends('layouts.admin')

@section('title', 'Tags')

@section('content')
<div class="container mx-auto px-4 py-8" x-data="tagManager()">
    <!-- Header -->
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">
                Tags
            </h1>
            <p class="mt-2" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                Manage all tags in the system
            </p>
        </div>
        <a href="{{ route('admin.tags.create') }}"
           class="bg-gradient-to-r from-[#0056b3] to-[#218838] text-white px-6 py-3 rounded-lg font-semibold hover:shadow-lg transition-all">
            + Create New
        </a>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="glass-light rounded-lg p-6" :class="darkMode ? 'glass-dark' : ''">
            <p class="text-sm font-semibold" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">Total Tags</p>
            <p class="text-4xl font-bold mt-2 text-[#0056b3]" x-text="stats.total ?? 0"></p>
        </div>
        <div class="glass-light rounded-lg p-6" :class="darkMode ? 'glass-dark' : ''">
            <p class="text-sm font-semibold" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">Total Uses</p>
            <p class="text-4xl font-bold mt-2 text-[#218838]" x-text="stats.total_uses ?? 0"></p>
        </div>
        <div class="glass-light rounded-lg p-6" :class="darkMode ? 'glass-dark' : ''">
            <p class="text-sm font-semibold" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">Most Used</p>
            <p class="text-lg font-bold mt-2 text-[#64CCC5]" x-text="stats.most_used ?? '—'"></p>
        </div>
        <div class="glass-light rounded-lg p-6" :class="darkMode ? 'glass-dark' : ''">
            <p class="text-sm font-semibold" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">Recently Added</p>
            <p class="text-4xl font-bold mt-2 text-purple-600" x-text="stats.recent ?? 0"></p>
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
                       placeholder="Search tags..."
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
                    <option value="article">Article</option>
                    <option value="hadith">Hadith</option>
                    <option value="program">Program</option>
                    <option value="event">Event</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                    Sort By
                </label>
                <select x-model="filters.sort"
                        @change="loadData()"
                        class="w-full px-4 py-2 rounded-lg border focus:outline-none focus:ring-2 focus:ring-[#0056b3]"
                        :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                    <option value="name">Name</option>
                    <option value="usage">Usage Count</option>
                    <option value="date">Created Date</option>
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
            <div class="flex gap-3">
                <button @click="showMergeModal()"
                        class="bg-blue-600 text-white px-6 py-2 rounded-lg font-semibold hover:bg-blue-700 transition">
                    Merge Selected
                </button>
                <button @click="bulkDelete()"
                        class="bg-red-600 text-white px-6 py-2 rounded-lg font-semibold hover:bg-red-700 transition">
                    Delete Selected
                </button>
            </div>
        </div>
    </div>

    <!-- Tags Table -->
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
                        <th class="px-6 py-3 text-right text-sm font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">Usage Count</th>
                        <th class="px-6 py-3 text-right text-sm font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">Created</th>
                        <th class="px-6 py-3 text-right text-sm font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="item in items" :key="item.id">
                        <tr :class="darkMode ? 'border-gray-700 hover:bg-gray-800' : 'border-gray-200 hover:bg-gray-50'" class="border-t transition">
                            <td class="px-6 py-4">
                                <input type="checkbox" :value="item.id" x-model="selected" @change="updateSelectedCount()" class="rounded">
                            </td>
                            <td class="px-6 py-4">
                                <span class="font-semibold" :class="darkMode ? 'text-white' : 'text-gray-900'" x-text="item.name?.en || item.name?.ar || '—'"></span>
                                <p class="text-xs mt-1" :class="darkMode ? 'text-gray-500' : 'text-gray-500'" x-text="item.slug"></p>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 rounded-full text-xs font-semibold"
                                      :class="item.type === 'article' ? 'bg-blue-100 text-blue-800' : item.type === 'hadith' ? 'bg-green-100 text-green-800' : item.type === 'program' ? 'bg-purple-100 text-purple-800' : 'bg-orange-100 text-orange-800'"
                                      x-text="item.type"></span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="font-bold text-[#64CCC5]" x-text="item.usage_count ?? 0"></span>
                            </td>
                            <td class="px-6 py-4 text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'" x-text="new Date(item.created_at).toLocaleDateString()"></td>
                            <td class="px-6 py-4">
                                <a :href="`/su/portal/v1/admin/tags/${item.id}`"
                                   class="text-[#64CCC5] hover:text-teal-700 font-semibold text-sm ml-3">
                                    View
                                </a>
                                <a :href="`/su/portal/v1/admin/tags/${item.id}/edit`"
                                   class="text-[#0056b3] hover:text-blue-700 font-semibold text-sm ml-3">
                                    Edit
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
                    No tags found
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

    <!-- Merge Modal -->
    <div x-show="mergeModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display: none;">
        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="mergeModalOpen = false"></div>
        <div class="glass-light rounded-lg p-6 max-w-md w-full relative z-10" :class="darkMode ? 'glass-dark' : ''">
            <h3 class="text-xl font-bold mb-4" :class="darkMode ? 'text-white' : 'text-gray-900'">Merge Tags</h3>
            <p class="mb-4" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                Select the destination tag. All selected tags will be merged into this one.
            </p>
            <select x-model="mergeDestination"
                    class="w-full px-4 py-2 rounded-lg border mb-4"
                    :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                <option value="">Select destination tag</option>
                <template x-for="item in items" :key="item.id">
                    <option :value="item.id" x-text="item.name?.en || item.name?.ar"></option>
                </template>
            </select>
            <div class="flex gap-3">
                <button @click="mergeTags()"
                        class="flex-1 bg-gradient-to-r from-[#0056b3] to-[#218838] text-white px-6 py-2 rounded-lg font-semibold">
                    Merge
                </button>
                <button @click="mergeModalOpen = false"
                        class="flex-1 border px-6 py-2 rounded-lg font-semibold"
                        :class="darkMode ? 'border-gray-700 text-white' : 'border-gray-300 text-gray-900'">
                    Cancel
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function tagManager() {
    return {
        items: [],
        search: '',
        filters: { type: '', sort: 'name' },
        selected: [],
        selectedCount: 0,
        currentPage: 1,
        perPage: 50,
        totalItems: 0,
        totalPages: 1,
        loading: false,
        stats: { total: 0, total_uses: 0, most_used: '—', recent: 0 },
        darkMode: localStorage.getItem('darkMode') === 'true',
        mergeModalOpen: false,
        mergeDestination: '',

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
                const response = await fetch(`/su/portal/v1/admin/tags?${params}`, {
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
            const self = this;
            window.showConfirm({
                title: 'Delete Tag',
                message: 'Are you sure you want to delete this tag? This action cannot be undone.',
                onConfirm: async () => {
                    try {
                        const response = await fetch(`/su/portal/v1/admin/tags/${id}`, {
                            method: 'DELETE',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            }
                        });

                        if (response.ok) {
                            window.showToast('Tag deleted successfully', 'success');
                            self.loadData();
                        } else {
                            const data = await response.json();
                            window.showToast(data.message || 'Failed to delete tag', 'error');
                        }
                    } catch (error) {
                        console.error('Error:', error);
                        window.showToast('An error occurred while deleting the tag', 'error');
                    }
                }
            });
        },

        async bulkDelete() {
            const self = this;
            window.showConfirm({
                title: 'Delete Multiple Tags',
                message: `Are you sure you want to delete ${this.selected.length} tags? This action cannot be undone.`,
                onConfirm: async () => {
                    try {
                        const response = await fetch(`/su/portal/v1/admin/tags/bulk-delete`, {
                            method: 'POST',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({ ids: self.selected })
                        });

                        if (response.ok) {
                            const data = await response.json();
                            self.selected = [];
                            self.selectedCount = 0;
                            self.loadData();
                            window.showToast(`${data.deleted} tags deleted successfully`, 'success');
                        } else {
                            window.showToast('Failed to delete tags', 'error');
                        }
                    } catch (error) {
                        console.error('Error:', error);
                        window.showToast('An error occurred while deleting the tags', 'error');
                    }
                }
            });
        },

        showMergeModal() {
            if (this.selected.length < 2) {
                alert('Please select at least 2 tags to merge');
                return;
            }
            this.mergeModalOpen = true;
        },

        async mergeTags() {
            if (!this.mergeDestination) {
                alert('Please select a destination tag');
                return;
            }

            try {
                const response = await fetch(`/su/portal/v1/admin/tags/merge`, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        destination_id: this.mergeDestination,
                        source_ids: this.selected.filter(id => id !== this.mergeDestination)
                    })
                });

                if (response.ok) {
                    this.mergeModalOpen = false;
                    this.selected = [];
                    this.selectedCount = 0;
                    this.mergeDestination = '';
                    this.loadData();
                    alert('Tags merged successfully');
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
