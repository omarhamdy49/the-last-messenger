@extends('layouts.admin')

@section('title', 'عرض الصحابي')

@section('content')
<div x-data="{ activeTab: 'ar' }">
    <div class="rounded-2xl p-6 transition-colors" :class="darkMode ? \'glass-dark\' : \'glass-light-soft\'">
        <div class="absolute top-0 right-0 w-96 h-96 bg-gradient-to-br from-blue-500/20 to-green-500/20 rounded-full blur-3xl -translate-y-1/2 translate-x-1/2"></div>

        <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between">
            <div class="flex-1">
                <div class="flex items-center gap-3 mb-4">
                    <a href="{{ route('admin.sahaba.index') }}" class="p-2 bg-white/10 hover:bg-white/20 rounded-lg transition-all">
                        <i data-lucide="arrow-right" class="w-5 h-5 text-white"></i>
                    </a>
                    <h1 class="text-3xl font-black text-white gradient-text">{{ $sahabi->name['ar'] ?? $sahabi->name['en'] ?? 'صحابي' }}</h1>
                </div>
                <p class="text-gray-400 flex items-center space-x-2 space-x-reverse">
                    <i data-lucide="users" class="w-4 h-4"></i>
                    <span>معلومات الصحابي</span>
                </p>
            </div>

            <div class="mt-4 md:mt-0 flex items-center space-x-3 space-x-reverse">
                <a href="{{ route('admin.sahaba.edit', $sahabi) }}" class="px-5 py-3 bg-green-500/20 hover:bg-green-500/30 text-green-400 rounded-xl font-semibold transition-all duration-300 flex items-center space-x-2 space-x-reverse border border-green-500/30 hover:scale-105">
                    <i data-lucide="edit" class="w-4 h-4"></i>
                    <span>تعديل</span>
                </a>

                <form action="{{ route('admin.sahaba.destroy', $sahabi) }}" method="POST" onsubmit="return confirm('هل أنت متأكد من حذف هذا الصحابي؟')">
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
                    <p class="text-sm text-gray-400 mb-1">الحالة</p>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-bold bg-green-500/20 text-green-400 border border-green-500/30">
                        {{ $sahabi->status ?? 'نشط' }}
                    </span>
                </div>
                <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-green-500 to-green-600 flex items-center justify-center">
                    <i data-lucide="check-circle" class="w-6 h-6 text-white"></i>
                </div>
            </div>
        </div>

        <div class="rounded-xl p-6 relative overflow-hidden transition-colors" :class="darkMode ? 'glass-dark' : 'glass-light-soft' group hover:scale-105 transition-transform duration-300">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-400 mb-1">عدد الأحاديث</p>
                    <span class="text-2xl font-black text-white">{{ $sahabi->hadith_count ?? 0 }}</span>
                </div>
                <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center">
                    <i data-lucide="book" class="w-6 h-6 text-white"></i>
                </div>
            </div>
        </div>

        <div class="rounded-xl p-6 relative overflow-hidden transition-colors" :class="darkMode ? 'glass-dark' : 'glass-light-soft' group hover:scale-105 transition-transform duration-300">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-400 mb-1">الحالة الإسلامية</p>
                    <span class="text-lg font-bold text-white">{{ $sahabi->islamic_status ?? '-' }}</span>
                </div>
                <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-purple-500 to-purple-600 flex items-center justify-center">
                    <i data-lucide="star" class="w-6 h-6 text-white"></i>
                </div>
            </div>
        </div>

        <div class="rounded-xl p-6 relative overflow-hidden transition-colors" :class="darkMode ? 'glass-dark' : 'glass-light-soft' group hover:scale-105 transition-transform duration-300">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-400 mb-1">تاريخ الإضافة</p>
                    <span class="text-sm font-semibold text-white">{{ $sahabi->created_at->format('Y-m-d') }}</span>
                </div>
                <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-yellow-500 to-yellow-600 flex items-center justify-center">
                    <i data-lucide="calendar" class="w-6 h-6 text-white"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="rounded-2xl p-6 transition-colors" :class="darkMode ? \'glass-dark\' : \'glass-light-soft\'">
        <h2 class="text-xl font-bold text-white mb-6 flex items-center gap-2">
            <i data-lucide="languages" class="w-6 h-6"></i>
            المعلومات الأساسية
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            @if($sahabi->full_name)
            <div>
                <label class="block text-sm font-semibold text-gray-400 mb-2">الاسم الكامل</label>
                <p class="text-white font-semibold px-4 py-3 bg-white/5 rounded-lg">{{ $sahabi->full_name['ar'] ?? $sahabi->full_name['en'] ?? '-' }}</p>
            </div>
            @endif

            @if($sahabi->tribe)
            <div>
                <label class="block text-sm font-semibold text-gray-400 mb-2">القبيلة</label>
                <p class="text-white font-semibold px-4 py-3 bg-white/5 rounded-lg">{{ $sahabi->tribe['ar'] ?? $sahabi->tribe['en'] ?? '-' }}</p>
            </div>
            @endif

            @if($sahabi->kunya)
            <div>
                <label class="block text-sm font-semibold text-gray-400 mb-2">الكنية</label>
                <p class="text-white font-semibold px-4 py-3 bg-white/5 rounded-lg">{{ $sahabi->kunya['ar'] ?? $sahabi->kunya['en'] ?? '-' }}</p>
            </div>
            @endif

            @if($sahabi->laqab)
            <div>
                <label class="block text-sm font-semibold text-gray-400 mb-2">اللقب</label>
                <p class="text-white font-semibold px-4 py-3 bg-white/5 rounded-lg">{{ $sahabi->laqab['ar'] ?? $sahabi->laqab['en'] ?? '-' }}</p>
            </div>
            @endif
        </div>
    </div>

    @if($sahabi->description)
    <div class="rounded-2xl p-6 transition-colors" :class="darkMode ? \'glass-dark\' : \'glass-light-soft\'">
        <h2 class="text-xl font-bold text-white mb-6 flex items-center gap-2">
            <i data-lucide="file-text" class="w-6 h-6"></i>
            السيرة الذاتية
        </h2>

        <div class="space-y-4">
            @foreach(['ar' => 'العربية', 'en' => 'English'] as $lang => $name)
                @if($sahabi->description && isset($sahabi->description[$lang]))
                <div>
                    <h3 class="text-sm font-semibold text-gray-400 mb-2">{{ $name }}</h3>
                    <p class="text-gray-300 leading-relaxed" dir="{{ $lang === 'ar' ? 'rtl' : 'ltr' }}">
                        {{ $sahabi->description[$lang] }}
                    </p>
                </div>
                @endif
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
                <p class="text-white font-semibold px-4 py-3 bg-white/5 rounded-lg">{{ $sahabi->created_at->format('Y-m-d H:i') }}</p>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-400 mb-2">آخر تحديث</label>
                <p class="text-white font-semibold px-4 py-3 bg-white/5 rounded-lg">{{ $sahabi->updated_at->format('Y-m-d H:i') }}</p>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-400 mb-2">معرّف الصحابي</label>
                <p class="text-white font-semibold px-4 py-3 bg-white/5 rounded-lg">{{ $sahabi->id }}</p>
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
