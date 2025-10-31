@extends('layouts.admin')

@section('title', 'عرض البرنامج - محسّن')

@section('content')
<div x-data="{ darkMode: localStorage.getItem('darkMode') === 'true', activeTab: 'ar' }">
    {{-- Header with Banner --}}
    @if($program->banner_image)
    <div class="relative h-64 rounded-2xl overflow-hidden mb-8">
        <img src="{{ $program->banner_image }}" alt="{{ $program->title['ar'] ?? 'Banner' }}" class="w-full h-full object-cover">
        <div class="absolute inset-0 bg-gradient-to-t from-black via-black/50 to-transparent"></div>
        <div class="absolute bottom-6 right-6 left-6">
            <h1 class="text-4xl font-black text-white mb-2">{{ $program->title['ar'] ?? $program->title['en'] }}</h1>
            <p class="text-gray-300">{{ $program->category->name['ar'] ?? $program->category->name['en'] }}</p>
        </div>
    </div>
    @endif

    {{-- Action Buttons --}}
    <div class="flex items-center justify-between mb-8">
        <a href="{{ route('admin.media-programs.index') }}" class="px-5 py-3 bg-white/10 hover:bg-white/20 text-white rounded-xl font-semibold transition-all flex items-center space-x-2 space-x-reverse">
            <i data-lucide="arrow-right" class="w-4 h-4"></i>
            <span>العودة</span>
        </a>

        <div class="flex items-center space-x-3 space-x-reverse">
            <a href="{{ route('admin.media-programs.edit', $program) }}" class="px-5 py-3 bg-green-500/20 hover:bg-green-500/30 text-green-400 rounded-xl font-semibold transition-all flex items-center space-x-2 space-x-reverse border border-green-500/30 hover:scale-105">
                <i data-lucide="edit" class="w-4 h-4"></i>
                <span>تعديل</span>
            </a>

            <form action="{{ route('admin.media-programs.destroy', $program) }}" method="POST" onsubmit="return confirm('هل أنت متأكد من حذف هذا البرنامج؟')">
                @csrf
                @method('DELETE')
                <button type="submit" class="px-5 py-3 bg-red-500/20 hover:bg-red-500/30 text-red-400 rounded-xl font-semibold transition-all flex items-center space-x-2 space-x-reverse border border-red-500/30 hover:scale-105">
                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                    <span>حذف</span>
                </button>
            </form>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="glass-dark rounded-xl p-6 group hover:scale-105 transition-transform">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-400 mb-1">الحالة</p>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-bold {{ $program->status->value === 'active' ? 'bg-green-500/20 text-green-400 border border-green-500/30' : 'bg-gray-500/20 text-gray-400 border border-gray-500/30' }}">
                        {{ $program->status->value === 'active' ? 'نشط' : ($program->status->value === 'completed' ? 'مكتمل' : 'قادم') }}
                    </span>
                </div>
                <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-green-500 to-green-600 flex items-center justify-center">
                    <i data-lucide="play-circle" class="w-6 h-6 text-white"></i>
                </div>
            </div>
        </div>

        <div class="glass-dark rounded-xl p-6 group hover:scale-105 transition-transform">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-400 mb-1">الحلقات</p>
                    <span class="text-2xl font-black text-white">{{ $program->total_episodes }}</span>
                </div>
                <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center">
                    <i data-lucide="film" class="w-6 h-6 text-white"></i>
                </div>
            </div>
        </div>

        <div class="glass-dark rounded-xl p-6 group hover:scale-105 transition-transform">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-400 mb-1">المشاهدات</p>
                    <span class="text-2xl font-black text-white">{{ number_format($program->total_views) }}</span>
                </div>
                <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-purple-500 to-purple-600 flex items-center justify-center">
                    <i data-lucide="eye" class="w-6 h-6 text-white"></i>
                </div>
            </div>
        </div>

        <div class="glass-dark rounded-xl p-6 group hover:scale-105 transition-transform">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-400 mb-1">التصنيف</p>
                    <span class="text-sm font-bold text-white line-clamp-2">{{ $program->category->name['ar'] ?? $program->category->name['en'] }}</span>
                </div>
                <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-orange-500 to-orange-600 flex items-center justify-center">
                    <i data-lucide="folder" class="w-6 h-6 text-white"></i>
                </div>
            </div>
        </div>
    </div>

    {{-- Thumbnail --}}
    @if($program->thumbnail)
    <div class="rounded-2xl p-6 transition-colors" :class="darkMode ? \'glass-dark\' : \'glass-light-soft\'">
        <h2 class="text-xl font-bold text-white mb-4 flex items-center gap-2">
            <i data-lucide="image" class="w-6 h-6"></i>
            الصورة المصغرة
        </h2>
        <img src="{{ $program->thumbnail }}" alt="{{ $program->title['ar'] ?? 'Thumbnail' }}" class="w-64 h-auto rounded-xl">
    </div>
    @endif

    {{-- Multi-Language Content --}}
    <div class="rounded-2xl p-6 transition-colors" :class="darkMode ? \'glass-dark\' : \'glass-light-soft\'">
        <h2 class="text-xl font-bold text-white mb-6 flex items-center gap-2">
            <i data-lucide="languages" class="w-6 h-6"></i>
            المحتوى متعدد اللغات
        </h2>

        {{-- Language Tabs --}}
        <div class="flex flex-wrap gap-2 mb-6 border-b border-white/10 pb-4">
            @foreach(['ar' => 'العربية', 'en' => 'English', 'fr' => 'Français', 'tr' => 'Türkçe', 'ur' => 'اردو'] as $code => $name)
            <button @click="activeTab = '{{ $code }}'" :class="activeTab === '{{ $code }}' ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-lg' : 'bg-white/10 text-gray-300 hover:bg-white/20'" class="px-6 py-2 rounded-full font-semibold transition-all duration-300">
                {{ $name }}
            </button>
            @endforeach
        </div>

        {{-- Content for Each Language --}}
        @foreach(['ar', 'en', 'fr', 'tr', 'ur'] as $lang)
        <div x-show="activeTab === '{{ $lang }}'" x-transition>
            <div class="space-y-6">
                <div>
                    <label class="block text-sm font-semibold text-gray-400 mb-2">العنوان</label>
                    <p class="text-white font-semibold px-4 py-3 bg-white/5 rounded-lg" dir="{{ in_array($lang, ['ar', 'ur']) ? 'rtl' : 'ltr' }}">
                        {{ $program->title[$lang] ?? 'غير متوفر' }}
                    </p>
                </div>

                @if($program->description && isset($program->description[$lang]))
                <div>
                    <label class="block text-sm font-semibold text-gray-400 mb-2">الوصف</label>
                    <p class="text-gray-300 px-4 py-3 bg-white/5 rounded-lg" dir="{{ in_array($lang, ['ar', 'ur']) ? 'rtl' : 'ltr' }}">
                        {{ $program->description[$lang] }}
                    </p>
                </div>
                @endif
            </div>
        </div>
        @endforeach
    </div>

    {{-- Episodes List --}}
    @if($program->episodes->count() > 0)
    <div class="rounded-2xl p-6 transition-colors" :class="darkMode ? \'glass-dark\' : \'glass-light-soft\'">
        <h2 class="text-xl font-bold text-white mb-6 flex items-center gap-2">
            <i data-lucide="list" class="w-6 h-6"></i>
            الحلقات ({{ $program->episodes->count() }})
        </h2>

        <div class="space-y-4">
            @foreach($program->episodes as $episode)
            <div class="bg-white/5 rounded-xl p-4 flex items-center justify-between hover:bg-white/10 transition-colors">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-blue-500 to-green-500 flex items-center justify-center text-white font-bold">
                        {{ $episode->episode_number }}
                    </div>
                    <div>
                        <h3 class="text-white font-bold">{{ $episode->title['ar'] ?? $episode->title['en'] }}</h3>
                        <p class="text-sm text-gray-400">{{ $episode->duration ?? 'N/A' }} دقيقة</p>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Metadata --}}
    <div class="glass-dark rounded-2xl p-6">
        <h2 class="text-xl font-bold text-white mb-6 flex items-center gap-2">
            <i data-lucide="database" class="w-6 h-6"></i>
            معلومات إضافية
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
                <label class="block text-sm font-semibold text-gray-400 mb-2">تاريخ الإنشاء</label>
                <p class="text-white font-semibold px-4 py-3 bg-white/5 rounded-lg">{{ $program->created_at->format('Y-m-d H:i') }}</p>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-400 mb-2">آخر تحديث</label>
                <p class="text-white font-semibold px-4 py-3 bg-white/5 rounded-lg">{{ $program->updated_at->format('Y-m-d H:i') }}</p>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-400 mb-2">الرابط الثابت</label>
                <p class="text-white font-semibold px-4 py-3 bg-white/5 rounded-lg">{{ $program->slug }}</p>
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
