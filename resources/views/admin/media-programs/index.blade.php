@extends('layouts.admin')

@section('title', 'البرامج الإعلامية - عرض محسّن')

@section('content')
<div x-data="mediaProgramsManager()" x-init="init()">
    {{-- Page Header with Glassmorphism --}}
    <div class="rounded-2xl p-6 transition-colors" :class="darkMode ? \'glass-dark\' : \'glass-light-soft\'">
        <div class="absolute top-0 right-0 w-96 h-96 bg-gradient-to-br from-blue-500/20 to-green-500/20 rounded-full blur-3xl -translate-y-1/2 translate-x-1/2 animate-pulse"></div>

        <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-4xl font-black text-white mb-2 gradient-text">البرامج الإعلامية</h1>
                <p class="text-gray-400 flex items-center space-x-2 space-x-reverse">
                    <i data-lucide="video" class="w-4 h-4"></i>
                    <span>إدارة وتنظيم البرامج والحلقات الإعلامية</span>
                </p>
            </div>

            <div class="mt-4 md:mt-0 flex items-center space-x-3 space-x-reverse">
                <a href="{{ route('admin.media-programs.create') }}" class="px-6 py-3 gradient-primary text-white rounded-xl font-bold shadow-lg hover:shadow-xl transition-all duration-300 flex items-center space-x-2 space-x-reverse hover:scale-105 animate-pulse-glow">
                    <i data-lucide="plus-circle" class="w-5 h-5"></i>
                    <span>برنامج جديد</span>
                </a>
            </div>
        </div>
    </div>

    {{-- Stats Overview --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="rounded-xl p-6 relative overflow-hidden transition-colors" :class="darkMode ? 'glass-dark' : 'glass-light-soft'" group hover:scale-105 transition-transform duration-300">
            <div class="absolute top-0 right-0 w-24 h-24 bg-blue-500/20 rounded-full -translate-y-1/2 translate-x-1/2 group-hover:scale-150 transition-transform duration-500"></div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center">
                        <i data-lucide="video" class="w-6 h-6 text-white"></i>
                    </div>
                    <span class="text-2xl font-black text-white" x-text="stats.total">0</span>
                </div>
                <p class="text-sm text-gray-400 font-semibold">إجمالي البرامج</p>
            </div>
        </div>

        <div class="rounded-xl p-6 relative overflow-hidden transition-colors" :class="darkMode ? 'glass-dark' : 'glass-light-soft'" group hover:scale-105 transition-transform duration-300">
            <div class="absolute top-0 right-0 w-24 h-24 bg-green-500/20 rounded-full -translate-y-1/2 translate-x-1/2 group-hover:scale-150 transition-transform duration-500"></div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-green-500 to-green-600 flex items-center justify-center">
                        <i data-lucide="play-circle" class="w-6 h-6 text-white"></i>
                    </div>
                    <span class="text-2xl font-black text-white" x-text="stats.active">0</span>
                </div>
                <p class="text-sm text-gray-400 font-semibold">نشط</p>
            </div>
        </div>

        <div class="rounded-xl p-6 relative overflow-hidden transition-colors" :class="darkMode ? 'glass-dark' : 'glass-light-soft'" group hover:scale-105 transition-transform duration-300">
            <div class="absolute top-0 right-0 w-24 h-24 bg-purple-500/20 rounded-full -translate-y-1/2 translate-x-1/2 group-hover:scale-150 transition-transform duration-500"></div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-purple-500 to-purple-600 flex items-center justify-center">
                        <i data-lucide="film" class="w-6 h-6 text-white"></i>
                    </div>
                    <span class="text-2xl font-black text-white" x-text="stats.episodes">0</span>
                </div>
                <p class="text-sm text-gray-400 font-semibold">الحلقات</p>
            </div>
        </div>

        <div class="rounded-xl p-6 relative overflow-hidden transition-colors" :class="darkMode ? 'glass-dark' : 'glass-light-soft'" group hover:scale-105 transition-transform duration-300">
            <div class="absolute top-0 right-0 w-24 h-24 bg-orange-500/20 rounded-full -translate-y-1/2 translate-x-1/2 group-hover:scale-150 transition-transform duration-500"></div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-orange-500 to-orange-600 flex items-center justify-center">
                        <i data-lucide="eye" class="w-6 h-6 text-white"></i>
                    </div>
                    <span class="text-2xl font-black text-white" x-text="stats.views">0</span>
                </div>
                <p class="text-sm text-gray-400 font-semibold">المشاهدات</p>
            </div>
        </div>
    </div>

    {{-- Filters & Search --}}
    <div class="rounded-2xl p-6 transition-colors" :class="darkMode ? \'glass-dark\' : \'glass-light-soft\'">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="md:col-span-2">
                <div class="relative">
                    <i data-lucide="search" class="absolute right-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400"></i>
                    <input
                        type="text"
                        x-model="filters.search"
                        @input.debounce.500ms="filterPrograms"
                        placeholder="البحث عن برنامج..."
                        class="w-full pr-12 pl-4 py-3 rounded-xl transition-all" :class="darkMode ? 'bg-white/5 border border-white/10 text-white' : 'glass-light-strong text-gray-900' placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                </div>
            </div>

            <div>
                <select
                    x-model="filters.status"
                    @change="filterPrograms"
                    class="w-full px-4 py-3 rounded-xl transition-all" :class="darkMode ? 'bg-white/5 border border-white/10 text-white' : 'glass-light-strong text-gray-900' focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                    <option value="">كل الحالات</option>
                    <option value="active">نشط</option>
                    <option value="completed">مكتمل</option>
                    <option value="upcoming">قادم</option>
                </select>
            </div>
        </div>
    </div>

    {{-- Programs Grid --}}
    <div x-show="!loading" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <template x-if="items.length === 0">
            <div class="col-span-full glass-dark rounded-2xl p-16 text-center">
                <div class="w-24 h-24 bg-gradient-to-br from-blue-500/20 to-green-500/20 rounded-full flex items-center justify-center mb-4 mx-auto">
                    <i data-lucide="video" class="w-12 h-12 text-gray-500"></i>
                </div>
                <p class="text-white font-bold text-xl mb-2">لا توجد برامج</p>
                <p class="text-gray-400 mb-6">ابدأ بإضافة برنامج جديد الآن</p>
                <a href="{{ route('admin.media-programs.create') }}" class="px-6 py-3 gradient-primary text-white rounded-xl font-bold shadow-lg inline-flex items-center space-x-2 space-x-reverse">
                    <i data-lucide="plus-circle" class="w-5 h-5"></i>
                    <span>برنامج جديد</span>
                </a>
            </div>
        </template>

        <template x-for="program in items" :key="program.id">
            <div class="rounded-2xl overflow-hidden transition-colors" :class="darkMode ? 'glass-dark' : 'glass-light-soft'" hover:scale-105 transition-transform duration-300 group">
                <div class="relative aspect-video">
                    <img :src="program.thumbnail || '/placeholder.jpg'" :alt="program.title" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/80 to-transparent"></div>
                    <div class="absolute top-4 right-4">
                        <span class="px-3 py-1 rounded-full text-xs font-bold bg-gradient-to-r from-blue-500 to-green-500 text-white">
                            <span x-text="program.total_episodes"></span> حلقة
                        </span>
                    </div>
                </div>

                <div class="p-6">
                    <h3 class="text-xl font-bold text-white mb-2 line-clamp-1" x-text="program.title"></h3>
                    <p class="text-gray-400 text-sm mb-4 line-clamp-2" x-text="program.description || 'لا يوجد وصف'"></p>

                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center space-x-1 space-x-reverse text-gray-400">
                            <i data-lucide="eye" class="w-4 h-4"></i>
                            <span class="text-sm font-semibold" x-text="program.total_views"></span>
                        </div>
                        <span :class="program.status === 'active' ? 'text-green-400' : 'text-gray-400'" class="text-sm font-semibold" x-text="program.status === 'active' ? 'نشط' : (program.status === 'completed' ? 'مكتمل' : 'قادم')"></span>
                    </div>

                    <div class="flex items-center space-x-2 space-x-reverse">
                        <a :href="`/su/portal/v1/admin/media-programs/${program.id}`" class="flex-1 px-4 py-2 bg-blue-500/20 hover:bg-blue-500/30 text-blue-400 rounded-lg transition-all text-center font-semibold">
                            عرض
                        </a>
                        <a :href="`/su/portal/v1/admin/media-programs/${program.id}/edit`" class="flex-1 px-4 py-2 bg-green-500/20 hover:bg-green-500/30 text-green-400 rounded-lg transition-all text-center font-semibold">
                            تعديل
                        </a>
                    </div>
                </div>
            </div>
        </template>
    </div>

    {{-- Loading Skeleton --}}
    <div x-show="loading" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div class="rounded-2xl overflow-hidden transition-colors" :class="darkMode ? 'glass-dark' : 'glass-light-soft'" animate-pulse" x-for="i in 6">
            <div class="aspect-video bg-white/10"></div>
            <div class="p-6 space-y-3">
                <div class="h-6 bg-white/10 rounded"></div>
                <div class="h-4 bg-white/10 rounded w-3/4"></div>
                <div class="flex gap-2">
                    <div class="h-10 bg-white/10 rounded flex-1"></div>
                    <div class="h-10 bg-white/10 rounded flex-1"></div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function mediaProgramsManager() {
    return {
        loading: true,
        items: [],
        stats: {
            total: 0,
            active: 0,
            episodes: 0,
            views: 0
        },
        filters: {
            search: '',
            status: ''
        },

        async init() {
            await this.loadData();
            this.loading = false;
            this.$nextTick(() => lucide.createIcons());
        },

        async loadData() {
            try {
                const params = new URLSearchParams(this.filters);
                const response = await fetch(`/su/portal/v1/admin/media-programs?${params}`, {
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
                console.error('Error loading programs:', error);
            }
        },

        filterPrograms() {
            this.loadData();
        }
    };
}
</script>
@endpush
