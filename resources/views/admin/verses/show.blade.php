@extends('layouts.admin')

@section('title', 'تفاصيل الآية')

@section('content')
<div class="max-w-6xl mx-auto" x-data="verseDetails()" x-init="init()">
    {{-- Page Header --}}
    <div class="mb-8">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h1 class="text-3xl font-bold gradient-text mb-2">تفاصيل الآية</h1>
                <p class="text-gray-600 dark:text-gray-400">عرض تفاصيل الآية من القرآن الكريم</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.verses.edit', $verse->id) }}"
                   class="flex items-center gap-2 px-4 py-2 bg-green-500/20 text-green-400 rounded-lg hover:bg-green-500/30 transition-colors">
                    <i data-lucide="edit" class="w-5 h-5"></i>
                    <span>تعديل</span>
                </a>
                <button
                    @click="if(confirm('هل أنت متأكد من حذف هذه الآية؟')) { $refs.deleteForm.submit(); }"
                    class="flex items-center gap-2 px-4 py-2 bg-red-500/20 text-red-400 rounded-lg hover:bg-red-500/30 transition-colors">
                    <i data-lucide="trash-2" class="w-5 h-5"></i>
                    <span>حذف</span>
                </button>
                <a href="{{ route('admin.verses.index') }}"
                   class="flex items-center gap-2 px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">
                    <i data-lucide="arrow-right" class="w-5 h-5"></i>
                    <span>العودة للقائمة</span>
                </a>
            </div>
        </div>
    </div>

    {{-- Verse Reference Card --}}
    <div class="rounded-2xl p-8 mb-6 transition-colors shadow-xl relative overflow-hidden" :class="darkMode ? 'glass-dark' : 'glass-light-soft'">
        <div class="absolute top-0 right-0 w-96 h-96 bg-gradient-to-br from-blue-500/20 to-green-500/20 rounded-full blur-3xl -translate-y-1/2 translate-x-1/2"></div>

        <div class="relative z-10">
            <div class="flex items-center gap-4 mb-6">
                <div class="w-20 h-20 rounded-xl bg-gradient-to-br from-blue-500 to-green-500 flex items-center justify-center text-white font-black text-2xl shadow-lg">
                    {{ $verse->surah->number }}
                </div>
                <div>
                    <h2 class="text-3xl font-black text-white mb-2">{{ $verse->surah->name['ar'] }}</h2>
                    <p class="text-lg text-gray-300">{{ $verse->surah->name_transliteration }} - الآية {{ $verse->verse_number }}</p>
                </div>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="glass-dark rounded-lg p-4">
                    <div class="text-sm text-gray-400 mb-1">مفتاح الآية</div>
                    <div class="text-xl font-bold text-white">{{ $verse->verse_key }}</div>
                </div>

                <div class="glass-dark rounded-lg p-4">
                    <div class="text-sm text-gray-400 mb-1">رقم الآية</div>
                    <div class="text-xl font-bold text-white">{{ $verse->verse_number }}</div>
                </div>

                @if($verse->juz_number)
                <div class="glass-dark rounded-lg p-4">
                    <div class="text-sm text-gray-400 mb-1">الجزء</div>
                    <div class="text-xl font-bold text-white">{{ $verse->juz_number }}</div>
                </div>
                @endif

                @if($verse->page_number)
                <div class="glass-dark rounded-lg p-4">
                    <div class="text-sm text-gray-400 mb-1">الصفحة</div>
                    <div class="text-xl font-bold text-white">{{ $verse->page_number }}</div>
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Additional Info --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        @if($verse->revelation_order)
        <div class="rounded-xl p-6 transition-colors" :class="darkMode ? 'glass-dark' : 'glass-light-soft'">
            <div class="flex items-center gap-3 mb-2">
                <div class="w-10 h-10 rounded-lg bg-purple-500/20 flex items-center justify-center">
                    <i data-lucide="list-ordered" class="w-5 h-5 text-purple-400"></i>
                </div>
                <h3 class="font-bold text-white">ترتيب النزول</h3>
            </div>
            <p class="text-2xl font-black text-white">{{ $verse->revelation_order }}</p>
        </div>
        @endif

        @if($verse->prostration_type)
        <div class="rounded-xl p-6 transition-colors" :class="darkMode ? 'glass-dark' : 'glass-light-soft'">
            <div class="flex items-center gap-3 mb-2">
                <div class="w-10 h-10 rounded-lg bg-orange-500/20 flex items-center justify-center">
                    <i data-lucide="arrow-down" class="w-5 h-5 text-orange-400"></i>
                </div>
                <h3 class="font-bold text-white">نوع السجدة</h3>
            </div>
            <span class="inline-flex px-4 py-2 rounded-full text-sm font-bold"
                  :class="'{{ $verse->prostration_type }}' === 'recommended' ? 'bg-blue-500/20 text-blue-400' : 'bg-orange-500/20 text-orange-400'">
                {{ $verse->prostration_type === 'recommended' ? 'سجدة مستحبة' : 'سجدة واجبة' }}
            </span>
        </div>
        @endif
    </div>

    {{-- Verse Text Card --}}
    <div class="rounded-2xl p-6 transition-colors shadow-xl" :class="darkMode ? 'glass-dark' : 'glass-light-soft'">
        <h2 class="text-xl font-bold text-white mb-6 flex items-center gap-2">
            <i data-lucide="book-open" class="w-6 h-6"></i>
            نص الآية متعدد اللغات
        </h2>

        {{-- Language Tabs --}}
        <div class="flex flex-wrap gap-2 mb-6" x-data="{ activeTab: 'ar' }">
            @foreach(['ar' => 'العربية', 'en' => 'English', 'fr' => 'Français', 'tr' => 'Türkçe', 'ur' => 'اردو'] as $lang => $label)
                <button
                    type="button"
                    @click="activeTab = '{{ $lang }}'"
                    :class="activeTab === '{{ $lang }}' ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white' : (darkMode ? 'glass-dark text-gray-400' : 'glass-light-soft text-gray-600')"
                    class="px-6 py-2 rounded-full font-semibold transition-all hover:scale-105"
                >
                    {{ $label }}
                </button>
            @endforeach

            {{-- Language Content --}}
            @foreach(['ar', 'en', 'fr', 'tr', 'ur'] as $lang)
                <div x-show="activeTab === '{{ $lang }}'" x-cloak class="w-full mt-4">
                    <div class="rounded-xl p-6 transition-colors" :class="darkMode ? 'bg-white/5' : 'bg-gray-50'">
                        <div class="mb-4">
                            <h3 class="text-sm font-semibold mb-2" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                                نص الآية ({{ $lang }})
                            </h3>
                            @if(isset($verse->text[$lang]))
                                <p class="text-2xl leading-loose"
                                   :class="darkMode ? 'text-white' : 'text-gray-900'"
                                   dir="{{ in_array($lang, ['ar', 'ur']) ? 'rtl' : 'ltr' }}">
                                    {{ $verse->text[$lang] }}
                                </p>
                            @else
                                <p class="text-gray-500 italic">لا يوجد نص متاح بهذه اللغة</p>
                            @endif
                        </div>

                        @if($lang === 'ar' && $verse->transliteration)
                            <div class="pt-4 mt-4 border-t" :class="darkMode ? 'border-white/10' : 'border-gray-200'">
                                <h3 class="text-sm font-semibold mb-2" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                                    الترجمة الصوتية (Transliteration)
                                </h3>
                                <p class="text-lg leading-relaxed" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                                    {{ $verse->transliteration }}
                                </p>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Metadata Card --}}
    <div class="rounded-2xl p-6 mt-6 transition-colors shadow-xl" :class="darkMode ? 'glass-dark' : 'glass-light-soft'">
        <h2 class="text-xl font-bold text-white mb-6 flex items-center gap-2">
            <i data-lucide="clock" class="w-6 h-6"></i>
            معلومات النظام
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <div class="text-sm text-gray-400 mb-1">تاريخ الإنشاء</div>
                <div class="text-white font-semibold">{{ $verse->created_at->format('Y-m-d H:i') }}</div>
            </div>

            <div>
                <div class="text-sm text-gray-400 mb-1">آخر تحديث</div>
                <div class="text-white font-semibold">{{ $verse->updated_at->format('Y-m-d H:i') }}</div>
            </div>
        </div>
    </div>

    {{-- Hidden Delete Form --}}
    <form x-ref="deleteForm" action="{{ route('admin.verses.destroy', $verse->id) }}" method="POST" class="hidden">
        @csrf
        @method('DELETE')
    </form>
</div>
@endsection

@push('scripts')
<script>
function verseDetails() {
    return {
        init() {
            this.$nextTick(() => lucide.createIcons());
        }
    }
}
</script>
@endpush
