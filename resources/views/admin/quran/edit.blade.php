@extends('layouts.admin')

@section('title', 'تعديل السورة')

@section('content')
<div class="max-w-6xl mx-auto">
    <div class="mb-8">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h1 class="text-3xl font-bold gradient-text mb-2">تعديل السورة</h1>
                <p class="text-gray-600 dark:text-gray-400">تحديث معلومات السورة</p>
            </div>
            <a href="{{ route('admin.quran.index') }}" class="flex items-center gap-2 px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">
                <i data-lucide="arrow-right" class="w-5 h-5"></i>
                <span>العودة للقائمة</span>
            </a>
        </div>
    </div>

    <form action="{{ route('admin.quran.update', $surah) }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')

        <div class="rounded-2xl p-6 transition-colors" :class="darkMode ? \'glass-dark\' : \'glass-light-soft\' shadow-xl">
            <h2 class="text-xl font-bold text-white mb-6 flex items-center gap-2">
                <i data-lucide="book" class="w-6 h-6"></i>
                المعلومات الأساسية
            </h2>

            <div class="space-y-6">
                <div>
                    <label class="block text-sm font-semibold" :class="darkMode ? \'text-gray-300\' : \'text-gray-700\' mb-2">
                        <span class="text-red-400">*</span> رقم السورة
                    </label>
                    <input type="number" name="number" value="{{ old('number', $surah->number) }}" required
                           class="w-full px-4 py-3 rounded-lg transition-all focus:outline-none focus:ring-2 focus:ring-blue-500" :class="darkMode ? \'bg-white/10 border border-white/20 text-white placeholder-gray-400\' : \'glass-light-strong text-gray-900 placeholder-gray-500\' focus:outline-none focus:ring-2 focus:ring-blue-500">
                    @error('number')
                        <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label class="block text-sm font-semibold" :class="darkMode ? \'text-gray-300\' : \'text-gray-700\' mb-2">
                            <span class="text-red-400">*</span> الاسم (العربية)
                        </label>
                        <input type="text" name="name[ar]" value="{{ old('name.ar', $surah->name['ar'] ?? '') }}" required
                               class="w-full px-4 py-3 rounded-lg transition-all focus:outline-none focus:ring-2 focus:ring-blue-500" :class="darkMode ? \'bg-white/10 border border-white/20 text-white placeholder-gray-400\' : \'glass-light-strong text-gray-900 placeholder-gray-500\' focus:outline-none focus:ring-2 focus:ring-blue-500">
                        @error('name.ar')
                            <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-semibold" :class="darkMode ? \'text-gray-300\' : \'text-gray-700\' mb-2">
                            <span class="text-red-400">*</span> الاسم (English)
                        </label>
                        <input type="text" name="name[en]" value="{{ old('name.en', $surah->name['en'] ?? '') }}" required
                               class="w-full px-4 py-3 rounded-lg transition-all focus:outline-none focus:ring-2 focus:ring-blue-500" :class="darkMode ? \'bg-white/10 border border-white/20 text-white placeholder-gray-400\' : \'glass-light-strong text-gray-900 placeholder-gray-500\' focus:outline-none focus:ring-2 focus:ring-blue-500">
                        @error('name.en')
                            <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-semibold" :class="darkMode ? \'text-gray-300\' : \'text-gray-700\' mb-2">مكان النزول</label>
                        <select name="revelation_place" class="w-full px-4 py-3 rounded-lg transition-all focus:outline-none focus:ring-2 focus:ring-blue-500" :class="darkMode ? \'bg-white/10 border border-white/20 text-white\' : \'glass-light-strong text-gray-900\' focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">اختر مكان النزول</option>
                            <option value="mecca" {{ old('revelation_place', $surah->revelation_place) === 'mecca' ? 'selected' : '' }}>مكة</option>
                            <option value="medina" {{ old('revelation_place', $surah->revelation_place) === 'medina' ? 'selected' : '' }}>المدينة</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label class="block text-sm font-semibold" :class="darkMode ? \'text-gray-300\' : \'text-gray-700\' mb-2">عدد الآيات</label>
                        <input type="number" name="verses_count" value="{{ old('verses_count', $surah->verses_count) }}"
                               class="w-full px-4 py-3 rounded-lg transition-all focus:outline-none focus:ring-2 focus:ring-blue-500" :class="darkMode ? \'bg-white/10 border border-white/20 text-white placeholder-gray-400\' : \'glass-light-strong text-gray-900 placeholder-gray-500\' focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold" :class="darkMode ? \'text-gray-300\' : \'text-gray-700\' mb-2">عدد الكلمات</label>
                        <input type="number" name="words_count" value="{{ old('words_count', $surah->words_count) }}"
                               class="w-full px-4 py-3 rounded-lg transition-all focus:outline-none focus:ring-2 focus:ring-blue-500" :class="darkMode ? \'bg-white/10 border border-white/20 text-white placeholder-gray-400\' : \'glass-light-strong text-gray-900 placeholder-gray-500\' focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold" :class="darkMode ? \'text-gray-300\' : \'text-gray-700\' mb-2">عدد الحروف</label>
                        <input type="number" name="letters_count" value="{{ old('letters_count', $surah->letters_count) }}"
                               class="w-full px-4 py-3 rounded-lg transition-all focus:outline-none focus:ring-2 focus:ring-blue-500" :class="darkMode ? \'bg-white/10 border border-white/20 text-white placeholder-gray-400\' : \'glass-light-strong text-gray-900 placeholder-gray-500\' focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>
            </div>
        </div>

        <div class="rounded-2xl p-6 transition-colors" :class="darkMode ? \'glass-dark\' : \'glass-light-soft\' shadow-xl">
            <h2 class="text-xl font-bold text-white mb-6 flex items-center gap-2">
                <i data-lucide="file-text" class="w-6 h-6"></i>
                الوصف
            </h2>

            <div class="space-y-6">
                <div>
                    <label class="block text-sm font-semibold" :class="darkMode ? \'text-gray-300\' : \'text-gray-700\' mb-2">الوصف (العربية)</label>
                    <textarea name="description[ar]" rows="6"
                              class="w-full px-4 py-3 rounded-lg transition-all focus:outline-none focus:ring-2 focus:ring-blue-500" :class="darkMode ? \'bg-white/10 border border-white/20 text-white placeholder-gray-400\' : \'glass-light-strong text-gray-900 placeholder-gray-500\' focus:outline-none focus:ring-2 focus:ring-blue-500">{{ old('description.ar', $surah->description['ar'] ?? '') }}</textarea>
                </div>

                <div>
                    <label class="block text-sm font-semibold" :class="darkMode ? \'text-gray-300\' : \'text-gray-700\' mb-2">الوصف (English)</label>
                    <textarea name="description[en]" rows="6"
                              class="w-full px-4 py-3 rounded-lg transition-all focus:outline-none focus:ring-2 focus:ring-blue-500" :class="darkMode ? \'bg-white/10 border border-white/20 text-white placeholder-gray-400\' : \'glass-light-strong text-gray-900 placeholder-gray-500\' focus:outline-none focus:ring-2 focus:ring-blue-500">{{ old('description.en', $surah->description['en'] ?? '') }}</textarea>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-between">
            <a href="{{ route('admin.quran.index') }}" class="px-6 py-3 bg-gray-600 hover:bg-gray-700 text-white rounded-lg font-semibold transition-colors">
                إلغاء
            </a>
            <button type="submit" class="px-6 py-3 bg-gradient-to-r from-[#0056b3] to-[#218838] hover:shadow-lg text-white rounded-lg font-semibold transition-all">
                حفظ التعديلات
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    lucide.createIcons();
</script>
@endpush
