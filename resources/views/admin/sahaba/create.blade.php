@extends('layouts.admin')

@section('page-header')
<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
    <!-- Page Title -->
    <div>
        <div class="flex items-center gap-2 text-sm mb-2" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
            <a href="{{ route('admin.sahaba.index') }}" class="hover:text-accent transition-colors">الصحابة الكرام</a>
            <i data-lucide="chevron-left" class="w-4 h-4"></i>
            <span>إضافة صحابي جديد</span>
        </div>
        <h1 class="text-3xl font-bold gradient-text flex items-center gap-3">
            <div class="p-3 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] shadow-lg">
                <i data-lucide="plus" class="w-7 h-7 text-white"></i>
            </div>
            إضافة صحابي جديد
        </h1>
    </div>

    <!-- Action Buttons -->
    <div class="flex flex-wrap items-center gap-3">
        <a href="{{ route('admin.sahaba.index') }}"
           class="px-4 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 flex items-center gap-2 border-2"
           :class="darkMode ? 'border-gray-700 bg-gray-800 text-white hover:bg-gray-700' : 'border-gray-300 bg-white text-gray-900 hover:bg-gray-50'">
            <i data-lucide="x" class="w-5 h-5"></i>
            <span>{{ __('admin.cancel') }}</span>
        </a>
    </div>
</div>
@endsection

@section('content')
<form action="{{ route('admin.sahaba.store') }}" method="POST" enctype="multipart/form-data" x-data="{ activeTab: 'ar' }">
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
                               placeholder="أدخل اسم الصحابي..."
                               {{ $lang === 'ar' ? 'required' : '' }}>
                        @error("name.{$lang}")
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Full Name -->
                    <div>
                        <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            الاسم الكامل ({{ strtoupper($lang) }})
                        </label>
                        <input type="text"
                               name="full_name[{{ $lang }}]"
                               value="{{ old("full_name.{$lang}") }}"
                               class="w-full px-4 py-3 rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                               :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'"
                               dir="{{ in_array($lang, ['ar', 'ur']) ? 'rtl' : 'ltr' }}"
                               placeholder="أدخل الاسم الكامل...">
                        @error("full_name.{$lang}")
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Kunya -->
                    <div>
                        <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            الكنية ({{ strtoupper($lang) }})
                        </label>
                        <input type="text"
                               name="kunya[{{ $lang }}]"
                               value="{{ old("kunya.{$lang}") }}"
                               class="w-full px-4 py-3 rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                               :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'"
                               dir="{{ in_array($lang, ['ar', 'ur']) ? 'rtl' : 'ltr' }}"
                               placeholder="مثال: أبو بكر">
                        @error("kunya.{$lang}")
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Laqab (honorific) -->
                    <div>
                        <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            اللقب ({{ strtoupper($lang) }})
                        </label>
                        <input type="text"
                               name="laqab[{{ $lang }}]"
                               value="{{ old("laqab.{$lang}") }}"
                               class="w-full px-4 py-3 rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                               :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'"
                               dir="{{ in_array($lang, ['ar', 'ur']) ? 'rtl' : 'ltr' }}"
                               placeholder="مثال: الصديق">
                        @error("laqab.{$lang}")
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Title -->
                    <div>
                        <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            اللقب الرسمي ({{ strtoupper($lang) }})
                        </label>
                        <input type="text"
                               name="title[{{ $lang }}]"
                               value="{{ old("title.{$lang}") }}"
                               class="w-full px-4 py-3 rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                               :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'"
                               dir="{{ in_array($lang, ['ar', 'ur']) ? 'rtl' : 'ltr' }}"
                               placeholder="مثال: الخليفة الأول">
                        @error("title.{$lang}")
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Occupation -->
                    <div>
                        <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            المهنة / الدور ({{ strtoupper($lang) }})
                        </label>
                        <input type="text"
                               name="occupation[{{ $lang }}]"
                               value="{{ old("occupation.{$lang}") }}"
                               class="w-full px-4 py-3 rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                               :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'"
                               dir="{{ in_array($lang, ['ar', 'ur']) ? 'rtl' : 'ltr' }}"
                               placeholder="مثال: تاجر">
                        @error("occupation.{$lang}")
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Biography -->
                    <div>
                        <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            السيرة الذاتية ({{ strtoupper($lang) }})
                        </label>
                        <x-admin.form.wysiwyg
                            name="biography[{{ $lang }}]"
                            :value="old('biography.'.$lang)"
                            dir="{{ in_array($lang, ['ar', 'ur']) ? 'rtl' : 'ltr' }}"
                            height="240px"
                            placeholder="أدخل السيرة الذاتية..." />
                        @error("biography.{$lang}")
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Virtues -->
                    <div>
                        <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            الفضائل والمناقب ({{ strtoupper($lang) }})
                        </label>
                        <x-admin.form.wysiwyg
                            name="virtues[{{ $lang }}]"
                            :value="old('virtues.'.$lang)"
                            dir="{{ in_array($lang, ['ar', 'ur']) ? 'rtl' : 'ltr' }}"
                            height="180px"
                            placeholder="أدخل الفضائل والمناقب..." />
                        @error("virtues.{$lang}")
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Famous Narrations -->
                    <div>
                        <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            الأحاديث المشهورة ({{ strtoupper($lang) }})
                        </label>
                        <x-admin.form.wysiwyg
                            name="famous_narrations[{{ $lang }}]"
                            :value="old('famous_narrations.'.$lang)"
                            dir="{{ in_array($lang, ['ar', 'ur']) ? 'rtl' : 'ltr' }}"
                            height="180px"
                            placeholder="أدخل الأحاديث المشهورة..." />
                        @error("famous_narrations.{$lang}")
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Martyrdom -->
                    <div>
                        <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            الاستشهاد / سبب الوفاة ({{ strtoupper($lang) }})
                        </label>
                        <x-admin.form.wysiwyg
                            name="martyrdom[{{ $lang }}]"
                            :value="old('martyrdom.'.$lang)"
                            dir="{{ in_array($lang, ['ar', 'ur']) ? 'rtl' : 'ltr' }}"
                            height="150px"
                            placeholder="أدخل تفاصيل الاستشهاد إن وجدت..." />
                        @error("martyrdom.{$lang}")
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Notes -->
                    <div>
                        <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            ملاحظات ({{ strtoupper($lang) }})
                        </label>
                        <textarea name="notes[{{ $lang }}]" rows="3"
                                  class="w-full px-4 py-3 rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                                  :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'"
                                  dir="{{ in_array($lang, ['ar', 'ur']) ? 'rtl' : 'ltr' }}"
                                  placeholder="أدخل ملاحظات إضافية...">{{ old("notes.{$lang}") }}</textarea>
                        @error("notes.{$lang}")
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
                               placeholder="abu-bakr-al-siddiq" pattern="[a-z0-9\-]+" required>
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

                    <!-- Categories (a companion can hold several at once) -->
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            المراتب التاريخية
                        </label>
                        <div class="flex flex-wrap gap-3">
                            @foreach(\App\Domain\Sahaba\Enums\CompanionStatus::cases() as $category)
                                <label class="flex items-center gap-2 px-3 py-2 rounded-xl border cursor-pointer"
                                       :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                                    <input type="checkbox" name="categories[]" value="{{ $category->value }}"
                                           {{ in_array($category->value, old('categories', [])) ? 'checked' : '' }}>
                                    {{ $category->labelArabic() }}
                                </label>
                            @endforeach
                        </div>
                        @error('categories') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
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

                    <!-- Clan -->
                    <div>
                        <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">البطن / الفخذ</label>
                        <input type="text" name="clan" value="{{ old('clan') }}"
                               class="w-full px-4 py-3 rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                               :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'"
                               placeholder="مثال: بنو هاشم">
                        @error('clan') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                    </div>

                    <!-- Birth Date Hijri -->
                    <div>
                        <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">تاريخ الولادة (هجري)</label>
                        <input type="text" name="birth_date_hijri" value="{{ old('birth_date_hijri') }}"
                               class="w-full px-4 py-3 rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                               :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'"
                               placeholder="مثال: 51 ق.هـ">
                        @error('birth_date_hijri') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                    </div>

                    <!-- Death Date Hijri -->
                    <div>
                        <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">تاريخ الوفاة (هجري)</label>
                        <input type="text" name="death_date_hijri" value="{{ old('death_date_hijri') }}"
                               class="w-full px-4 py-3 rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                               :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'"
                               placeholder="مثال: 13 هـ">
                        @error('death_date_hijri') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                    </div>

                    <!-- Birth Place -->
                    <div>
                        <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">مكان الولادة</label>
                        <input type="text" name="birth_place" value="{{ old('birth_place') }}"
                               class="w-full px-4 py-3 rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                               :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'"
                               placeholder="مثال: مكة المكرمة">
                        @error('birth_place') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                    </div>

                    <!-- Death Place -->
                    <div>
                        <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">مكان الوفاة</label>
                        <input type="text" name="death_place" value="{{ old('death_place') }}"
                               class="w-full px-4 py-3 rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                               :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'"
                               placeholder="مثال: المدينة المنورة">
                        @error('death_place') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                    </div>

                    <!-- Burial Place -->
                    <div>
                        <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">مكان الدفن</label>
                        <input type="text" name="burial_place" value="{{ old('burial_place') }}"
                               class="w-full px-4 py-3 rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                               :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'"
                               placeholder="مثال: البقيع">
                        @error('burial_place') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                    </div>

                    <!-- Reported Hadith Count (source claim, not a live count from this database) -->
                    <div>
                        <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">عدد الأحاديث المروية (كما ورد في المصدر)</label>
                        <input type="number" name="reported_hadith_count" value="{{ old('reported_hadith_count', 0) }}" min="0"
                               class="w-full px-4 py-3 rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                               :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                        @error('reported_hadith_count') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                    </div>

                    <!-- Importance Rank -->
                    <div>
                        <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">ترتيب الأهمية</label>
                        <input type="number" name="importance_rank" value="{{ old('importance_rank', 0) }}" min="0"
                               class="w-full px-4 py-3 rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                               :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                        @error('importance_rank') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
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

                    <!-- Age at Death -->
                    <div>
                        <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">العمر عند الوفاة</label>
                        <input type="number" name="age_at_death" value="{{ old('age_at_death') }}" min="0"
                               class="w-full px-4 py-3 rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                               :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                        @error('age_at_death') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                    </div>

                    <!-- Conversion Date Hijri -->
                    <div>
                        <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">تاريخ الإسلام (هجري)</label>
                        <input type="text" name="conversion_date_hijri" value="{{ old('conversion_date_hijri') }}"
                               class="w-full px-4 py-3 rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                               :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'"
                               placeholder="مثال: 1 هـ">
                        @error('conversion_date_hijri') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                    </div>

                    <!-- Conversion Order (As-Sabiqun al-Awwalun ordering) -->
                    <div>
                        <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">ترتيب الإسلام (من السابقين الأولين)</label>
                        <input type="number" name="conversion_order" value="{{ old('conversion_order') }}" min="1"
                               class="w-full px-4 py-3 rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                               :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'"
                               placeholder="مثال: 3">
                        @error('conversion_order') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                    </div>

                    <!-- Roles / tags -->
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">الأدوار (مفصولة بفواصل)</label>
                        <input type="text" name="roles" value="{{ old('roles') }}"
                               class="w-full px-4 py-3 rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                               :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'"
                               placeholder="مثال: خليفة، قائد عسكري، راوي">
                        @error('roles') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
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
                    صورة الصحابي
                </h3>

                <div class="space-y-4">
                    <x-admin.image-upload
                        name="profile_image"
                        aspectClass="aspect-square"
                        placeholderIcon="user"
                        maxSize="10MB"
                        formats="JPG, PNG, WebP"
                    />
                </div>
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
                        <input type="checkbox" name="is_featured" value="1"
                               {{ old('is_featured') ? 'checked' : '' }}
                               class="w-5 h-5 rounded text-amber-500 border-gray-300 focus:ring-amber-500">
                        <span :class="darkMode ? 'text-gray-300' : 'text-gray-700'">صحابي مميز</span>
                    </label>

                    <label class="flex items-center gap-3 cursor-pointer p-3 rounded-xl transition-all"
                           :class="darkMode ? 'hover:bg-gray-800' : 'hover:bg-gray-50'">
                        <input type="checkbox" name="is_verified" value="1"
                               {{ old('is_verified') ? 'checked' : '' }}
                               class="w-5 h-5 rounded text-green-500 border-gray-300 focus:ring-green-500">
                        <span :class="darkMode ? 'text-gray-300' : 'text-gray-700'">بيانات موثقة</span>
                    </label>

                    <div>
                        <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">الجيل (بالنسبة للنبي ﷺ)</label>
                        <input type="number" name="generation" value="{{ old('generation') }}" min="0"
                               class="w-full px-4 py-3 rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                               :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                        @error('generation') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
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

                <a href="{{ route('admin.sahaba.index') }}"
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
