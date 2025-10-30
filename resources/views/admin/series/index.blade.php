@extends('layouts.admin')

@section('title', 'السلاسل')

@section('content')
<div class="container mx-auto px-4 py-8" x-data="seriesManager()">
    <!-- Header -->
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">
                السلاسل
            </h1>
            <p class="mt-2" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                إدارة سلاسل المقالات المجمعة
            </p>
        </div>
        <a href="{{ route('admin.series.create') }}"
           class="bg-gradient-to-r from-[#0056b3] to-[#218838] text-white px-6 py-3 rounded-lg font-semibold hover:shadow-lg transition-all">
            + إنشاء سلسلة جديدة
        </a>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="glass rounded-lg p-6" :class="darkMode ? 'bg-gray-900/50' : 'bg-white/80'">
            <p class="text-sm font-semibold" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">إجمالي السلاسل</p>
            <p class="text-4xl font-bold mt-2 text-[#0056b3]" x-text="stats.total ?? 0"></p>
        </div>
        <div class="glass rounded-lg p-6" :class="darkMode ? 'bg-gray-900/50' : 'bg-white/80'">
            <p class="text-sm font-semibold" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">إجمالي المقالات</p>
            <p class="text-4xl font-bold mt-2 text-[#218838]" x-text="stats.totalArticles ?? 0"></p>
        </div>
        <div class="glass rounded-lg p-6" :class="darkMode ? 'bg-gray-900/50' : 'bg-white/80'">
            <p class="text-sm font-semibold" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">أكثر سلسلة</p>
            <p class="text-4xl font-bold mt-2 text-[#64CCC5]" x-text="stats.maxArticles ?? 0"></p>
        </div>
    </div>

    <!-- Search & Filters -->
    <div class="glass rounded-lg p-6 mb-6" :class="darkMode ? 'bg-gray-900/50' : 'bg-white/80'">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="md:col-span-2">
                <label class="block text-sm font-semibold mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                    البحث
                </label>
                <input type="text"
                       x-model="search"
                       @input="debounce(() => loadData(), 500)"
                       placeholder="ابحث في السلاسل..."
                       class="w-full px-4 py-2 rounded-lg border focus:outline-none focus:ring-2 focus:ring-[#0056b3]"
                       :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
            </div>
            <div>
                <label class="block text-sm font-semibold mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                    ترتيب حسب
                </label>
                <select x-model="sortBy"
                        @change="loadData()"
                        class="w-full px-4 py-2 rounded-lg border focus:outline-none focus:ring-2 focus:ring-[#0056b3]"
                        :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                    <option value="title">العنوان</option>
                    <option value="article_count">عدد المقالات</option>
                    <option value="created_at">تاريخ الإنشاء</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Bulk Actions -->
    <div class="glass rounded-lg p-4 mb-6" :class="darkMode ? 'bg-gray-900/50' : 'bg-white/80'" x-show="selectedCount > 0">
        <div class="flex items-center justify-between">
            <span class="font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                <span x-text="selectedCount"></span> سلسلة محددة
            </span>
            <button @click="bulkDelete()"
                    class="bg-red-600 text-white px-6 py-2 rounded-lg font-semibold hover:bg-red-700 transition">
                حذف المحدد
            </button>
        </div>
    </div>

    <!-- Series Table -->
    <div class="glass rounded-lg overflow-hidden" :class="darkMode ? 'bg-gray-900/50' : 'bg-white/80'">
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
                        <th class="px-6 py-3 text-right text-sm font-semibold">الصورة</th>
                        <th class="px-6 py-3 text-right text-sm font-semibold">العنوان</th>
                        <th class="px-6 py-3 text-right text-sm font-semibold">عدد المقالات</th>
                        <th class="px-6 py-3 text-right text-sm font-semibold">تاريخ الإنشاء</th>
                        <th class="px-6 py-3 text-right text-sm font-semibold">الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="item in items" :key="item.id">
                        <tr :class="darkMode ? 'border-gray-700 hover:bg-gray-800' : 'border-gray-200 hover:bg-gray-50'" class="border-t transition">
                            <td class="px-6 py-4">
                                <input type="checkbox" :value="item.id" x-model="selected" @change="updateSelectedCount()" class="rounded">
                            </td>
                            <td class="px-6 py-4">
                                <img :src="item.thumbnail || '/placeholder.jpg'" :alt="item.title?.ar || item.title?.en" class="w-16 h-16 object-cover rounded-lg">
                            </td>
                            <td class="px-6 py-4 font-semibold" :class="darkMode ? 'text-white' : 'text-gray-900'" x-text="item.title?.en || item.title?.ar || '—'"></td>
                            <td class="px-6 py-4">
                                <span class="px-3 py-1 rounded-full text-sm font-semibold bg-blue-100 text-blue-800" x-text="item.articles_count || 0"></span>
                            </td>
                            <td class="px-6 py-4 text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'" x-text="new Date(item.created_at).toLocaleDateString('ar-EG')"></td>
                            <td class="px-6 py-4">
                                <div class="flex items-center space-x-2 space-x-reverse">
                                    <a :href="`/su/portal/v1/admin/series/${item.id}`"
                                       class="text-[#0056b3] hover:text-blue-700 font-semibold text-sm"
                                       title="عرض">
                                        <i data-lucide="eye" class="w-4 h-4"></i>
                                    </a>
                                    <a :href="`/su/portal/v1/admin/series/${item.id}/edit`"
                                       class="text-[#64CCC5] hover:text-teal-700 font-semibold text-sm"
                                       title="تعديل">
                                        <i data-lucide="edit" class="w-4 h-4"></i>
                                    </a>
                                    <button @click="deleteItem(item.id)"
                                            class="text-red-600 hover:text-red-700 font-semibold text-sm"
                                            title="حذف">
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
                <p :class="darkMode ? 'text-gray-400' : 'text-gray-600'" class="text-lg">
                    لا توجد سلاسل
                </p>
            </div>
        </template>
    </div>

    <!-- Pagination -->
    <div class="mt-6 flex justify-between items-center">
        <p :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
            عرض <span x-text="(currentPage - 1) * perPage + 1"></span>
            إلى <span x-text="Math.min(currentPage * perPage, totalItems)"></span>
            من <span x-text="totalItems"></span> سلسلة
        </p>
        <div class="flex gap-2">
            <button @click="previousPage()"
                    :disabled="currentPage === 1"
                    :class="currentPage === 1 ? 'opacity-50 cursor-not-allowed' : ''"
                    class="px-4 py-2 bg-[#0056b3] text-white rounded-lg hover:bg-blue-700 disabled:bg-gray-400 transition">
                السابق
            </button>
            <button @click="nextPage()"
                    :disabled="currentPage >= totalPages"
                    :class="currentPage >= totalPages ? 'opacity-50 cursor-not-allowed' : ''"
                    class="px-4 py-2 bg-[#0056b3] text-white rounded-lg hover:bg-blue-700 disabled:bg-gray-400 transition">
                التالي
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script>
function seriesManager() {
    return {
        items: [],
        search: '',
        sortBy: 'created_at',
        selected: [],
        selectedCount: 0,
        currentPage: 1,
        perPage: 50,
        totalItems: 0,
        totalPages: 1,
        loading: false,
        stats: { total: 0, totalArticles: 0, maxArticles: 0 },

        init() {
            this.loadData();
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
                const response = await fetch(`/su/portal/v1/admin/series?${params}`, {
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
            if (!confirm('هل أنت متأكد من حذف هذه السلسلة؟')) return;

            try {
                const response = await fetch(`/su/portal/v1/admin/series/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                if (response.ok) {
                    this.loadData();
                    window.showToast?.('تم الحذف بنجاح', 'success');
                }
            } catch (error) {
                console.error('Error:', error);
                window.showToast?.('حدث خطأ أثناء الحذف', 'error');
            }
        },

        async bulkDelete() {
            if (!confirm(`حذف ${this.selected.length} سلسلة؟`)) return;

            try {
                const response = await fetch(`/su/portal/v1/admin/series/bulk-delete`, {
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
                    this.loadData();
                    window.showToast?.('تم الحذف بنجاح', 'success');
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
