@extends('layouts.admin')

@section('title', 'رواة الحديث')

@section('content')
<div x-data="narratorsManager()" x-init="init()">
    <div class="rounded-2xl p-6 mb-8 transition-colors" :class="darkMode ? 'glass-dark' : 'glass-light-soft'">
        <div class="absolute top-0 right-0 w-96 h-96 bg-gradient-to-br from-blue-500/20 to-green-500/20 rounded-full blur-3xl -translate-y-1/2 translate-x-1/2 animate-pulse"></div>

        <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-4xl font-black text-white mb-2 gradient-text">إدارة رواة الحديث</h1>
                <p class="text-gray-400 flex items-center space-x-2 space-x-reverse">
                    <i data-lucide="users" class="w-4 h-4"></i>
                    <span>إدارة رواة الأحاديث النبوية الشريفة</span>
                </p>
            </div>

            <div class="mt-4 md:mt-0 flex items-center space-x-3 space-x-reverse">
                <a href="{{ route('admin.hadith-narrators.create') }}" class="px-6 py-3 gradient-primary text-white rounded-xl font-semibold transition-all duration-300 flex items-center space-x-2 space-x-reverse hover:scale-105 shadow-lg">
                    <i data-lucide="plus" class="w-5 h-5"></i>
                    <span>إضافة راوٍ جديد</span>
                </a>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-5 gap-6 mb-8">
        <div class="group rounded-xl p-6 relative overflow-hidden hover:scale-105 transition-all duration-300" :class="darkMode ? 'glass-dark' : 'glass-light-soft'">
            <div class="absolute top-0 right-0 w-24 h-24 bg-blue-500/20 rounded-full -translate-y-1/2 translate-x-1/2 group-hover:scale-150 transition-transform duration-500"></div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center">
                        <i data-lucide="users" class="w-6 h-6 text-white"></i>
                    </div>
                    <span class="text-2xl font-black text-white" x-text="stats.total">0</span>
                </div>
                <p class="text-sm text-gray-400 font-semibold">إجمالي الرواة</p>
            </div>
        </div>

        <div class="group rounded-xl p-6 relative overflow-hidden hover:scale-105 transition-all duration-300" :class="darkMode ? 'glass-dark' : 'glass-light-soft'">
            <div class="absolute top-0 right-0 w-24 h-24 bg-green-500/20 rounded-full -translate-y-1/2 translate-x-1/2 group-hover:scale-150 transition-transform duration-500"></div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-green-500 to-green-600 flex items-center justify-center">
                        <i data-lucide="shield-check" class="w-6 h-6 text-white"></i>
                    </div>
                    <span class="text-2xl font-black text-white" x-text="stats.sahih">0</span>
                </div>
                <p class="text-sm text-gray-400 font-semibold">صحيح</p>
            </div>
        </div>

        <div class="group rounded-xl p-6 relative overflow-hidden hover:scale-105 transition-all duration-300" :class="darkMode ? 'glass-dark' : 'glass-light-soft'">
            <div class="absolute top-0 right-0 w-24 h-24 bg-blue-500/20 rounded-full -translate-y-1/2 translate-x-1/2 group-hover:scale-150 transition-transform duration-500"></div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-blue-400 to-blue-500 flex items-center justify-center">
                        <i data-lucide="check" class="w-6 h-6 text-white"></i>
                    </div>
                    <span class="text-2xl font-black text-white" x-text="stats.hasan">0</span>
                </div>
                <p class="text-sm text-gray-400 font-semibold">حسن</p>
            </div>
        </div>

        <div class="group rounded-xl p-6 relative overflow-hidden hover:scale-105 transition-all duration-300" :class="darkMode ? 'glass-dark' : 'glass-light-soft'">
            <div class="absolute top-0 right-0 w-24 h-24 bg-orange-500/20 rounded-full -translate-y-1/2 translate-x-1/2 group-hover:scale-150 transition-transform duration-500"></div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-orange-500 to-orange-600 flex items-center justify-center">
                        <i data-lucide="alert-triangle" class="w-6 h-6 text-white"></i>
                    </div>
                    <span class="text-2xl font-black text-white" x-text="stats.daif">0</span>
                </div>
                <p class="text-sm text-gray-400 font-semibold">ضعيف</p>
            </div>
        </div>

        <div class="group rounded-xl p-6 relative overflow-hidden hover:scale-105 transition-all duration-300" :class="darkMode ? 'glass-dark' : 'glass-light-soft'">
            <div class="absolute top-0 right-0 w-24 h-24 bg-purple-500/20 rounded-full -translate-y-1/2 translate-x-1/2 group-hover:scale-150 transition-transform duration-500"></div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-purple-500 to-purple-600 flex items-center justify-center">
                        <i data-lucide="users" class="w-6 h-6 text-white"></i>
                    </div>
                    <span class="text-2xl font-black text-white" x-text="stats.sahabah">0</span>
                </div>
                <p class="text-sm text-gray-400 font-semibold">الصحابة</p>
            </div>
        </div>
    </div>

    <div class="rounded-2xl p-6 mb-8 transition-colors" :class="darkMode ? 'glass-dark' : 'glass-light-soft'">
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <div class="md:col-span-1">
                <div class="relative">
                    <i data-lucide="search" class="absolute right-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400"></i>
                    <input type="text" x-model="filters.search" @input.debounce.500ms="filterData" placeholder="البحث بالاسم..." class="w-full pr-12 pl-4 py-3 rounded-xl transition-all focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent placeholder-gray-400" :class="darkMode ? 'bg-white/5 border border-white/10 text-white' : 'glass-light-strong text-gray-900'">
                </div>
            </div>

            <div>
                <select x-model="filters.grade" @change="filterData" class="w-full px-4 py-3 rounded-xl transition-all focus:outline-none focus:ring-2 focus:ring-blue-500" :class="darkMode ? 'bg-white/5 border border-white/10 text-white' : 'glass-light-strong text-gray-900'">
                    <option value="">كل الدرجات</option>
                    <option value="Sahih">صحيح</option>
                    <option value="Hasan">حسن</option>
                    <option value="Daif">ضعيف</option>
                    <option value="Weak">ضعيف جداً</option>
                </select>
            </div>

            <div>
                <select x-model="filters.era" @change="filterData" class="w-full px-4 py-3 rounded-xl transition-all focus:outline-none focus:ring-2 focus:ring-blue-500" :class="darkMode ? 'bg-white/5 border border-white/10 text-white' : 'glass-light-strong text-gray-900'">
                    <option value="">كل العصور</option>
                    <option value="Sahabah">الصحابة</option>
                    <option value="Tabiun">التابعون</option>
                    <option value="Taba al-Tabiun">تابعو التابعين</option>
                    <option value="Taba Taba al-Tabiun">تابعو تابعي التابعين</option>
                    <option value="Later Scholars">علماء متأخرون</option>
                </select>
            </div>

            <div>
                <select x-model="filters.country" @change="filterData" class="w-full px-4 py-3 rounded-xl transition-all focus:outline-none focus:ring-2 focus:ring-blue-500" :class="darkMode ? 'bg-white/5 border border-white/10 text-white' : 'glass-light-strong text-gray-900'">
                    <option value="">كل الدول</option>
                    <option value="مكة">مكة</option>
                    <option value="المدينة">المدينة</option>
                    <option value="الشام">الشام</option>
                    <option value="العراق">العراق</option>
                    <option value="مصر">مصر</option>
                    <option value="خراسان">خراسان</option>
                </select>
            </div>

            <div>
                <select x-model="filters.status" @change="filterData" class="w-full px-4 py-3 rounded-xl transition-all focus:outline-none focus:ring-2 focus:ring-blue-500" :class="darkMode ? 'bg-white/5 border border-white/10 text-white' : 'glass-light-strong text-gray-900'">
                    <option value="">كل الحالات</option>
                    <option value="active">نشط</option>
                    <option value="inactive">غير نشط</option>
                </select>
            </div>
        </div>
    </div>

    <div x-show="loading" class="rounded-2xl overflow-hidden transition-colors" :class="darkMode ? 'glass-dark' : 'glass-light-soft'">
        @include('admin.components.skeleton-table')
    </div>

    <div x-show="!loading" class="rounded-2xl overflow-hidden transition-colors" :class="darkMode ? 'glass-dark' : 'glass-light-soft'">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b" :class="darkMode ? 'border-white/10 bg-white/5' : 'border-gray-200 bg-gray-50'">
                        <th class="px-6 py-4 text-right text-xs font-bold uppercase tracking-wider" :class="darkMode ? 'text-gray-300' : 'text-gray-700'"><input type="checkbox" @change="toggleSelectAll" class="rounded"></th>
                        <th class="px-6 py-4 text-right text-xs font-bold uppercase tracking-wider" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">الاسم</th>
                        <th class="px-6 py-4 text-right text-xs font-bold uppercase tracking-wider" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">درجة الوثوق</th>
                        <th class="px-6 py-4 text-right text-xs font-bold uppercase tracking-wider" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">العصر</th>
                        <th class="px-6 py-4 text-right text-xs font-bold uppercase tracking-wider" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">البلد</th>
                        <th class="px-6 py-4 text-right text-xs font-bold uppercase tracking-wider" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">الأحاديث</th>
                        <th class="px-6 py-4 text-right text-xs font-bold uppercase tracking-wider" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">الحالة</th>
                        <th class="px-6 py-4 text-right text-xs font-bold uppercase tracking-wider" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">الإجراءات</th>
                    </tr>
                </thead>
                <tbody class="divide-y" :class="darkMode ? 'divide-white/10' : 'divide-gray-200'">
                    <template x-if="items.length === 0">
                        <tr><td colspan="8" class="px-6 py-16 text-center">
                            <div class="flex flex-col items-center justify-center">
                                <div class="w-24 h-24 bg-gradient-to-br from-blue-500/20 to-green-500/20 rounded-full flex items-center justify-center mb-4">
                                    <i data-lucide="users" class="w-12 h-12 text-gray-500"></i>
                                </div>
                                <p class="text-white font-bold text-xl mb-2">لا يوجد رواة</p>
                                <p class="text-gray-400">لم يتم العثور على أي راوٍ</p>
                            </div>
                        </td></tr>
                    </template>

                    <template x-for="narrator in items" :key="narrator.id">
                        <tr class="hover:bg-white/5 transition-colors">
                            <td class="px-6 py-4"><input type="checkbox" :value="narrator.id" class="rounded narrator-select"></td>
                            <td class="px-6 py-4"><div class="font-semibold" :class="darkMode ? 'text-white' : 'text-gray-900'" x-text="narrator.name.ar || narrator.name.en"></div></td>
                            <td class="px-6 py-4">
                                <span class="px-3 py-1 rounded-full text-xs font-bold" :class="{
                                    'bg-green-500/20 text-green-400': narrator.reliability_grade === 'Sahih',
                                    'bg-blue-500/20 text-blue-400': narrator.reliability_grade === 'Hasan',
                                    'bg-orange-500/20 text-orange-400': narrator.reliability_grade === 'Daif',
                                    'bg-red-500/20 text-red-400': narrator.reliability_grade === 'Weak'
                                }" x-text="getGradeName(narrator.reliability_grade)"></span>
                            </td>
                            <td class="px-6 py-4"><div :class="darkMode ? 'text-gray-300' : 'text-gray-700'" x-text="getEraName(narrator.era)"></div></td>
                            <td class="px-6 py-4"><div :class="darkMode ? 'text-gray-300' : 'text-gray-700'" x-text="narrator.country || '-'"></div></td>
                            <td class="px-6 py-4"><div class="font-bold text-blue-400" x-text="narrator.hadith_count || 0"></div></td>
                            <td class="px-6 py-4">
                                <span class="px-3 py-1 rounded-full text-xs font-bold" :class="narrator.status === 'active' ? 'bg-green-500/20 text-green-400' : 'bg-gray-500/20 text-gray-400'" x-text="narrator.status === 'active' ? 'نشط' : 'غير نشط'"></span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <a :href="`/su/portal/v1/admin/hadith-narrators/${narrator.id}`" class="p-2 hover:bg-blue-500/20 rounded-lg transition-colors text-blue-400"><i data-lucide="eye" class="w-4 h-4"></i></a>
                                    <a :href="`/su/portal/v1/admin/hadith-narrators/${narrator.id}/edit`" class="p-2 hover:bg-green-500/20 rounded-lg transition-colors text-green-400"><i data-lucide="edit" class="w-4 h-4"></i></a>
                                    <button @click="deleteNarrator(narrator.id)" class="p-2 hover:bg-red-500/20 rounded-lg transition-colors text-red-400"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <div class="px-6 py-4 border-t" :class="darkMode ? 'border-white/10' : 'border-gray-200'">
            <div class="flex items-center justify-between">
                <div :class="darkMode ? 'text-gray-400' : 'text-gray-600'">عرض <span x-text="items.length"></span> من <span x-text="stats.total"></span> راوٍ</div>
                <div class="flex gap-2">
                    <button class="px-4 py-2 rounded-lg transition-all" :class="darkMode ? 'bg-white/10 text-white hover:bg-white/20' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'">السابق</button>
                    <button class="px-4 py-2 rounded-lg transition-all" :class="darkMode ? 'bg-white/10 text-white hover:bg-white/20' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'">التالي</button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function narratorsManager() {
    return {
        loading: true,
        items: [],
        stats: { total: 0, sahih: 0, hasan: 0, daif: 0, sahabah: 0 },
        filters: { search: '', grade: '', era: '', country: '', status: '' },

        async init() {
            await this.loadData();
            this.loading = false;
            this.$nextTick(() => lucide.createIcons());
        },

        async loadData() {
            try {
                const params = new URLSearchParams(this.filters);
                const response = await fetch(`/su/portal/v1/admin/hadith-narrators?${params}`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                });

                if (response.ok) {
                    const data = await response.json();
                    this.items = data.data || [];
                    this.stats = data.stats || this.stats;
                }
            } catch (error) {
                console.error('Error loading narrators:', error);
                window.showToast('حدث خطأ أثناء تحميل الرواة', 'error');
            }
        },

        filterData() { this.loadData(); },
        toggleSelectAll(event) { document.querySelectorAll('.narrator-select').forEach(cb => cb.checked = event.target.checked); },

        getGradeName(grade) {
            const names = { 'Sahih': 'صحيح', 'Hasan': 'حسن', 'Daif': 'ضعيف', 'Weak': 'ضعيف جداً' };
            return names[grade] || grade;
        },

        getEraName(era) {
            const names = {
                'Sahabah': 'الصحابة',
                'Tabiun': 'التابعون',
                'Taba al-Tabiun': 'تابعو التابعين',
                'Taba Taba al-Tabiun': 'تابعو تابعي التابعين',
                'Later Scholars': 'علماء متأخرون'
            };
            return names[era] || era;
        },

        async deleteNarrator(id) {
            if (!confirm('هل أنت متأكد من حذف هذا الراوي؟')) return;

            try {
                const response = await fetch(`/su/portal/v1/admin/hadith-narrators/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });

                if (response.ok) {
                    await this.loadData();
                    this.$nextTick(() => lucide.createIcons());
                    window.showToast('تم حذف الراوي بنجاح', 'success');
                }
            } catch (error) {
                console.error('Error deleting narrator:', error);
                window.showToast('حدث خطأ أثناء الحذف', 'error');
            }
        }
    };
}
</script>
@endpush
