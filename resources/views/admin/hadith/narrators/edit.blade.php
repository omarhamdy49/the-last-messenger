@extends('layouts.admin')

@section('page-header')
<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
    <!-- Page Title -->
    <div>
        <div class="flex items-center gap-2 text-sm mb-2" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
            <a href="{{ route('admin.hadith.narrators.index') }}" class="hover:text-accent transition-colors">رواة الأحاديث</a>
            <i data-lucide="chevron-left" class="w-4 h-4"></i>
            <a href="{{ route('admin.hadith.narrators.show', $narrator->id) }}" class="hover:text-accent transition-colors">{{ $narrator->name_ar ?? ($narrator->name['ar'] ?? '') }}</a>
            <i data-lucide="chevron-left" class="w-4 h-4"></i>
            <span>{{ __('admin.edit') }}</span>
        </div>
        <h1 class="text-3xl font-bold gradient-text flex items-center gap-3">
            <div class="p-3 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] shadow-lg">
                <i data-lucide="edit-2" class="w-7 h-7 text-white"></i>
            </div>
            تعديل الراوي: {{ $narrator->name_ar ?? ($narrator->name['ar'] ?? '') }}
        </h1>
    </div>

    <!-- Action Buttons -->
    <div class="flex flex-wrap items-center gap-3">
        <a href="{{ route('admin.hadith.narrators.show', $narrator->id) }}"
           class="px-4 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 flex items-center gap-2 border-2"
           :class="darkMode ? 'border-gray-700 bg-gray-800 text-white hover:bg-gray-700' : 'border-gray-300 bg-white text-gray-900 hover:bg-gray-50'">
            <i data-lucide="x" class="w-5 h-5"></i>
            <span>{{ __('admin.cancel') }}</span>
        </a>
    </div>
</div>
@endsection

@section('content')
<form action="{{ route('admin.hadith.narrators.update', $narrator->id) }}" method="POST" x-data="{ activeTab: 'ar' }">
    @csrf
    @method('PUT')

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Main Form -->
        <div class="lg:col-span-2 space-y-6">

            <!-- Names (Multi-language) -->
            <div class="rounded-2xl border p-6"
                 :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
                <h2 class="text-xl font-bold mb-6 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                    <i data-lucide="languages" class="w-5 h-5 text-accent"></i>
                    الاسم والسيرة
                </h2>

                <!-- Language Tabs -->
                <div class="flex flex-wrap gap-2 border-b pb-4 mb-6" :class="darkMode ? 'border-gray-700' : 'border-gray-200'">
                    @foreach(['ar' => 'العربية', 'en' => 'English', 'fr' => 'Français', 'tr' => 'Türkçe', 'ur' => 'اردو'] as $lang => $label)
                    <button type="button"
                            @click="activeTab = '{{ $lang }}'"
                            class="px-4 py-2 rounded-lg font-medium transition-all"
                            :class="activeTab === '{{ $lang }}' ?
                                'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white' :
                                (darkMode ? 'bg-gray-800 text-gray-400 hover:text-white' : 'bg-gray-100 text-gray-600 hover:text-gray-900')">
                        {{ $label }}
                    </button>
                    @endforeach
                </div>

                <!-- Tab Contents -->
                @foreach(['ar', 'en', 'fr', 'tr', 'ur'] as $lang)
                <div x-show="activeTab === '{{ $lang }}'" class="space-y-4">
                    <!-- Name -->
                    <div>
                        <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            الاسم ({{ strtoupper($lang) }})
                            @if($lang === 'ar') <span class="text-red-500">*</span> @endif
                        </label>
                        <input type="text"
                               name="name[{{ $lang }}]"
                               value="{{ old("name.{$lang}", $narrator->{"name_{$lang}"} ?? ($narrator->name[$lang] ?? '')) }}"
                               class="w-full px-4 py-3 rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                               :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'"
                               dir="{{ in_array($lang, ['ar', 'ur']) ? 'rtl' : 'ltr' }}"
                               placeholder="أدخل اسم الراوي..."
                               {{ $lang === 'ar' ? 'required' : '' }}>
                        @error("name.{$lang}")
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Biography -->
                    <div>
                        <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            السيرة الذاتية ({{ strtoupper($lang) }})
                        </label>
                        <textarea name="biography_json[{{ $lang }}]"
                                  rows="4"
                                  class="w-full px-4 py-3 rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all resize-none"
                                  :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'"
                                  dir="{{ in_array($lang, ['ar', 'ur']) ? 'rtl' : 'ltr' }}"
                                  placeholder="أدخل السيرة الذاتية...">{{ old("biography_json.{$lang}", $narrator->biography_json[$lang] ?? '') }}</textarea>
                        @error("biography_json.{$lang}")
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
                @endforeach
            </div>

            <!-- Personal Details -->
            <div class="rounded-2xl border p-6"
                 :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
                <h2 class="text-xl font-bold mb-6 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                    <i data-lucide="info" class="w-5 h-5 text-accent"></i>
                    التفاصيل الشخصية
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Slug -->
                    <div>
                        <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            الرابط المختصر (Slug)
                        </label>
                        <input type="text"
                               name="slug"
                               value="{{ old('slug', $narrator->slug ?? '') }}"
                               class="w-full px-4 py-3 rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                               :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'"
                               placeholder="abu-hurayrah"
                               pattern="[a-z0-9\-]+">
                        @error('slug')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Reliability Grade -->
                    <div>
                        <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            درجة الموثوقية <span class="text-red-500">*</span>
                        </label>
                        @php
                            $currentGrade = is_object($narrator->reliability_grade) ? $narrator->reliability_grade->value : $narrator->reliability_grade;
                        @endphp
                        <select name="reliability_grade"
                                required
                                class="w-full px-4 py-3 rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                                :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                            <option value="">اختر الدرجة</option>
                            @foreach($grades ?? [] as $grade)
                                <option value="{{ $grade->value }}" {{ old('reliability_grade', $currentGrade) == $grade->value ? 'selected' : '' }}>
                                    {{ $grade->labelArabic() }}
                                </option>
                            @endforeach
                        </select>
                        @error('reliability_grade')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Era -->
                    <div>
                        <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            العصر / الطبقة
                        </label>
                        <input type="text"
                               name="era"
                               value="{{ old('era', $narrator->era ?? '') }}"
                               class="w-full px-4 py-3 rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                               :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'"
                               placeholder="صحابي، تابعي...">
                        @error('era')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Country -->
                    <div>
                        <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            البلد / المنطقة
                        </label>
                        <input type="text"
                               name="country"
                               value="{{ old('country', $narrator->country ?? '') }}"
                               class="w-full px-4 py-3 rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                               :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'"
                               placeholder="المدينة المنورة">
                        @error('country')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Birth Year Hijri -->
                    <div>
                        <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            سنة الولادة (هجري)
                        </label>
                        <input type="number"
                               name="birth_hijri"
                               value="{{ old('birth_hijri', $narrator->birth_hijri ?? $narrator->birth_year_hijri ?? '') }}"
                               class="w-full px-4 py-3 rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                               :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                        @error('birth_hijri')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Death Year Hijri -->
                    <div>
                        <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            سنة الوفاة (هجري)
                        </label>
                        <input type="number"
                               name="death_hijri"
                               value="{{ old('death_hijri', $narrator->death_hijri ?? $narrator->death_year_hijri ?? '') }}"
                               class="w-full px-4 py-3 rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                               :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                        @error('death_hijri')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Teachers & Students -->
            <div class="rounded-2xl border p-6"
                 :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
                <h2 class="text-xl font-bold mb-6 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                    <i data-lucide="network" class="w-5 h-5 text-accent"></i>
                    الشيوخ والتلاميذ
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Teachers -->
                    <div>
                        <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            الشيوخ (المعلمون)
                        </label>
                        @php
                            $currentTeachers = old('teachers', $narrator->teachers ?? []);
                            if (!is_array($currentTeachers)) $currentTeachers = [];
                        @endphp
                        <select name="teachers[]"
                                multiple
                                class="w-full px-4 py-3 rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                                :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'"
                                size="5">
                            @foreach($narrators ?? [] as $n)
                                <option value="{{ $n->id }}" {{ in_array($n->id, $currentTeachers) ? 'selected' : '' }}>
                                    {{ $n->name_ar ?? ($n->name['ar'] ?? '') }}
                                </option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">
                            اضغط Ctrl للاختيار المتعدد
                        </p>
                    </div>

                    <!-- Students -->
                    <div>
                        <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            التلاميذ
                        </label>
                        @php
                            $currentStudents = old('students', $narrator->students ?? []);
                            if (!is_array($currentStudents)) $currentStudents = [];
                        @endphp
                        <select name="students[]"
                                multiple
                                class="w-full px-4 py-3 rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                                :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'"
                                size="5">
                            @foreach($narrators ?? [] as $n)
                                <option value="{{ $n->id }}" {{ in_array($n->id, $currentStudents) ? 'selected' : '' }}>
                                    {{ $n->name_ar ?? ($n->name['ar'] ?? '') }}
                                </option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">
                            اضغط Ctrl للاختيار المتعدد
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">

            <!-- Status -->
            <div class="rounded-2xl border p-6"
                 :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
                <h3 class="text-lg font-bold mb-4 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                    <i data-lucide="toggle-right" class="w-5 h-5 text-accent"></i>
                    الحالة
                </h3>

                <div>
                    <select name="status"
                            class="w-full px-4 py-3 rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                            :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                        <option value="active" {{ old('status', $narrator->status ?? 'active') == 'active' ? 'selected' : '' }}>نشط</option>
                        <option value="inactive" {{ old('status', $narrator->status ?? '') == 'inactive' ? 'selected' : '' }}>غير نشط</option>
                    </select>
                </div>
            </div>

            <!-- Info -->
            <div class="rounded-2xl border p-6"
                 :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
                <h3 class="text-lg font-bold mb-4 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                    <i data-lucide="info" class="w-5 h-5 text-accent"></i>
                    معلومات
                </h3>

                <div class="space-y-3">
                    <div class="flex items-center justify-between p-3 rounded-xl" :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                        <span :class="darkMode ? 'text-gray-400' : 'text-gray-600'">تاريخ الإنشاء</span>
                        <span class="font-medium" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $narrator->created_at?->format('Y/m/d') }}</span>
                    </div>
                    <div class="flex items-center justify-between p-3 rounded-xl" :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                        <span :class="darkMode ? 'text-gray-400' : 'text-gray-600'">آخر تحديث</span>
                        <span class="font-medium" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $narrator->updated_at?->format('Y/m/d') }}</span>
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="rounded-2xl border p-6"
                 :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
                <button type="submit"
                        class="w-full px-6 py-3 rounded-xl font-semibold transition-all hover:scale-105 bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-lg flex items-center justify-center gap-2">
                    <i data-lucide="save" class="w-5 h-5"></i>
                    {{ __('admin.save') }}
                </button>

                <div class="flex gap-3 mt-4">
                    <a href="{{ route('admin.hadith.narrators.show', $narrator->id) }}"
                       class="flex-1 px-4 py-2.5 rounded-xl font-semibold text-center transition-all hover:scale-105 border"
                       :class="darkMode ? 'border-gray-700 bg-gray-800 hover:bg-gray-700 text-white' : 'border-gray-200 bg-white hover:bg-gray-50 text-gray-900'">
                        {{ __('admin.cancel') }}
                    </a>
                    <a href="{{ route('admin.hadith.narrators.index') }}"
                       class="flex-1 px-4 py-2.5 rounded-xl font-semibold text-center transition-all hover:scale-105 border"
                       :class="darkMode ? 'border-gray-700 bg-gray-800 hover:bg-gray-700 text-white' : 'border-gray-200 bg-white hover:bg-gray-50 text-gray-900'">
                        القائمة
                    </a>
                </div>
            </div>

        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        lucide.createIcons();
    });
</script>
@endpush
