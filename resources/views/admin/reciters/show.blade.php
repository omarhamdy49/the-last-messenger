@extends('layouts.admin')

@section('title', 'تفاصيل القارئ')

@section('content')
<div class="max-w-6xl mx-auto" x-data="reciterDetails()" x-init="init()">
    {{-- Page Header --}}
    <div class="mb-8">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h1 class="text-3xl font-bold gradient-text mb-2">تفاصيل القارئ</h1>
                <p class="text-gray-600 dark:text-gray-400">عرض تفاصيل القارئ</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.reciters.edit', $reciter->id) }}"
                   class="flex items-center gap-2 px-4 py-2 bg-green-500/20 text-green-400 rounded-lg hover:bg-green-500/30 transition-colors">
                    <i data-lucide="edit" class="w-5 h-5"></i>
                    <span>تعديل</span>
                </a>
                <button
                    @click="if(confirm('هل أنت متأكد من حذف هذا القارئ؟')) { $refs.deleteForm.submit(); }"
                    class="flex items-center gap-2 px-4 py-2 bg-red-500/20 text-red-400 rounded-lg hover:bg-red-500/30 transition-colors">
                    <i data-lucide="trash-2" class="w-5 h-5"></i>
                    <span>حذف</span>
                </button>
                <a href="{{ route('admin.reciters.index') }}"
                   class="flex items-center gap-2 px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">
                    <i data-lucide="arrow-right" class="w-5 h-5"></i>
                    <span>العودة للقائمة</span>
                </a>
            </div>
        </div>
    </div>

    {{-- Profile Card --}}
    <div class="rounded-2xl p-8 mb-6 transition-colors shadow-xl relative overflow-hidden" :class="darkMode ? 'glass-dark' : 'glass-light-soft'">
        <div class="absolute top-0 right-0 w-96 h-96 bg-gradient-to-br from-blue-500/20 to-green-500/20 rounded-full blur-3xl -translate-y-1/2 translate-x-1/2"></div>

        <div class="relative z-10">
            <div class="flex flex-col md:flex-row items-start md:items-center gap-6 mb-6">
                <div class="flex-shrink-0">
                    <img src="{{ $reciter->profile_image ?? '/images/default-avatar.png' }}"
                         alt="{{ $reciter->name['ar'] }}"
                         class="w-32 h-32 rounded-xl object-cover shadow-lg border-4 border-white/20">
                </div>
                <div class="flex-1">
                    <div class="flex items-center gap-3 mb-3">
                        <h2 class="text-3xl font-black text-white">{{ $reciter->name['ar'] ?? $reciter->name['en'] }}</h2>
                        @if($reciter->featured)
                            <span class="px-3 py-1 rounded-full text-xs font-bold bg-orange-500/20 text-orange-400 border border-orange-500/30 flex items-center gap-1">
                                <i data-lucide="star" class="w-4 h-4 fill-current"></i>
                                مميز
                            </span>
                        @endif
                        <span class="px-3 py-1 rounded-full text-xs font-bold"
                              :class="'{{ $reciter->status }}' === 'active' ? 'bg-green-500/20 text-green-400' : 'bg-gray-500/20 text-gray-400'">
                            {{ $reciter->status === 'active' ? 'نشط' : 'غير نشط' }}
                        </span>
                    </div>
                    <p class="text-lg text-gray-300 mb-2">{{ $reciter->name['en'] }}</p>

                    <div class="flex flex-wrap gap-4 mt-4">
                        @if($reciter->country)
                        <div class="flex items-center gap-2 text-gray-300">
                            <i data-lucide="map-pin" class="w-5 h-5 text-blue-400"></i>
                            <span>{{ $reciter->country }}</span>
                        </div>
                        @endif

                        @if($reciter->style)
                        <div class="flex items-center gap-2">
                            <i data-lucide="music" class="w-5 h-5 text-green-400"></i>
                            <span class="px-3 py-1 rounded-full text-xs font-bold bg-blue-500/20 text-blue-400">
                                {{ $reciter->style }}
                            </span>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Biography Card --}}
    <div class="rounded-2xl p-6 mb-6 transition-colors shadow-xl" :class="darkMode ? 'glass-dark' : 'glass-light-soft'">
        <h2 class="text-xl font-bold text-white mb-6 flex items-center gap-2">
            <i data-lucide="book-open" class="w-6 h-6"></i>
            السيرة الذاتية
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

            {{-- Biography Content --}}
            @foreach(['ar', 'en', 'fr', 'tr', 'ur'] as $lang)
                <div x-show="activeTab === '{{ $lang }}'" x-cloak class="w-full mt-4">
                    <div class="rounded-xl p-6 transition-colors" :class="darkMode ? 'bg-white/5' : 'bg-gray-50'">
                        @if(isset($reciter->biography[$lang]) && $reciter->biography[$lang])
                            <p class="text-lg leading-relaxed whitespace-pre-line"
                               :class="darkMode ? 'text-gray-300' : 'text-gray-700'"
                               dir="{{ in_array($lang, ['ar', 'ur']) ? 'rtl' : 'ltr' }}">
                                {{ $reciter->biography[$lang] }}
                            </p>
                        @else
                            <p class="text-gray-500 italic">لا توجد سيرة ذاتية متاحة بهذه اللغة</p>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Names in All Languages --}}
    <div class="rounded-2xl p-6 mb-6 transition-colors shadow-xl" :class="darkMode ? 'glass-dark' : 'glass-light-soft'">
        <h2 class="text-xl font-bold text-white mb-6 flex items-center gap-2">
            <i data-lucide="languages" class="w-6 h-6"></i>
            الاسم بجميع اللغات
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach(['ar' => 'العربية', 'en' => 'English', 'fr' => 'Français', 'tr' => 'Türkçe', 'ur' => 'اردو'] as $lang => $label)
                <div class="rounded-lg p-4 transition-colors" :class="darkMode ? 'bg-white/5' : 'bg-gray-50'">
                    <div class="text-sm font-semibold mb-1" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                        {{ $label }}
                    </div>
                    <div class="text-lg font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'" dir="{{ in_array($lang, ['ar', 'ur']) ? 'rtl' : 'ltr' }}">
                        {{ $reciter->name[$lang] ?? '-' }}
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Statistics --}}
    @if(isset($reciter->recitations_count))
    <div class="rounded-2xl p-6 mb-6 transition-colors shadow-xl" :class="darkMode ? 'glass-dark' : 'glass-light-soft'">
        <h2 class="text-xl font-bold text-white mb-6 flex items-center gap-2">
            <i data-lucide="bar-chart" class="w-6 h-6"></i>
            الإحصائيات
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="rounded-xl p-6 relative overflow-hidden" :class="darkMode ? 'bg-white/5' : 'bg-gray-50'">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-sm text-gray-400 mb-1">عدد التلاوات</div>
                        <div class="text-3xl font-black text-white">{{ $reciter->recitations_count ?? 0 }}</div>
                    </div>
                    <div class="w-14 h-14 rounded-lg bg-blue-500/20 flex items-center justify-center">
                        <i data-lucide="mic" class="w-8 h-8 text-blue-400"></i>
                    </div>
                </div>
            </div>

            <div class="rounded-xl p-6 relative overflow-hidden" :class="darkMode ? 'bg-white/5' : 'bg-gray-50'">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-sm text-gray-400 mb-1">عدد المستمعين</div>
                        <div class="text-3xl font-black text-white">{{ $reciter->listeners_count ?? 0 }}</div>
                    </div>
                    <div class="w-14 h-14 rounded-lg bg-green-500/20 flex items-center justify-center">
                        <i data-lucide="users" class="w-8 h-8 text-green-400"></i>
                    </div>
                </div>
            </div>

            <div class="rounded-xl p-6 relative overflow-hidden" :class="darkMode ? 'bg-white/5' : 'bg-gray-50'">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-sm text-gray-400 mb-1">التقييم</div>
                        <div class="text-3xl font-black text-white">{{ $reciter->rating ?? 0 }}/5</div>
                    </div>
                    <div class="w-14 h-14 rounded-lg bg-orange-500/20 flex items-center justify-center">
                        <i data-lucide="star" class="w-8 h-8 text-orange-400"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Metadata Card --}}
    <div class="rounded-2xl p-6 transition-colors shadow-xl" :class="darkMode ? 'glass-dark' : 'glass-light-soft'">
        <h2 class="text-xl font-bold text-white mb-6 flex items-center gap-2">
            <i data-lucide="clock" class="w-6 h-6"></i>
            معلومات النظام
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <div class="text-sm text-gray-400 mb-1">تاريخ الإضافة</div>
                <div class="text-white font-semibold">{{ $reciter->created_at->format('Y-m-d H:i') }}</div>
            </div>

            <div>
                <div class="text-sm text-gray-400 mb-1">آخر تحديث</div>
                <div class="text-white font-semibold">{{ $reciter->updated_at->format('Y-m-d H:i') }}</div>
            </div>

            <div>
                <div class="text-sm text-gray-400 mb-1">رقم التعريف (ID)</div>
                <div class="text-white font-semibold">{{ $reciter->id }}</div>
            </div>

            <div>
                <div class="text-sm text-gray-400 mb-1">الحالة</div>
                <span class="px-3 py-1 rounded-full text-xs font-bold"
                      :class="'{{ $reciter->status }}' === 'active' ? 'bg-green-500/20 text-green-400' : 'bg-gray-500/20 text-gray-400'">
                    {{ $reciter->status === 'active' ? 'نشط' : 'غير نشط' }}
                </span>
            </div>
        </div>
    </div>

    {{-- Hidden Delete Form --}}
    <form x-ref="deleteForm" action="{{ route('admin.reciters.destroy', $reciter->id) }}" method="POST" class="hidden">
        @csrf
        @method('DELETE')
    </form>
</div>
@endsection

@push('scripts')
<script>
function reciterDetails() {
    return {
        init() {
            this.$nextTick(() => lucide.createIcons());
        }
    }
}
</script>
@endpush
