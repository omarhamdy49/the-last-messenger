@extends('layouts.admin')

@section('title', 'سلة المهملات - المقالات')

@section('page-title', 'سلة المهملات')

@section('content')
<div x-data="articlesTrash()" x-init="init()" class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold transition-colors" :class="darkMode ? 'text-white' : 'text-gray-900'">
                <i data-lucide="trash-2" class="w-8 h-8 inline-block ml-2"></i>
                سلة المهملات - المقالات
            </h1>
            <p class="mt-1 transition-colors" :class="darkMode ? 'text-gray-300' : 'text-gray-600'">
                المقالات المحذوفة - يمكن استرجاعها أو حذفها نهائياً
            </p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.articles.index') }}"
               class="px-4 py-2 rounded-lg transition-all duration-300 flex items-center gap-2"
               :class="darkMode ? 'glass-dark hover:bg-white/10 text-white' : 'glass-light-soft hover:bg-gray-100 text-gray-700 border-0'">
                <i data-lucide="arrow-right" class="w-5 h-5"></i>
                <span>العودة للمقالات</span>
            </a>
            <button @click="emptyTrash()"
                    x-show="articles.length > 0"
                    class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors flex items-center gap-2 shadow-lg">
                <i data-lucide="trash-2" class="w-5 h-5"></i>
                <span>إفراغ السلة</span>
            </button>
        </div>
    </div>

    <!-- Stats Overview -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Total Deleted -->
        <div class="group rounded-xl p-6 relative overflow-hidden hover:scale-105 transition-all duration-300"
             :class="darkMode ? 'glass-dark' : 'glass-light-soft'">
            <div class="absolute top-0 right-0 w-24 h-24 bg-red-500/20 rounded-full -translate-y-1/2 translate-x-1/2 group-hover:scale-150 transition-transform duration-500"></div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-red-500 to-red-600 flex items-center justify-center">
                        <i data-lucide="trash-2" class="w-6 h-6 text-white"></i>
                    </div>
                    <span class="text-2xl font-black transition-colors"
                          :class="darkMode ? 'text-white' : 'text-gray-900'"
                          x-text="stats.total">0</span>
                </div>
                <p class="text-sm font-semibold transition-colors"
                   :class="darkMode ? 'text-gray-400' : 'text-gray-600'">إجمالي المحذوفات</p>
            </div>
        </div>

        <!-- Auto Archived -->
        <div class="group rounded-xl p-6 relative overflow-hidden hover:scale-105 transition-all duration-300"
             :class="darkMode ? 'glass-dark' : 'glass-light-soft'">
            <div class="absolute top-0 right-0 w-24 h-24 bg-blue-500/20 rounded-full -translate-y-1/2 translate-x-1/2 group-hover:scale-150 transition-transform duration-500"></div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center">
                        <i data-lucide="archive" class="w-6 h-6 text-white"></i>
                    </div>
                    <span class="text-2xl font-black transition-colors"
                          :class="darkMode ? 'text-white' : 'text-gray-900'"
                          x-text="stats.autoArchived">0</span>
                </div>
                <p class="text-sm font-semibold transition-colors"
                   :class="darkMode ? 'text-gray-400' : 'text-gray-600'">مؤرشف تلقائياً</p>
            </div>
        </div>

        <!-- Manually Deleted -->
        <div class="group rounded-xl p-6 relative overflow-hidden hover:scale-105 transition-all duration-300"
             :class="darkMode ? 'glass-dark' : 'glass-light-soft'">
            <div class="absolute top-0 right-0 w-24 h-24 bg-orange-500/20 rounded-full -translate-y-1/2 translate-x-1/2 group-hover:scale-150 transition-transform duration-500"></div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-orange-500 to-orange-600 flex items-center justify-center">
                        <i data-lucide="user-x" class="w-6 h-6 text-white"></i>
                    </div>
                    <span class="text-2xl font-black transition-colors"
                          :class="darkMode ? 'text-white' : 'text-gray-900'"
                          x-text="stats.manuallyDeleted">0</span>
                </div>
                <p class="text-sm font-semibold transition-colors"
                   :class="darkMode ? 'text-gray-400' : 'text-gray-600'">محذوف يدوياً</p>
            </div>
        </div>
    </div>

    <!-- Search and Filters -->
    <div class="rounded-xl shadow-sm p-6 space-y-4 transition-colors"
         :class="darkMode ? 'glass-dark' : 'glass-light-soft'">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Search -->
            <div class="md:col-span-2">
                <div class="relative">
                    <input
                        type="text"
                        x-model="search"
                        @input.debounce.500ms="loadArticles()"
                        placeholder="ابحث في المحذوفات..."
                        class="w-full px-4 py-2 pr-10 rounded-lg transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-blue-500/50"
                        :class="darkMode ? 'bg-white/10 border border-white/20 text-white placeholder-gray-400' : 'glass-light-strong text-gray-900 placeholder-gray-500 border-0'"
                    >
                    <i data-lucide="search" class="w-5 h-5 absolute left-3 top-1/2 transform -translate-y-1/2 transition-colors"
                       :class="darkMode ? 'text-gray-400' : 'text-gray-600'"></i>
                </div>
            </div>

            <!-- Deletion Type Filter -->
            <div>
                <select
                    x-model="filters.deletionType"
                    @change="loadArticles()"
                    class="w-full px-4 py-2 rounded-lg transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-blue-500/50"
                    :class="darkMode ? 'bg-white/10 border border-white/20 text-white' : 'glass-light-strong text-gray-900 border-0'"
                >
                    <option value="">نوع الحذف: الكل</option>
                    <option value="auto">تلقائي</option>
                    <option value="manual">يدوي</option>
                </select>
            </div>
        </div>

        <!-- Bulk Actions Bar (shows when items selected) -->
        <div x-show="selected.length > 0" x-transition
             class="flex items-center justify-between p-4 rounded-lg border transition-colors"
             :class="darkMode ? 'bg-blue-500/10 border-blue-500' : 'bg-blue-50 border-blue-500'">
            <div class="flex items-center gap-3">
                <span class="font-semibold text-brand-primary" x-text="`تم تحديد ${selected.length} من المقالات`"></span>
                <button @click="selected = []"
                        class="text-sm transition-colors hover:underline"
                        :class="darkMode ? 'text-gray-300 hover:text-white' : 'text-gray-600 hover:text-gray-900'">
                    إلغاء التحديد
                </button>
            </div>
            <div class="flex items-center gap-2">
                <button @click="bulkRestore()"
                        class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors flex items-center gap-2">
                    <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                    <span>استرجاع</span>
                </button>
                <button @click="bulkPermanentDelete()"
                        class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors flex items-center gap-2">
                    <i data-lucide="x-circle" class="w-4 h-4"></i>
                    <span>حذف نهائي</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Loading State -->
    <div x-show="loading" class="rounded-xl shadow-sm p-12 transition-colors"
         :class="darkMode ? 'glass-dark' : 'glass-light-soft'">
        <div class="flex flex-col items-center justify-center gap-4">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-brand-primary"></div>
            <p class="transition-colors" :class="darkMode ? 'text-gray-300' : 'text-gray-600'">جاري التحميل...</p>
        </div>
    </div>

    <!-- Empty State -->
    <div x-show="!loading && articles.length === 0" class="rounded-xl shadow-sm p-12 transition-colors"
         :class="darkMode ? 'glass-dark' : 'glass-light-soft'">
        <div class="flex flex-col items-center justify-center gap-4 text-center">
            <div class="w-20 h-20 rounded-full flex items-center justify-center transition-colors"
                 :class="darkMode ? 'bg-gray-800' : 'bg-gray-100'">
                <i data-lucide="check-circle" class="w-10 h-10 transition-colors"
                   :class="darkMode ? 'text-green-400' : 'text-green-500'"></i>
            </div>
            <div>
                <h3 class="text-xl font-bold mb-2 transition-colors"
                    :class="darkMode ? 'text-white' : 'text-gray-900'">سلة المهملات فارغة</h3>
                <p class="transition-colors"
                   :class="darkMode ? 'text-gray-400' : 'text-gray-600'">لا توجد مقالات محذوفة حالياً</p>
            </div>
        </div>
    </div>

    <!-- Data Table -->
    <div x-show="!loading && articles.length > 0" class="rounded-xl shadow-sm overflow-hidden transition-colors"
         :class="darkMode ? 'glass-dark' : 'glass-light-soft'">
        <div class="overflow-x-auto custom-scrollbar">
            <table class="w-full">
                <thead class="border-b transition-colors"
                       :class="darkMode ? 'bg-gray-800/50 border-gray-700' : 'bg-gray-50 border-gray-200'">
                    <tr>
                        <th class="px-6 py-4 text-right">
                            <input
                                type="checkbox"
                                @change="toggleAll()"
                                :checked="selected.length === articles.length && articles.length > 0"
                                class="rounded border-gray-300 text-brand-primary focus:ring-brand-primary"
                            >
                        </th>
                        <th class="px-6 py-4 text-right text-xs font-bold uppercase tracking-wider transition-colors"
                            :class="darkMode ? 'text-gray-300' : 'text-gray-700'">#</th>
                        <th class="px-6 py-4 text-right text-xs font-bold uppercase tracking-wider transition-colors"
                            :class="darkMode ? 'text-gray-300' : 'text-gray-700'">العنوان</th>
                        <th class="px-6 py-4 text-right text-xs font-bold uppercase tracking-wider transition-colors"
                            :class="darkMode ? 'text-gray-300' : 'text-gray-700'">التصنيف</th>
                        <th class="px-6 py-4 text-right text-xs font-bold uppercase tracking-wider transition-colors"
                            :class="darkMode ? 'text-gray-300' : 'text-gray-700'">الكاتب</th>
                        <th class="px-6 py-4 text-right text-xs font-bold uppercase tracking-wider transition-colors"
                            :class="darkMode ? 'text-gray-300' : 'text-gray-700'">تاريخ الحذف</th>
                        <th class="px-6 py-4 text-right text-xs font-bold uppercase tracking-wider transition-colors"
                            :class="darkMode ? 'text-gray-300' : 'text-gray-700'">سبب الحذف</th>
                        <th class="px-6 py-4 text-right text-xs font-bold uppercase tracking-wider transition-colors"
                            :class="darkMode ? 'text-gray-300' : 'text-gray-700'">الإجراءات</th>
                    </tr>
                </thead>
                <tbody class="divide-y transition-colors" :class="darkMode ? 'divide-gray-700' : 'divide-gray-200'">
                    <template x-for="(article, index) in articles" :key="article.id">
                        <tr class="transition-colors" :class="darkMode ? 'hover:bg-gray-800/50' : 'hover:bg-gray-50'">
                            <td class="px-6 py-4">
                                <input
                                    type="checkbox"
                                    :value="article.id"
                                    x-model="selected"
                                    class="rounded border-gray-300 text-brand-primary focus:ring-brand-primary"
                                >
                            </td>
                            <td class="px-6 py-4 text-sm transition-colors"
                                :class="darkMode ? 'text-gray-300' : 'text-gray-900'"
                                x-text="article.id"></td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div x-show="article.featured_image" class="w-12 h-12 rounded-lg overflow-hidden flex-shrink-0 opacity-50">
                                        <img :src="article.featured_image" :alt="article.title.ar" class="w-full h-full object-cover">
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="font-semibold transition-colors"
                                           :class="darkMode ? 'text-white' : 'text-gray-900'"
                                           x-text="article.title.ar || article.title.en || 'غير محدد'"></p>
                                        <p class="text-xs mt-1 transition-colors"
                                           :class="darkMode ? 'text-gray-400' : 'text-gray-500'"
                                           x-text="article.title.en || ''"></p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm transition-colors"
                                      :class="darkMode ? 'text-gray-300' : 'text-gray-700'"
                                      x-text="article.category?.name.ar || '-'"></span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <div class="w-8 h-8 rounded-full gradient-primary flex items-center justify-center flex-shrink-0">
                                        <span class="text-white font-bold text-xs" x-text="article.author?.name.substring(0, 2)"></span>
                                    </div>
                                    <span class="text-sm transition-colors"
                                          :class="darkMode ? 'text-gray-300' : 'text-gray-700'"
                                          x-text="article.author?.name || 'غير محدد'"></span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm">
                                    <p class="transition-colors"
                                       :class="darkMode ? 'text-gray-300' : 'text-gray-900'"
                                       x-text="formatDate(article.deleted_at)"></p>
                                    <p class="text-xs mt-1 transition-colors"
                                       :class="darkMode ? 'text-gray-500' : 'text-gray-500'"
                                       x-text="formatTime(article.deleted_at)"></p>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span
                                    class="px-3 py-1 text-xs font-semibold rounded-full"
                                    :class="article.deletion_reason === 'auto' ?
                                        'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400' :
                                        'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400'"
                                    x-text="article.deletion_reason === 'auto' ? 'أرشفة تلقائية' : 'حذف يدوي'"
                                ></span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <button @click="restoreArticle(article.id)"
                                            class="p-2 rounded-lg transition-all duration-300 text-green-600"
                                            :class="darkMode ? 'hover:bg-green-500/20' : 'hover:bg-green-50'"
                                            title="استرجاع">
                                        <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                                    </button>
                                    <button @click="permanentDeleteArticle(article.id)"
                                            class="p-2 rounded-lg transition-all duration-300 text-red-600"
                                            :class="darkMode ? 'hover:bg-red-500/20' : 'hover:bg-red-50'"
                                            title="حذف نهائي">
                                        <i data-lucide="x-circle" class="w-4 h-4"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div x-show="pagination.total > 0" class="px-6 py-4 border-t transition-colors"
             :class="darkMode ? 'border-gray-700 bg-gray-800/50' : 'border-gray-200 bg-gray-50'">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div class="text-sm transition-colors" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                    عرض
                    <span class="font-semibold" x-text="pagination.from"></span>
                    إلى
                    <span class="font-semibold" x-text="pagination.to"></span>
                    من
                    <span class="font-semibold" x-text="pagination.total"></span>
                    مقال محذوف
                </div>
                <div class="flex items-center gap-2">
                    <button
                        @click="changePage(pagination.current_page - 1)"
                        :disabled="pagination.current_page === 1"
                        :class="pagination.current_page === 1 ? 'opacity-50 cursor-not-allowed' : (darkMode ? 'hover:bg-gray-700' : 'hover:bg-gray-200')"
                        class="px-4 py-2 rounded-lg transition-all duration-300"
                        :class="darkMode ? 'glass-dark text-white border-gray-700' : 'glass-light text-gray-700 border-gray-300'"
                    >
                        السابق
                    </button>
                    <template x-for="page in visiblePages" :key="page">
                        <button
                            @click="changePage(page)"
                            :class="page === pagination.current_page ? 'gradient-primary text-white' : (darkMode ? 'glass-dark text-white hover:bg-gray-700' : 'glass-light text-gray-700 hover:bg-gray-100')"
                            class="px-4 py-2 rounded-lg transition-all duration-300 font-semibold"
                            x-text="page"
                        ></button>
                    </template>
                    <button
                        @click="changePage(pagination.current_page + 1)"
                        :disabled="pagination.current_page === pagination.last_page"
                        :class="pagination.current_page === pagination.last_page ? 'opacity-50 cursor-not-allowed' : (darkMode ? 'hover:bg-gray-700' : 'hover:bg-gray-200')"
                        class="px-4 py-2 rounded-lg transition-all duration-300"
                        :class="darkMode ? 'glass-dark text-white border-gray-700' : 'glass-light text-gray-700 border-gray-300'"
                    >
                        التالي
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Notification -->
    <div
        x-show="toast.show"
        x-transition
        class="fixed bottom-6 left-6 max-w-sm z-50"
    >
        <div
            class="px-6 py-4 rounded-lg shadow-lg flex items-center gap-3"
            :class="{
                'bg-green-500 text-white': toast.type === 'success',
                'bg-red-500 text-white': toast.type === 'error',
                'bg-yellow-500 text-white': toast.type === 'warning'
            }"
        >
            <i :data-lucide="toast.type === 'success' ? 'check-circle' : (toast.type === 'error' ? 'x-circle' : 'alert-circle')" class="w-5 h-5"></i>
            <span x-text="toast.message"></span>
        </div>
    </div>
</div>

<script>
function articlesTrash() {
    return {
        articles: [],
        loading: true,
        search: '',
        filters: {
            deletionType: ''
        },
        selected: [],
        pagination: {
            current_page: 1,
            last_page: 1,
            from: 0,
            to: 0,
            total: 0
        },
        stats: {
            total: 0,
            autoArchived: 0,
            manuallyDeleted: 0
        },
        toast: {
            show: false,
            type: 'success',
            message: ''
        },

        async init() {
            await this.loadArticles();
            lucide.createIcons();
        },

        async loadArticles(page = 1) {
            this.loading = true;
            try {
                const params = new URLSearchParams({
                    page: page,
                    per_page: 20,
                    search: this.search,
                    ...this.filters
                });

                const response = await fetch(`{{ route('admin.articles.index') }}/trash?${params}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                const data = await response.json();

                if (data.success) {
                    this.articles = data.data;
                    this.pagination = {
                        current_page: data.meta.current_page,
                        last_page: data.meta.last_page,
                        from: data.meta.from,
                        to: data.meta.to,
                        total: data.meta.total
                    };
                    this.stats = data.stats || this.stats;

                    // Reinitialize icons after DOM update
                    this.$nextTick(() => lucide.createIcons());
                } else {
                    this.showToast('error', 'فشل تحميل البيانات');
                }
            } catch (error) {
                console.error('Error loading trash:', error);
                this.showToast('error', 'حدث خطأ في تحميل البيانات');
            } finally {
                this.loading = false;
            }
        },

        async restoreArticle(id) {
            if (!confirm('هل أنت متأكد من استرجاع هذا المقال؟')) {
                return;
            }

            try {
                const response = await fetch(`{{ route('admin.articles.index') }}/${id}/restore`, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                const data = await response.json();

                if (data.success) {
                    this.showToast('success', 'تم استرجاع المقال بنجاح');
                    await this.loadArticles(this.pagination.current_page);
                } else {
                    this.showToast('error', data.message || 'فشل استرجاع المقال');
                }
            } catch (error) {
                console.error('Error restoring article:', error);
                this.showToast('error', 'حدث خطأ في استرجاع المقال');
            }
        },

        async permanentDeleteArticle(id) {
            if (!confirm('هل أنت متأكد من الحذف النهائي لهذا المقال؟ لا يمكن التراجع عن هذا الإجراء!')) {
                return;
            }

            try {
                const response = await fetch(`{{ route('admin.articles.index') }}/${id}/force-delete`, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                const data = await response.json();

                if (data.success) {
                    this.showToast('success', 'تم حذف المقال نهائياً');
                    await this.loadArticles(this.pagination.current_page);
                } else {
                    this.showToast('error', data.message || 'فشل الحذف النهائي');
                }
            } catch (error) {
                console.error('Error permanently deleting article:', error);
                this.showToast('error', 'حدث خطأ في الحذف النهائي');
            }
        },

        async bulkRestore() {
            if (this.selected.length === 0) return;

            if (!confirm(`هل أنت متأكد من استرجاع ${this.selected.length} من المقالات؟`)) {
                return;
            }

            try {
                const response = await fetch(`{{ route('admin.articles.index') }}/bulk-restore`, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ ids: this.selected })
                });

                const data = await response.json();

                if (data.success) {
                    this.showToast('success', data.message || 'تم استرجاع المقالات بنجاح');
                    this.selected = [];
                    await this.loadArticles(this.pagination.current_page);
                } else {
                    this.showToast('error', data.message || 'فشل استرجاع المقالات');
                }
            } catch (error) {
                console.error('Error bulk restoring:', error);
                this.showToast('error', 'حدث خطأ في استرجاع المقالات');
            }
        },

        async bulkPermanentDelete() {
            if (this.selected.length === 0) return;

            if (!confirm(`هل أنت متأكد من الحذف النهائي لـ ${this.selected.length} من المقالات؟ لا يمكن التراجع عن هذا الإجراء!`)) {
                return;
            }

            try {
                const response = await fetch(`{{ route('admin.articles.index') }}/bulk-force-delete`, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ ids: this.selected })
                });

                const data = await response.json();

                if (data.success) {
                    this.showToast('success', data.message || 'تم الحذف النهائي للمقالات');
                    this.selected = [];
                    await this.loadArticles(this.pagination.current_page);
                } else {
                    this.showToast('error', data.message || 'فشل الحذف النهائي');
                }
            } catch (error) {
                console.error('Error bulk force deleting:', error);
                this.showToast('error', 'حدث خطأ في الحذف النهائي');
            }
        },

        async emptyTrash() {
            if (this.articles.length === 0) return;

            if (!confirm('هل أنت متأكد من إفراغ سلة المهملات؟ سيتم حذف جميع المقالات نهائياً ولا يمكن التراجع!')) {
                return;
            }

            try {
                const response = await fetch(`{{ route('admin.articles.index') }}/empty-trash`, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                const data = await response.json();

                if (data.success) {
                    this.showToast('success', 'تم إفراغ سلة المهملات بنجاح');
                    await this.loadArticles();
                } else {
                    this.showToast('error', data.message || 'فشل إفراغ سلة المهملات');
                }
            } catch (error) {
                console.error('Error emptying trash:', error);
                this.showToast('error', 'حدث خطأ في إفراغ سلة المهملات');
            }
        },

        toggleAll() {
            if (this.selected.length === this.articles.length) {
                this.selected = [];
            } else {
                this.selected = this.articles.map(a => a.id);
            }
        },

        changePage(page) {
            if (page >= 1 && page <= this.pagination.last_page) {
                this.loadArticles(page);
            }
        },

        get visiblePages() {
            const pages = [];
            const current = this.pagination.current_page;
            const last = this.pagination.last_page;
            const delta = 2;

            for (let i = Math.max(1, current - delta); i <= Math.min(last, current + delta); i++) {
                pages.push(i);
            }

            return pages;
        },

        formatDate(dateString) {
            if (!dateString) return '-';
            const date = new Date(dateString);
            return date.toLocaleDateString('ar-EG', { year: 'numeric', month: 'long', day: 'numeric' });
        },

        formatTime(dateString) {
            if (!dateString) return '';
            const date = new Date(dateString);
            return date.toLocaleTimeString('ar-EG', { hour: '2-digit', minute: '2-digit' });
        },

        showToast(type, message) {
            this.toast = { show: true, type, message };
            this.$nextTick(() => lucide.createIcons());
            setTimeout(() => {
                this.toast.show = false;
            }, 5000);
        }
    }
}
</script>
@endsection
