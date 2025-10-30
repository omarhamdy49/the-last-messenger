@extends('layouts.admin')

@section('title', 'تفاصيل التفسير')

@section('content')
<div class="max-w-6xl mx-auto" x-data="tafseerDetails()" x-init="init()">
    <div class="mb-8">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h1 class="text-3xl font-bold gradient-text mb-2">تفاصيل التفسير</h1>
                <p class="text-gray-600 dark:text-gray-400">عرض تفاصيل التفسير</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.tafseer.edit', $tafseer->id) }}" class="flex items-center gap-2 px-4 py-2 bg-green-500/20 text-green-400 rounded-lg hover:bg-green-500/30 transition-colors">
                    <i data-lucide="edit" class="w-5 h-5"></i>
                    <span>تعديل</span>
                </a>
                <button @click="if(confirm('هل أنت متأكد من حذف هذا التفسير؟')) { $refs.deleteForm.submit(); }" class="flex items-center gap-2 px-4 py-2 bg-red-500/20 text-red-400 rounded-lg hover:bg-red-500/30 transition-colors">
                    <i data-lucide="trash-2" class="w-5 h-5"></i>
                    <span>حذف</span>
                </button>
                <a href="{{ route('admin.tafseer.index') }}" class="flex items-center gap-2 px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">
                    <i data-lucide="arrow-right" class="w-5 h-5"></i>
                    <span>العودة للقائمة</span>
                </a>
            </div>
        </div>
    </div>

    <div class="rounded-2xl p-8 mb-6 transition-colors shadow-xl relative overflow-hidden" :class="darkMode ? 'glass-dark' : 'glass-light-soft'">
        <div class="absolute top-0 right-0 w-96 h-96 bg-gradient-to-br from-blue-500/20 to-green-500/20 rounded-full blur-3xl -translate-y-1/2 translate-x-1/2"></div>

        <div class="relative z-10">
            <div class="flex items-center gap-4 mb-6">
                <div class="w-20 h-20 rounded-xl bg-gradient-to-br from-blue-500 to-green-500 flex items-center justify-center text-white font-black text-2xl shadow-lg">
                    <i data-lucide="book-open" class="w-10 h-10"></i>
                </div>
                <div>
                    <h2 class="text-3xl font-black text-white mb-2">{{ $tafseer->verse_reference }}</h2>
                    <div class="flex items-center gap-3">
                        <span class="px-4 py-1 rounded-full text-sm font-bold bg-blue-500/20 text-blue-400">{{ $tafseer->source }}</span>
                        <span class="px-4 py-1 rounded-full text-sm font-bold" :class="'{{ $tafseer->status }}' === 'published' ? 'bg-green-500/20 text-green-400' : 'bg-yellow-500/20 text-yellow-400'">
                            {{ $tafseer->status === 'published' ? 'منشورة' : 'مسودة' }}
                        </span>
                    </div>
                </div>
            </div>

            @if($tafseer->author)
            <div class="flex items-center gap-2 text-gray-300">
                <i data-lucide="user" class="w-5 h-5"></i>
                <span>المؤلف: {{ $tafseer->author }}</span>
            </div>
            @endif
        </div>
    </div>

    <div class="rounded-2xl p-6 mb-6 transition-colors shadow-xl" :class="darkMode ? 'glass-dark' : 'glass-light-soft'">
        <h2 class="text-xl font-bold text-white mb-6 flex items-center gap-2">
            <i data-lucide="file-text" class="w-6 h-6"></i>
            نص التفسير
        </h2>

        <div class="flex flex-wrap gap-2 mb-6" x-data="{ activeTab: 'ar' }">
            @foreach(['ar' => 'العربية', 'en' => 'English', 'fr' => 'Français', 'tr' => 'Türkçe', 'ur' => 'اردو'] as $lang => $label)
                <button type="button" @click="activeTab = '{{ $lang }}'" :class="activeTab === '{{ $lang }}' ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white' : (darkMode ? 'glass-dark text-gray-400' : 'glass-light-soft text-gray-600')" class="px-6 py-2 rounded-full font-semibold transition-all hover:scale-105">{{ $label }}</button>
            @endforeach

            @foreach(['ar', 'en', 'fr', 'tr', 'ur'] as $lang)
                <div x-show="activeTab === '{{ $lang }}'" x-cloak class="w-full mt-4">
                    <div class="rounded-xl p-6 transition-colors" :class="darkMode ? 'bg-white/5' : 'bg-gray-50'">
                        @if(isset($tafseer->text[$lang]) && $tafseer->text[$lang])
                            <p class="text-lg leading-relaxed whitespace-pre-line" :class="darkMode ? 'text-gray-300' : 'text-gray-700'" dir="{{ in_array($lang, ['ar', 'ur']) ? 'rtl' : 'ltr' }}">{{ $tafseer->text[$lang] }}</p>
                        @else
                            <p class="text-gray-500 italic">لا يوجد نص متاح بهذه اللغة</p>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    @if($tafseer->footnotes && count($tafseer->footnotes) > 0)
    <div class="rounded-2xl p-6 mb-6 transition-colors shadow-xl" :class="darkMode ? 'glass-dark' : 'glass-light-soft'">
        <h2 class="text-xl font-bold text-white mb-6 flex items-center gap-2">
            <i data-lucide="bookmark" class="w-6 h-6"></i>
            الحواشي
        </h2>

        <ol class="list-decimal list-inside space-y-3">
            @foreach($tafseer->footnotes as $footnote)
                <li class="text-lg" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ $footnote }}</li>
            @endforeach
        </ol>
    </div>
    @endif

    <div class="rounded-2xl p-6 transition-colors shadow-xl" :class="darkMode ? 'glass-dark' : 'glass-light-soft'">
        <h2 class="text-xl font-bold text-white mb-6 flex items-center gap-2">
            <i data-lucide="clock" class="w-6 h-6"></i>
            معلومات النظام
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <div class="text-sm text-gray-400 mb-1">تاريخ الإضافة</div>
                <div class="text-white font-semibold">{{ $tafseer->created_at->format('Y-m-d H:i') }}</div>
            </div>

            <div>
                <div class="text-sm text-gray-400 mb-1">آخر تحديث</div>
                <div class="text-white font-semibold">{{ $tafseer->updated_at->format('Y-m-d H:i') }}</div>
            </div>
        </div>
    </div>

    <form x-ref="deleteForm" action="{{ route('admin.tafseer.destroy', $tafseer->id) }}" method="POST" class="hidden">
        @csrf
        @method('DELETE')
    </form>
</div>
@endsection

@push('scripts')
<script>
function tafseerDetails() {
    return {
        init() { this.$nextTick(() => lucide.createIcons()); }
    }
}
</script>
@endpush
