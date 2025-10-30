@extends('layouts.admin')

@section('title', 'تعديل التفسير')

@section('content')
<div class="max-w-6xl mx-auto" x-data="tafseerForm({{ json_encode($tafseer ?? []) }})" x-init="init()">
    <div class="mb-8">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h1 class="text-3xl font-bold gradient-text mb-2">تعديل التفسير</h1>
                <p class="text-gray-600 dark:text-gray-400">تعديل بيانات التفسير</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.tafseer.show', $tafseer->id) }}" class="flex items-center gap-2 px-4 py-2 bg-blue-500/20 text-blue-400 rounded-lg hover:bg-blue-500/30 transition-colors">
                    <i data-lucide="eye" class="w-5 h-5"></i>
                    <span>عرض</span>
                </a>
                <a href="{{ route('admin.tafseer.index') }}" class="flex items-center gap-2 px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">
                    <i data-lucide="arrow-right" class="w-5 h-5"></i>
                    <span>العودة للقائمة</span>
                </a>
            </div>
        </div>
    </div>

    <form action="{{ route('admin.tafseer.update', $tafseer->id) }}" method="POST" @submit="handleSubmit" class="space-y-6">
        @csrf
        @method('PUT')

        <div class="rounded-2xl p-6 transition-colors shadow-xl" :class="darkMode ? 'glass-dark' : 'glass-light-soft'">
            <h2 class="text-xl font-bold text-white mb-6 flex items-center gap-2">
                <i data-lucide="info" class="w-6 h-6"></i>
                المعلومات الأساسية
            </h2>

            <div class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-semibold mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            <span class="text-red-400">*</span> رقم الآية
                        </label>
                        <input type="text" name="verse_reference" value="{{ old('verse_reference', $tafseer->verse_reference) }}" placeholder="مثال: 2:255 (سورة:آية)" class="w-full px-4 py-3 rounded-lg transition-all focus:outline-none focus:ring-2 focus:ring-blue-500" :class="darkMode ? 'bg-white/10 border border-white/20 text-white placeholder-gray-400' : 'glass-light-strong text-gray-900 placeholder-gray-500'" required>
                        <p class="mt-1 text-xs text-gray-500">الصيغة: رقم السورة:رقم الآية (مثال: 2:255)</p>
                        @error('verse_reference')<p class="mt-2 text-sm text-red-400">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-sm font-semibold mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            <span class="text-red-400">*</span> المصدر
                        </label>
                        <select name="source" class="w-full px-4 py-3 rounded-lg transition-all focus:outline-none focus:ring-2 focus:ring-blue-500" :class="darkMode ? 'bg-white/10 border border-white/20 text-white' : 'glass-light-strong text-gray-900'" required>
                            <option value="">اختر المصدر</option>
                            <option value="Ibn Kathir" {{ old('source', $tafseer->source) == 'Ibn Kathir' ? 'selected' : '' }}>ابن كثير</option>
                            <option value="Jalalayn" {{ old('source', $tafseer->source) == 'Jalalayn' ? 'selected' : '' }}>الجلالين</option>
                            <option value="Sa'di" {{ old('source', $tafseer->source) == "Sa'di" ? 'selected' : '' }}>السعدي</option>
                            <option value="Qurtubi" {{ old('source', $tafseer->source) == 'Qurtubi' ? 'selected' : '' }}>القرطبي</option>
                            <option value="Tabari" {{ old('source', $tafseer->source) == 'Tabari' ? 'selected' : '' }}>الطبري</option>
                            <option value="Baghawi" {{ old('source', $tafseer->source) == 'Baghawi' ? 'selected' : '' }}>البغوي</option>
                            <option value="Muyassar" {{ old('source', $tafseer->source) == 'Muyassar' ? 'selected' : '' }}>الميسر</option>
                            <option value="Ashhari" {{ old('source', $tafseer->source) == 'Ashhari' ? 'selected' : '' }}>الأشعري</option>
                            <option value="Qasimi" {{ old('source', $tafseer->source) == 'Qasimi' ? 'selected' : '' }}>القاسمي</option>
                        </select>
                        @error('source')<p class="mt-2 text-sm text-red-400">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-semibold mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">المؤلف (اختياري)</label>
                    <input type="text" name="author" value="{{ old('author', $tafseer->author) }}" placeholder="اسم المؤلف" class="w-full px-4 py-3 rounded-lg transition-all focus:outline-none focus:ring-2 focus:ring-blue-500" :class="darkMode ? 'bg-white/10 border border-white/20 text-white placeholder-gray-400' : 'glass-light-strong text-gray-900 placeholder-gray-500'">
                    @error('author')<p class="mt-2 text-sm text-red-400">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>

        <div class="rounded-2xl p-6 transition-colors shadow-xl" :class="darkMode ? 'glass-dark' : 'glass-light-soft'">
            <h2 class="text-xl font-bold text-white mb-6 flex items-center gap-2">
                <i data-lucide="languages" class="w-6 h-6"></i>
                نص التفسير متعدد اللغات
            </h2>

            <div class="flex flex-wrap gap-2 mb-6" x-data="{ activeTab: 'ar' }">
                @foreach(['ar' => 'العربية', 'en' => 'English', 'fr' => 'Français', 'tr' => 'Türkçe', 'ur' => 'اردو'] as $lang => $label)
                    <button type="button" @click="activeTab = '{{ $lang }}'" :class="activeTab === '{{ $lang }}' ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white' : (darkMode ? 'glass-dark text-gray-400' : 'glass-light-soft text-gray-600')" class="px-6 py-2 rounded-full font-semibold transition-all hover:scale-105">{{ $label }}</button>
                @endforeach

                @foreach(['ar', 'en', 'fr', 'tr', 'ur'] as $lang)
                    <div x-show="activeTab === '{{ $lang }}'" x-cloak class="w-full mt-4">
                        <textarea name="text[{{ $lang }}]" rows="12" class="w-full px-4 py-3 rounded-lg transition-all focus:outline-none focus:ring-2 focus:ring-blue-500" :class="darkMode ? 'bg-white/10 border border-white/20 text-white placeholder-gray-400' : 'glass-light-strong text-gray-900 placeholder-gray-500'" dir="{{ in_array($lang, ['ar', 'ur']) ? 'rtl' : 'ltr' }}" placeholder="نص التفسير..." required>{{ old('text.' . $lang, $tafseer->text[$lang] ?? '') }}</textarea>
                        @error('text.' . $lang)<p class="mt-2 text-sm text-red-400">{{ $message }}</p>@enderror
                    </div>
                @endforeach
            </div>
        </div>

        <div class="rounded-2xl p-6 transition-colors shadow-xl" :class="darkMode ? 'glass-dark' : 'glass-light-soft'">
            <h2 class="text-xl font-bold text-white mb-6 flex items-center gap-2">
                <i data-lucide="settings" class="w-6 h-6"></i>
                الإعدادات
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-semibold mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">الحالة</label>
                    <select name="status" class="w-full px-4 py-3 rounded-lg transition-all focus:outline-none focus:ring-2 focus:ring-blue-500" :class="darkMode ? 'bg-white/10 border border-white/20 text-white' : 'glass-light-strong text-gray-900'">
                        <option value="published" {{ old('status', $tafseer->status) == 'published' ? 'selected' : '' }}>منشورة</option>
                        <option value="draft" {{ old('status', $tafseer->status) == 'draft' ? 'selected' : '' }}>مسودة</option>
                    </select>
                    @error('status')<p class="mt-2 text-sm text-red-400">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-semibold mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">الحواشي (JSON - اختياري)</label>
                    <textarea name="footnotes" rows="3" placeholder='["الحاشية 1", "الحاشية 2"]' class="w-full px-4 py-3 rounded-lg transition-all focus:outline-none focus:ring-2 focus:ring-blue-500" :class="darkMode ? 'bg-white/10 border border-white/20 text-white placeholder-gray-400' : 'glass-light-strong text-gray-900 placeholder-gray-500'">{{ old('footnotes', is_array($tafseer->footnotes) ? json_encode($tafseer->footnotes) : $tafseer->footnotes) }}</textarea>
                    <p class="mt-1 text-xs text-gray-500">صيغة JSON: ["نص الحاشية 1", "نص الحاشية 2"]</p>
                    @error('footnotes')<p class="mt-2 text-sm text-red-400">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>

        <div class="rounded-2xl p-6 transition-colors shadow-xl" :class="darkMode ? 'glass-dark' : 'glass-light-soft'">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <div class="flex items-center gap-3">
                    <a href="{{ route('admin.tafseer.index') }}" class="px-6 py-3 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition-colors flex items-center gap-2">
                        <i data-lucide="x" class="w-5 h-5"></i>
                        إلغاء
                    </a>

                    <button type="button" @click="if(confirm('هل أنت متأكد من حذف هذا التفسير؟')) { $refs.deleteForm.submit(); }" class="px-6 py-3 bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors flex items-center gap-2">
                        <i data-lucide="trash-2" class="w-5 h-5"></i>
                        حذف
                    </button>
                </div>

                <button type="submit" class="px-8 py-3 gradient-primary text-white rounded-lg font-semibold flex items-center gap-2 hover:shadow-lg transition-all" x-bind:disabled="isSubmitting">
                    <i data-lucide="save" class="w-5 h-5" x-show="!isSubmitting"></i>
                    <span x-text="isSubmitting ? 'جاري الحفظ...' : 'حفظ التغييرات'"></span>
                </button>
            </div>
        </div>
    </form>

    <form x-ref="deleteForm" action="{{ route('admin.tafseer.destroy', $tafseer->id) }}" method="POST" class="hidden">
        @csrf
        @method('DELETE')
    </form>
</div>
@endsection

@push('scripts')
<script>
function tafseerForm(tafseer) {
    return {
        isSubmitting: false,
        tafseer: tafseer,
        handleSubmit() { this.isSubmitting = true; return true; },
        init() { this.$nextTick(() => lucide.createIcons()); }
    }
}
</script>
@endpush
