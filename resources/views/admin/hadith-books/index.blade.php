@extends('layouts.admin')

@section('title', 'Hadith Books')

@section('content')
<div class="container mx-auto px-4 py-8" x-data="hadithBookManager()">
    <!-- Header -->
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">
                Hadith Books
            </h1>
            <p class="mt-2" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                Manage hadith book collections and their chapters
            </p>
        </div>
        <a href="{{ route('admin.hadith-books.create') }}"
           class="bg-gradient-to-r from-[#0056b3] to-[#218838] text-white px-6 py-3 rounded-lg font-semibold hover:shadow-lg transition-all">
            <i data-lucide="plus" class="w-4 h-4 inline ml-2"></i>
            Create New
        </a>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="glass-light rounded-lg p-6" :class="darkMode ? 'glass-dark' : ''">
            <p class="text-sm font-semibold" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">Total Books</p>
            <p class="text-4xl font-bold mt-2 text-[#0056b3]" x-text="stats.totalBooks ?? 0"></p>
        </div>
        <div class="glass-light rounded-lg p-6" :class="darkMode ? 'glass-dark' : ''">
            <p class="text-sm font-semibold" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">Total Chapters</p>
            <p class="text-4xl font-bold mt-2 text-green-600" x-text="stats.totalChapters ?? 0"></p>
        </div>
        <div class="glass-light rounded-lg p-6" :class="darkMode ? 'glass-dark' : ''">
            <p class="text-sm font-semibold" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">Total Hadith</p>
            <p class="text-4xl font-bold mt-2 text-[#64CCC5]" x-text="stats.totalHadith ?? 0"></p>
        </div>
        <div class="glass-light rounded-lg p-6" :class="darkMode ? 'glass-dark' : ''">
            <p class="text-sm font-semibold" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">Most Used</p>
            <p class="text-lg font-bold mt-2" :class="darkMode ? 'text-white' : 'text-gray-900'" x-text="stats.mostUsedBook ?? '—'"></p>
        </div>
    </div>

    <!-- Search & Filters -->
    <div class="glass-light rounded-lg p-6 mb-6" :class="darkMode ? 'glass-dark' : ''">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="md:col-span-2">
                <label class="block text-sm font-semibold mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                    <i data-lucide="search" class="w-4 h-4 inline ml-1"></i>
                    Search Books
                </label>
                <input type="text"
                       x-model="search"
                       placeholder="Search by book name in any language..."
                       @input="debounce(() => loadData(), 500)"
                       class="w-full px-4 py-2 rounded-lg border focus:outline-none focus:ring-2 focus:ring-[#0056b3]"
                       :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
            </div>
            <div>
                <label class="block text-sm font-semibold mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                    Book Type
                </label>
                <select x-model="filters.bookType"
                        @change="loadData()"
                        class="w-full px-4 py-2 rounded-lg border focus:outline-none focus:ring-2 focus:ring-[#0056b3]"
                        :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                    <option value="">All Types</option>
                    <option value="sahih">Sahih</option>
                    <option value="sunan">Sunan</option>
                    <option value="musnad">Musnad</option>
                    <option value="jami">Jami</option>
                    <option value="tafsir">Tafsir</option>
                    <option value="other">Other</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                    Sort By
                </label>
                <select x-model="filters.sortBy"
                        @change="loadData()"
                        class="w-full px-4 py-2 rounded-lg border focus:outline-none focus:ring-2 focus:ring-[#0056b3]"
                        :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                    <option value="name">Name</option>
                    <option value="chapters">Chapters Count</option>
                    <option value="hadith">Hadith Count</option>
                    <option value="created_at">Creation Date</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Bulk Actions -->
    <div class="glass-light rounded-lg p-4 mb-6" :class="darkMode ? 'glass-dark' : ''" x-show="selectedCount > 0">
        <div class="flex items-center justify-between">
            <span class="font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                <span x-text="selectedCount"></span> book(s) selected
            </span>
            <button @click="bulkDelete()"
                    class="bg-red-600 text-white px-6 py-2 rounded-lg font-semibold hover:bg-red-700 transition">
                <i data-lucide="trash-2" class="w-4 h-4 inline ml-2"></i>
                Delete Selected
            </button>
        </div>
    </div>

    <!-- Hadith Books Table -->
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
                        <th class="px-6 py-3 text-right text-sm font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">Book Name</th>
                        <th class="px-6 py-3 text-right text-sm font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">Type</th>
                        <th class="px-6 py-3 text-right text-sm font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">Author</th>
                        <th class="px-6 py-3 text-right text-sm font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">Chapters</th>
                        <th class="px-6 py-3 text-right text-sm font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">Hadith</th>
                        <th class="px-6 py-3 text-right text-sm font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">Status</th>
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
                                <i data-lucide="book-open" class="w-4 h-4 inline ml-2"></i>
                                <span x-text="item.name?.en || item.name?.ar || '—'"></span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 rounded-full text-xs font-semibold"
                                      :class="{
                                          'bg-green-100 text-green-800': item.book_type === 'sahih',
                                          'bg-blue-100 text-blue-800': item.book_type === 'sunan',
                                          'bg-teal-100 text-teal-800': item.book_type === 'musnad',
                                          'bg-purple-100 text-purple-800': item.book_type === 'jami',
                                          'bg-yellow-100 text-yellow-800': item.book_type === 'tafsir',
                                          'bg-gray-100 text-gray-800': item.book_type === 'other'
                                      }"
                                      x-text="item.book_type || '—'"></span>
                            </td>
                            <td class="px-6 py-4 text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'" x-text="item.author?.en || item.author?.ar || '—'"></td>
                            <td class="px-6 py-4 text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                                <span class="font-bold text-[#0056b3]" x-text="item.chapters_count ?? 0"></span>
                            </td>
                            <td class="px-6 py-4 text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                                <span class="font-bold text-[#64CCC5]" x-text="item.hadith_count ?? 0"></span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 rounded-full text-xs font-semibold"
                                      :class="{
                                          'bg-green-100 text-green-800': item.status === 'published',
                                          'bg-yellow-100 text-yellow-800': item.status === 'draft',
                                          'bg-gray-100 text-gray-800': item.status === 'archived'
                                      }"
                                      x-text="item.status || 'draft'"></span>
                            </td>
                            <td class="px-6 py-4">
                                <a :href="`/su/portal/v1/admin/hadith-books/${item.id}`"
                                   class="text-[#0056b3] hover:text-blue-700 font-semibold text-sm ml-3">
                                    <i data-lucide="eye" class="w-4 h-4 inline"></i>
                                    View
                                </a>
                                <a :href="`/su/portal/v1/admin/hadith-books/${item.id}/edit`"
                                   class="text-[#64CCC5] hover:text-teal-700 font-semibold text-sm ml-3">
                                    <i data-lucide="pencil" class="w-4 h-4 inline"></i>
                                    Edit
                                </a>
                                <button @click="deleteItem(item.id)"
                                        class="text-red-600 hover:text-red-700 font-semibold text-sm ml-3">
                                    <i data-lucide="trash-2" class="w-4 h-4 inline"></i>
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
                <i data-lucide="book-open" class="w-16 h-16 mx-auto mb-4 opacity-30"></i>
                <p :class="darkMode ? 'text-gray-400' : 'text-gray-600'" class="text-lg">
                    No hadith books found
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
                <i data-lucide="chevron-right" class="w-4 h-4 inline"></i>
                Previous
            </button>
            <button @click="nextPage()"
                    :disabled="currentPage >= totalPages"
                    :class="currentPage >= totalPages ? 'opacity-50 cursor-not-allowed' : ''"
                    class="px-4 py-2 bg-[#0056b3] text-white rounded-lg hover:bg-blue-700 disabled:bg-gray-400 transition">
                Next
                <i data-lucide="chevron-left" class="w-4 h-4 inline"></i>
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script>
function hadithBookManager() {
    return {
        items: [],
        search: '',
        filters: { bookType: '', sortBy: 'name' },
        selected: [],
        selectedCount: 0,
        currentPage: 1,
        perPage: 15,
        totalItems: 0,
        totalPages: 1,
        loading: false,
        stats: { totalBooks: 0, totalChapters: 0, totalHadith: 0, mostUsedBook: '—' },
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
                book_type: this.filters.bookType,
                sort_by: this.filters.sortBy
            });

            try {
                const response = await fetch(`/su/portal/v1/admin/hadith-books?${params}`, {
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
            if (!confirm('Are you sure you want to delete this hadith book? This will also delete all chapters and hadith in this book.')) return;

            try {
                const response = await fetch(`/su/portal/v1/admin/hadith-books/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                if (response.ok) {
                    this.loadData();
                    alert('Hadith book deleted successfully');
                } else {
                    alert('Failed to delete book. It may have related chapters or hadith.');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred while deleting the book');
            }
        },

        async bulkDelete() {
            if (!confirm(`Delete ${this.selected.length} hadith books? This will also delete all related chapters and hadith.`)) return;

            try {
                const response = await fetch(`/su/portal/v1/admin/hadith-books/bulk-delete`, {
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
                    alert('Hadith books deleted successfully');
                } else {
                    alert('Failed to delete some books');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred during bulk delete');
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
