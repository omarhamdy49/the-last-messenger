@extends('layouts.admin')

@section('title', 'التفاسير')

@section('content')
<div x-data="tafseerManager()" x-init="init()">
    {{-- Page Header --}}
    <div class="rounded-2xl p-6 mb-8 transition-colors" :class="darkMode ? 'glass-dark' : 'glass-light-soft'">
        <div class="absolute top-0 right-0 w-96 h-96 bg-gradient-to-br from-blue-500/20 to-green-500/20 rounded-full blur-3xl -translate-y-1/2 translate-x-1/2 animate-pulse"></div>

        <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-4xl font-black text-white mb-2 gradient-text">إدارة التفاسير</h1>
                <p class="text-gray-400 flex items-center space-x-2 space-x-reverse">
                    <i data-lucide="book-open" class="w-4 h-4"></i>
                    <span>إدارة تفاسير القرآن الكريم</span>
                </p>
            </div>

            <div class="mt-4 md:mt-0 flex items-center space-x-3 space-x-reverse">
                <a href="{{ route('admin.tafseer.create') }}" class="px-6 py-3 gradient-primary text-white rounded-xl font-semibold transition-all duration-300 flex items-center space-x-2 space-x-reverse hover:scale-105 shadow-lg">
                    <i data-lucide="plus" class="w-5 h-5"></i>
                    <span>إضافة تفسير جديد</span>
                </a>
            </div>
        </div>
    </div>

    {{-- Stats Overview --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="group rounded-xl p-6 relative overflow-hidden hover:scale-105 transition-all duration-300" :class="darkMode ? 'glass-dark' : 'glass-light-soft'">
            <div class="absolute top-0 right-0 w-24 h-24 bg-blue-500/20 rounded-full -translate-y-1/2 translate-x-1/2 group-hover:scale-150 transition-transform duration-500"></div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center">
                        <i data-lucide="book" class="w-6 h-6 text-white"></i>
                    </div>
                    <span class="text-2xl font-black text-white" x-text="stats.total">0</span>
                </div>
                <p class="text-sm text-gray-400 font-semibold">إجمالي التفاسير</p>
            </div>
        </div>

        <div class="group rounded-xl p-6 relative overflow-hidden hover:scale-105 transition-all duration-300" :class="darkMode ? 'glass-dark' : 'glass-light-soft'">
            <div class="absolute top-0 right-0 w-24 h-24 bg-green-500/20 rounded-full -translate-y-1/2 translate-x-1/2 group-hover:scale-150 transition-transform duration-500"></div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-green-500 to-green-600 flex items-center justify-center">
                        <i data-lucide="check-circle" class="w-6 h-6 text-white"></i>
                    </div>
                    <span class="text-2xl font-black text-white" x-text="stats.published">0</span>
                </div>
                <p class="text-sm text-gray-400 font-semibold">منشورة</p>
            </div>
        </div>

        <div class="group rounded-xl p-6 relative overflow-hidden hover:scale-105 transition-all duration-300" :class="darkMode ? 'glass-dark' : 'glass-light-soft'">
            <div class="absolute top-0 right-0 w-24 h-24 bg-yellow-500/20 rounded-full -translate-y-1/2 translate-x-1/2 group-hover:scale-150 transition-transform duration-500"></div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-yellow-500 to-yellow-600 flex items-center justify-center">
                        <i data-lucide="file-edit" class="w-6 h-6 text-white"></i>
                    </div>
                    <span class="text-2xl font-black text-white" x-text="stats.draft">0</span>
                </div>
                <p class="text-sm text-gray-400 font-semibold">مسودات</p>
            </div>
        </div>

        <div class="group rounded-xl p-6 relative overflow-hidden hover:scale-105 transition-all duration-300" :class="darkMode ? 'glass-dark' : 'glass-light-soft'">
            <div class="absolute top-0 right-0 w-24 h-24 bg-purple-500/20 rounded-full -translate-y-1/2 translate-x-1/2 group-hover:scale-150 transition-transform duration-500"></div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-purple-500 to-purple-600 flex items-center justify-center">
                        <i data-lucide="layers" class="w-6 h-6 text-white"></i>
                    </div>
                    <span class="text-2xl font-black text-white" x-text="stats.sources">9</span>
                </div>
                <p class="text-sm text-gray-400 font-semibold">المصادر</p>
            </div>
        </div>
    </div>

    {{-- Filters Section --}}
    <div class="rounded-2xl p-6 mb-8 transition-colors" :class="darkMode ? 'glass-dark' : 'glass-light-soft'">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="md:col-span-1">
                <div class="relative">
                    <i data-lucide="search" class="absolute right-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400"></i>
                    <input
                        type="text"
                        x-model="filters.search"
                        @input.debounce.500ms="filterData"
                        placeholder="البحث... (رقم الآية، المؤلف)"
                        class="w-full pr-12 pl-4 py-3 rounded-xl transition-all focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent placeholder-gray-400"
                        :class="darkMode ? 'bg-white/5 border border-white/10 text-white' : 'glass-light-strong text-gray-900'"
                    >
                </div>
            </div>

            <div>
                <select
                    x-model="filters.source"
                    @change="filterData"
                    class="w-full px-4 py-3 rounded-xl transition-all focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    :class="darkMode ? 'bg-white/5 border border-white/10 text-white' : 'glass-light-strong text-gray-900'"
                >
                    <option value="">كل المصادر</option>
                    <option value="Ibn Kathir">ابن كثير</option>
                    <option value="Jalalayn">الجلالين</option>
                    <option value="Sa'di">السعدي</option>
                    <option value="Qurtubi">القرطبي</option>
                    <option value="Tabari">الطبري</option>
                    <option value="Baghawi">البغوي</option>
                    <option value="Muyassar">الميسر</option>
                    <option value="Ashhari">الأشعري</option>
                    <option value="Qasimi">القاسمي</option>
                </select>
            </div>

            <div>
                <select
                    x-model="filters.status"
                    @change="filterData"
                    class="w-full px-4 py-3 rounded-xl transition-all focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    :class="darkMode ? 'bg-white/5 border border-white/10 text-white' : 'glass-light-strong text-gray-900'"
                >
                    <option value="">كل الحالات</option>
                    <option value="published">منشورة</option>
                    <option value="draft">مسودة</option>
                </select>
            </div>

            <div>
                <select
                    x-model="filters.sortBy"
                    @change="filterData"
                    class="w-full px-4 py-3 rounded-xl transition-all focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    :class="darkMode ? 'bg-white/5 border border-white/10 text-white' : 'glass-light-strong text-gray-900'"
                >
                    <option value="verse">رقم الآية</option>
                    <option value="source">المصدر</option>
                    <option value="created_at">تاريخ الإضافة</option>
                </select>
            </div>
        </div>
    </div>

    {{-- Loading Skeleton --}}
    <div x-show="loading" class="rounded-2xl overflow-hidden transition-colors" :class="darkMode ? 'glass-dark' : 'glass-light-soft'">
        @include('admin.components.skeleton-table')
    </div>

    {{-- Tafseer Table --}}
    <div x-show="!loading" class="rounded-2xl overflow-hidden transition-colors" :class="darkMode ? 'glass-dark' : 'glass-light-soft'">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b" :class="darkMode ? 'border-white/10 bg-white/5' : 'border-gray-200 bg-gray-50'">
                        <th class="px-6 py-4 text-right text-xs font-bold uppercase tracking-wider" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            <input type="checkbox" @change="toggleSelectAll" class="rounded">
                        </th>
                        <th class="px-6 py-4 text-right text-xs font-bold uppercase tracking-wider" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">رقم الآية</th>
                        <th class="px-6 py-4 text-right text-xs font-bold uppercase tracking-wider" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">المصدر</th>
                        <th class="px-6 py-4 text-right text-xs font-bold uppercase tracking-wider" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">المؤلف</th>
                        <th class="px-6 py-4 text-right text-xs font-bold uppercase tracking-wider" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">الحالة</th>
                        <th class="px-6 py-4 text-right text-xs font-bold uppercase tracking-wider" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">الإجراءات</th>
                    </tr>
                </thead>
                <tbody class="divide-y" :class="darkMode ? 'divide-white/10' : 'divide-gray-200'">
                    <template x-if="items.length === 0">
                        <tr>
                            <td colspan="6" class="px-6 py-16 text-center">
                                <div class="flex flex-col items-center justify-center">
                                    <div class="w-24 h-24 bg-gradient-to-br from-blue-500/20 to-green-500/20 rounded-full flex items-center justify-center mb-4">
                                        <i data-lucide="book-open" class="w-12 h-12 text-gray-500"></i>
                                    </div>
                                    <p class="text-white font-bold text-xl mb-2">لا توجد تفاسير</p>
                                    <p class="text-gray-400">لم يتم العثور على أي تفسير</p>
                                </div>
                            </td>
                        </tr>
                    </template>

                    <template x-for="tafseer in items" :key="tafseer.id">
                        <tr class="hover:bg-white/5 transition-colors">
                            <td class="px-6 py-4">
                                <input type="checkbox" :value="tafseer.id" class="rounded tafseer-select">
                            </td>
                            <td class="px-6 py-4">
                                <div class="font-bold text-lg" :class="darkMode ? 'text-white' : 'text-gray-900'" x-text="tafseer.verse_reference"></div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-3 py-1 rounded-full text-xs font-bold"
                                      :class="getSourceColor(tafseer.source)"
                                      x-text="getSourceName(tafseer.source)"></span>
                            </td>
                            <td class="px-6 py-4">
                                <div :class="darkMode ? 'text-gray-300' : 'text-gray-700'" x-text="tafseer.author || '-'"></div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-3 py-1 rounded-full text-xs font-bold"
                                      :class="tafseer.status === 'published' ? 'bg-green-500/20 text-green-400' : 'bg-yellow-500/20 text-yellow-400'"
                                      x-text="tafseer.status === 'published' ? 'منشورة' : 'مسودة'"></span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <a :href="`/su/portal/v1/admin/tafseer/${tafseer.id}`" class="p-2 hover:bg-blue-500/20 rounded-lg transition-colors text-blue-400">
                                        <i data-lucide="eye" class="w-4 h-4"></i>
                                    </a>
                                    <a :href="`/su/portal/v1/admin/tafseer/${tafseer.id}/edit`" class="p-2 hover:bg-green-500/20 rounded-lg transition-colors text-green-400">
                                        <i data-lucide="edit" class="w-4 h-4"></i>
                                    </a>
                                    <button @click="deleteTafseer(tafseer.id)" class="p-2 hover:bg-red-500/20 rounded-lg transition-colors text-red-400">
                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="px-6 py-4 border-t" :class="darkMode ? 'border-white/10' : 'border-gray-200'">
            <div class="flex items-center justify-between">
                <div :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                    عرض <span x-text="items.length"></span> من <span x-text="stats.total"></span> تفسير
                </div>
                <div class="flex gap-2">
                    <button class="px-4 py-2 rounded-lg transition-all" :class="darkMode ? 'bg-white/10 text-white hover:bg-white/20' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'">
                        السابق
                    </button>
                    <button class="px-4 py-2 rounded-lg transition-all" :class="darkMode ? 'bg-white/10 text-white hover:bg-white/20' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'">
                        التالي
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function tafseerManager() {
    return {
        loading: true,
        items: [],
        stats: {
            total: 0,
            published: 0,
            draft: 0,
            sources: 9
        },
        filters: {
            search: '',
            source: '',
            status: '',
            sortBy: 'verse'
        },

        async init() {
            await this.loadData();
            this.loading = false;
            this.$nextTick(() => lucide.createIcons());
        },

        async loadData() {
            try {
                const params = new URLSearchParams(this.filters);
                const response = await fetch(`/su/portal/v1/admin/tafseer?${params}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });

                if (response.ok) {
                    const data = await response.json();
                    this.items = data.data || [];
                    this.stats = data.stats || this.stats;
                }
            } catch (error) {
                console.error('Error loading tafseer:', error);
                window.showToast('حدث خطأ أثناء تحميل التفاسير', 'error');
            }
        },

        filterData() {
            this.loadData();
        },

        toggleSelectAll(event) {
            document.querySelectorAll('.tafseer-select').forEach(cb => cb.checked = event.target.checked);
        },

        getSourceColor(source) {
            const colors = {
                'Ibn Kathir': 'bg-blue-500/20 text-blue-400',
                'Jalalayn': 'bg-green-500/20 text-green-400',
                'Sa\'di': 'bg-purple-500/20 text-purple-400',
                'Qurtubi': 'bg-orange-500/20 text-orange-400',
                'Tabari': 'bg-red-500/20 text-red-400',
                'Baghawi': 'bg-pink-500/20 text-pink-400',
                'Muyassar': 'bg-teal-500/20 text-teal-400',
                'Ashhari': 'bg-indigo-500/20 text-indigo-400',
                'Qasimi': 'bg-cyan-500/20 text-cyan-400'
            };
            return colors[source] || 'bg-gray-500/20 text-gray-400';
        },

        getSourceName(source) {
            const names = {
                'Ibn Kathir': 'ابن كثير',
                'Jalalayn': 'الجلالين',
                'Sa\'di': 'السعدي',
                'Qurtubi': 'القرطبي',
                'Tabari': 'الطبري',
                'Baghawi': 'البغوي',
                'Muyassar': 'الميسر',
                'Ashhari': 'الأشعري',
                'Qasimi': 'القاسمي'
            };
            return names[source] || source;
        },

        async deleteTafseer(id) {
            if (!confirm('هل أنت متأكد من حذف هذا التفسير؟')) return;

            try {
                const response = await fetch(`/su/portal/v1/admin/tafseer/${id}`, {
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
                    window.showToast('تم حذف التفسير بنجاح', 'success');
                }
            } catch (error) {
                console.error('Error deleting tafseer:', error);
                window.showToast('حدث خطأ أثناء الحذف', 'error');
            }
        }
    };
}
</script>
@endpush
