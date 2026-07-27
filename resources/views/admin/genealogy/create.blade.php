@extends('layouts.admin')

@section('page-header')
<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
    <!-- Page Title -->
    <div>
        <div class="flex items-center gap-2 text-sm mb-2" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
            <a href="{{ route('admin.genealogy.index') }}" class="hover:text-accent transition-colors">شجرة النسب</a>
            <i data-lucide="chevron-left" class="w-4 h-4"></i>
            <span>إضافة شخص جديد</span>
        </div>
        <h1 class="text-3xl font-bold gradient-text flex items-center gap-3">
            <div class="p-3 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] shadow-lg">
                <i data-lucide="plus" class="w-7 h-7 text-white"></i>
            </div>
            إضافة شخص جديد
        </h1>
    </div>

    <!-- Action Buttons -->
    <div class="flex flex-wrap items-center gap-3">
        <a href="{{ route('admin.genealogy.index') }}"
           class="px-4 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 flex items-center gap-2 border-2"
           :class="darkMode ? 'border-gray-700 bg-gray-800 text-white hover:bg-gray-700' : 'border-gray-300 bg-white text-gray-900 hover:bg-gray-50'">
            <i data-lucide="x" class="w-5 h-5"></i>
            <span>{{ __('admin.cancel') }}</span>
        </a>
    </div>
</div>
@endsection

@section('content')
<form action="{{ route('admin.genealogy.store') }}" method="POST" enctype="multipart/form-data" x-data="{ activeTab: 'ar' }">
    @csrf

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
                               value="{{ old("name.{$lang}") }}"
                               class="w-full px-4 py-3 rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                               :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'"
                               dir="{{ in_array($lang, ['ar', 'ur']) ? 'rtl' : 'ltr' }}"
                               placeholder="أدخل الاسم..."
                               {{ $lang === 'ar' ? 'required' : '' }}>
                        @error("name.{$lang}")
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Title -->
                    <div>
                        <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            اللقب ({{ strtoupper($lang) }})
                        </label>
                        <input type="text"
                               name="title[{{ $lang }}]"
                               value="{{ old("title.{$lang}") }}"
                               class="w-full px-4 py-3 rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                               :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'"
                               dir="{{ in_array($lang, ['ar', 'ur']) ? 'rtl' : 'ltr' }}"
                               placeholder="مثال: الصدّيق">
                        @error("title.{$lang}")
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Biography -->
                    <div>
                        <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            السيرة الذاتية ({{ strtoupper($lang) }})
                        </label>
                        <textarea name="biography[{{ $lang }}]"
                                  rows="5"
                                  class="w-full px-4 py-3 rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all resize-none"
                                  :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'"
                                  dir="{{ in_array($lang, ['ar', 'ur']) ? 'rtl' : 'ltr' }}"
                                  placeholder="أدخل السيرة الذاتية...">{{ old("biography.{$lang}") }}</textarea>
                        @error("biography.{$lang}")
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
                @endforeach
            </div>

            <!-- Details -->
            <div class="rounded-2xl border p-6"
                 :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
                <h2 class="text-xl font-bold mb-6 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                    <i data-lucide="info" class="w-5 h-5 text-accent"></i>
                    التفاصيل
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Slug -->
                    <div>
                        <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            الرابط المختصر (Slug) <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="slug" value="{{ old('slug') }}"
                               class="w-full px-4 py-3 rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                               :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'"
                               placeholder="prophet-muhammad" pattern="[a-z0-9\-]+" required>
                        <p class="mt-1 text-xs" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">أحرف إنجليزية صغيرة وأرقام وشرطات فقط</p>
                        @error('slug') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                    </div>

                    <!-- Gender -->
                    <div>
                        <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            الجنس <span class="text-red-500">*</span>
                        </label>
                        <select name="gender" required
                                class="w-full px-4 py-3 rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                                :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                            <option value="">اختر الجنس</option>
                            <option value="male" {{ old('gender') == 'male' ? 'selected' : '' }}>ذكر</option>
                            <option value="female" {{ old('gender') == 'female' ? 'selected' : '' }}>أنثى</option>
                        </select>
                        @error('gender') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                    </div>

                    <!-- Person Type -->
                    <div>
                        <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            النوع <span class="text-red-500">*</span>
                        </label>
                        <select name="person_type" required
                                class="w-full px-4 py-3 rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                                :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                            <option value="">اختر النوع</option>
                            @foreach(\App\Domain\Genealogy\Enums\PersonType::cases() as $type)
                                <option value="{{ $type->value }}" {{ old('person_type') == $type->value ? 'selected' : '' }}>
                                    {{ $type->label()['ar'] }}
                                </option>
                            @endforeach
                        </select>
                        @error('person_type') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                    </div>

                    <!-- Tribe -->
                    <div>
                        <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">القبيلة</label>
                        <input type="text" name="tribe" value="{{ old('tribe') }}"
                               class="w-full px-4 py-3 rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                               :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'"
                               placeholder="مثال: قريش">
                        @error('tribe') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                    </div>

                    <!-- Father ID -->
                    <div>
                        <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">معرّف الأب (ID)</label>
                        <input type="number" name="father_id" value="{{ old('father_id') }}" min="1"
                               class="w-full px-4 py-3 rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                               :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'"
                               placeholder="أدخل معرّف الأب من قاعدة البيانات">
                        @error('father_id') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                    </div>

                    <!-- Mother ID -->
                    <div>
                        <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">معرّف الأم (ID)</label>
                        <input type="number" name="mother_id" value="{{ old('mother_id') }}" min="1"
                               class="w-full px-4 py-3 rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                               :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'"
                               placeholder="أدخل معرّف الأم من قاعدة البيانات">
                        @error('mother_id') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                    </div>

                    <!-- Tree Display Order -->
                    <div>
                        <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">ترتيب الظهور في الشجرة العامة</label>
                        <input type="number" name="tree_display_order" value="{{ old('tree_display_order') }}" min="0"
                               class="w-full px-4 py-3 rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                               :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                        <p class="mt-1 text-xs" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">الأصغر يظهر أولاً بين إخوته في الشجرة؛ اتركه فارغاً للترتيب الافتراضي</p>
                        @error('tree_display_order') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                    </div>

                    <!-- Birth Date Hijri -->
                    <div>
                        <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">تاريخ الولادة (هجري)</label>
                        <input type="date" name="birth_date_hijri" value="{{ old('birth_date_hijri') }}"
                               class="w-full px-4 py-3 rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                               :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                        @error('birth_date_hijri') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                    </div>

                    <!-- Death Date Hijri -->
                    <div>
                        <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">تاريخ الوفاة (هجري)</label>
                        <input type="date" name="death_date_hijri" value="{{ old('death_date_hijri') }}"
                               class="w-full px-4 py-3 rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                               :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                        @error('death_date_hijri') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                    </div>

                    <!-- Birth Date Gregorian -->
                    <div>
                        <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">تاريخ الولادة (ميلادي)</label>
                        <input type="date" name="birth_date_gregorian" value="{{ old('birth_date_gregorian') }}"
                               class="w-full px-4 py-3 rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                               :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                        @error('birth_date_gregorian') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                    </div>

                    <!-- Death Date Gregorian -->
                    <div>
                        <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">تاريخ الوفاة (ميلادي)</label>
                        <input type="date" name="death_date_gregorian" value="{{ old('death_date_gregorian') }}"
                               class="w-full px-4 py-3 rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                               :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                        @error('death_date_gregorian') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">

            <!-- Profile Image -->
            <div class="rounded-2xl border p-6"
                 :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
                <h3 class="text-lg font-bold mb-4 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                    <i data-lucide="image" class="w-5 h-5 text-accent"></i>
                    الصورة
                </h3>

                <x-admin.image-upload
                    name="profile_image"
                    aspectClass="aspect-square"
                    placeholderIcon="user"
                    maxSize="10MB"
                    formats="JPG, PNG, WebP"
                    :mediaLibrary="false"
                />
            </div>

            <!-- Status -->
            <div class="rounded-2xl border p-6"
                 :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
                <h3 class="text-lg font-bold mb-4 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                    <i data-lucide="toggle-right" class="w-5 h-5 text-accent"></i>
                    الحالة
                </h3>

                <div class="space-y-4">
                    <label class="flex items-center gap-3 cursor-pointer p-3 rounded-xl transition-all"
                           :class="darkMode ? 'hover:bg-gray-800' : 'hover:bg-gray-50'">
                        <input type="checkbox" name="is_verified" value="1"
                               {{ old('is_verified') ? 'checked' : '' }}
                               class="w-5 h-5 rounded text-accent border-gray-300 focus:ring-accent">
                        <span :class="darkMode ? 'text-gray-300' : 'text-gray-700'">موثّق</span>
                    </label>

                    <label class="flex items-center gap-3 cursor-pointer p-3 rounded-xl transition-all"
                           :class="darkMode ? 'hover:bg-gray-800' : 'hover:bg-gray-50'">
                        <input type="checkbox" name="is_featured" value="1"
                               {{ old('is_featured') ? 'checked' : '' }}
                               class="w-5 h-5 rounded text-amber-500 border-gray-300 focus:ring-amber-500">
                        <span :class="darkMode ? 'text-gray-300' : 'text-gray-700'">مميز</span>
                    </label>

                    <label class="flex items-center gap-3 cursor-pointer p-3 rounded-xl transition-all"
                           :class="darkMode ? 'hover:bg-gray-800' : 'hover:bg-gray-50'">
                        <input type="hidden" name="hide_from_tree" value="0">
                        <input type="checkbox" name="hide_from_tree" value="1"
                               {{ old('hide_from_tree') ? 'checked' : '' }}
                               class="w-5 h-5 rounded text-red-500 border-gray-300 focus:ring-red-500">
                        <span :class="darkMode ? 'text-gray-300' : 'text-gray-700'">إخفاء من الشجرة العامة</span>
                    </label>
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

                <a href="{{ route('admin.genealogy.index') }}"
                   class="mt-4 w-full px-4 py-2.5 rounded-xl font-semibold text-center transition-all hover:scale-105 border flex items-center justify-center gap-2"
                   :class="darkMode ? 'border-gray-700 bg-gray-800 hover:bg-gray-700 text-white' : 'border-gray-200 bg-white hover:bg-gray-50 text-gray-900'">
                    {{ __('admin.cancel') }}
                </a>
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
