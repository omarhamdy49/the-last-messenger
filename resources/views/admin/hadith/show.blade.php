@extends('layouts.admin')

@section('title', 'عرض الحديث - محسّن')

@section('content')
<div x-data="{ darkMode: localStorage.getItem('darkMode') === 'true', activeTab: 'ar' }">
    {{-- Header with Actions --}}
    <div class="rounded-2xl p-6 transition-colors" :class="darkMode ? \'glass-dark\' : \'glass-light-soft\'">
        <div class="absolute top-0 right-0 w-96 h-96 bg-gradient-to-br from-blue-500/20 to-green-500/20 rounded-full blur-3xl -translate-y-1/2 translate-x-1/2"></div>

        <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between">
            <div class="flex-1">
                <div class="flex items-center gap-3 mb-4">
                    <a href="{{ route('admin.hadith.index') }}" class="p-2 bg-white/10 hover:bg-white/20 rounded-lg transition-all">
                        <i data-lucide="arrow-right" class="w-5 h-5 text-white"></i>
                    </a>
                    <h1 class="text-3xl font-black text-white gradient-text">الحديث رقم {{ $hadith->hadith_number }}</h1>
                </div>
                <p class="text-gray-400 flex items-center space-x-2 space-x-reverse">
                    <i data-lucide="book-open" class="w-4 h-4"></i>
                    <span>{{ $hadith->book->name['ar'] ?? $hadith->book->name['en'] }}</span>
                </p>
            </div>

            <div class="mt-4 md:mt-0 flex items-center space-x-3 space-x-reverse">
                <a href="{{ route('admin.hadith.edit', $hadith) }}" class="px-5 py-3 bg-green-500/20 hover:bg-green-500/30 text-green-400 rounded-xl font-semibold transition-all duration-300 flex items-center space-x-2 space-x-reverse border border-green-500/30 hover:scale-105">
                    <i data-lucide="edit" class="w-4 h-4"></i>
                    <span>تعديل</span>
                </a>

                <form action="{{ route('admin.hadith.destroy', $hadith) }}" method="POST" onsubmit="return confirm('هل أنت متأكد من حذف هذا الحديث؟')">
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

    {{-- Hadith Info Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        {{-- Grade --}}
        <div class="rounded-xl p-6 relative overflow-hidden transition-colors" :class="darkMode ? 'glass-dark' : 'glass-light-soft' group hover:scale-105 transition-transform duration-300">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-400 mb-1">الدرجة</p>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-bold {{ $hadith->grade->value === 'sahih' ? 'bg-green-500/20 text-green-400 border border-green-500/30' : 'bg-yellow-500/20 text-yellow-400 border border-yellow-500/30' }}">
                        {{ $hadith->grade->getLabel() }}
                    </span>
                </div>
                <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-green-500 to-green-600 flex items-center justify-center">
                    <i data-lucide="shield-check" class="w-6 h-6 text-white"></i>
                </div>
            </div>
        </div>

        {{-- Hadith Number --}}
        <div class="rounded-xl p-6 relative overflow-hidden transition-colors" :class="darkMode ? 'glass-dark' : 'glass-light-soft' group hover:scale-105 transition-transform duration-300">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-400 mb-1">رقم الحديث</p>
                    <span class="text-2xl font-black text-white">{{ $hadith->hadith_number }}</span>
                </div>
                <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center">
                    <i data-lucide="hash" class="w-6 h-6 text-white"></i>
                </div>
            </div>
        </div>

        {{-- Views --}}
        <div class="rounded-xl p-6 relative overflow-hidden transition-colors" :class="darkMode ? 'glass-dark' : 'glass-light-soft' group hover:scale-105 transition-transform duration-300">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-400 mb-1">المشاهدات</p>
                    <span class="text-2xl font-black text-white">{{ $hadith->views_count ?? 0 }}</span>
                </div>
                <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-purple-500 to-purple-600 flex items-center justify-center">
                    <i data-lucide="eye" class="w-6 h-6 text-white"></i>
                </div>
            </div>
        </div>

        {{-- Book --}}
        <div class="rounded-xl p-6 relative overflow-hidden transition-colors" :class="darkMode ? 'glass-dark' : 'glass-light-soft' group hover:scale-105 transition-transform duration-300">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-400 mb-1">الكتاب</p>
                    <span class="text-sm font-bold text-white line-clamp-2">{{ $hadith->book->name['ar'] ?? $hadith->book->name['en'] }}</span>
                </div>
                <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-orange-500 to-orange-600 flex items-center justify-center">
                    <i data-lucide="book" class="w-6 h-6 text-white"></i>
                </div>
            </div>
        </div>
    </div>

    {{-- Arabic Text --}}
    <div class="rounded-2xl p-6 transition-colors" :class="darkMode ? \'glass-dark\' : \'glass-light-soft\'">
        <h2 class="text-xl font-bold text-white mb-4 flex items-center gap-2">
            <i data-lucide="text" class="w-6 h-6"></i>
            النص العربي
        </h2>
        <div class="bg-white/5 rounded-xl p-6 border border-white/10">
            <p class="text-white text-lg leading-relaxed font-semibold" dir="rtl">{{ $hadith->arabic_text }}</p>
        </div>
    </div>

    {{-- Translations --}}
    @if($hadith->translations)
    <div class="rounded-2xl p-6 transition-colors" :class="darkMode ? \'glass-dark\' : \'glass-light-soft\'">
        <h2 class="text-xl font-bold text-white mb-6 flex items-center gap-2">
            <i data-lucide="languages" class="w-6 h-6"></i>
            الترجمات
        </h2>

        {{-- Language Tabs --}}
        <div class="flex flex-wrap gap-2 mb-6 border-b border-white/10 pb-4">
            @foreach(['ar' => 'العربية', 'en' => 'English', 'fr' => 'Français', 'tr' => 'Türkçe', 'ur' => 'اردو'] as $code => $name)
                @if(isset($hadith->translations[$code]))
                <button @click="activeTab = '{{ $code }}'" :class="activeTab === '{{ $code }}' ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-lg' : 'bg-white/10 text-gray-300 hover:bg-white/20'" class="px-6 py-2 rounded-full font-semibold transition-all duration-300">
                    {{ $name }}
                </button>
                @endif
            @endforeach
        </div>

        {{-- Translation Content --}}
        @foreach(['ar', 'en', 'fr', 'tr', 'ur'] as $lang)
            @if(isset($hadith->translations[$lang]))
            <div x-show="activeTab === '{{ $lang }}'" x-transition>
                <div class="bg-white/5 rounded-xl p-6 border border-white/10">
                    <p class="text-gray-200 text-lg leading-relaxed" dir="{{ in_array($lang, ['ar', 'ur']) ? 'rtl' : 'ltr' }}">
                        {{ $hadith->translations[$lang] }}
                    </p>
                </div>
            </div>
            @endif
        @endforeach
    </div>
    @endif

    {{-- Narrator Chain --}}
    @if($hadith->narrator_chain || $hadith->narrators->count() > 0)
    <div class="rounded-2xl p-6 transition-colors" :class="darkMode ? \'glass-dark\' : \'glass-light-soft\'">
        <h2 class="text-xl font-bold text-white mb-4 flex items-center gap-2">
            <i data-lucide="users" class="w-6 h-6"></i>
            سلسلة الرواة
        </h2>

        @if($hadith->narrator_chain)
        <div class="bg-white/5 rounded-xl p-6 border border-white/10 mb-4">
            <p class="text-gray-200 leading-relaxed" dir="rtl">{{ $hadith->narrator_chain }}</p>
        </div>
        @endif

        @if($hadith->narrators->count() > 0)
        <div class="flex flex-wrap gap-3">
            @foreach($hadith->narrators as $narrator)
            <div class="glass rounded-lg px-4 py-2 flex items-center gap-2">
                <div class="w-8 h-8 rounded-full bg-gradient-to-br from-blue-500 to-green-500 flex items-center justify-center text-white font-bold text-sm">
                    {{ $loop->iteration }}
                </div>
                <span class="text-white font-semibold">{{ $narrator->name['ar'] ?? $narrator->name['en'] }}</span>
            </div>
            @endforeach
        </div>
        @endif
    </div>
    @endif

    {{-- Chapter Information --}}
    @if($hadith->chapter)
    <div class="rounded-2xl p-6 transition-colors" :class="darkMode ? \'glass-dark\' : \'glass-light-soft\'">
        <h2 class="text-xl font-bold text-white mb-4 flex items-center gap-2">
            <i data-lucide="bookmark" class="w-6 h-6"></i>
            الباب
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-semibold text-gray-400 mb-2">اسم الباب</label>
                <p class="text-white font-semibold px-4 py-3 bg-white/5 rounded-lg">{{ $hadith->chapter->name['ar'] ?? $hadith->chapter->name['en'] }}</p>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-400 mb-2">رقم الباب</label>
                <p class="text-white font-semibold px-4 py-3 bg-white/5 rounded-lg">{{ $hadith->chapter->chapter_number }}</p>
            </div>
        </div>
    </div>
    @endif

    {{-- Reference URL --}}
    @if($hadith->reference_url)
    <div class="rounded-2xl p-6 transition-colors" :class="darkMode ? \'glass-dark\' : \'glass-light-soft\'">
        <h2 class="text-xl font-bold text-white mb-4 flex items-center gap-2">
            <i data-lucide="link" class="w-6 h-6"></i>
            مرجع خارجي
        </h2>
        <a href="{{ $hadith->reference_url }}" target="_blank" class="text-brand-accent hover:text-blue-400 underline">
            {{ $hadith->reference_url }}
        </a>
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
                <label class="block text-sm font-semibold text-gray-400 mb-2">تاريخ الإضافة</label>
                <p class="text-white font-semibold px-4 py-3 bg-white/5 rounded-lg">{{ $hadith->created_at->format('Y-m-d H:i') }}</p>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-400 mb-2">آخر تحديث</label>
                <p class="text-white font-semibold px-4 py-3 bg-white/5 rounded-lg">{{ $hadith->updated_at->format('Y-m-d H:i') }}</p>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-400 mb-2">معرّف الحديث</label>
                <p class="text-white font-semibold px-4 py-3 bg-white/5 rounded-lg">{{ $hadith->id }}</p>
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
