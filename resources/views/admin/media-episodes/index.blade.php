@extends('layouts.admin')

@section('title', 'Media Episodes')

@section('content')
<div class="container mx-auto px-4 py-8" x-data="mediaEpisodeManager()" x-init="init()">
    <!-- Header -->
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">
                Media Episodes
            </h1>
            <p class="mt-2" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                Manage video episodes across all programs
            </p>
        </div>
        <a href="{{ route('admin.media-episodes.create') }}"
           class="bg-gradient-to-r from-[#0056b3] to-[#218838] text-white px-6 py-3 rounded-lg font-semibold hover:shadow-lg transition-all flex items-center gap-2">
            <i data-lucide="plus-circle" class="w-5 h-5"></i>
            <span>Create New Episode</span>
        </a>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="glass rounded-lg p-6 transition-colors" :class="darkMode ? 'bg-gray-900/50' : 'bg-white/80'">
            <p class="text-sm font-semibold" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">Total Episodes</p>
            <p class="text-4xl font-bold mt-2 text-[#0056b3]" x-text="stats.total ?? 0"></p>
        </div>
        <div class="glass rounded-lg p-6 transition-colors" :class="darkMode ? 'bg-gray-900/50' : 'bg-white/80'">
            <p class="text-sm font-semibold" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">Published</p>
            <p class="text-4xl font-bold mt-2 text-[#218838]" x-text="stats.published ?? 0"></p>
        </div>
        <div class="glass rounded-lg p-6 transition-colors" :class="darkMode ? 'bg-gray-900/50' : 'bg-white/80'">
            <p class="text-sm font-semibold" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">Scheduled</p>
            <p class="text-4xl font-bold mt-2 text-orange-500" x-text="stats.scheduled ?? 0"></p>
        </div>
        <div class="glass rounded-lg p-6 transition-colors" :class="darkMode ? 'bg-gray-900/50' : 'bg-white/80'">
            <p class="text-sm font-semibold" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">Total Duration</p>
            <p class="text-2xl font-bold mt-2 text-[#64CCC5]" x-text="stats.total_duration ?? '00:00:00'"></p>
        </div>
    </div>

    <!-- Search & Filters -->
    <div class="glass rounded-lg p-6 mb-6 transition-colors" :class="darkMode ? 'bg-gray-900/50' : 'bg-white/80'">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="md:col-span-2">
                <label class="block text-sm font-semibold mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                    Search
                </label>
                <input type="text"
                       x-model="search"
                       @input.debounce.500ms="loadData()"
                       placeholder="Search episodes by title or description..."
                       class="w-full px-4 py-2 rounded-lg border focus:outline-none focus:ring-2 focus:ring-[#0056b3]"
                       :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
            </div>
            <div>
                <label class="block text-sm font-semibold mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                    Filter by Program
                </label>
                <select x-model="selectedProgram"
                        @change="loadData()"
                        class="w-full px-4 py-2 rounded-lg border focus:outline-none focus:ring-2 focus:ring-[#0056b3]"
                        :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                    <option value="">All Programs</option>
                    <template x-for="program in programs" :key="program.id">
                        <option :value="program.id" x-text="program.title?.en || program.title?.ar"></option>
                    </template>
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                    Filter by Status
                </label>
                <select x-model="selectedStatus"
                        @change="loadData()"
                        class="w-full px-4 py-2 rounded-lg border focus:outline-none focus:ring-2 focus:ring-[#0056b3]"
                        :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                    <option value="">All Statuses</option>
                    <option value="draft">Draft</option>
                    <option value="scheduled">Scheduled</option>
                    <option value="published">Published</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Bulk Actions -->
    <div class="glass rounded-lg p-4 mb-6 transition-colors" :class="darkMode ? 'bg-gray-900/50' : 'bg-white/80'" x-show="selected.length > 0">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <span class="font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                <span x-text="selected.length"></span> episode(s) selected
            </span>
            <div class="flex gap-2">
                <button @click="bulkChangeStatus('published')"
                        class="bg-green-600 text-white px-4 py-2 rounded-lg font-semibold hover:bg-green-700 transition text-sm">
                    Mark as Published
                </button>
                <button @click="bulkChangeStatus('scheduled')"
                        class="bg-orange-600 text-white px-4 py-2 rounded-lg font-semibold hover:bg-orange-700 transition text-sm">
                    Mark as Scheduled
                </button>
                <button @click="bulkChangeStatus('draft')"
                        class="bg-gray-600 text-white px-4 py-2 rounded-lg font-semibold hover:bg-gray-700 transition text-sm">
                    Mark as Draft
                </button>
                <button @click="bulkDelete()"
                        class="bg-red-600 text-white px-4 py-2 rounded-lg font-semibold hover:bg-red-700 transition text-sm">
                    Delete Selected
                </button>
            </div>
        </div>
    </div>

    <!-- Episodes List (Grouped by Program) -->
    <div class="space-y-6">
        <template x-if="loading">
            <div class="glass rounded-lg p-8 transition-colors" :class="darkMode ? 'bg-gray-900/50' : 'bg-white/80'">
                <div class="space-y-4">
                    <div class="h-16 bg-gray-200 rounded animate-pulse" :class="darkMode ? 'bg-gray-800' : ''"></div>
                    <div class="h-16 bg-gray-200 rounded animate-pulse" :class="darkMode ? 'bg-gray-800' : ''"></div>
                    <div class="h-16 bg-gray-200 rounded animate-pulse" :class="darkMode ? 'bg-gray-800' : ''"></div>
                </div>
            </div>
        </template>

        <template x-if="!loading && Object.keys(groupedEpisodes).length > 0">
            <div class="space-y-6">
                <template x-for="(episodes, programName) in groupedEpisodes" :key="programName">
                    <div class="glass rounded-lg overflow-hidden transition-colors" :class="darkMode ? 'bg-gray-900/50' : 'bg-white/80'">
                        <!-- Program Header (Collapsible) -->
                        <div @click="toggleProgram(programName)"
                             class="px-6 py-4 cursor-pointer flex justify-between items-center border-b transition-colors"
                             :class="darkMode ? 'bg-gray-800 border-gray-700 hover:bg-gray-750' : 'bg-gray-100 border-gray-200 hover:bg-gray-200'">
                            <div class="flex items-center gap-3">
                                <i :data-lucide="expandedPrograms[programName] ? 'chevron-down' : 'chevron-right'" class="w-5 h-5"></i>
                                <h3 class="text-xl font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'" x-text="programName"></h3>
                                <span class="px-3 py-1 rounded-full text-sm font-semibold bg-[#0056b3] text-white" x-text="episodes.length + ' episodes'"></span>
                            </div>
                        </div>

                        <!-- Episodes Table -->
                        <div x-show="expandedPrograms[programName]" x-collapse>
                            <table class="w-full">
                                <thead :class="darkMode ? 'bg-gray-800' : 'bg-gray-50'">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-sm font-semibold">
                                            <input type="checkbox" @change="toggleProgramSelection(programName)" class="rounded">
                                        </th>
                                        <th class="px-6 py-3 text-left text-sm font-semibold">#</th>
                                        <th class="px-6 py-3 text-left text-sm font-semibold">Title</th>
                                        <th class="px-6 py-3 text-left text-sm font-semibold">Status</th>
                                        <th class="px-6 py-3 text-left text-sm font-semibold">Duration</th>
                                        <th class="px-6 py-3 text-left text-sm font-semibold">Released</th>
                                        <th class="px-6 py-3 text-left text-sm font-semibold">Views</th>
                                        <th class="px-6 py-3 text-left text-sm font-semibold">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="episode in episodes" :key="episode.id">
                                        <tr :class="darkMode ? 'border-gray-700 hover:bg-gray-800' : 'border-gray-200 hover:bg-gray-50'" class="border-t transition">
                                            <td class="px-6 py-4">
                                                <input type="checkbox" :value="episode.id" @change="toggleEpisodeSelect(episode.id)" :checked="selected.includes(episode.id)" class="rounded">
                                            </td>
                                            <td class="px-6 py-4 font-bold text-[#0056b3]" x-text="episode.episode_number"></td>
                                            <td class="px-6 py-4">
                                                <div class="flex items-center gap-3">
                                                    <img :src="episode.thumbnail_url || 'https://via.placeholder.com/80x45'" class="w-20 h-12 rounded object-cover">
                                                    <span class="font-semibold" :class="darkMode ? 'text-white' : 'text-gray-900'" x-text="episode.title"></span>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4">
                                                <span class="px-3 py-1 rounded-full text-sm font-semibold"
                                                      :class="{
                                                          'bg-green-100 text-green-800': episode.status === 'published',
                                                          'bg-orange-100 text-orange-800': episode.status === 'scheduled',
                                                          'bg-gray-100 text-gray-800': episode.status === 'draft'
                                                      }"
                                                      x-text="episode.status.charAt(0).toUpperCase() + episode.status.slice(1)"></span>
                                            </td>
                                            <td class="px-6 py-4 text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'" x-text="episode.duration || '—'"></td>
                                            <td class="px-6 py-4 text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'" x-text="episode.released_at ? new Date(episode.released_at).toLocaleDateString() : '—'"></td>
                                            <td class="px-6 py-4">
                                                <div class="flex items-center gap-2">
                                                    <i data-lucide="eye" class="w-4 h-4 text-gray-500"></i>
                                                    <span class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'" x-text="episode.views_count || 0"></span>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="flex items-center gap-2">
                                                    <a :href="`/su/portal/v1/admin/media-episodes/${episode.id}`"
                                                       class="text-[#64CCC5] hover:text-[#0056b3] font-semibold text-sm"
                                                       title="View">
                                                        <i data-lucide="eye" class="w-4 h-4"></i>
                                                    </a>
                                                    <a :href="`/su/portal/v1/admin/media-episodes/${episode.id}/edit`"
                                                       class="text-[#0056b3] hover:text-blue-700 font-semibold text-sm"
                                                       title="Edit">
                                                        <i data-lucide="edit" class="w-4 h-4"></i>
                                                    </a>
                                                    <button @click="deleteItem(episode.id)"
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
                        </div>
                    </div>
                </template>
            </div>
        </template>

        <template x-if="!loading && Object.keys(groupedEpisodes).length === 0">
            <div class="glass rounded-lg p-8 text-center transition-colors" :class="darkMode ? 'bg-gray-900/50' : 'bg-white/80'">
                <i data-lucide="video" class="w-16 h-16 mx-auto mb-4" :class="darkMode ? 'text-gray-600' : 'text-gray-400'"></i>
                <p :class="darkMode ? 'text-gray-400' : 'text-gray-600'" class="text-lg">
                    No media episodes found
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
function mediaEpisodeManager() {
    return {
        items: [],
        groupedEpisodes: {},
        programs: [],
        search: '',
        selectedProgram: '',
        selectedStatus: '',
        selected: [],
        expandedPrograms: {},
        currentPage: 1,
        perPage: 15,
        totalItems: 0,
        totalPages: 1,
        loading: true,
        stats: { total: 0, published: 0, scheduled: 0, total_duration: '00:00:00' },
        darkMode: localStorage.getItem('darkMode') === 'true',

        async init() {
            await Promise.all([this.loadPrograms(), this.loadData()]);
            this.$nextTick(() => lucide.createIcons());
        },

        async loadPrograms() {
            try {
                const response = await fetch('/su/portal/v1/admin/media-programs?all=true', {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });

                if (response.ok) {
                    const data = await response.json();
                    this.programs = data.data || [];
                }
            } catch (error) {
                console.error('Error loading programs:', error);
            }
        },

        async loadData() {
            this.loading = true;
            const params = new URLSearchParams({
                page: this.currentPage,
                per_page: this.perPage,
                search: this.search,
                program_id: this.selectedProgram,
                status: this.selectedStatus
            });

            try {
                const response = await fetch(`/su/portal/v1/admin/media-episodes?${params}`, {
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

                    // Group episodes by program
                    this.groupEpisodes();
                }
            } catch (error) {
                console.error('Error loading media episodes:', error);
            } finally {
                this.loading = false;
                this.$nextTick(() => lucide.createIcons());
            }
        },

        groupEpisodes() {
            this.groupedEpisodes = {};
            this.items.forEach(episode => {
                const programName = episode.program?.title?.en || episode.program?.title?.ar || 'Unknown Program';
                if (!this.groupedEpisodes[programName]) {
                    this.groupedEpisodes[programName] = [];
                    this.expandedPrograms[programName] = true; // Auto-expand by default
                }
                this.groupedEpisodes[programName].push(episode);
            });
        },

        toggleProgram(programName) {
            this.expandedPrograms[programName] = !this.expandedPrograms[programName];
            this.$nextTick(() => lucide.createIcons());
        },

        toggleEpisodeSelect(episodeId) {
            const idx = this.selected.indexOf(episodeId);
            if (idx > -1) {
                this.selected.splice(idx, 1);
            } else {
                this.selected.push(episodeId);
            }
        },

        toggleProgramSelection(programName) {
            const programEpisodes = this.groupedEpisodes[programName];
            const programEpisodeIds = programEpisodes.map(e => e.id);
            const allSelected = programEpisodeIds.every(id => this.selected.includes(id));

            if (allSelected) {
                this.selected = this.selected.filter(id => !programEpisodeIds.includes(id));
            } else {
                programEpisodeIds.forEach(id => {
                    if (!this.selected.includes(id)) {
                        this.selected.push(id);
                    }
                });
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

        async deleteItem(id) {
            if (!confirm('Are you sure you want to delete this episode?')) return;

            try {
                const response = await fetch(`/su/portal/v1/admin/media-episodes/${id}`, {
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
                console.error('Error deleting episode:', error);
            }
        },

        async bulkDelete() {
            if (!confirm(`Delete ${this.selected.length} episodes?`)) return;

            try {
                const response = await fetch('/su/portal/v1/admin/media-episodes/bulk-delete', {
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
        },

        async bulkChangeStatus(newStatus) {
            if (!confirm(`Change status to "${newStatus}" for ${this.selected.length} episodes?`)) return;

            try {
                const response = await fetch('/su/portal/v1/admin/media-episodes/bulk-status', {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ ids: this.selected, status: newStatus })
                });

                if (response.ok) {
                    this.selected = [];
                    await this.loadData();
                }
            } catch (error) {
                console.error('Error changing status:', error);
            }
        }
    };
}
</script>
@endpush
@endsection
