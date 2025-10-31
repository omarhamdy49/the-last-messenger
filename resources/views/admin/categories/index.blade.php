@extends('layouts.admin')

@section('title', 'Categories')

@section('content')
<div class="container mx-auto px-4 py-8" x-data="categoryManager()">
    <!-- Header -->
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">
                الفئات
            </h1>
            <p class="mt-2" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                إدارة جميع الفئات في النظام
            </p>
        </div>
        <a href="{{ route('admin.categories.create') }}"
           class="bg-gradient-to-r from-[#0056b3] to-[#218838] text-white px-6 py-3 rounded-lg font-semibold hover:shadow-lg transition-all">
            + إنشاء فئة جديدة
        </a>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="glass-light rounded-lg p-6" :class="darkMode ? 'glass-dark' : ''">
            <p class="text-sm font-semibold" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">إجمالي الفئات</p>
            <p class="text-4xl font-bold mt-2 text-[#0056b3]" x-text="stats.total ?? 0"></p>
        </div>
        <div class="glass-light rounded-lg p-6" :class="darkMode ? 'glass-dark' : ''">
            <p class="text-sm font-semibold" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">مقالات</p>
            <p class="text-4xl font-bold mt-2 text-blue-600" x-text="stats.blog ?? 0"></p>
        </div>
        <div class="glass-light rounded-lg p-6" :class="darkMode ? 'glass-dark' : ''">
            <p class="text-sm font-semibold" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">وسائط</p>
            <p class="text-4xl font-bold mt-2 text-green-600" x-text="stats.media ?? 0"></p>
        </div>
        <div class="glass-light rounded-lg p-6" :class="darkMode ? 'glass-dark' : ''">
            <p class="text-sm font-semibold" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">الخط الزمني</p>
            <p class="text-4xl font-bold mt-2 text-teal-600" x-text="stats.timeline ?? 0"></p>
        </div>
    </div>

    <!-- Search & Filters -->
    <div class="glass-light rounded-lg p-6 mb-6" :class="darkMode ? 'glass-dark' : ''">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-semibold mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                    البحث
                </label>
                <input type="text"
                       x-model="search"
                       placeholder="ابحث في الفئات..."
                       @input="debounce(() => loadData(), 500)"
                       class="w-full px-4 py-2 rounded-lg border focus:outline-none focus:ring-2 focus:ring-[#0056b3]"
                       :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
            </div>
            <div>
                <label class="block text-sm font-semibold mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                    النوع
                </label>
                <select x-model="filters.type"
                        @change="loadData()"
                        class="w-full px-4 py-2 rounded-lg border focus:outline-none focus:ring-2 focus:ring-[#0056b3]"
                        :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                    <option value="">جميع الأنواع</option>
                    <option value="blog">مقالات</option>
                    <option value="media">وسائط</option>
                    <option value="timeline">خط زمني</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                    الفئة الأب
                </label>
                <select x-model="filters.parent_id"
                        @change="loadData()"
                        class="w-full px-4 py-2 rounded-lg border focus:outline-none focus:ring-2 focus:ring-[#0056b3]"
                        :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                    <option value="">جميع الفئات</option>
                    <option value="root">الفئات الرئيسية فقط</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                    &nbsp;
                </label>
                <button @click="loadData()"
                        class="w-full bg-[#0056b3] text-white px-4 py-2 rounded-lg font-semibold hover:bg-blue-700 transition">
                    بحث
                </button>
            </div>
        </div>
    </div>

    <!-- Bulk Actions -->
    <div class="glass-light rounded-lg p-4 mb-6" :class="darkMode ? 'glass-dark' : ''" x-show="selectedCount > 0">
        <div class="flex items-center justify-between">
            <span class="font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                تم تحديد <span x-text="selectedCount"></span> عنصر
            </span>
            <button @click="bulkDelete()"
                    class="bg-red-600 text-white px-6 py-2 rounded-lg font-semibold hover:bg-red-700 transition">
                حذف المحدد
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
                        <th class="px-6 py-3 text-right text-sm font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">الاسم</th>
                        <th class="px-6 py-3 text-right text-sm font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">النوع</th>
                        <th class="px-6 py-3 text-right text-sm font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">الفئات الفرعية</th>
                        <th class="px-6 py-3 text-right text-sm font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">المقالات</th>
                        <th class="px-6 py-3 text-right text-sm font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">الترتيب</th>
                        <th class="px-6 py-3 text-right text-sm font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">الإجراءات</th>
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
                                <span x-text="item.name?.ar || item.name?.en || '—'"></span>
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
                                    تحرير
                                </a>
                                <a :href="`/su/portal/v1/admin/categories/${item.id}`"
                                   class="text-[#64CCC5] hover:text-teal-700 font-semibold text-sm ml-3">
                                    عرض
                                </a>
                                <button @click="deleteItem(item.id)"
                                        class="text-red-600 hover:text-red-700 font-semibold text-sm ml-3">
                                    حذف
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
                    لم يتم العثور على فئات
                </p>
            </div>
        </template>
    </div>

    <!-- Pagination -->
    <div class="mt-6 flex justify-between items-center">
        <p :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
            عرض <span x-text="(currentPage - 1) * perPage + 1"></span>
            إلى <span x-text="Math.min(currentPage * perPage, totalItems)"></span>
            من <span x-text="totalItems"></span> عنصر
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
            if (!confirm('هل أنت متأكد من رغبتك في حذف هذه الفئة؟')) return;

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
                    alert('تم حذف الفئة بنجاح');
                }
            } catch (error) {
                console.error('Error:', error);
            }
        },

        async bulkDelete() {
            if (!confirm(`حذف ${this.selected.length} فئة؟`)) return;

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
                    alert('تم حذف الفئات بنجاح');
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
