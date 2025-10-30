@extends('layouts.admin')

@section('title', 'إضافة حدث جديد')

@push('styles')
<style>
    .tab-button {
        transition: all 0.3s ease;
        position: relative;
    }

    .tab-button.active {
        background: linear-gradient(135deg, #0056b3 0%, #218838 100%);
        color: white;
        box-shadow: 0 4px 15px rgba(0, 86, 179, 0.3);
    }

    .tab-button.active::after {
        content: '';
        position: absolute;
        bottom: -2px;
        left: 50%;
        transform: translateX(-50%);
        width: 80%;
        height: 3px;
        background: linear-gradient(135deg, #0056b3 0%, #218838 100%);
        border-radius: 2px;
    }

    .tab-content {
        display: none;
    }

    .tab-content.active {
        display: block;
        animation: fadeInUp 0.4s ease;
    }

    .form-input {
        transition: all 0.3s ease;
    }

    .form-input:focus {
        box-shadow: 0 0 0 3px rgba(0, 86, 179, 0.1);
        border-color: #0056b3;
    }

    .btn-submit {
        background: linear-gradient(135deg, #0056b3 0%, #218838 100%);
        transition: all 0.3s ease;
    }

    .btn-submit:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 25px rgba(0, 86, 179, 0.3);
    }

    .btn-submit:active {
        transform: translateY(0);
    }

    .btn-submit.loading {
        position: relative;
        color: transparent;
    }

    .btn-submit.loading::after {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 20px;
        height: 20px;
        border: 2px solid white;
        border-top-color: transparent;
        border-radius: 50%;
        animation: spin 0.6s linear infinite;
    }

    @keyframes spin {
        to { transform: translate(-50%, -50%) rotate(360deg); }
    }

    .required-asterisk {
        color: #dc2626;
        margin-right: 4px;
    }
</style>
@endpush

@section('content')
<div class="max-w-6xl mx-auto" x-data="seerahEventForm()">
    <!-- Page Header -->
    <div class="mb-8">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h1 class="text-3xl font-bold gradient-text mb-2">إضافة حدث جديد في السيرة</h1>
                <p class="text-gray-600 dark:text-gray-400">قم بإنشاء حدث تاريخي جديد في السيرة النبوية الشريفة</p>
            </div>
            <a href="{{ route('admin.seerah-events.index') }}"
               class="flex items-center gap-2 px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">
                <i data-lucide="arrow-right" class="w-5 h-5"></i>
                <span>العودة للقائمة</span>
            </a>
        </div>
    </div>

    <!-- Form Card -->
    <form action="{{ route('admin.seerah-events.store') }}" method="POST" @submit="handleSubmit" class="space-y-6">
        @csrf

        <!-- Basic Information Card -->
        <div class="rounded-2xl p-6 transition-colors" :class="darkMode ? \'glass-dark\' : \'glass-light-soft\' shadow-xl">
            <h2 class="text-xl font-bold text-white mb-6 flex items-center gap-2">
                <i data-lucide="timeline" class="w-6 h-6"></i>
                المعلومات الأساسية
            </h2>

            <div class="space-y-6">
                <!-- Slug -->
                <div>
                    <label class="block text-sm font-semibold" :class="darkMode ? \'text-gray-300\' : \'text-gray-700\' mb-2">
                        <span class="required-asterisk">*</span>
                        الرابط الثابت (Slug)
                    </label>
                    <input type="text"
                           name="slug"
                           id="slug"
                           value="{{ old('slug') }}"
                           class="form-input w-full px-4 py-3 bg-white/10 border border-white/20 rounded-lg text-white placeholder-gray-400 focus:outline-none"
                           placeholder="battle-of-badr"
                           required>
                    @error('slug')
                        <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                    @enderror
                    <p class="mt-2 text-xs text-gray-400">الرابط الفريد للحدث (بالإنجليزية، بدون مسافات)</p>
                </div>

                <!-- Event Type & Importance Row -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Event Type -->
                    <div>
                        <label class="block text-sm font-semibold" :class="darkMode ? \'text-gray-300\' : \'text-gray-700\' mb-2">
                            <span class="required-asterisk">*</span>
                            نوع الحدث
                        </label>
                        <select name="event_type"
                                id="event_type"
                                class="form-input w-full px-4 py-3 bg-white/10 border border-white/20 rounded-lg text-white focus:outline-none"
                                required>
                            <option value="">اختر نوع الحدث</option>
                            <option value="birth" {{ old('event_type') == 'birth' ? 'selected' : '' }}>ولادة</option>
                            <option value="death" {{ old('event_type') == 'death' ? 'selected' : '' }}>وفاة</option>
                            <option value="battle" {{ old('event_type') == 'battle' ? 'selected' : '' }}>غزوة/معركة</option>
                            <option value="migration" {{ old('event_type') == 'migration' ? 'selected' : '' }}>هجرة</option>
                            <option value="revelation" {{ old('event_type') == 'revelation' ? 'selected' : '' }}>نزول وحي</option>
                            <option value="treaty" {{ old('event_type') == 'treaty' ? 'selected' : '' }}>معاهدة</option>
                            <option value="marriage" {{ old('event_type') == 'marriage' ? 'selected' : '' }}>زواج</option>
                            <option value="miracle" {{ old('event_type') == 'miracle' ? 'selected' : '' }}>معجزة</option>
                            <option value="other" {{ old('event_type') == 'other' ? 'selected' : '' }}>حدث آخر</option>
                        </select>
                        @error('event_type')
                            <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Importance -->
                    <div>
                        <label class="block text-sm font-semibold" :class="darkMode ? \'text-gray-300\' : \'text-gray-700\' mb-2">
                            <span class="required-asterisk">*</span>
                            مستوى الأهمية
                        </label>
                        <select name="importance"
                                id="importance"
                                class="form-input w-full px-4 py-3 bg-white/10 border border-white/20 rounded-lg text-white focus:outline-none"
                                required>
                            <option value="">اختر مستوى الأهمية</option>
                            <option value="high" {{ old('importance') == 'high' ? 'selected' : '' }}>أهمية عالية</option>
                            <option value="medium" {{ old('importance') == 'medium' ? 'selected' : '' }}>أهمية متوسطة</option>
                            <option value="low" {{ old('importance') == 'low' ? 'selected' : '' }}>أهمية منخفضة</option>
                        </select>
                        @error('importance')
                            <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Dates Section -->
                <div class="border-t border-white/10 pt-6">
                    <h3 class="text-lg font-bold text-white mb-4 flex items-center gap-2">
                        <i data-lucide="calendar" class="w-5 h-5"></i>
                        التواريخ
                    </h3>

                    <!-- Hijri Date -->
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
                        <div class="md:col-span-1">
                            <label class="block text-sm font-semibold" :class="darkMode ? \'text-gray-300\' : \'text-gray-700\' mb-2">
                                التاريخ الهجري (نص)
                            </label>
                            <input type="text"
                                   name="hijri_date"
                                   id="hijri_date"
                                   value="{{ old('hijri_date') }}"
                                   class="form-input w-full px-4 py-3 bg-white/10 border border-white/20 rounded-lg text-white placeholder-gray-400 focus:outline-none"
                                   placeholder="1 رمضان 2 هـ">
                            @error('hijri_date')
                                <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-semibold" :class="darkMode ? \'text-gray-300\' : \'text-gray-700\' mb-2">
                                السنة الهجرية
                            </label>
                            <input type="number"
                                   name="hijri_year"
                                   value="{{ old('hijri_year') }}"
                                   class="form-input w-full px-4 py-3 bg-white/10 border border-white/20 rounded-lg text-white placeholder-gray-400 focus:outline-none"
                                   placeholder="2">
                            @error('hijri_year')
                                <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-semibold" :class="darkMode ? \'text-gray-300\' : \'text-gray-700\' mb-2">
                                الشهر الهجري
                            </label>
                            <input type="number"
                                   name="hijri_month"
                                   value="{{ old('hijri_month') }}"
                                   min="1"
                                   max="12"
                                   class="form-input w-full px-4 py-3 bg-white/10 border border-white/20 rounded-lg text-white placeholder-gray-400 focus:outline-none"
                                   placeholder="9">
                            @error('hijri_month')
                                <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-semibold" :class="darkMode ? \'text-gray-300\' : \'text-gray-700\' mb-2">
                                اليوم الهجري
                            </label>
                            <input type="number"
                                   name="hijri_day"
                                   value="{{ old('hijri_day') }}"
                                   min="1"
                                   max="30"
                                   class="form-input w-full px-4 py-3 bg-white/10 border border-white/20 rounded-lg text-white placeholder-gray-400 focus:outline-none"
                                   placeholder="1">
                            @error('hijri_day')
                                <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Gregorian Date -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold" :class="darkMode ? \'text-gray-300\' : \'text-gray-700\' mb-2">
                                التاريخ الميلادي
                            </label>
                            <input type="date"
                                   name="gregorian_date"
                                   value="{{ old('gregorian_date') }}"
                                   class="form-input w-full px-4 py-3 bg-white/10 border border-white/20 rounded-lg text-white focus:outline-none">
                            @error('gregorian_date')
                                <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-semibold" :class="darkMode ? \'text-gray-300\' : \'text-gray-700\' mb-2">
                                السنة الميلادية
                            </label>
                            <input type="number"
                                   name="gregorian_year"
                                   value="{{ old('gregorian_year') }}"
                                   class="form-input w-full px-4 py-3 bg-white/10 border border-white/20 rounded-lg text-white placeholder-gray-400 focus:outline-none"
                                   placeholder="624">
                            @error('gregorian_year')
                                <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Location Section -->
                <div class="border-t border-white/10 pt-6">
                    <h3 class="text-lg font-bold text-white mb-4 flex items-center gap-2">
                        <i data-lucide="map-pin" class="w-5 h-5"></i>
                        الموقع الجغرافي
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-semibold" :class="darkMode ? \'text-gray-300\' : \'text-gray-700\' mb-2">
                                اسم الموقع
                            </label>
                            <input type="text"
                                   name="location"
                                   value="{{ old('location') }}"
                                   class="form-input w-full px-4 py-3 bg-white/10 border border-white/20 rounded-lg text-white placeholder-gray-400 focus:outline-none"
                                   placeholder="مثال: بدر، مكة، المدينة">
                            @error('location')
                                <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-semibold" :class="darkMode ? \'text-gray-300\' : \'text-gray-700\' mb-2">
                                خط العرض (Latitude)
                            </label>
                            <input type="number"
                                   name="latitude"
                                   value="{{ old('latitude') }}"
                                   step="0.0000001"
                                   class="form-input w-full px-4 py-3 bg-white/10 border border-white/20 rounded-lg text-white placeholder-gray-400 focus:outline-none"
                                   placeholder="23.5880">
                            @error('latitude')
                                <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-semibold" :class="darkMode ? \'text-gray-300\' : \'text-gray-700\' mb-2">
                                خط الطول (Longitude)
                            </label>
                            <input type="number"
                                   name="longitude"
                                   value="{{ old('longitude') }}"
                                   step="0.0000001"
                                   class="form-input w-full px-4 py-3 bg-white/10 border border-white/20 rounded-lg text-white placeholder-gray-400 focus:outline-none"
                                   placeholder="38.7436">
                            @error('longitude')
                                <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Media & Settings -->
                <div class="border-t border-white/10 pt-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Featured Image -->
                        <div>
                            <label class="block text-sm font-semibold" :class="darkMode ? \'text-gray-300\' : \'text-gray-700\' mb-2">
                                الصورة المميزة (URL)
                            </label>
                            <input type="url"
                                   name="featured_image"
                                   value="{{ old('featured_image') }}"
                                   class="form-input w-full px-4 py-3 bg-white/10 border border-white/20 rounded-lg text-white placeholder-gray-400 focus:outline-none"
                                   placeholder="https://example.com/image.jpg">
                            @error('featured_image')
                                <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Display Order -->
                        <div>
                            <label class="block text-sm font-semibold" :class="darkMode ? \'text-gray-300\' : \'text-gray-700\' mb-2">
                                ترتيب العرض
                            </label>
                            <input type="number"
                                   name="display_order"
                                   value="{{ old('display_order', 0) }}"
                                   class="form-input w-full px-4 py-3 bg-white/10 border border-white/20 rounded-lg text-white placeholder-gray-400 focus:outline-none"
                                   placeholder="0">
                            @error('display_order')
                                <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                            @enderror
                            <p class="mt-2 text-xs text-gray-400">رقم أكبر = يظهر أولاً</p>
                        </div>
                    </div>

                    <!-- Is Featured -->
                    <div class="mt-4">
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="hidden" name="is_featured" value="0">
                            <input type="checkbox"
                                   name="is_featured"
                                   id="is_featured"
                                   value="1"
                                   class="sr-only peer"
                                   {{ old('is_featured') ? 'checked' : '' }}>
                            <div class="w-14 h-7 bg-gray-600 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-800 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:right-[4px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-gradient-to-r peer-checked:from-[#0056b3] peer-checked:to-[#218838]"></div>
                            <span class="mr-3 text-sm font-semibold text-gray-300">حدث مميز</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Multi-Language Content Card -->
        <div class="rounded-2xl p-6 transition-colors" :class="darkMode ? \'glass-dark\' : \'glass-light-soft\' shadow-xl">
            <h2 class="text-xl font-bold text-white mb-6 flex items-center gap-2">
                <i data-lucide="languages" class="w-6 h-6"></i>
                المحتوى متعدد اللغات
            </h2>

            <!-- Language Tabs -->
            <div class="flex gap-2 mb-6 flex-wrap">
                <button type="button"
                        @click="currentLang = 'ar'"
                        :class="currentLang === 'ar' ? 'active' : 'bg-white/10 text-gray-300 hover:bg-white/20'"
                        class="tab-button transition-all px-6 py-2 rounded-lg font-semibold">
                    العربية (AR)
                </button>
                <button type="button"
                        @click="currentLang = 'en'"
                        :class="currentLang === 'en' ? 'active' : 'bg-white/10 text-gray-300 hover:bg-white/20'"
                        class="tab-button transition-all px-6 py-2 rounded-lg font-semibold">
                    English (EN)
                </button>
                <button type="button"
                        @click="currentLang = 'fr'"
                        :class="currentLang === 'fr' ? 'active' : 'bg-white/10 text-gray-300 hover:bg-white/20'"
                        class="tab-button transition-all px-6 py-2 rounded-lg font-semibold">
                    Français (FR)
                </button>
                <button type="button"
                        @click="currentLang = 'tr'"
                        :class="currentLang === 'tr' ? 'active' : 'bg-white/10 text-gray-300 hover:bg-white/20'"
                        class="tab-button transition-all px-6 py-2 rounded-lg font-semibold">
                    Türkçe (TR)
                </button>
                <button type="button"
                        @click="currentLang = 'ur'"
                        :class="currentLang === 'ur' ? 'active' : 'bg-white/10 text-gray-300 hover:bg-white/20'"
                        class="tab-button transition-all px-6 py-2 rounded-lg font-semibold">
                    اردو (UR)
                </button>
            </div>

            <!-- Tab Contents -->
            <div class="space-y-6">
                <!-- Arabic Tab -->
                <div x-show="currentLang === 'ar'" class="tab-content" :class="currentLang === 'ar' ? 'active' : ''">
                    <div class="space-y-4">
                        <!-- Title AR -->
                        <div>
                            <label class="block text-sm font-semibold" :class="darkMode ? \'text-gray-300\' : \'text-gray-700\' mb-2">
                                <span class="required-asterisk">*</span>
                                العنوان (عربي)
                            </label>
                            <input type="text"
                                   name="title[ar]"
                                   value="{{ old('title.ar') }}"
                                   class="form-input w-full px-4 py-3 bg-white/10 border border-white/20 rounded-lg text-white placeholder-gray-400 focus:outline-none"
                                   placeholder="أدخل عنوان الحدث بالعربية"
                                   dir="rtl"
                                   required>
                            @error('title.ar')
                                <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Description AR -->
                        <div>
                            <label class="block text-sm font-semibold" :class="darkMode ? \'text-gray-300\' : \'text-gray-700\' mb-2">
                                <span class="required-asterisk">*</span>
                                الوصف (عربي)
                            </label>
                            <textarea name="description[ar]"
                                      rows="4"
                                      class="form-input w-full px-4 py-3 bg-white/10 border border-white/20 rounded-lg text-white placeholder-gray-400 focus:outline-none"
                                      placeholder="وصف مختصر عن الحدث"
                                      dir="rtl"
                                      required>{{ old('description.ar') }}</textarea>
                            @error('description.ar')
                                <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Content AR -->
                        <div>
                            <label class="block text-sm font-semibold" :class="darkMode ? \'text-gray-300\' : \'text-gray-700\' mb-2">
                                المحتوى التفصيلي (عربي)
                            </label>
                            <textarea name="content[ar]"
                                      rows="10"
                                      class="form-input w-full px-4 py-3 bg-white/10 border border-white/20 rounded-lg text-white placeholder-gray-400 focus:outline-none custom-scrollbar"
                                      placeholder="محتوى الحدث بالتفصيل..."
                                      dir="rtl">{{ old('content.ar') }}</textarea>
                            @error('content.ar')
                                <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- English Tab -->
                <div x-show="currentLang === 'en'" class="tab-content" :class="currentLang === 'en' ? 'active' : ''">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-semibold" :class="darkMode ? \'text-gray-300\' : \'text-gray-700\' mb-2">Title (English)</label>
                            <input type="text"
                                   name="title[en]"
                                   value="{{ old('title.en') }}"
                                   class="form-input w-full px-4 py-3 bg-white/10 border border-white/20 rounded-lg text-white placeholder-gray-400 focus:outline-none"
                                   placeholder="Enter event title in English"
                                   dir="ltr">
                            @error('title.en')
                                <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-semibold" :class="darkMode ? \'text-gray-300\' : \'text-gray-700\' mb-2">Description (English)</label>
                            <textarea name="description[en]"
                                      rows="4"
                                      class="form-input w-full px-4 py-3 bg-white/10 border border-white/20 rounded-lg text-white placeholder-gray-400 focus:outline-none"
                                      placeholder="Short description of the event"
                                      dir="ltr">{{ old('description.en') }}</textarea>
                            @error('description.en')
                                <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-semibold" :class="darkMode ? \'text-gray-300\' : \'text-gray-700\' mb-2">Detailed Content (English)</label>
                            <textarea name="content[en]"
                                      rows="10"
                                      class="form-input w-full px-4 py-3 bg-white/10 border border-white/20 rounded-lg text-white placeholder-gray-400 focus:outline-none custom-scrollbar"
                                      placeholder="Detailed event content..."
                                      dir="ltr">{{ old('content.en') }}</textarea>
                            @error('content.en')
                                <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- French Tab -->
                <div x-show="currentLang === 'fr'" class="tab-content" :class="currentLang === 'fr' ? 'active' : ''">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-semibold" :class="darkMode ? \'text-gray-300\' : \'text-gray-700\' mb-2">Titre (Français)</label>
                            <input type="text"
                                   name="title[fr]"
                                   value="{{ old('title.fr') }}"
                                   class="form-input w-full px-4 py-3 bg-white/10 border border-white/20 rounded-lg text-white placeholder-gray-400 focus:outline-none"
                                   placeholder="Entrez le titre de l'événement en français"
                                   dir="ltr">
                            @error('title.fr')
                                <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-semibold" :class="darkMode ? \'text-gray-300\' : \'text-gray-700\' mb-2">Description (Français)</label>
                            <textarea name="description[fr]"
                                      rows="4"
                                      class="form-input w-full px-4 py-3 bg-white/10 border border-white/20 rounded-lg text-white placeholder-gray-400 focus:outline-none"
                                      placeholder="Description courte de l'événement"
                                      dir="ltr">{{ old('description.fr') }}</textarea>
                            @error('description.fr')
                                <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-semibold" :class="darkMode ? \'text-gray-300\' : \'text-gray-700\' mb-2">Contenu détaillé (Français)</label>
                            <textarea name="content[fr]"
                                      rows="10"
                                      class="form-input w-full px-4 py-3 bg-white/10 border border-white/20 rounded-lg text-white placeholder-gray-400 focus:outline-none custom-scrollbar"
                                      placeholder="Contenu détaillé de l'événement..."
                                      dir="ltr">{{ old('content.fr') }}</textarea>
                            @error('content.fr')
                                <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Turkish Tab -->
                <div x-show="currentLang === 'tr'" class="tab-content" :class="currentLang === 'tr' ? 'active' : ''">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-semibold" :class="darkMode ? \'text-gray-300\' : \'text-gray-700\' mb-2">Başlık (Türkçe)</label>
                            <input type="text"
                                   name="title[tr]"
                                   value="{{ old('title.tr') }}"
                                   class="form-input w-full px-4 py-3 bg-white/10 border border-white/20 rounded-lg text-white placeholder-gray-400 focus:outline-none"
                                   placeholder="Olay başlığını Türkçe girin"
                                   dir="ltr">
                            @error('title.tr')
                                <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-semibold" :class="darkMode ? \'text-gray-300\' : \'text-gray-700\' mb-2">Açıklama (Türkçe)</label>
                            <textarea name="description[tr]"
                                      rows="4"
                                      class="form-input w-full px-4 py-3 bg-white/10 border border-white/20 rounded-lg text-white placeholder-gray-400 focus:outline-none"
                                      placeholder="Olayın kısa açıklaması"
                                      dir="ltr">{{ old('description.tr') }}</textarea>
                            @error('description.tr')
                                <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-semibold" :class="darkMode ? \'text-gray-300\' : \'text-gray-700\' mb-2">Detaylı İçerik (Türkçe)</label>
                            <textarea name="content[tr]"
                                      rows="10"
                                      class="form-input w-full px-4 py-3 bg-white/10 border border-white/20 rounded-lg text-white placeholder-gray-400 focus:outline-none custom-scrollbar"
                                      placeholder="Detaylı olay içeriği..."
                                      dir="ltr">{{ old('content.tr') }}</textarea>
                            @error('content.tr')
                                <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Urdu Tab -->
                <div x-show="currentLang === 'ur'" class="tab-content" :class="currentLang === 'ur' ? 'active' : ''">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-semibold" :class="darkMode ? \'text-gray-300\' : \'text-gray-700\' mb-2">عنوان (اردو)</label>
                            <input type="text"
                                   name="title[ur]"
                                   value="{{ old('title.ur') }}"
                                   class="form-input w-full px-4 py-3 bg-white/10 border border-white/20 rounded-lg text-white placeholder-gray-400 focus:outline-none"
                                   placeholder="واقعہ کا عنوان اردو میں درج کریں"
                                   dir="rtl">
                            @error('title.ur')
                                <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-semibold" :class="darkMode ? \'text-gray-300\' : \'text-gray-700\' mb-2">تفصیل (اردو)</label>
                            <textarea name="description[ur]"
                                      rows="4"
                                      class="form-input w-full px-4 py-3 bg-white/10 border border-white/20 rounded-lg text-white placeholder-gray-400 focus:outline-none"
                                      placeholder="واقعہ کی مختصر تفصیل"
                                      dir="rtl">{{ old('description.ur') }}</textarea>
                            @error('description.ur')
                                <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-semibold" :class="darkMode ? \'text-gray-300\' : \'text-gray-700\' mb-2">تفصیلی مواد (اردو)</label>
                            <textarea name="content[ur]"
                                      rows="10"
                                      class="form-input w-full px-4 py-3 bg-white/10 border border-white/20 rounded-lg text-white placeholder-gray-400 focus:outline-none custom-scrollbar"
                                      placeholder="واقعہ کا تفصیلی مواد..."
                                      dir="rtl">{{ old('content.ur') }}</textarea>
                            @error('content.ur')
                                <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Form Actions -->
        <div class="rounded-2xl p-6 transition-colors" :class="darkMode ? \'glass-dark\' : \'glass-light-soft\' shadow-xl">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <a href="{{ route('admin.seerah-events.index') }}"
                   class="px-6 py-3 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition-colors flex items-center gap-2">
                    <i data-lucide="x" class="w-5 h-5"></i>
                    إلغاء
                </a>

                <button type="submit"
                        class="btn-submit px-8 py-3 text-white rounded-lg font-semibold flex items-center gap-2"
                        x-bind:class="{ 'loading': isSubmitting }"
                        x-bind:disabled="isSubmitting">
                    <i data-lucide="check-circle" class="w-5 h-5" x-show="!isSubmitting"></i>
                    <span x-text="isSubmitting ? '' : 'حفظ الحدث'"></span>
                </button>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
    function seerahEventForm() {
        return {
            currentLang: 'ar',
            isSubmitting: false,

            handleSubmit(event) {
                this.isSubmitting = true;
                return true;
            },

            init() {
                // Auto-generate slug from Arabic title
                const titleInput = document.querySelector('input[name="title[ar]"]');
                const slugInput = document.getElementById('slug');

                if (titleInput && slugInput && !slugInput.value) {
                    titleInput.addEventListener('input', (e) => {
                        const title = e.target.value;
                        const slug = this.generateSlug(title);
                        slugInput.value = slug;
                    });
                }

                // Initialize icons
                this.$nextTick(() => {
                    lucide.createIcons();
                });
            },

            generateSlug(text) {
                return text
                    .toLowerCase()
                    .trim()
                    .replace(/[\s_]+/g, '-')
                    .replace(/[^\w\-أ-ي]+/g, '')
                    .replace(/\-\-+/g, '-')
                    .replace(/^-+/, '')
                    .replace(/-+$/, '');
            }
        }
    }
</script>
@endpush
@endsection
