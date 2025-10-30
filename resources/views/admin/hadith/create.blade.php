@extends('layouts.admin')

@section('title', 'إضافة حديث جديد - محسّن')

@section('content')
<div class="max-w-6xl mx-auto" x-data="hadithForm()" x-init="init()">
    {{-- Page Header --}}
    <div class="mb-8">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h1 class="text-3xl font-bold gradient-text mb-2">إضافة حديث جديد</h1>
                <p class="text-gray-600 dark:text-gray-400">إضافة حديث نبوي شريف مع الترجمات</p>
            </div>
            <a href="{{ route('admin.hadith.index') }}"
               class="flex items-center gap-2 px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">
                <i data-lucide="arrow-right" class="w-5 h-5"></i>
                <span>العودة للقائمة</span>
            </a>
        </div>
    </div>

    {{-- Form Card --}}
    <form action="{{ route('admin.hadith.store') }}" method="POST" @submit="handleSubmit" class="space-y-6">
        @csrf

        {{-- Basic Information Card --}}
        <div class="rounded-2xl p-6 transition-colors" :class="darkMode ? \'glass-dark\' : \'glass-light-soft\' shadow-xl">
            <h2 class="text-xl font-bold text-white mb-6 flex items-center gap-2">
                <i data-lucide="book-open" class="w-6 h-6"></i>
                المعلومات الأساسية
            </h2>

            <div class="space-y-6">
                {{-- Book & Chapter Row --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-semibold" :class="darkMode ? \'text-gray-300\' : \'text-gray-700\' mb-2">
                            <span class="text-red-400">*</span> الكتاب
                        </label>
                        <select name="book_id" id="book_id" required class="w-full px-4 py-3 rounded-lg transition-all focus:outline-none focus:ring-2 focus:ring-blue-500" :class="darkMode ? \'bg-white/10 border border-white/20 text-white\' : \'glass-light-strong text-gray-900\' focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">اختر الكتاب</option>
                            @foreach($books ?? [] as $book)
                                <option value="{{ $book->id }}">{{ $book->name['ar'] ?? $book->name['en'] }}</option>
                            @endforeach
                        </select>
                        @error('book_id')
                            <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-semibold" :class="darkMode ? \'text-gray-300\' : \'text-gray-700\' mb-2">الباب</label>
                        <select name="chapter_id" class="w-full px-4 py-3 rounded-lg transition-all focus:outline-none focus:ring-2 focus:ring-blue-500" :class="darkMode ? \'bg-white/10 border border-white/20 text-white\' : \'glass-light-strong text-gray-900\' focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">اختر الباب (اختياري)</option>
                            @foreach($chapters ?? [] as $chapter)
                                <option value="{{ $chapter->id }}">{{ $chapter->name['ar'] ?? $chapter->name['en'] }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- Hadith Number & Grade Row --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-semibold" :class="darkMode ? \'text-gray-300\' : \'text-gray-700\' mb-2">
                            <span class="text-red-400">*</span> رقم الحديث
                        </label>
                        <input type="number" name="hadith_number" value="{{ old('hadith_number') }}"
                               class="w-full px-4 py-3 rounded-lg transition-all focus:outline-none focus:ring-2 focus:ring-blue-500" :class="darkMode ? \'bg-white/10 border border-white/20 text-white placeholder-gray-400\' : \'glass-light-strong text-gray-900 placeholder-gray-500\' focus:outline-none focus:ring-2 focus:ring-blue-500"
                               placeholder="1" required>
                        @error('hadith_number')
                            <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-semibold" :class="darkMode ? \'text-gray-300\' : \'text-gray-700\' mb-2">
                            <span class="text-red-400">*</span> درجة الحديث
                        </label>
                        <select name="grade" required class="w-full px-4 py-3 rounded-lg transition-all focus:outline-none focus:ring-2 focus:ring-blue-500" :class="darkMode ? \'bg-white/10 border border-white/20 text-white\' : \'glass-light-strong text-gray-900\' focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">اختر الدرجة</option>
                            <option value="sahih">صحيح</option>
                            <option value="hasan">حسن</option>
                            <option value="daif">ضعيف</option>
                            <option value="mawdu">موضوع</option>
                        </select>
                        @error('grade')
                            <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Arabic Text --}}
                <div>
                    <label class="block text-sm font-semibold" :class="darkMode ? \'text-gray-300\' : \'text-gray-700\' mb-2">
                        <span class="text-red-400">*</span> النص العربي
                    </label>
                    <textarea name="arabic_text" rows="5" required
                              class="w-full px-4 py-3 rounded-lg transition-all focus:outline-none focus:ring-2 focus:ring-blue-500" :class="darkMode ? \'bg-white/10 border border-white/20 text-white placeholder-gray-400\' : \'glass-light-strong text-gray-900 placeholder-gray-500\' focus:outline-none focus:ring-2 focus:ring-blue-500"
                              dir="rtl" placeholder="أدخل نص الحديث بالعربية...">{{ old('arabic_text') }}</textarea>
                    @error('arabic_text')
                        <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Narrator Chain --}}
                <div>
                    <label class="block text-sm font-semibold" :class="darkMode ? \'text-gray-300\' : \'text-gray-700\' mb-2">سلسلة الرواة</label>
                    <textarea name="narrator_chain" rows="3"
                              class="w-full px-4 py-3 rounded-lg transition-all focus:outline-none focus:ring-2 focus:ring-blue-500" :class="darkMode ? \'bg-white/10 border border-white/20 text-white placeholder-gray-400\' : \'glass-light-strong text-gray-900 placeholder-gray-500\' focus:outline-none focus:ring-2 focus:ring-blue-500"
                              dir="rtl" placeholder="أدخل سلسلة الرواة (اختياري)...">{{ old('narrator_chain') }}</textarea>
                </div>

                {{-- Narrators Selection --}}
                <div>
                    <label class="block text-sm font-semibold" :class="darkMode ? \'text-gray-300\' : \'text-gray-700\' mb-2">الرواة</label>
                    <select name="narrators[]" multiple class="w-full px-4 py-3 rounded-lg transition-all focus:outline-none focus:ring-2 focus:ring-blue-500" :class="darkMode ? \'bg-white/10 border border-white/20 text-white\' : \'glass-light-strong text-gray-900\' focus:outline-none">
                        @foreach($narrators ?? [] as $narrator)
                            <option value="{{ $narrator->id }}">{{ $narrator->name['ar'] ?? $narrator->name['en'] }}</option>
                        @endforeach
                    </select>
                    <p class="mt-2 text-xs text-gray-400">اضغط Ctrl/Cmd لاختيار عدة رواة</p>
                </div>

                {{-- Reference URL --}}
                <div>
                    <label class="block text-sm font-semibold" :class="darkMode ? \'text-gray-300\' : \'text-gray-700\' mb-2">رابط المرجع</label>
                    <input type="url" name="reference_url" value="{{ old('reference_url') }}"
                           class="w-full px-4 py-3 rounded-lg transition-all focus:outline-none focus:ring-2 focus:ring-blue-500" :class="darkMode ? \'bg-white/10 border border-white/20 text-white placeholder-gray-400\' : \'glass-light-strong text-gray-900 placeholder-gray-500\' focus:outline-none focus:ring-2 focus:ring-blue-500"
                           placeholder="https://...">
                </div>
            </div>
        </div>

        {{-- Translations Card --}}
        <div class="rounded-2xl p-6 transition-colors" :class="darkMode ? \'glass-dark\' : \'glass-light-soft\' shadow-xl">
            <h2 class="text-xl font-bold text-white mb-6 flex items-center gap-2">
                <i data-lucide="languages" class="w-6 h-6"></i>
                الترجمات
            </h2>

            <div class="space-y-6">
                <x-forms.multi-lang-input
                    name="translations"
                    label="ترجمة الحديث"
                    type="textarea"
                    :rows="5"
                    :value="old('translations', [])"
                />
            </div>
        </div>

        {{-- Form Actions --}}
        <div class="rounded-2xl p-6 transition-colors" :class="darkMode ? \'glass-dark\' : \'glass-light-soft\' shadow-xl">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <a href="{{ route('admin.hadith.index') }}" class="px-6 py-3 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition-colors flex items-center gap-2">
                    <i data-lucide="x" class="w-5 h-5"></i>
                    إلغاء
                </a>

                <button type="submit" class="px-8 py-3 gradient-primary text-white rounded-lg font-semibold flex items-center gap-2" x-bind:disabled="isSubmitting">
                    <i data-lucide="check-circle" class="w-5 h-5" x-show="!isSubmitting"></i>
                    <span x-text="isSubmitting ? 'جاري الحفظ...' : 'حفظ الحديث'"></span>
                </button>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
function hadithForm() {
    return {
        isSubmitting: false,

        handleSubmit() {
            this.isSubmitting = true;
            return true;
        },

        init() {
            this.$nextTick(() => lucide.createIcons());
        }
    }
}
</script>
@endpush
