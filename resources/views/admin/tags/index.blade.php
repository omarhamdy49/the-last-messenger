@extends('layouts.admin')

@section('content')
<div x-data="tagManagement()">

    <!-- Page Header with Stats -->
    <div class="mb-8">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6">
            <div>
                <h1 class="text-3xl font-bold gradient-text mb-2">Tags Management</h1>
                <p class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                    Manage content tags and organize your articles
                </p>
            </div>

            <div class="flex items-center gap-3 flex-wrap">
                <button @click="mergeSelected()"
                        x-show="selectedTags.length > 1"
                        class="px-4 py-2.5 rounded-lg font-semibold transition-all hover:scale-105 flex items-center gap-2"
                        :class="darkMode ? 'bg-gray-800 text-white hover:bg-gray-700' : 'bg-gray-100 text-gray-900 hover:bg-gray-200'">
                    <i data-lucide="git-merge" class="w-5 h-5"></i>
                    <span>Merge Selected</span>
                </button>

                <button @click="deleteUnused()"
                        class="px-4 py-2.5 rounded-lg font-semibold transition-all hover:scale-105 flex items-center gap-2"
                        :class="darkMode ? 'bg-gray-800 text-white hover:bg-gray-700' : 'bg-gray-100 text-gray-900 hover:bg-gray-200'">
                    <i data-lucide="trash" class="w-5 h-5"></i>
                    <span>Delete Unused</span>
                </button>

                <a href="{{ route('admin.tags.create') }}"
                   class="px-6 py-2.5 rounded-lg font-semibold bg-gradient-to-r from-[#0056b3] to-[#218838] text-white hover:scale-105 transition-all shadow-lg hover:shadow-xl flex items-center gap-2">
                    <i data-lucide="plus" class="w-5 h-5"></i>
                    <span>New Tag</span>
                </a>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <x-admin.stats-card
                title="Total Tags"
                :value="$totalTags"
                icon="tag"
                color="blue"
                :trend="null"
            />
            <x-admin.stats-card
                title="Most Used"
                :value="$mostUsedTag?->name ?? 'N/A'"
                icon="trending-up"
                color="green"
                :trend="null"
            />
            <x-admin.stats-card
                title="Unused Tags"
                :value="$unusedTags"
                icon="alert-circle"
                color="orange"
                :trend="null"
            />
            <x-admin.stats-card
                title="Total Usage"
                :value="$totalUsage"
                icon="hash"
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
                   @input.debounce.300ms="filterTags()"
                   placeholder="Search tags..."
                   class="w-full pl-10 pr-10 py-3 rounded-xl border-2 transition-all duration-300 focus:ring-2 focus:ring-accent/50"
                   :class="darkMode ?
                       'bg-gray-800 border-gray-700 text-white placeholder-gray-500 focus:border-accent' :
                       'bg-white border-gray-300 text-gray-900 placeholder-gray-400 focus:border-accent'">
            <button x-show="searchQuery"
                    @click="searchQuery = ''; filterTags();"
                    class="absolute right-3 top-1/2 -translate-y-1/2 hover:scale-110 transition-transform"
                    :class="darkMode ? 'text-gray-400 hover:text-white' : 'text-gray-500 hover:text-gray-900'">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <!-- Usage Filter -->
        <select x-model="usageFilter"
                @change="filterTags()"
                class="px-4 py-3 rounded-xl border-2 transition-all focus:ring-2 focus:ring-accent/50 w-full lg:w-48"
                :class="darkMode ?
                    'bg-gray-800 border-gray-700 text-white focus:border-accent' :
                    'bg-white border-gray-300 text-gray-900 focus:border-accent'">
            <option value="all">All Tags</option>
            <option value="used">Used Tags</option>
            <option value="unused">Unused Tags</option>
            <option value="popular">Popular (10+)</option>
        </select>

        <!-- Sort -->
        <select x-model="sortBy"
                @change="sortTags()"
                class="px-4 py-3 rounded-xl border-2 transition-all focus:ring-2 focus:ring-accent/50 w-full lg:w-48"
                :class="darkMode ?
                    'bg-gray-800 border-gray-700 text-white focus:border-accent' :
                    'bg-white border-gray-300 text-gray-900 focus:border-accent'">
            <option value="name">Sort by Name</option>
            <option value="usage">Sort by Usage</option>
            <option value="created">Sort by Date</option>
        </select>
    </div>

    <!-- Tags Data Table -->
    <div class="rounded-2xl overflow-hidden shadow-xl border"
         :class="darkMode ? 'bg-gray-900 border-gray-800' : 'bg-white border-gray-200'">

        <!-- Table Header -->
        <div class="overflow-x-auto custom-scrollbar">
            <table class="w-full">
                <thead class="border-b"
                       :class="darkMode ? 'border-gray-800 bg-gray-800/50' : 'border-gray-200 bg-gray-50'">
                    <tr>
                        <th class="px-6 py-4 text-left">
                            <input type="checkbox"
                                   x-model="selectAll"
                                   @change="toggleSelectAll()"
                                   class="w-5 h-5 rounded border-2 transition-all cursor-pointer"
                                   :class="darkMode ?
                                       'bg-gray-800 border-gray-700 checked:bg-accent checked:border-accent' :
                                       'bg-white border-gray-300 checked:bg-accent checked:border-accent'">
                        </th>
                        <th class="px-6 py-4 text-left font-semibold"
                            :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            Tag Name
                        </th>
                        <th class="px-6 py-4 text-left font-semibold"
                            :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            Color
                        </th>
                        <th class="px-6 py-4 text-center font-semibold"
                            :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            Usage Count
                        </th>
                        <th class="px-6 py-4 text-left font-semibold"
                            :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            Created
                        </th>
                        <th class="px-6 py-4 text-center font-semibold"
                            :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            Actions
                        </th>
                    </tr>
                </thead>

                <tbody class="divide-y" :class="darkMode ? 'divide-gray-800' : 'divide-gray-200'">
                    <!-- Loading State -->
                    <template x-if="loading">
                        <template x-for="i in 5" :key="i">
                            <tr>
                                <td colspan="6" class="px-6 py-4">
                                    <div class="h-4 rounded animate-pulse"
                                         :class="darkMode ? 'bg-gray-800' : 'bg-gray-200'"></div>
                                </td>
                            </tr>
                        </template>
                    </template>

                    <!-- Tags List -->
                    <template x-if="!loading && filteredTags.length > 0">
                        <template x-for="tag in paginatedTags" :key="tag.id">
                            <tr class="transition-colors"
                                :class="darkMode ? 'hover:bg-gray-800/50' : 'hover:bg-gray-50'">
                                <!-- Checkbox -->
                                <td class="px-6 py-4">
                                    <input type="checkbox"
                                           :value="tag.id"
                                           x-model="selectedTags"
                                           class="w-5 h-5 rounded border-2 transition-all cursor-pointer"
                                           :class="darkMode ?
                                               'bg-gray-800 border-gray-700 checked:bg-accent checked:border-accent' :
                                               'bg-white border-gray-300 checked:bg-accent checked:border-accent'">
                                </td>

                                <!-- Name (All Languages) -->
                                <td class="px-6 py-4">
                                    <div class="space-y-1">
                                        <a :href="`/admin/tags/${tag.id}/edit`"
                                           class="font-semibold hover:text-accent transition-colors"
                                           :class="darkMode ? 'text-white' : 'text-gray-900'"
                                           x-text="tag.name"></a>
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <template x-if="tag.translations">
                                                <template x-for="(trans, lang) in tag.translations" :key="lang">
                                                    <span class="text-xs px-2 py-0.5 rounded"
                                                          :class="darkMode ? 'bg-gray-800 text-gray-400' : 'bg-gray-100 text-gray-600'">
                                                        <span x-text="lang.toUpperCase()"></span>: <span x-text="trans"></span>
                                                    </span>
                                                </template>
                                            </template>
                                        </div>
                                    </div>
                                </td>

                                <!-- Color -->
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <div class="w-8 h-8 rounded-lg shadow-sm border-2 border-white"
                                             :style="`background: ${tag.color};`"></div>
                                        <code class="text-xs font-mono"
                                              :class="darkMode ? 'text-gray-400' : 'text-gray-600'"
                                              x-text="tag.color"></code>
                                    </div>
                                </td>

                                <!-- Usage Count -->
                                <td class="px-6 py-4 text-center">
                                    <span class="inline-flex items-center justify-center px-3 py-1 rounded-full text-sm font-bold"
                                          :class="[
                                              tag.articles_count === 0 ? 'bg-gray-500/10 text-gray-500' :
                                              tag.articles_count < 5 ? 'bg-blue-500/10 text-blue-500' :
                                              tag.articles_count < 10 ? 'bg-green-500/10 text-green-500' :
                                              'bg-purple-500/10 text-purple-500'
                                          ]"
                                          x-text="tag.articles_count || 0"></span>
                                </td>

                                <!-- Created -->
                                <td class="px-6 py-4">
                                    <span class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'"
                                          x-text="formatDate(tag.created_at)"></span>
                                </td>

                                <!-- Actions -->
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-center gap-2">
                                        <a :href="`/admin/tags/${tag.id}/edit`"
                                           class="p-2 rounded-lg transition-all hover:scale-110"
                                           :class="darkMode ? 'hover:bg-gray-700 text-accent' : 'hover:bg-gray-100 text-accent'">
                                            <i data-lucide="edit" class="w-4 h-4"></i>
                                        </a>
                                        <button @click="deleteTag(tag.id)"
                                                class="p-2 rounded-lg transition-all hover:scale-110"
                                                :class="darkMode ? 'hover:bg-red-900/20 text-red-400' : 'hover:bg-red-50 text-red-600'">
                                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </template>

                    <!-- Empty State -->
                    <template x-if="!loading && filteredTags.length === 0">
                        <tr>
                            <td colspan="6" class="px-6 py-16 text-center">
                                <div class="inline-flex items-center justify-center w-20 h-20 rounded-full mb-4"
                                     :class="darkMode ? 'bg-gray-800' : 'bg-gray-100'">
                                    <i data-lucide="tag" class="w-10 h-10"
                                       :class="darkMode ? 'text-gray-600' : 'text-gray-400'"></i>
                                </div>
                                <h3 class="text-xl font-bold mb-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                                    No Tags Found
                                </h3>
                                <p :class="darkMode ? 'text-gray-400' : 'text-gray-600'" class="mb-6">
                                    <template x-if="searchQuery || usageFilter !== 'all'">
                                        <span>Try adjusting your filters or search query</span>
                                    </template>
                                    <template x-if="!searchQuery && usageFilter === 'all'">
                                        <span>Get started by creating your first tag</span>
                                    </template>
                                </p>
                                <a href="{{ route('admin.tags.create') }}"
                                   class="inline-flex items-center gap-2 px-6 py-3 rounded-lg font-semibold bg-gradient-to-r from-[#0056b3] to-[#218838] text-white hover:scale-105 transition-all shadow-lg">
                                    <i data-lucide="plus" class="w-5 h-5"></i>
                                    <span>Create Tag</span>
                                </a>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="border-t px-6 py-4"
             :class="darkMode ? 'border-gray-800 bg-gray-900/50' : 'border-gray-200 bg-gray-50'"
             x-show="filteredTags.length > perPage">
            <div class="flex items-center justify-between">
                <div class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                    Showing <span class="font-bold" x-text="startIndex + 1"></span> to
                    <span class="font-bold" x-text="endIndex"></span> of
                    <span class="font-bold" x-text="filteredTags.length"></span> tags
                </div>

                <div class="flex items-center gap-2">
                    <button @click="currentPage--"
                            :disabled="currentPage === 1"
                            class="px-3 py-1.5 rounded-lg transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                            :class="darkMode ? 'bg-gray-800 hover:bg-gray-700' : 'bg-gray-100 hover:bg-gray-200'">
                        <i data-lucide="chevron-left" class="w-4 h-4"></i>
                    </button>

                    <span class="px-4 py-1.5 font-semibold" :class="darkMode ? 'text-white' : 'text-gray-900'">
                        <span x-text="currentPage"></span> / <span x-text="totalPages"></span>
                    </span>

                    <button @click="currentPage++"
                            :disabled="currentPage === totalPages"
                            class="px-3 py-1.5 rounded-lg transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                            :class="darkMode ? 'bg-gray-800 hover:bg-gray-700' : 'bg-gray-100 hover:bg-gray-200'">
                        <i data-lucide="chevron-right" class="w-4 h-4"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Merge Modal -->
    <x-admin.tag-merge-modal />
</div>
@endsection

@push('scripts')
<script>
function tagManagement() {
    return {
        loading: false,
        tags: @json($tags),
        filteredTags: [],
        paginatedTags: [],
        selectedTags: [],
        selectAll: false,
        searchQuery: '',
        usageFilter: 'all',
        sortBy: 'name',
        currentPage: 1,
        perPage: 25,
        totalPages: 1,

        init() {
            this.filteredTags = this.tags;
            this.updatePagination();
            this.$nextTick(() => lucide.createIcons());
        },

        filterTags() {
            let filtered = [...this.tags];

            // Search filter
            if (this.searchQuery) {
                const query = this.searchQuery.toLowerCase();
                filtered = filtered.filter(tag =>
                    tag.name.toLowerCase().includes(query)
                );
            }

            // Usage filter
            if (this.usageFilter === 'used') {
                filtered = filtered.filter(tag => (tag.articles_count || 0) > 0);
            } else if (this.usageFilter === 'unused') {
                filtered = filtered.filter(tag => (tag.articles_count || 0) === 0);
            } else if (this.usageFilter === 'popular') {
                filtered = filtered.filter(tag => (tag.articles_count || 0) >= 10);
            }

            this.filteredTags = filtered;
            this.sortTags();
        },

        sortTags() {
            if (this.sortBy === 'name') {
                this.filteredTags.sort((a, b) => a.name.localeCompare(b.name));
            } else if (this.sortBy === 'usage') {
                this.filteredTags.sort((a, b) => (b.articles_count || 0) - (a.articles_count || 0));
            } else if (this.sortBy === 'created') {
                this.filteredTags.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
            }

            this.currentPage = 1;
            this.updatePagination();
        },

        updatePagination() {
            this.totalPages = Math.ceil(this.filteredTags.length / this.perPage);
            const start = (this.currentPage - 1) * this.perPage;
            const end = start + this.perPage;
            this.paginatedTags = this.filteredTags.slice(start, end);
            this.$nextTick(() => lucide.createIcons());
        },

        get startIndex() {
            return (this.currentPage - 1) * this.perPage;
        },

        get endIndex() {
            return Math.min(this.startIndex + this.perPage, this.filteredTags.length);
        },

        toggleSelectAll() {
            if (this.selectAll) {
                this.selectedTags = this.paginatedTags.map(tag => tag.id);
            } else {
                this.selectedTags = [];
            }
        },

        mergeSelected() {
            if (this.selectedTags.length < 2) {
                alert('Please select at least 2 tags to merge');
                return;
            }
            window.dispatchEvent(new CustomEvent('open-tag-merge-modal', {
                detail: { tagIds: this.selectedTags }
            }));
        },

        async deleteUnused() {
            if (!confirm('Are you sure you want to delete all unused tags? This cannot be undone.')) {
                return;
            }

            this.loading = true;
            try {
                const response = await fetch('/admin/tags/delete-unused', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    }
                });

                if (response.ok) {
                    window.location.reload();
                }
            } catch (error) {
                console.error('Failed to delete unused tags:', error);
            } finally {
                this.loading = false;
            }
        },

        async deleteTag(tagId) {
            if (!confirm('Are you sure you want to delete this tag?')) {
                return;
            }

            try {
                const response = await fetch(`/admin/tags/${tagId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    }
                });

                if (response.ok) {
                    this.tags = this.tags.filter(tag => tag.id !== tagId);
                    this.filterTags();
                }
            } catch (error) {
                console.error('Failed to delete tag:', error);
            }
        },

        formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            });
        }
    };
}
</script>
@endpush
@endsection
