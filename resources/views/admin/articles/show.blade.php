@extends('layouts.admin')

@section('title', 'عرض المقالة - محسّن')

@section('content')
<div x-data="{ darkMode: localStorage.getItem('darkMode') === 'true', activeTab: 'ar' }">
    {{-- Header with Actions --}}
    <div class="rounded-2xl p-6 transition-colors" :class="darkMode ? \'glass-dark\' : \'glass-light-soft\'">
        <div class="absolute top-0 right-0 w-96 h-96 bg-gradient-to-br from-blue-500/20 to-green-500/20 rounded-full blur-3xl -translate-y-1/2 translate-x-1/2"></div>

        <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between">
            <div class="flex-1">
                <div class="flex items-center gap-3 mb-4">
                    <a href="{{ route('admin.articles.index') }}" class="p-2 bg-white/10 hover:bg-white/20 rounded-lg transition-all">
                        <i data-lucide="arrow-right" class="w-5 h-5 text-white"></i>
                    </a>
                    <h1 class="text-3xl font-black text-white gradient-text">{{ $article->title['ar'] ?? $article->title['en'] ?? 'عرض المقالة' }}</h1>
                </div>
                <p class="text-gray-400 flex items-center space-x-2 space-x-reverse">
                    <i data-lucide="file-text" class="w-4 h-4"></i>
                    <span>تفاصيل المقالة كاملة</span>
                </p>
            </div>

            <div class="mt-4 md:mt-0 flex items-center space-x-3 space-x-reverse">
                <a href="{{ route('admin.articles.edit', $article) }}" class="px-5 py-3 bg-green-500/20 hover:bg-green-500/30 text-green-400 rounded-xl font-semibold transition-all duration-300 flex items-center space-x-2 space-x-reverse border border-green-500/30 hover:scale-105">
                    <i data-lucide="edit" class="w-4 h-4"></i>
                    <span>تعديل</span>
                </a>

                <form action="{{ route('admin.articles.destroy', $article) }}" method="POST" onsubmit="return confirm('هل أنت متأكد من حذف هذه المقالة؟')">
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

    {{-- Article Info Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        {{-- Status --}}
        <div class="rounded-xl p-6 relative overflow-hidden transition-colors" :class="darkMode ? 'glass-dark' : 'glass-light-soft' group hover:scale-105 transition-transform duration-300">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-400 mb-1">الحالة</p>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-bold {{ $article->status === 'published' ? 'bg-green-500/20 text-green-400 border border-green-500/30' : ($article->status === 'draft' ? 'bg-yellow-500/20 text-yellow-400 border border-yellow-500/30' : 'bg-gray-500/20 text-gray-400 border border-gray-500/30') }}">
                        {{ $article->status === 'published' ? 'منشور' : ($article->status === 'draft' ? 'مسودة' : 'مؤرشف') }}
                    </span>
                </div>
                <div class="w-12 h-12 rounded-lg {{ $article->status === 'published' ? 'bg-gradient-to-br from-green-500 to-green-600' : 'bg-gradient-to-br from-gray-500 to-gray-600' }} flex items-center justify-center">
                    <i data-lucide="{{ $article->status === 'published' ? 'check-circle' : 'edit' }}" class="w-6 h-6 text-white"></i>
                </div>
            </div>
        </div>

        {{-- Views --}}
        <div class="rounded-xl p-6 relative overflow-hidden transition-colors" :class="darkMode ? 'glass-dark' : 'glass-light-soft' group hover:scale-105 transition-transform duration-300">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-400 mb-1">المشاهدات</p>
                    <span class="text-2xl font-black text-white">{{ $article->views ?? 0 }}</span>
                </div>
                <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center">
                    <i data-lucide="eye" class="w-6 h-6 text-white"></i>
                </div>
            </div>
        </div>

        {{-- Featured --}}
        <div class="rounded-xl p-6 relative overflow-hidden transition-colors" :class="darkMode ? 'glass-dark' : 'glass-light-soft' group hover:scale-105 transition-transform duration-300">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-400 mb-1">مميز</p>
                    <span class="text-lg font-bold text-white">{{ $article->is_featured ? 'نعم' : 'لا' }}</span>
                </div>
                <div class="w-12 h-12 rounded-lg bg-gradient-to-br {{ $article->is_featured ? 'from-yellow-500 to-yellow-600' : 'from-gray-500 to-gray-600' }} flex items-center justify-center">
                    <i data-lucide="{{ $article->is_featured ? 'star' : 'minus' }}" class="w-6 h-6 text-white"></i>
                </div>
            </div>
        </div>

        {{-- Date --}}
        <div class="rounded-xl p-6 relative overflow-hidden transition-colors" :class="darkMode ? 'glass-dark' : 'glass-light-soft' group hover:scale-105 transition-transform duration-300">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-400 mb-1">تاريخ النشر</p>
                    <span class="text-sm font-semibold text-white">{{ $article->published_at ? $article->published_at->format('Y-m-d') : 'غير محدد' }}</span>
                </div>
                <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-purple-500 to-purple-600 flex items-center justify-center">
                    <i data-lucide="calendar" class="w-6 h-6 text-white"></i>
                </div>
            </div>
        </div>
    </div>

    {{-- Featured Image --}}
    @if($article->featured_image)
    <div class="rounded-2xl p-6 transition-colors" :class="darkMode ? \'glass-dark\' : \'glass-light-soft\'">
        <h2 class="text-xl font-bold text-white mb-4 flex items-center gap-2">
            <i data-lucide="image" class="w-6 h-6"></i>
            الصورة المميزة
        </h2>
        <div class="rounded-xl overflow-hidden">
            <img src="{{ $article->featured_image }}" alt="{{ $article->title['ar'] ?? 'Article' }}" class="w-full h-auto object-cover">
        </div>
    </div>
    @endif

    {{-- Basic Information --}}
    <div class="rounded-2xl p-6 transition-colors" :class="darkMode ? \'glass-dark\' : \'glass-light-soft\'">
        <h2 class="text-xl font-bold text-white mb-6 flex items-center gap-2">
            <i data-lucide="info" class="w-6 h-6"></i>
            المعلومات الأساسية
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-semibold text-gray-400 mb-2">الرابط الثابت (Slug)</label>
                <p class="text-white font-semibold px-4 py-3 bg-white/5 rounded-lg">{{ $article->slug }}</p>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-400 mb-2">التصنيف</label>
                <p class="text-white font-semibold px-4 py-3 bg-white/5 rounded-lg">{{ $article->category->name['ar'] ?? $article->category->name['en'] ?? 'غير محدد' }}</p>
            </div>

            @if($article->series)
            <div>
                <label class="block text-sm font-semibold text-gray-400 mb-2">السلسلة</label>
                <p class="text-white font-semibold px-4 py-3 bg-white/5 rounded-lg">{{ $article->series->name['ar'] ?? $article->series->name['en'] ?? 'غير محدد' }}</p>
            </div>
            @endif

            <div>
                <label class="block text-sm font-semibold text-gray-400 mb-2">الكاتب</label>
                <p class="text-white font-semibold px-4 py-3 bg-white/5 rounded-lg">{{ $article->author->name ?? 'غير محدد' }}</p>
            </div>

            @if($article->tags && $article->tags->count() > 0)
            <div class="md:col-span-2">
                <label class="block text-sm font-semibold text-gray-400 mb-2">الوسوم</label>
                <div class="flex flex-wrap gap-2">
                    @foreach($article->tags as $tag)
                        <span class="px-3 py-1 bg-blue-500/20 text-blue-300 rounded-full text-sm font-semibold border border-blue-500/30">
                            {{ $tag->name['ar'] ?? $tag->name['en'] }}
                        </span>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </div>

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
                {{-- Title --}}
                <div>
                    <label class="block text-sm font-semibold text-gray-400 mb-2">العنوان</label>
                    <p class="text-white font-semibold px-4 py-3 bg-white/5 rounded-lg" dir="{{ in_array($lang, ['ar', 'ur']) ? 'rtl' : 'ltr' }}">
                        {{ $article->title[$lang] ?? 'غير متوفر' }}
                    </p>
                </div>

                {{-- Excerpt --}}
                @if($article->excerpt && isset($article->excerpt[$lang]))
                <div>
                    <label class="block text-sm font-semibold text-gray-400 mb-2">الملخص</label>
                    <p class="text-gray-300 px-4 py-3 bg-white/5 rounded-lg" dir="{{ in_array($lang, ['ar', 'ur']) ? 'rtl' : 'ltr' }}">
                        {{ $article->excerpt[$lang] }}
                    </p>
                </div>
                @endif

                {{-- Content --}}
                <div>
                    <label class="block text-sm font-semibold text-gray-400 mb-2">المحتوى</label>
                    <div class="text-gray-200 px-6 py-4 bg-white/5 rounded-lg prose prose-invert max-w-none" dir="{{ in_array($lang, ['ar', 'ur']) ? 'rtl' : 'ltr' }}">
                        {!! $article->content[$lang] ?? '<p class="text-gray-500">غير متوفر</p>' !!}
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    {{-- Metadata --}}
    <div class="glass-dark rounded-2xl p-6">
        <h2 class="text-xl font-bold text-white mb-6 flex items-center gap-2">
            <i data-lucide="database" class="w-6 h-6"></i>
            معلومات إضافية
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
                <label class="block text-sm font-semibold text-gray-400 mb-2">تاريخ الإنشاء</label>
                <p class="text-white font-semibold px-4 py-3 bg-white/5 rounded-lg">{{ $article->created_at->format('Y-m-d H:i') }}</p>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-400 mb-2">آخر تحديث</label>
                <p class="text-white font-semibold px-4 py-3 bg-white/5 rounded-lg">{{ $article->updated_at->format('Y-m-d H:i') }}</p>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-400 mb-2">معرّف المقالة</label>
                <p class="text-white font-semibold px-4 py-3 bg-white/5 rounded-lg">{{ $article->id }}</p>
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
