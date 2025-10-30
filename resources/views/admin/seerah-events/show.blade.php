@extends('layouts.admin')

@section('title', 'عرض الحدث')

@section('content')
<div x-data="{ activeTab: 'ar' }">
    <div class="rounded-2xl p-6 transition-colors" :class="darkMode ? \'glass-dark\' : \'glass-light-soft\'">
        <div class="absolute top-0 right-0 w-96 h-96 bg-gradient-to-br from-blue-500/20 to-green-500/20 rounded-full blur-3xl -translate-y-1/2 translate-x-1/2"></div>

        <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between">
            <div class="flex-1">
                <div class="flex items-center gap-3 mb-4">
                    <a href="{{ route('admin.seerah-events.index') }}" class="p-2 bg-white/10 hover:bg-white/20 rounded-lg transition-all">
                        <i data-lucide="arrow-right" class="w-5 h-5 text-white"></i>
                    </a>
                    <h1 class="text-3xl font-black text-white gradient-text">{{ $seerahEvent->title['ar'] ?? $seerahEvent->title['en'] ?? 'حدث' }}</h1>
                </div>
                <p class="text-gray-400 flex items-center space-x-2 space-x-reverse">
                    <i data-lucide="calendar" class="w-4 h-4"></i>
                    <span>{{ $seerahEvent->hijri_date ?? 'غير محدد' }}</span>
                </p>
            </div>

            <div class="mt-4 md:mt-0 flex items-center space-x-3 space-x-reverse">
                <a href="{{ route('admin.seerah-events.edit', $seerahEvent) }}" class="px-5 py-3 bg-green-500/20 hover:bg-green-500/30 text-green-400 rounded-xl font-semibold transition-all duration-300 flex items-center space-x-2 space-x-reverse border border-green-500/30 hover:scale-105">
                    <i data-lucide="edit" class="w-4 h-4"></i>
                    <span>تعديل</span>
                </a>

                <form action="{{ route('admin.seerah-events.destroy', $seerahEvent) }}" method="POST" onsubmit="return confirm('هل أنت متأكد من حذف هذا الحدث؟')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="px-5 py-3 bg-red-500/20 hover:bg-red-500/30 text-red-400 rounded-xl font-semibold transition-all duration-300 flex items-center space-x-2 space-x-reverse border border-red-500/30 hover:scale-105">
                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                        <span>حذف</span>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="rounded-xl p-6 relative overflow-hidden transition-colors" :class="darkMode ? 'glass-dark' : 'glass-light-soft' group hover:scale-105 transition-transform duration-300">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-400 mb-1">نوع الحدث</p>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-bold bg-blue-500/20 text-blue-400 border border-blue-500/30">
                        {{ $seerahEvent->type ?? '-' }}
                    </span>
                </div>
                <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center">
                    <i data-lucide="flag" class="w-6 h-6 text-white"></i>
                </div>
            </div>
        </div>

        <div class="rounded-xl p-6 relative overflow-hidden transition-colors" :class="darkMode ? 'glass-dark' : 'glass-light-soft' group hover:scale-105 transition-transform duration-300">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-400 mb-1">الأهمية</p>
                    <span class="text-lg font-bold text-white">{{ $seerahEvent->importance ?? '-' }}</span>
                </div>
                <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-yellow-500 to-yellow-600 flex items-center justify-center">
                    <i data-lucide="star" class="w-6 h-6 text-white"></i>
                </div>
            </div>
        </div>

        <div class="rounded-xl p-6 relative overflow-hidden transition-colors" :class="darkMode ? 'glass-dark' : 'glass-light-soft' group hover:scale-105 transition-transform duration-300">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-400 mb-1">عدد المشاركين</p>
                    <span class="text-2xl font-black text-white">{{ $seerahEvent->participants->count() ?? 0 }}</span>
                </div>
                <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-green-500 to-green-600 flex items-center justify-center">
                    <i data-lucide="users" class="w-6 h-6 text-white"></i>
                </div>
            </div>
        </div>

        <div class="rounded-xl p-6 relative overflow-hidden transition-colors" :class="darkMode ? 'glass-dark' : 'glass-light-soft' group hover:scale-105 transition-transform duration-300">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-400 mb-1">تاريخ الإضافة</p>
                    <span class="text-sm font-semibold text-white">{{ $seerahEvent->created_at->format('Y-m-d') }}</span>
                </div>
                <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-purple-500 to-purple-600 flex items-center justify-center">
                    <i data-lucide="calendar" class="w-6 h-6 text-white"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="rounded-2xl p-6 transition-colors" :class="darkMode ? \'glass-dark\' : \'glass-light-soft\'">
        <h2 class="text-xl font-bold text-white mb-6 flex items-center gap-2">
            <i data-lucide="info" class="w-6 h-6"></i>
            المعلومات الأساسية
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-semibold text-gray-400 mb-2">التاريخ الهجري</label>
                <p class="text-white font-semibold px-4 py-3 bg-white/5 rounded-lg">{{ $seerahEvent->hijri_date ?? '-' }}</p>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-400 mb-2">التاريخ الميلادي</label>
                <p class="text-white font-semibold px-4 py-3 bg-white/5 rounded-lg">{{ $seerahEvent->gregorian_date ? $seerahEvent->gregorian_date->format('Y-m-d') : '-' }}</p>
            </div>

            @if($seerahEvent->location)
            <div>
                <label class="block text-sm font-semibold text-gray-400 mb-2">المكان</label>
                <p class="text-white font-semibold px-4 py-3 bg-white/5 rounded-lg">{{ $seerahEvent->location['ar'] ?? $seerahEvent->location['en'] ?? '-' }}</p>
            </div>
            @endif
        </div>
    </div>

    <div class="rounded-2xl p-6 transition-colors" :class="darkMode ? \'glass-dark\' : \'glass-light-soft\'">
        <h2 class="text-xl font-bold text-white mb-6 flex items-center gap-2">
            <i data-lucide="languages" class="w-6 h-6"></i>
            الوصف
        </h2>

        <div class="flex flex-wrap gap-2 mb-6 border-b border-white/10 pb-4">
            @foreach(['ar' => 'العربية', 'en' => 'English', 'fr' => 'Français'] as $code => $name)
            <button @click="activeTab = '{{ $code }}'" :class="activeTab === '{{ $code }}' ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-lg' : 'bg-white/10 text-gray-300 hover:bg-white/20'" class="px-6 py-2 rounded-full font-semibold transition-all duration-300">
                {{ $name }}
            </button>
            @endforeach
        </div>

        @foreach(['ar', 'en', 'fr'] as $lang)
        <div x-show="activeTab === '{{ $lang }}'" x-transition>
            <p class="text-gray-200 leading-relaxed" dir="{{ $lang === 'ar' ? 'rtl' : 'ltr' }}">
                @if($seerahEvent->description && isset($seerahEvent->description[$lang]))
                    {{ $seerahEvent->description[$lang] }}
                @else
                    <span class="text-gray-500">غير متوفر</span>
                @endif
            </p>
        </div>
        @endforeach
    </div>

    @if($seerahEvent->participants->count() > 0)
    <div class="rounded-2xl p-6 transition-colors" :class="darkMode ? \'glass-dark\' : \'glass-light-soft\'">
        <h2 class="text-xl font-bold text-white mb-6 flex items-center gap-2">
            <i data-lucide="users" class="w-6 h-6"></i>
            المشاركون
        </h2>

        <div class="space-y-3">
            @foreach($seerahEvent->participants as $participant)
                <div class="flex items-center gap-4 p-3 bg-white/5 rounded-lg border border-white/10">
                    <div class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-500 to-green-500 flex items-center justify-center text-white font-bold">
                        {{ substr($participant->name['ar'] ?? 'X', 0, 1) }}
                    </div>
                    <div class="flex-1">
                        <p class="text-white font-semibold">{{ $participant->name['ar'] ?? $participant->name['en'] }}</p>
                        <p class="text-xs text-gray-400">{{ $participant->pivot->role ?? '-' }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    @endif

    <div class="glass-dark rounded-2xl p-6">
        <h2 class="text-xl font-bold text-white mb-6 flex items-center gap-2">
            <i data-lucide="database" class="w-6 h-6"></i>
            معلومات إضافية
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
                <label class="block text-sm font-semibold text-gray-400 mb-2">تاريخ الإنشاء</label>
                <p class="text-white font-semibold px-4 py-3 bg-white/5 rounded-lg">{{ $seerahEvent->created_at->format('Y-m-d H:i') }}</p>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-400 mb-2">آخر تحديث</label>
                <p class="text-white font-semibold px-4 py-3 bg-white/5 rounded-lg">{{ $seerahEvent->updated_at->format('Y-m-d H:i') }}</p>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-400 mb-2">معرّف الحدث</label>
                <p class="text-white font-semibold px-4 py-3 bg-white/5 rounded-lg">{{ $seerahEvent->id }}</p>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    lucide.createIcons();
</script>
@endpush
