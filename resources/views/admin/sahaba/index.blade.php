@extends('layouts.admin')

@section('title', 'إدارة الصحابة')

@section('content')
<div x-data="sahabaTable()" x-init="init()" class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">إدارة الصحابة</h1>
            <p class="text-gray-600 mt-1">عرض وإدارة صحابة رسول الله ﷺ</p>
        </div>
        <div class="flex items-center gap-3">
            <button @click="loadSahaba()" class="px-4 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors flex items-center gap-2">
                <i data-lucide="refresh-cw" class="w-4 h-4"></i>
                <span>تحديث</span>
            </button>
            <a href="/su/portal/v1/admin/sahaba/create" class="px-6 py-2 gradient-primary text-white rounded-lg hover:opacity-90 transition-opacity flex items-center gap-2 shadow-lg">
                <i data-lucide="plus" class="w-5 h-5"></i>
                <span>إضافة صحابي جديد</span>
            </a>
        </div>
    </div>

    <!-- Search and Filters -->
    <div class="bg-white rounded-xl shadow-sm p-6 space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <!-- Search -->
            <div class="md:col-span-2">
                <div class="relative">
                    <input
                        type="text"
                        x-model="search"
                        @input.debounce.500ms="loadSahaba()"
                        placeholder="ابحث بالاسم، القبيلة..."
                        class="w-full px-4 py-2 pr-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-primary focus:border-transparent"
                    >
                    <i data-lucide="search" class="w-5 h-5 text-gray-400 absolute left-3 top-1/2 transform -translate-y-1/2"></i>
                </div>
            </div>

            <!-- Gender Filter -->
            <div>
                <select
                    x-model="filters.gender"
                    @change="loadSahaba()"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-primary focus:border-transparent"
                >
                    <option value="">الجنس: الكل</option>
                    <option value="male">ذكر</option>
                    <option value="female">أنثى</option>
                </select>
            </div>

            <!-- Status Filter -->
            <div>
                <select
                    x-model="filters.status"
                    @change="loadSahaba()"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-primary focus:border-transparent"
                >
                    <option value="">الحالة: الكل</option>
                    <option value="sahabi">صحابي</option>
                    <option value="tabii">تابعي</option>
                    <option value="tabi_tabiin">تابع التابعين</option>
                </select>
            </div>
        </div>

        <!-- Bulk Actions Bar (shows when items selected) -->
        <div x-show="selected.length > 0" x-transition class="flex items-center justify-between p-4 bg-brand-primary bg-opacity-10 border border-brand-primary rounded-lg">
            <div class="flex items-center gap-3">
                <span class="font-semibold text-brand-primary" x-text="`تم تحديد ${selected.length} من الصحابة`"></span>
                <button @click="selected = []" class="text-sm text-gray-600 hover:text-gray-900">إلغاء التحديد</button>
            </div>
            <div class="flex items-center gap-2">
                <button @click="bulkDelete()" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors flex items-center gap-2">
                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                    <span>حذف المحدد</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Loading State -->
    <div x-show="loading" class="bg-white rounded-xl shadow-sm p-12">
        <div class="flex flex-col items-center justify-center gap-4">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-brand-primary"></div>
            <p class="text-gray-600">جاري التحميل...</p>
        </div>
    </div>

    <!-- Empty State -->
    <div x-show="!loading && sahaba.length === 0" class="bg-white rounded-xl shadow-sm p-12">
        <div class="flex flex-col items-center justify-center gap-4 text-center">
            <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center">
                <i data-lucide="users" class="w-10 h-10 text-gray-400"></i>
            </div>
            <div>
                <h3 class="text-xl font-bold text-gray-900 mb-2">لا توجد صحابة</h3>
                <p class="text-gray-600">لم يتم العثور على صحابة. جرب تغيير معايير البحث.</p>
            </div>
        </div>
    </div>

    <!-- Data Table -->
    <div x-show="!loading && sahaba.length > 0" class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-4 text-right">
                            <input
                                type="checkbox"
                                @change="toggleAll()"
                                :checked="selected.length === sahaba.length && sahaba.length > 0"
                                class="rounded border-gray-300 text-brand-primary focus:ring-brand-primary"
                            >
                        </th>
                        <th class="px-6 py-4 text-right text-xs font-bold text-gray-700 uppercase tracking-wider">#</th>
                        <th class="px-6 py-4 text-right text-xs font-bold text-gray-700 uppercase tracking-wider">الاسم</th>
                        <th class="px-6 py-4 text-right text-xs font-bold text-gray-700 uppercase tracking-wider">الكنية</th>
                        <th class="px-6 py-4 text-right text-xs font-bold text-gray-700 uppercase tracking-wider">القبيلة</th>
                        <th class="px-6 py-4 text-right text-xs font-bold text-gray-700 uppercase tracking-wider">الحالة</th>
                        <th class="px-6 py-4 text-right text-xs font-bold text-gray-700 uppercase tracking-wider">عدد الأحاديث</th>
                        <th class="px-6 py-4 text-right text-xs font-bold text-gray-700 uppercase tracking-wider">المشاهدات</th>
                        <th class="px-6 py-4 text-right text-xs font-bold text-gray-700 uppercase tracking-wider">الإجراءات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <template x-for="(sahabi, index) in sahaba" :key="sahabi.id">
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4">
                                <input
                                    type="checkbox"
                                    :value="sahabi.id"
                                    x-model="selected"
                                    class="rounded border-gray-300 text-brand-primary focus:ring-brand-primary"
                                >
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900" x-text="sahabi.id"></td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div x-show="sahabi.profile_image" class="w-10 h-10 bg-gray-200 rounded-full overflow-hidden flex-shrink-0">
                                        <img :src="sahabi.profile_image" :alt="sahabi.name.ar" class="w-full h-full object-cover">
                                    </div>
                                    <div x-show="!sahabi.profile_image" class="w-10 h-10 bg-gradient-to-br from-brand-primary to-brand-secondary rounded-full flex items-center justify-center flex-shrink-0">
                                        <span class="text-white font-bold text-sm" x-text="sahabi.name.ar ? sahabi.name.ar.charAt(0) : ''"></span>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-gray-900" x-text="sahabi.name.ar || sahabi.name.en || 'غير محدد'"></p>
                                        <p class="text-xs text-gray-500" x-text="sahabi.name.en || ''"></p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm text-gray-700" x-text="sahabi.kunya?.ar || '-'"></span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm text-gray-700" x-text="sahabi.tribe || '-'"></span>
                            </td>
                            <td class="px-6 py-4">
                                <span
                                    class="px-3 py-1 text-xs font-semibold rounded-full"
                                    :class="{
                                        'bg-emerald-100 text-emerald-800': sahabi.status === 'sahabi',
                                        'bg-blue-100 text-blue-800': sahabi.status === 'tabii',
                                        'bg-purple-100 text-purple-800': sahabi.status === 'tabi_tabiin'
                                    }"
                                    x-text="getStatusLabel(sahabi.status)"
                                ></span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <i data-lucide="book-open" class="w-4 h-4 text-gray-400"></i>
                                    <span class="text-sm font-semibold text-gray-900" x-text="sahabi.hadith_count || 0"></span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <i data-lucide="eye" class="w-4 h-4 text-gray-400"></i>
                                    <span class="text-sm text-gray-700" x-text="sahabi.views_count || 0"></span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <a :href="`/su/portal/v1/admin/sahaba/${sahabi.id}`" class="p-2 text-brand-primary hover:bg-brand-primary hover:bg-opacity-10 rounded-lg transition-colors" title="عرض">
                                        <i data-lucide="eye" class="w-4 h-4"></i>
                                    </a>
                                    <a :href="`/su/portal/v1/admin/sahaba/${sahabi.id}/edit`" class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" title="تعديل">
                                        <i data-lucide="edit" class="w-4 h-4"></i>
                                    </a>
                                    <button @click="deleteSahabi(sahabi.id)" class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="حذف">
                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div x-show="pagination.total > 0" class="px-6 py-4 border-t border-gray-200 bg-gray-50">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div class="text-sm text-gray-700">
                    عرض
                    <span class="font-semibold" x-text="pagination.from"></span>
                    إلى
                    <span class="font-semibold" x-text="pagination.to"></span>
                    من
                    <span class="font-semibold" x-text="pagination.total"></span>
                    صحابي
                </div>
                <div class="flex items-center gap-2">
                    <button
                        @click="changePage(pagination.current_page - 1)"
                        :disabled="pagination.current_page === 1"
                        :class="pagination.current_page === 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-200'"
                        class="px-4 py-2 bg-white border border-gray-300 rounded-lg transition-colors"
                    >
                        السابق
                    </button>
                    <template x-for="page in visiblePages" :key="page">
                        <button
                            @click="changePage(page)"
                            :class="page === pagination.current_page ? 'gradient-primary text-white' : 'bg-white text-gray-700 hover:bg-gray-100'"
                            class="px-4 py-2 border border-gray-300 rounded-lg transition-colors font-semibold"
                            x-text="page"
                        ></button>
                    </template>
                    <button
                        @click="changePage(pagination.current_page + 1)"
                        :disabled="pagination.current_page === pagination.last_page"
                        :class="pagination.current_page === pagination.last_page ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-200'"
                        class="px-4 py-2 bg-white border border-gray-300 rounded-lg transition-colors"
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
function sahabaTable() {
    return {
        sahaba: [],
        loading: true,
        search: '',
        filters: {
            gender: '',
            status: ''
        },
        selected: [],
        pagination: {
            current_page: 1,
            last_page: 1,
            from: 0,
            to: 0,
            total: 0
        },
        toast: {
            show: false,
            type: 'success',
            message: ''
        },

        async init() {
            await this.loadSahaba();
            lucide.createIcons();
        },

        async loadSahaba(page = 1) {
            this.loading = true;
            try {
                const params = new URLSearchParams({
                    page: page,
                    per_page: 15,
                    search: this.search,
                    ...this.filters
                });

                const response = await fetch(`/su/portal/v1/admin/sahaba?${params}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                const data = await response.json();

                if (data.success) {
                    this.sahaba = data.data;
                    this.pagination = {
                        current_page: data.meta.current_page,
                        last_page: data.meta.last_page,
                        from: data.meta.from,
                        to: data.meta.to,
                        total: data.meta.total
                    };

                    // Reinitialize icons after DOM update
                    this.$nextTick(() => lucide.createIcons());
                } else {
                    this.showToast('error', 'فشل تحميل البيانات');
                }
            } catch (error) {
                console.error('Error loading sahaba:', error);
                this.showToast('error', 'حدث خطأ في تحميل البيانات');
            } finally {
                this.loading = false;
            }
        },

        async deleteSahabi(id) {
            if (!confirm('هل أنت متأكد من حذف هذا الصحابي؟ لا يمكن التراجع عن هذا الإجراء.')) {
                return;
            }

            try {
                const response = await fetch(`/su/portal/v1/admin/sahaba/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                const data = await response.json();

                if (data.success) {
                    this.showToast('success', 'تم حذف الصحابي بنجاح');
                    await this.loadSahaba(this.pagination.current_page);
                } else {
                    this.showToast('error', data.message || 'فشل حذف الصحابي');
                }
            } catch (error) {
                console.error('Error deleting sahabi:', error);
                this.showToast('error', 'حدث خطأ في حذف الصحابي');
            }
        },

        async bulkDelete() {
            if (this.selected.length === 0) return;

            if (!confirm(`هل أنت متأكد من حذف ${this.selected.length} من الصحابة؟ لا يمكن التراجع عن هذا الإجراء.`)) {
                return;
            }

            try {
                const response = await fetch('/su/portal/v1/admin/sahaba/bulk-delete', {
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
                    this.showToast('success', data.message || 'تم حذف الصحابة بنجاح');
                    this.selected = [];
                    await this.loadSahaba(this.pagination.current_page);
                } else {
                    this.showToast('error', data.message || 'فشل حذف الصحابة');
                }
            } catch (error) {
                console.error('Error bulk deleting sahaba:', error);
                this.showToast('error', 'حدث خطأ في حذف الصحابة');
            }
        },

        toggleAll() {
            if (this.selected.length === this.sahaba.length) {
                this.selected = [];
            } else {
                this.selected = this.sahaba.map(s => s.id);
            }
        },

        changePage(page) {
            if (page >= 1 && page <= this.pagination.last_page) {
                this.loadSahaba(page);
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

        getStatusLabel(status) {
            const labels = {
                'sahabi': 'صحابي',
                'tabii': 'تابعي',
                'tabi_tabiin': 'تابع التابعين'
            };
            return labels[status] || status;
        },

        showToast(type, message) {
            this.toast = { show: true, type, message };
            setTimeout(() => {
                this.toast.show = false;
            }, 5000);
        }
    }
}
</script>
@endsection
