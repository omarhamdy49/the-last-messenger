@extends('layouts.admin')

@section('title', 'عرض الشخص')

@section('content')
<div x-data="{}">
    <div class="rounded-2xl p-6 transition-colors" :class="darkMode ? \'glass-dark\' : \'glass-light-soft\'">
        <div class="absolute top-0 right-0 w-96 h-96 bg-gradient-to-br from-blue-500/20 to-green-500/20 rounded-full blur-3xl -translate-y-1/2 translate-x-1/2"></div>

        <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between">
            <div class="flex-1">
                <div class="flex items-center gap-3 mb-4">
                    <a href="{{ route('admin.genealogy.index') }}" class="p-2 bg-white/10 hover:bg-white/20 rounded-lg transition-all">
                        <i data-lucide="arrow-right" class="w-5 h-5 text-white"></i>
                    </a>
                    <h1 class="text-3xl font-black text-white gradient-text">{{ $person->name['ar'] ?? $person->name['en'] ?? 'شخص' }}</h1>
                </div>
                <p class="text-gray-400 flex items-center space-x-2 space-x-reverse">
                    <i data-lucide="users" class="w-4 h-4"></i>
                    <span>معلومات الشخص</span>
                </p>
            </div>

            <div class="mt-4 md:mt-0 flex items-center space-x-3 space-x-reverse">
                <a href="{{ route('admin.genealogy.edit', $person) }}" class="px-5 py-3 bg-green-500/20 hover:bg-green-500/30 text-green-400 rounded-xl font-semibold transition-all duration-300 flex items-center space-x-2 space-x-reverse border border-green-500/30 hover:scale-105">
                    <i data-lucide="edit" class="w-4 h-4"></i>
                    <span>تعديل</span>
                </a>

                <form action="{{ route('admin.genealogy.destroy', $person) }}" method="POST" onsubmit="return confirm('هل أنت متأكد من حذف هذا الشخص؟')">
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
                    <p class="text-sm text-gray-400 mb-1">النوع</p>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-bold bg-blue-500/20 text-blue-400 border border-blue-500/30">
                        {{ $person->gender ?? '-' }}
                    </span>
                </div>
                <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center">
                    <i data-lucide="user" class="w-6 h-6 text-white"></i>
                </div>
            </div>
        </div>

        <div class="rounded-xl p-6 relative overflow-hidden transition-colors" :class="darkMode ? 'glass-dark' : 'glass-light-soft' group hover:scale-105 transition-transform duration-300">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-400 mb-1">تاريخ الميلاد</p>
                    <span class="text-sm font-semibold text-white">{{ $person->birth_date ? $person->birth_date->format('Y-m-d') : '-' }}</span>
                </div>
                <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-green-500 to-green-600 flex items-center justify-center">
                    <i data-lucide="calendar" class="w-6 h-6 text-white"></i>
                </div>
            </div>
        </div>

        <div class="rounded-xl p-6 relative overflow-hidden transition-colors" :class="darkMode ? 'glass-dark' : 'glass-light-soft' group hover:scale-105 transition-transform duration-300">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-400 mb-1">تاريخ الوفاة</p>
                    <span class="text-sm font-semibold text-white">{{ $person->death_date ? $person->death_date->format('Y-m-d') : '-' }}</span>
                </div>
                <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-red-500 to-red-600 flex items-center justify-center">
                    <i data-lucide="x-circle" class="w-6 h-6 text-white"></i>
                </div>
            </div>
        </div>

        <div class="rounded-xl p-6 relative overflow-hidden transition-colors" :class="darkMode ? 'glass-dark' : 'glass-light-soft' group hover:scale-105 transition-transform duration-300">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-400 mb-1">تاريخ الإضافة</p>
                    <span class="text-sm font-semibold text-white">{{ $person->created_at->format('Y-m-d') }}</span>
                </div>
                <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-yellow-500 to-yellow-600 flex items-center justify-center">
                    <i data-lucide="plus-circle" class="w-6 h-6 text-white"></i>
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
                <label class="block text-sm font-semibold text-gray-400 mb-2">الاسم (العربية)</label>
                <p class="text-white font-semibold px-4 py-3 bg-white/5 rounded-lg">{{ $person->name['ar'] ?? '-' }}</p>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-400 mb-2">الاسم (English)</label>
                <p class="text-white font-semibold px-4 py-3 bg-white/5 rounded-lg">{{ $person->name['en'] ?? '-' }}</p>
            </div>

            @if($person->birth_place)
            <div>
                <label class="block text-sm font-semibold text-gray-400 mb-2">مكان الميلاد</label>
                <p class="text-white font-semibold px-4 py-3 bg-white/5 rounded-lg">{{ $person->birth_place['ar'] ?? $person->birth_place['en'] ?? '-' }}</p>
            </div>
            @endif

            @if($person->death_place)
            <div>
                <label class="block text-sm font-semibold text-gray-400 mb-2">مكان الوفاة</label>
                <p class="text-white font-semibold px-4 py-3 bg-white/5 rounded-lg">{{ $person->death_place['ar'] ?? $person->death_place['en'] ?? '-' }}</p>
            </div>
            @endif
        </div>
    </div>

    @if($person->description)
    <div class="rounded-2xl p-6 transition-colors" :class="darkMode ? \'glass-dark\' : \'glass-light-soft\'">
        <h2 class="text-xl font-bold text-white mb-6 flex items-center gap-2">
            <i data-lucide="file-text" class="w-6 h-6"></i>
            السيرة الذاتية
        </h2>

        <div class="space-y-4">
            @foreach(['ar', 'en'] as $lang)
                @if($person->description && isset($person->description[$lang]))
                <div>
                    <h3 class="text-sm font-semibold text-gray-400 mb-2">{{ $lang === 'ar' ? 'العربية' : 'English' }}</h3>
                    <p class="text-gray-300 leading-relaxed" dir="{{ $lang === 'ar' ? 'rtl' : 'ltr' }}">
                        {{ $person->description[$lang] }}
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
                <p class="text-white font-semibold px-4 py-3 bg-white/5 rounded-lg">{{ $person->created_at->format('Y-m-d H:i') }}</p>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-400 mb-2">آخر تحديث</label>
                <p class="text-white font-semibold px-4 py-3 bg-white/5 rounded-lg">{{ $person->updated_at->format('Y-m-d H:i') }}</p>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-400 mb-2">معرّف الشخص</label>
                <p class="text-white font-semibold px-4 py-3 bg-white/5 rounded-lg">{{ $person->id }}</p>
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
