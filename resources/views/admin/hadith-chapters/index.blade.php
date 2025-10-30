@extends('layouts.admin')

@section('title', 'Hadith Chapters')

@section('content')
<div x-data="hadithChapterManager()" x-init="init()">
    {{-- Page Header --}}
    <div class="rounded-2xl p-6 mb-8 transition-colors" :class="darkMode ? 'glass-dark' : 'glass-light-soft'">
        <div class="absolute top-0 right-0 w-96 h-96 bg-gradient-to-br from-blue-500/20 to-green-500/20 rounded-full blur-3xl -translate-y-1/2 translate-x-1/2 animate-pulse"></div>

        <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-4xl font-black text-white mb-2 gradient-text">Hadith Chapters</h1>
                <p class="text-gray-400 flex items-center space-x-2 space-x-reverse">
                    <i data-lucide="book" class="w-4 h-4"></i>
                    <span>Manage hadith chapters organized by books</span>
                </p>
            </div>

            <div class="mt-4 md:mt-0">
                <a href="{{ route('admin.hadith-chapters.create') }}"
                   class="px-6 py-3 gradient-primary text-white rounded-xl font-bold shadow-lg hover:shadow-xl transition-all duration-300 flex items-center space-x-2 space-x-reverse hover:scale-105 animate-pulse-glow">
                    <i data-lucide="plus-circle" class="w-5 h-5"></i>
                    <span>Create New Chapter</span>
                </a>
            </div>
        </div>
    </div>

    {{-- Stats Overview --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        {{-- Total Chapters --}}
        <div class="rounded-xl p-6 relative overflow-hidden transition-colors group hover:scale-105 transition-transform duration-300"
             :class="darkMode ? 'glass-dark' : 'glass-light-soft'">
            <div class="absolute top-0 right-0 w-24 h-24 bg-blue-500/20 rounded-full -translate-y-1/2 translate-x-1/2 group-hover:scale-150 transition-transform duration-500"></div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center">
                        <i data-lucide="layers" class="w-6 h-6 text-white"></i>
                    </div>
                    <span class="text-2xl font-black text-white" x-text="stats.total">0</span>
                </div>
                <p class="text-sm text-gray-400 font-semibold">Total Chapters</p>
            </div>
        </div>

        {{-- Total Books --}}
        <div class="rounded-xl p-6 relative overflow-hidden transition-colors group hover:scale-105 transition-transform duration-300"
             :class="darkMode ? 'glass-dark' : 'glass-light-soft'">
            <div class="absolute top-0 right-0 w-24 h-24 bg-green-500/20 rounded-full -translate-y-1/2 translate-x-1/2 group-hover:scale-150 transition-transform duration-500"></div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-green-500 to-green-600 flex items-center justify-center">
                        <i data-lucide="book-open" class="w-6 h-6 text-white"></i>
                    </div>
                    <span class="text-2xl font-black text-white" x-text="stats.totalBooks">0</span>
                </div>
                <p class="text-sm text-gray-400 font-semibold">Books</p>
            </div>
        </div>

        {{-- Total Hadiths --}}
        <div class="rounded-xl p-6 relative overflow-hidden transition-colors group hover:scale-105 transition-transform duration-300"
             :class="darkMode ? 'glass-dark' : 'glass-light-soft'">
            <div class="absolute top-0 right-0 w-24 h-24 bg-purple-500/20 rounded-full -translate-y-1/2 translate-x-1/2 group-hover:scale-150 transition-transform duration-500"></div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-purple-500 to-purple-600 flex items-center justify-center">
                        <i data-lucide="file-text" class="w-6 h-6 text-white"></i>
                    </div>
                    <span class="text-2xl font-black text-white" x-text="stats.totalHadiths">0</span>
                </div>
                <p class="text-sm text-gray-400 font-semibold">Total Hadiths</p>
            </div>
        </div>

        {{-- Largest Chapter --}}
        <div class="rounded-xl p-6 relative overflow-hidden transition-colors group hover:scale-105 transition-transform duration-300"
             :class="darkMode ? 'glass-dark' : 'glass-light-soft'">
            <div class="absolute top-0 right-0 w-24 h-24 bg-teal-500/20 rounded-full -translate-y-1/2 translate-x-1/2 group-hover:scale-150 transition-transform duration-500"></div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-[#64CCC5] to-teal-600 flex items-center justify-center">
                        <i data-lucide="star" class="w-6 h-6 text-white"></i>
                    </div>
                    <span class="text-2xl font-black text-white" x-text="stats.maxHadiths">0</span>
                </div>
                <p class="text-sm text-gray-400 font-semibold">Most Hadiths</p>
            </div>
        </div>
    </div>

    {{-- Search & Filters --}}
    <div class="rounded-2xl p-6 mb-6 transition-colors" :class="darkMode ? 'glass-dark' : 'glass-light-soft'">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            {{-- Search --}}
            <div class="md:col-span-2">
                <div class="relative">
                    <i data-lucide="search" class="absolute right-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400"></i>
                    <input type="text"
                           x-model="search"
                           @input.debounce.500ms="loadData()"
                           placeholder="Search by chapter name or number..."
                           class="w-full pr-12 pl-4 py-3 rounded-xl transition-all focus:outline-none focus:ring-2 focus:ring-blue-500"
                           :class="darkMode ? 'bg-white/5 border border-white/10 text-white placeholder-gray-400' : 'glass-light-strong text-gray-900 placeholder-gray-500'">
                </div>
            </div>

            {{-- Book Filter --}}
            <div>
                <select x-model="selectedBook"
                        @change="loadData()"
                        class="w-full px-4 py-3 rounded-xl transition-all focus:outline-none focus:ring-2 focus:ring-blue-500"
                        :class="darkMode ? 'bg-white/5 border border-white/10 text-white' : 'glass-light-strong text-gray-900'">
                    <option value="">All Books</option>
                    <template x-for="book in books" :key="book.id">
                        <option :value="book.id" x-text="book.name?.en || book.name?.ar"></option>
                    </template>
                </select>
            </div>
        </div>
    </div>

    {{-- Bulk Actions --}}
    <div class="rounded-xl p-4 mb-6 transition-colors"
         :class="darkMode ? 'glass-dark' : 'glass-light-soft'"
         x-show="selectedCount > 0"
         x-transition>
        <div class="flex items-center justify-between">
            <span class="font-semibold text-white">
                <span x-text="selectedCount"></span> chapter(s) selected
            </span>
            <button @click="bulkDelete()"
                    class="bg-red-600 text-white px-6 py-2 rounded-lg font-semibold hover:bg-red-700 transition">
                <i data-lucide="trash-2" class="w-4 h-4 inline-block mr-2"></i>
                Delete Selected
            </button>
        </div>
    </div>

    {{-- Chapters by Book (Grouped & Collapsible) --}}
    <div class="space-y-6">
        <template x-if="loading">
            <div class="rounded-xl p-8 transition-colors" :class="darkMode ? 'glass-dark' : 'glass-light-soft'">
                <div class="space-y-4">
                    <div class="h-16 bg-gray-200 rounded animate-pulse" :class="darkMode ? 'bg-gray-800' : ''"></div>
                    <div class="h-16 bg-gray-200 rounded animate-pulse" :class="darkMode ? 'bg-gray-800' : ''"></div>
                    <div class="h-16 bg-gray-200 rounded animate-pulse" :class="darkMode ? 'bg-gray-800' : ''"></div>
                </div>
            </div>
        </template>

        <template x-if="!loading && Object.keys(groupedChapters).length > 0">
            <div class="space-y-4">
                <template x-for="(chapters, bookName) in groupedChapters" :key="bookName">
                    <div class="rounded-xl overflow-hidden transition-colors" :class="darkMode ? 'glass-dark' : 'glass-light-soft'">
                        {{-- Book Header --}}
                        <button @click="toggleBook(bookName)"
                                class="w-full p-6 flex items-center justify-between hover:bg-white/5 transition-colors">
                            <div class="flex items-center space-x-4 space-x-reverse">
                                <div class="w-12 h-12 rounded-lg gradient-primary flex items-center justify-center">
                                    <i data-lucide="book" class="w-6 h-6 text-white"></i>
                                </div>
                                <div class="text-left">
                                    <h3 class="text-xl font-bold text-white" x-text="bookName"></h3>
                                    <p class="text-sm text-gray-400">
                                        <span x-text="chapters.length"></span> chapters
                                    </p>
                                </div>
                            </div>
                            <i data-lucide="chevron-down"
                               class="w-5 h-5 text-gray-400 transition-transform"
                               :class="expandedBooks[bookName] ? 'rotate-180' : ''"></i>
                        </button>

                        {{-- Chapters Table --}}
                        <div x-show="expandedBooks[bookName]"
                             x-transition
                             class="border-t"
                             :class="darkMode ? 'border-white/10' : 'border-gray-200'">
                            <table class="w-full">
                                <thead :class="darkMode ? 'bg-white/5' : 'bg-gray-50'">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-400">
                                            <input type="checkbox"
                                                   @change="toggleSelectAllInBook(chapters)"
                                                   class="rounded">
                                        </th>
                                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-400">Chapter #</th>
                                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-400">Name</th>
                                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-400">Hadiths</th>
                                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-400">Created</th>
                                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-400">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="chapter in chapters" :key="chapter.id">
                                        <tr class="border-t transition-colors hover:bg-white/5"
                                            :class="darkMode ? 'border-white/10' : 'border-gray-200'">
                                            <td class="px-6 py-4">
                                                <input type="checkbox"
                                                       :value="chapter.id"
                                                       x-model="selected"
                                                       @change="updateSelectedCount()"
                                                       class="rounded">
                                            </td>
                                            <td class="px-6 py-4">
                                                <span class="px-3 py-1 rounded-full text-sm font-semibold bg-blue-100 text-blue-800"
                                                      x-text="'Ch. ' + chapter.chapter_number"></span>
                                            </td>
                                            <td class="px-6 py-4 font-semibold text-white"
                                                x-text="chapter.name?.en || chapter.name?.ar || '—'"></td>
                                            <td class="px-6 py-4">
                                                <span class="text-[#64CCC5] font-semibold"
                                                      x-text="chapter.hadiths_count || 0"></span>
                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-400"
                                                x-text="new Date(chapter.created_at).toLocaleDateString()"></td>
                                            <td class="px-6 py-4">
                                                <div class="flex items-center space-x-2">
                                                    <a :href="`/su/portal/v1/admin/hadith-chapters/${chapter.id}`"
                                                       class="text-[#0056b3] hover:text-blue-400 transition"
                                                       title="View">
                                                        <i data-lucide="eye" class="w-4 h-4"></i>
                                                    </a>
                                                    <a :href="`/su/portal/v1/admin/hadith-chapters/${chapter.id}/edit`"
                                                       class="text-[#64CCC5] hover:text-teal-400 transition"
                                                       title="Edit">
                                                        <i data-lucide="pencil" class="w-4 h-4"></i>
                                                    </a>
                                                    <button @click="deleteChapter(chapter.id)"
                                                            class="text-red-600 hover:text-red-400 transition"
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

        <template x-if="!loading && Object.keys(groupedChapters).length === 0">
            <div class="rounded-xl p-12 text-center transition-colors" :class="darkMode ? 'glass-dark' : 'glass-light-soft'">
                <i data-lucide="inbox" class="w-16 h-16 text-gray-400 mx-auto mb-4"></i>
                <p class="text-gray-400 text-lg">No chapters found</p>
            </div>
        </template>
    </div>

    {{-- Pagination --}}
    <div class="mt-8 flex justify-between items-center">
        <p class="text-gray-400">
            Showing <span x-text="(currentPage - 1) * perPage + 1"></span>
            to <span x-text="Math.min(currentPage * perPage, totalItems)"></span>
            of <span x-text="totalItems"></span> chapters
        </p>
        <div class="flex gap-2">
            <button @click="previousPage()"
                    :disabled="currentPage === 1"
                    :class="currentPage === 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-blue-700'"
                    class="px-4 py-2 bg-[#0056b3] text-white rounded-lg transition">
                Previous
            </button>
            <button @click="nextPage()"
                    :disabled="currentPage >= totalPages"
                    :class="currentPage >= totalPages ? 'opacity-50 cursor-not-allowed' : 'hover:bg-blue-700'"
                    class="px-4 py-2 bg-[#0056b3] text-white rounded-lg transition">
                Next
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script>
function hadithChapterManager() {
    return {
        chapters: [],
        groupedChapters: {},
        books: [],
        search: '',
        selectedBook: '',
        selected: [],
        selectedCount: 0,
        expandedBooks: {},
        currentPage: 1,
        perPage: 50,
        totalItems: 0,
        totalPages: 1,
        loading: false,
        stats: {
            total: 0,
            totalBooks: 0,
            totalHadiths: 0,
            maxHadiths: 0
        },

        init() {
            this.loadBooks();
            this.loadData();
        },

        async loadBooks() {
            try {
                const response = await fetch('/su/portal/v1/admin/hadith-books', {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });

                if (response.ok) {
                    const data = await response.json();
                    this.books = data.data || [];
                }
            } catch (error) {
                console.error('Error loading books:', error);
            }
        },

        async loadData() {
            this.loading = true;
            const params = new URLSearchParams({
                page: this.currentPage,
                per_page: this.perPage,
                search: this.search,
                book_id: this.selectedBook
            });

            try {
                const response = await fetch(`/su/portal/v1/admin/hadith-chapters?${params}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });

                if (response.ok) {
                    const data = await response.json();
                    this.chapters = data.data || [];
                    this.groupChaptersByBook();
                    this.stats = data.stats || this.stats;
                    this.totalItems = data.meta?.total || 0;
                    this.totalPages = data.meta?.last_page || 1;

                    // Auto-expand first book if searching/filtering
                    if (Object.keys(this.groupedChapters).length > 0) {
                        const firstBook = Object.keys(this.groupedChapters)[0];
                        if (!this.expandedBooks[firstBook]) {
                            this.expandedBooks[firstBook] = true;
                        }
                    }

                    this.$nextTick(() => lucide.createIcons());
                } else {
                    console.error('Failed to load chapters');
                }
            } catch (error) {
                console.error('Error:', error);
            } finally {
                this.loading = false;
            }
        },

        groupChaptersByBook() {
            this.groupedChapters = {};
            this.chapters.forEach(chapter => {
                const bookName = chapter.book?.name?.en || chapter.book?.name?.ar || 'Unknown Book';
                if (!this.groupedChapters[bookName]) {
                    this.groupedChapters[bookName] = [];
                }
                this.groupedChapters[bookName].push(chapter);
            });
        },

        toggleBook(bookName) {
            this.expandedBooks[bookName] = !this.expandedBooks[bookName];
            this.$nextTick(() => lucide.createIcons());
        },

        toggleSelectAllInBook(chapters) {
            const chapterIds = chapters.map(c => c.id);
            const allSelected = chapterIds.every(id => this.selected.includes(id));

            if (allSelected) {
                this.selected = this.selected.filter(id => !chapterIds.includes(id));
            } else {
                chapterIds.forEach(id => {
                    if (!this.selected.includes(id)) {
                        this.selected.push(id);
                    }
                });
            }
            this.updateSelectedCount();
        },

        updateSelectedCount() {
            this.selectedCount = this.selected.length;
        },

        async deleteChapter(id) {
            if (!confirm('Are you sure you want to delete this chapter?')) return;

            try {
                const response = await fetch(`/su/portal/v1/admin/hadith-chapters/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                if (response.ok) {
                    this.loadData();
                    window.showToast?.('Chapter deleted successfully', 'success');
                } else {
                    const data = await response.json();
                    window.showToast?.(data.message || 'Failed to delete chapter', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                window.showToast?.('An error occurred while deleting', 'error');
            }
        },

        async bulkDelete() {
            if (!confirm(`Delete ${this.selected.length} chapter(s)?`)) return;

            try {
                const response = await fetch('/su/portal/v1/admin/hadith-chapters/bulk-delete', {
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
                    window.showToast?.('Chapters deleted successfully', 'success');
                } else {
                    window.showToast?.('Failed to delete chapters', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
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
        }
    };
}
</script>
@endpush
@endsection
