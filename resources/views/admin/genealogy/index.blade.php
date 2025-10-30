@extends('admin.layouts.app')

@section('title', 'إدارة النسب')

@section('content')
<div x-data="peopleTable()">
    <!-- Page Header -->
    <div class="mb-8">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold mb-2 transition-colors duration-300"
                    :class="darkMode ? 'text-white' : 'text-gray-900'">
                    إدارة النسب
                </h1>
                <p class="transition-colors duration-300"
                   :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                    إدارة الأنساب والعائلات في شجرة النسب النبوية
                </p>
            </div>
            <a href="/su/portal/v1/admin/genealogy/create"
               class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-[#0056b3] to-[#218838] text-white rounded-xl hover:shadow-lg transition-all duration-300 hover:-translate-y-0.5 group">
                <i data-lucide="plus" class="w-5 h-5 ml-2 group-hover:rotate-90 transition-transform duration-300"></i>
                إضافة شخص جديد
            </a>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <!-- Total People -->
        <div class="rounded-2xl p-6 transition-all duration-300 hover:shadow-xl"
             :class="darkMode ? 'glass-dark' : 'bg-white shadow-soft'">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm mb-1 transition-colors duration-300"
                       :class="darkMode ? 'text-gray-400' : 'text-gray-600'">إجمالي الأفراد</p>
                    <p class="text-3xl font-bold transition-colors duration-300"
                       :class="darkMode ? 'text-white' : 'text-gray-900'"
                       x-text="pagination.total || 0"></p>
                </div>
                <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center shadow-lg">
                    <i data-lucide="users" class="w-7 h-7 text-white"></i>
                </div>
            </div>
        </div>

        <!-- Prophets -->
        <div class="rounded-2xl p-6 transition-all duration-300 hover:shadow-xl"
             :class="darkMode ? 'glass-dark' : 'bg-white shadow-soft'">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm mb-1 transition-colors duration-300"
                       :class="darkMode ? 'text-gray-400' : 'text-gray-600'">الأنبياء</p>
                    <p class="text-3xl font-bold transition-colors duration-300"
                       :class="darkMode ? 'text-white' : 'text-gray-900'"
                       x-text="stats.prophets || 0"></p>
                </div>
                <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-green-500 to-green-600 flex items-center justify-center shadow-lg">
                    <i data-lucide="crown" class="w-7 h-7 text-white"></i>
                </div>
            </div>
        </div>

        <!-- Companions -->
        <div class="rounded-2xl p-6 transition-all duration-300 hover:shadow-xl"
             :class="darkMode ? 'glass-dark' : 'bg-white shadow-soft'">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm mb-1 transition-colors duration-300"
                       :class="darkMode ? 'text-gray-400' : 'text-gray-600'">الصحابة</p>
                    <p class="text-3xl font-bold transition-colors duration-300"
                       :class="darkMode ? 'text-white' : 'text-gray-900'"
                       x-text="stats.companions || 0"></p>
                </div>
                <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-purple-500 to-purple-600 flex items-center justify-center shadow-lg">
                    <i data-lucide="star" class="w-7 h-7 text-white"></i>
                </div>
            </div>
        </div>

        <!-- Selected -->
        <div class="rounded-2xl p-6 transition-all duration-300 hover:shadow-xl"
             :class="darkMode ? 'glass-dark' : 'bg-white shadow-soft'">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm mb-1 transition-colors duration-300"
                       :class="darkMode ? 'text-gray-400' : 'text-gray-600'">محدد</p>
                    <p class="text-3xl font-bold transition-colors duration-300"
                       :class="darkMode ? 'text-white' : 'text-gray-900'"
                       x-text="selected.length"></p>
                </div>
                <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-orange-500 to-orange-600 flex items-center justify-center shadow-lg">
                    <i data-lucide="check-square" class="w-7 h-7 text-white"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters and Search -->
    <div class="rounded-2xl p-6 mb-6 transition-colors duration-300"
         :class="darkMode ? 'glass-dark' : 'bg-white shadow-soft'">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Search -->
            <div class="md:col-span-2">
                <div class="relative">
                    <i data-lucide="search"
                       class="w-5 h-5 absolute right-4 top-1/2 -translate-y-1/2 transition-colors duration-300"
                       :class="darkMode ? 'text-gray-400' : 'text-gray-500'"></i>
                    <input type="text"
                           x-model="search"
                           @input.debounce.500ms="loadPeople(1)"
                           placeholder="ابحث بالاسم أو القبيلة..."
                           class="w-full pr-12 pl-4 py-3 rounded-xl border transition-all duration-300 focus:ring-2 focus:ring-[#64CCC5] focus:outline-none"
                           :class="darkMode ? 'bg-gray-800 border-gray-700 text-white placeholder-gray-500' : 'bg-gray-50 border-gray-200 text-gray-900 placeholder-gray-400'">
                </div>
            </div>

            <!-- Person Type Filter -->
            <div>
                <select x-model="filters.type"
                        @change="loadPeople(1)"
                        class="w-full px-4 py-3 rounded-xl border transition-all duration-300 focus:ring-2 focus:ring-[#64CCC5] focus:outline-none"
                        :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-gray-50 border-gray-200 text-gray-900'">
                    <option value="">كل الأنواع</option>
                    <option value="prophet">نبي</option>
                    <option value="companion_male">صحابي</option>
                    <option value="companion_female">صحابية</option>
                    <option value="family">عائلة النبي</option>
                    <option value="tribe_leader">زعيم قبيلة</option>
                    <option value="historical_figure">شخصية تاريخية</option>
                </select>
            </div>
        </div>

        <!-- Bulk Actions -->
        <div x-show="selected.length > 0"
             x-transition
             class="mt-4 p-4 rounded-xl transition-colors duration-300"
             :class="darkMode ? 'bg-gray-800/50' : 'bg-blue-50'">
            <div class="flex items-center justify-between">
                <span class="transition-colors duration-300"
                      :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                    تم تحديد <strong x-text="selected.length"></strong> عنصر
                </span>
                <button @click="confirmBulkDelete"
                        class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors duration-300 flex items-center gap-2">
                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                    حذف المحدد
                </button>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="rounded-2xl overflow-hidden transition-colors duration-300"
         :class="darkMode ? 'glass-dark' : 'bg-white shadow-soft'">

        <!-- Loading State -->
        <div x-show="loading" class="p-12 text-center">
            <div class="inline-block animate-spin rounded-full h-12 w-12 border-4 border-t-[#64CCC5] transition-colors duration-300"
                 :class="darkMode ? 'border-gray-700' : 'border-gray-200'"></div>
            <p class="mt-4 transition-colors duration-300"
               :class="darkMode ? 'text-gray-400' : 'text-gray-600'">جاري التحميل...</p>
        </div>

        <!-- Empty State -->
        <div x-show="!loading && people.length === 0" class="p-12 text-center">
            <div class="w-20 h-20 rounded-full mx-auto mb-4 flex items-center justify-center transition-colors duration-300"
                 :class="darkMode ? 'bg-gray-800' : 'bg-gray-100'">
                <i data-lucide="users" class="w-10 h-10 transition-colors duration-300"
                   :class="darkMode ? 'text-gray-600' : 'text-gray-400'"></i>
            </div>
            <h3 class="text-xl font-semibold mb-2 transition-colors duration-300"
                :class="darkMode ? 'text-white' : 'text-gray-900'">لا توجد بيانات</h3>
            <p class="transition-colors duration-300"
               :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                <template x-if="search || filters.type">
                    لم يتم العثور على نتائج مطابقة لبحثك
                </template>
                <template x-if="!search && !filters.type">
                    لا توجد أفراد في قاعدة البيانات
                </template>
            </p>
        </div>

        <!-- Table Content -->
        <div x-show="!loading && people.length > 0" class="overflow-x-auto">
            <table class="w-full">
                <thead class="transition-colors duration-300"
                       :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                    <tr>
                        <th class="px-6 py-4 text-right">
                            <input type="checkbox"
                                   @change="toggleAll"
                                   :checked="selected.length === people.length && people.length > 0"
                                   class="w-4 h-4 rounded border-gray-300 text-[#64CCC5] focus:ring-[#64CCC5] cursor-pointer">
                        </th>
                        <th class="px-6 py-4 text-right text-sm font-semibold transition-colors duration-300"
                            :class="darkMode ? 'text-gray-300' : 'text-gray-700'">الاسم</th>
                        <th class="px-6 py-4 text-right text-sm font-semibold transition-colors duration-300"
                            :class="darkMode ? 'text-gray-300' : 'text-gray-700'">النوع</th>
                        <th class="px-6 py-4 text-right text-sm font-semibold transition-colors duration-300"
                            :class="darkMode ? 'text-gray-300' : 'text-gray-700'">الجنس</th>
                        <th class="px-6 py-4 text-right text-sm font-semibold transition-colors duration-300"
                            :class="darkMode ? 'text-gray-300' : 'text-gray-700'">القبيلة</th>
                        <th class="px-6 py-4 text-right text-sm font-semibold transition-colors duration-300"
                            :class="darkMode ? 'text-gray-300' : 'text-gray-700'">الأب</th>
                        <th class="px-6 py-4 text-right text-sm font-semibold transition-colors duration-300"
                            :class="darkMode ? 'text-gray-300' : 'text-gray-700'">حالة التحقق</th>
                        <th class="px-6 py-4 text-right text-sm font-semibold transition-colors duration-300"
                            :class="darkMode ? 'text-gray-300' : 'text-gray-700'">الإجراءات</th>
                    </tr>
                </thead>
                <tbody class="divide-y transition-colors duration-300"
                       :class="darkMode ? 'divide-gray-800' : 'divide-gray-200'">
                    <template x-for="person in people" :key="person.id">
                        <tr class="transition-all duration-300 hover:shadow-md"
                            :class="darkMode ? 'hover:bg-gray-800/50' : 'hover:bg-gray-50'">
                            <td class="px-6 py-4">
                                <input type="checkbox"
                                       :value="person.id"
                                       x-model="selected"
                                       class="w-4 h-4 rounded border-gray-300 text-[#64CCC5] focus:ring-[#64CCC5] cursor-pointer">
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full bg-gradient-to-br from-[#0056b3] to-[#218838] flex items-center justify-center text-white font-bold">
                                        <span x-text="person.name?.ar?.charAt(0) || person.name?.en?.charAt(0) || '؟'"></span>
                                    </div>
                                    <div>
                                        <p class="font-semibold transition-colors duration-300"
                                           :class="darkMode ? 'text-white' : 'text-gray-900'"
                                           x-text="person.name?.ar || person.name?.en || 'غير محدد'"></p>
                                        <p class="text-sm transition-colors duration-300"
                                           :class="darkMode ? 'text-gray-400' : 'text-gray-600'"
                                           x-text="person.title?.ar || person.title?.en || ''"></p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-3 py-1 rounded-full text-xs font-semibold transition-colors duration-300"
                                      :class="getTypeBadgeClass(person.person_type)"
                                      x-text="person.person_type_label?.ar || 'غير محدد'"></span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="transition-colors duration-300"
                                      :class="darkMode ? 'text-gray-300' : 'text-gray-700'"
                                      x-text="person.gender === 'male' ? 'ذكر' : person.gender === 'female' ? 'أنثى' : 'غير محدد'"></span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="transition-colors duration-300"
                                      :class="darkMode ? 'text-gray-300' : 'text-gray-700'"
                                      x-text="person.tribe || '—'"></span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="transition-colors duration-300"
                                      :class="darkMode ? 'text-gray-300' : 'text-gray-700'"
                                      x-text="person.father?.name?.ar || person.father?.name?.en || '—'"></span>
                            </td>
                            <td class="px-6 py-4">
                                <span x-show="person.is_verified"
                                      class="px-3 py-1 rounded-full text-xs font-semibold transition-colors duration-300"
                                      :class="darkMode ? 'bg-green-500/20 text-green-300' : 'bg-green-100 text-green-700'">
                                    <i data-lucide="check-circle" class="w-3 h-3 inline"></i>
                                    موثق
                                </span>
                                <span x-show="!person.is_verified"
                                      class="px-3 py-1 rounded-full text-xs font-semibold transition-colors duration-300"
                                      :class="darkMode ? 'bg-gray-700 text-gray-400' : 'bg-gray-100 text-gray-600'">
                                    غير موثق
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <a :href="`/su/portal/v1/admin/genealogy/${person.id}/edit`"
                                       class="p-2 rounded-lg transition-all duration-300 hover:scale-110"
                                       :class="darkMode ? 'hover:bg-gray-700 text-blue-400' : 'hover:bg-blue-50 text-blue-600'"
                                       title="تعديل">
                                        <i data-lucide="edit" class="w-4 h-4"></i>
                                    </a>
                                    <button @click="deletePerson(person.id)"
                                            class="p-2 rounded-lg transition-all duration-300 hover:scale-110"
                                            :class="darkMode ? 'hover:bg-gray-700 text-red-400' : 'hover:bg-red-50 text-red-600'"
                                            title="حذف">
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
        <div x-show="!loading && people.length > 0"
             class="px-6 py-4 border-t transition-colors duration-300"
             :class="darkMode ? 'border-gray-800' : 'border-gray-200'">
            <div class="flex items-center justify-between">
                <div class="text-sm transition-colors duration-300"
                     :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                    عرض <span x-text="pagination.from || 0"></span> إلى <span x-text="pagination.to || 0"></span>
                    من <span x-text="pagination.total || 0"></span> عنصر
                </div>
                <div class="flex gap-2">
                    <button @click="loadPeople(pagination.current_page - 1)"
                            :disabled="pagination.current_page <= 1"
                            class="px-4 py-2 rounded-lg transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed"
                            :class="darkMode ? 'bg-gray-800 text-white hover:bg-gray-700' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'">
                        السابق
                    </button>
                    <button @click="loadPeople(pagination.current_page + 1)"
                            :disabled="pagination.current_page >= pagination.last_page"
                            class="px-4 py-2 rounded-lg transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed"
                            :class="darkMode ? 'bg-gray-800 text-white hover:bg-gray-700' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'">
                        التالي
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function peopleTable() {
    return {
        people: [],
        search: '',
        filters: {
            type: ''
        },
        selected: [],
        loading: true,
        pagination: {
            current_page: 1,
            last_page: 1,
            per_page: 15,
            total: 0,
            from: 0,
            to: 0
        },
        stats: {
            prophets: 0,
            companions: 0
        },
        perPage: 15,

        init() {
            this.loadPeople();
            this.$nextTick(() => lucide.createIcons());
        },

        async loadPeople(page = 1) {
            this.loading = true;

            try {
                const params = new URLSearchParams({
                    page: page,
                    per_page: this.perPage,
                    search: this.search,
                    type: this.filters.type
                });

                const response = await fetch(`/su/portal/v1/admin/genealogy?${params}`, {
                    headers: {
                        'Accept': 'application/json'
                    }
                });

                const data = await response.json();

                if (data.success) {
                    this.people = data.data;
                    this.pagination = data.meta;

                    // Calculate stats
                    this.calculateStats();

                    this.$nextTick(() => lucide.createIcons());
                }
            } catch (error) {
                console.error('Error loading people:', error);
                this.showToast('حدث خطأ أثناء تحميل البيانات', 'error');
            } finally {
                this.loading = false;
            }
        },

        calculateStats() {
            // These would ideally come from the backend
            // For now, we'll calculate from current page data
            this.stats.prophets = this.people.filter(p => p.person_type === 'prophet').length;
            this.stats.companions = this.people.filter(p =>
                p.person_type === 'companion_male' || p.person_type === 'companion_female'
            ).length;
        },

        getTypeBadgeClass(type) {
            const darkMode = this.darkMode;
            const classes = {
                'prophet': darkMode ? 'bg-green-500/20 text-green-300' : 'bg-green-100 text-green-700',
                'companion_male': darkMode ? 'bg-blue-500/20 text-blue-300' : 'bg-blue-100 text-blue-700',
                'companion_female': darkMode ? 'bg-purple-500/20 text-purple-300' : 'bg-purple-100 text-purple-700',
                'family': darkMode ? 'bg-yellow-500/20 text-yellow-300' : 'bg-yellow-100 text-yellow-700',
                'tribe_leader': darkMode ? 'bg-orange-500/20 text-orange-300' : 'bg-orange-100 text-orange-700',
                'historical_figure': darkMode ? 'bg-gray-500/20 text-gray-300' : 'bg-gray-100 text-gray-700'
            };
            return classes[type] || (darkMode ? 'bg-gray-500/20 text-gray-300' : 'bg-gray-100 text-gray-700');
        },

        toggleAll() {
            if (this.selected.length === this.people.length) {
                this.selected = [];
            } else {
                this.selected = this.people.map(p => p.id);
            }
        },

        async deletePerson(id) {
            if (!confirm('هل أنت متأكد من حذف هذا الشخص؟')) {
                return;
            }

            try {
                const response = await fetch(`/su/portal/v1/admin/genealogy/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                const data = await response.json();

                if (data.success) {
                    this.showToast('تم حذف الشخص بنجاح', 'success');
                    this.loadPeople(this.pagination.current_page);
                } else {
                    this.showToast(data.message || 'حدث خطأ أثناء الحذف', 'error');
                }
            } catch (error) {
                console.error('Error deleting person:', error);
                this.showToast('حدث خطأ أثناء الحذف', 'error');
            }
        },

        async confirmBulkDelete() {
            if (!confirm(`هل أنت متأكد من حذف ${this.selected.length} عنصر؟`)) {
                return;
            }

            try {
                const response = await fetch('/su/portal/v1/admin/genealogy/bulk-delete', {
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
                    this.showToast(`تم حذف ${data.data.deleted} عنصر بنجاح`, 'success');
                    this.selected = [];
                    this.loadPeople(this.pagination.current_page);
                } else {
                    this.showToast(data.message || 'حدث خطأ أثناء الحذف', 'error');
                }
            } catch (error) {
                console.error('Error bulk deleting:', error);
                this.showToast('حدث خطأ أثناء الحذف', 'error');
            }
        },

        showToast(message, type = 'info') {
            // Simple toast implementation
            const toast = document.createElement('div');
            toast.className = `fixed bottom-4 left-4 px-6 py-3 rounded-lg text-white z-50 ${
                type === 'success' ? 'bg-green-600' :
                type === 'error' ? 'bg-red-600' :
                'bg-blue-600'
            }`;
            toast.textContent = message;
            document.body.appendChild(toast);

            setTimeout(() => {
                toast.remove();
            }, 3000);
        }
    };
}
</script>
@endsection
