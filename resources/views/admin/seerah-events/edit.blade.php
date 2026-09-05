@extends('layouts.admin')

@section('page-header')
<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
    <!-- Page Title -->
    <div>
        <div class="flex items-center gap-2 text-sm mb-2" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
            <a href="{{ route('admin.seerah-events.index') }}" class="hover:text-accent transition-colors">أحداث السيرة النبوية</a>
            <i data-lucide="chevron-left" class="w-4 h-4"></i>
            <a href="{{ route('admin.seerah-events.show', $event->id) }}" class="hover:text-accent transition-colors">{{ $event->getTranslation('title', 'ar') }}</a>
            <i data-lucide="chevron-left" class="w-4 h-4"></i>
            <span>{{ __('admin.edit') }}</span>
        </div>
        <h1 class="text-3xl font-bold gradient-text flex items-center gap-3">
            <div class="p-3 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] shadow-lg">
                <i data-lucide="edit-2" class="w-7 h-7 text-white"></i>
            </div>
            تعديل الحدث: {{ $event->getTranslation('title', 'ar') }}
        </h1>
    </div>

    <!-- Action Buttons -->
    <div class="flex flex-wrap items-center gap-3">
        <a href="{{ route('admin.seerah-events.show', $event->id) }}"
           class="px-4 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 flex items-center gap-2 border-2"
           :class="darkMode ? 'border-gray-700 bg-gray-800 text-white hover:bg-gray-700' : 'border-gray-300 bg-white text-gray-900 hover:bg-gray-50'">
            <i data-lucide="x" class="w-5 h-5"></i>
            <span>{{ __('admin.cancel') }}</span>
        </a>
    </div>
</div>
@endsection

@section('content')
<form action="{{ route('admin.seerah-events.update', $event->id) }}" method="POST" enctype="multipart/form-data" x-data="{ activeTab: 'ar' }">
    @csrf
    @method('PUT')

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Main Form -->
        <div class="lg:col-span-2 space-y-6">

            <!-- Title & Description (Multi-language) -->
            <div class="rounded-2xl border p-6"
                 :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
                <h2 class="text-xl font-bold mb-6 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                    <i data-lucide="languages" class="w-5 h-5 text-accent"></i>
                    العنوان والوصف
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
                    <!-- Title -->
                    <div>
                        <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            العنوان ({{ strtoupper($lang) }})
                            @if($lang === 'ar') <span class="text-red-500">*</span> @endif
                        </label>
                        <input type="text"
                               name="title[{{ $lang }}]"
                               value="{{ old("title.{$lang}", $event->getTranslation('title', $lang, false)) }}"
                               class="w-full px-4 py-3 rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                               :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'"
                               dir="{{ in_array($lang, ['ar', 'ur']) ? 'rtl' : 'ltr' }}"
                               placeholder="أدخل عنوان الحدث..."
                               {{ $lang === 'ar' ? 'required' : '' }}>
                        @error("title.{$lang}")
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Description -->
                    <div>
                        <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            الوصف ({{ strtoupper($lang) }})
                        </label>
                        <textarea name="description[{{ $lang }}]"
                                  rows="4"
                                  class="w-full px-4 py-3 rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all resize-none"
                                  :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'"
                                  dir="{{ in_array($lang, ['ar', 'ur']) ? 'rtl' : 'ltr' }}"
                                  placeholder="أدخل وصف الحدث...">{{ old("description.{$lang}", $event->getTranslation('description', $lang, false)) }}</textarea>
                        @error("description.{$lang}")
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Content -->
                    <div>
                        <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            المحتوى التفصيلي ({{ strtoupper($lang) }})
                        </label>
                        <x-admin.form.wysiwyg
                            :name="'content['.$lang.']'"
                            :value="old('content.'.$lang, $event->getTranslation('content', $lang, false) ?? '')"
                            :locale="$lang"
                            height="360px"
                            toolbar="full"
                            :modules="true"
                            placeholder="أدخل المحتوى التفصيلي..."
                        />
                        @error("content.{$lang}")
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- تنبيه الخلاف في التأريخ: يُعرض في صفحة الحدث كصندوق تحذير، وكشارة ⚠ على البطاقات --}}
                    <div>
                        <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            تنبيه الخلاف في التأريخ ({{ strtoupper($lang) }})
                        </label>
                        <textarea name="dispute_note[{{ $lang }}]"
                                  rows="3"
                                  maxlength="1000"
                                  class="w-full px-4 py-3 rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all resize-none"
                                  :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'"
                                  dir="{{ in_array($lang, ['ar', 'ur']) ? 'rtl' : 'ltr' }}"
                                  placeholder="مثال: موافقة عام الفيل لسنة 570/571م هي المشهور، وقد رجّح باحثون سنة 571م.">{{ old("dispute_note.{$lang}", $event->getTranslation('dispute_note', $lang, false)) }}</textarea>
                        <p class="mt-1 text-xs" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">
                            اتركه فارغاً إن لم يكن في تأريخ الحدث خلاف.
                        </p>
                        @error("dispute_note.{$lang}")
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
                @endforeach
            </div>

            <!-- Dates -->
            <div class="rounded-2xl border p-6"
                 :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
                <h2 class="text-xl font-bold mb-6 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                    <i data-lucide="calendar" class="w-5 h-5 text-accent"></i>
                    التواريخ
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">التاريخ الهجري</label>
                        <input type="text" name="hijri_date" value="{{ old('hijri_date', $event->hijri_date) }}"
                               class="w-full px-4 py-3 rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                               :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'"
                               placeholder="مثال: 12 ربيع الأول">
                        @error('hijri_date') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">السنة الهجرية</label>
                        <input type="number" name="hijri_year" value="{{ old('hijri_year', $event->hijri_year) }}"
                               class="w-full px-4 py-3 rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                               :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                        @error('hijri_year') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">التاريخ الميلادي</label>
                        <input type="date" name="gregorian_date" value="{{ old('gregorian_date', $event->gregorian_date?->format('Y-m-d')) }}"
                               class="w-full px-4 py-3 rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                               :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                        @error('gregorian_date') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">السنة الميلادية</label>
                        <input type="number" name="gregorian_year" value="{{ old('gregorian_year', $event->gregorian_year) }}"
                               class="w-full px-4 py-3 rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                               :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                        @error('gregorian_year') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            <!-- Location -->
            <div class="rounded-2xl border p-6"
                 :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
                <h2 class="text-xl font-bold mb-6 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                    <i data-lucide="map-pin" class="w-5 h-5 text-accent"></i>
                    الموقع
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    {{-- The canonical place drives the map pin and the localized
                         name. The free-text field beside it keeps the historian's
                         description, which no place record can reconstruct. --}}
                    <div class="md:col-span-3">
                        <x-admin.place-picker
                            name="location_id"
                            :selected="old('location_id', $event->location_id)"
                            :selected-label="$event->place?->name()"
                            :label="__('locations.singular')"
                            :hint="__('locations.hints.parent')" />
                        @error('location_id') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                    </div>

                    <div class="md:col-span-3">
                        <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ __('locations.public.place_note') }}</label>
                        <input type="text" name="location" value="{{ old('location', $event->location) }}"
                               class="w-full px-4 py-3 rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                               :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'"
                               placeholder="مثال: حمراء الأسد على نحو 8 أميال جنوب المدينة">
                        @error('location') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">خط العرض</label>
                        <input type="number" name="latitude" value="{{ old('latitude', $event->latitude) }}" step="0.0000001"
                               class="w-full px-4 py-3 rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                               :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                        @error('latitude') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">خط الطول</label>
                        <input type="number" name="longitude" value="{{ old('longitude', $event->longitude) }}" step="0.0000001"
                               class="w-full px-4 py-3 rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                               :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                        @error('longitude') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">ترتيب العرض</label>
                        <input type="number" name="display_order" value="{{ old('display_order', $event->display_order) }}" min="0"
                               class="w-full px-4 py-3 rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                               :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                        @error('display_order') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            @include('admin.seerah-events._advanced', ['event' => $event])
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">

            <!-- Event Type & Importance -->
            <div class="rounded-2xl border p-6"
                 :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
                <h3 class="text-lg font-bold mb-4 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                    <i data-lucide="tag" class="w-5 h-5 text-accent"></i>
                    التصنيف
                </h3>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            الرابط المختصر (Slug) <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="slug" value="{{ old('slug', $event->slug) }}"
                               class="w-full px-4 py-3 rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                               :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'"
                               pattern="[a-z0-9\-]+" required>
                        <p class="mt-1 text-xs" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">أحرف إنجليزية صغيرة وأرقام وشرطات فقط</p>
                        @error('slug') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">نوع الحدث <span class="text-red-500">*</span></label>
                        <select name="seerah_event_type_id" required
                                class="w-full px-4 py-3 text-base sm:text-sm rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                                :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                            <option value="">اختر النوع</option>
                            @foreach($eventTypes as $type)
                                <option value="{{ $type->id }}" {{ (string) old('seerah_event_type_id', $event->seerah_event_type_id) === (string) $type->id ? 'selected' : '' }}>
                                    {{ $type->getTranslation('name', 'ar') }}
                                </option>
                            @endforeach
                        </select>
                        @error('seerah_event_type_id') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ __('admin.milestone') }}</label>
                        <select name="seerah_milestone_id"
                                class="w-full px-4 py-3 text-base sm:text-sm rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                                :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                            <option value="">{{ __('admin.no_milestone') }}</option>
                            @foreach($milestones as $milestone)
                                <option value="{{ $milestone->id }}" {{ (string) old('seerah_milestone_id', $event->seerah_milestone_id) === (string) $milestone->id ? 'selected' : '' }}>
                                    {{ $milestone->order_index }}. {{ $milestone->getTranslation('title', 'ar') }}@if($milestone->gregorianRangeLabel()) ({{ $milestone->gregorianRangeLabel() }})@endif
                                </option>
                            @endforeach
                        </select>
                        @error('seerah_milestone_id') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ __('admin.status') }}</label>
                        <select name="status"
                                class="w-full px-4 py-3 text-base sm:text-sm rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                                :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                            @foreach($statuses as $status)
                                <option value="{{ $status->value }}" {{ old('status', $event->status?->value ?? 'published') === $status->value ? 'selected' : '' }}>
                                    {{ $status->labelArabic() }}
                                </option>
                            @endforeach
                        </select>
                        @error('status') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">الأهمية</label>
                        <select name="importance"
                                class="w-full px-4 py-3 rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                                :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                            <option value="">اختر الأهمية</option>
                            @foreach(\App\Domain\Seerah\Enums\EventImportance::cases() as $imp)
                                <option value="{{ $imp->value }}" {{ old('importance', $event->importance?->value) == $imp->value ? 'selected' : '' }}>
                                    {{ $imp->labelArabic() }}
                                </option>
                            @endforeach
                        </select>
                        @error('importance') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">المقال المرتبط</label>
                        <select name="related_article_id"
                                class="w-full px-4 py-3 rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                                :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                            <option value="">بدون ربط</option>
                            @foreach($articles as $article)
                                @php
                                    $articleTitle = $article->locales->firstWhere('locale', 'ar')?->title
                                        ?? $article->locales->firstWhere('locale', 'en')?->title
                                        ?? ('Article #' . $article->id);
                                @endphp
                                <option value="{{ $article->id }}" {{ (string) old('related_article_id', $event->related_article_id) === (string) $article->id ? 'selected' : '' }}>
                                    #{{ $article->id }} - {{ $articleTitle }}
                                </option>
                            @endforeach
                        </select>
                        @error('related_article_id') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                    </div>
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
                               {{ old('is_featured', $event->is_featured) ? 'checked' : '' }}
                               class="w-5 h-5 rounded text-amber-500 border-gray-300 focus:ring-amber-500">
                        <span :class="darkMode ? 'text-gray-300' : 'text-gray-700'">حدث مميز</span>
                    </label>
                </div>
            </div>

            <!-- Featured Image -->
            <div class="rounded-2xl border p-6"
                 :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
                <h3 class="text-lg font-bold mb-4 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                    <i data-lucide="image" class="w-5 h-5 text-accent"></i>
                    الصورة الرئيسية
                </h3>

                <x-admin.image-upload
                    name="featured_image"
                    idName="featured_image_id"
                    aspectClass="aspect-video"
                    placeholderIcon="calendar"
                    maxSize="10MB"
                    formats="JPG, PNG, WebP"
                    :currentImage="$event->getFirstMediaUrl('featured_image') ?: null"
                />
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
                        <span class="font-medium" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $event->created_at?->format('Y/m/d') }}</span>
                    </div>
                    <div class="flex items-center justify-between p-3 rounded-xl" :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                        <span :class="darkMode ? 'text-gray-400' : 'text-gray-600'">آخر تحديث</span>
                        <span class="font-medium" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $event->updated_at?->format('Y/m/d') }}</span>
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
                    <a href="{{ route('admin.seerah-events.show', $event->id) }}"
                       class="flex-1 px-4 py-2.5 rounded-xl font-semibold text-center transition-all hover:scale-105 border"
                       :class="darkMode ? 'border-gray-700 bg-gray-800 hover:bg-gray-700 text-white' : 'border-gray-200 bg-white hover:bg-gray-50 text-gray-900'">
                        {{ __('admin.cancel') }}
                    </a>
                    <a href="{{ route('admin.seerah-events.index') }}"
                       class="flex-1 px-4 py-2.5 rounded-xl font-semibold text-center transition-all hover:scale-105 border"
                       :class="darkMode ? 'border-gray-700 bg-gray-800 hover:bg-gray-700 text-white' : 'border-gray-200 bg-white hover:bg-gray-50 text-gray-900'">
                        القائمة
                    </a>
                </div>
            </div>

        </div>
    </div>
</form>

{{-- One instance per page; every participant row opens it via `open-muslim-picker`. --}}
<x-admin.muslim-picker-modal />
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        lucide.createIcons();
    });
</script>
@endpush
