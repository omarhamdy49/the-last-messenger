@php
    /**
     * Seerah event form, shared by create and edit.
     *
     * @var \App\Domain\Seerah\Models\SeerahEvent|null $event
     */
    $event = $event ?? null;
    $galleryItems = $galleryItems ?? [];
    $mediaItems = $mediaItems ?? [];

    $inputClass = 'w-full px-4 py-3 rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all';
    $inputDark = "darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'";
    $card = 'rounded-2xl border p-6';
    $cardDark = "darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'";
    $label = 'block text-sm font-medium mb-2';
    $labelDark = "darkMode ? 'text-gray-300' : 'text-gray-700'";

    $oldStatistics = old('statistics');
    if ($oldStatistics === null && is_array($event?->statistics)) {
        $oldStatistics = collect($event->statistics)
            ->filter(fn ($v) => is_scalar($v))
            ->map(fn ($v, $k) => ['label' => (string) $k, 'value' => (string) $v])
            ->values()
            ->all();
    }

    // Read the row for exactly this locale — no Arabic fallback, or saving an
    // untranslated locale would copy the Arabic rows into it.
    $localeRows = fn (string $field, string $lang): array => old("{$field}.{$lang}")
        ?? ($event?->translations->firstWhere('locale', $lang)?->{$field} ?? []);

    $hijriMonths = \App\Utils\DateUtil::getHijriMonthNames();
@endphp

<div class="seerah-layout seerah-authoring">

    <!-- Main Form -->
    <div class="min-w-0 space-y-8">

        <!-- Title & Description (Multi-language) -->
        <div class="{{ $card }}" :class="{{ $cardDark }}">
            <h2 class="text-xl font-bold mb-6 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                <i data-lucide="languages" class="w-5 h-5 text-accent"></i>
                المحتوى
            </h2>

            <div class="flex flex-wrap gap-2 border-b pb-4 mb-6" :class="darkMode ? 'border-gray-700' : 'border-gray-200'">
                @foreach(enabled_content_languages() as $lang => $langLabel)
                <button type="button"
                        @click="activeTab = '{{ $lang }}'"
                        class="px-4 py-2 rounded-lg font-medium transition-all"
                        :class="activeTab === '{{ $lang }}' ?
                            'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white' :
                            (darkMode ? 'bg-gray-800 text-gray-400 hover:text-white' : 'bg-gray-100 text-gray-600 hover:text-gray-900')">
                    {{ $langLabel }}
                </button>
                @endforeach
            </div>

            @foreach(enabled_content_locales() as $lang)
            @php $dir = in_array($lang, ['ar', 'ur']) ? 'rtl' : 'ltr'; @endphp
            <div x-show="activeTab === '{{ $lang }}'" class="space-y-8">
                <div>
                    <label class="{{ $label }}" :class="{{ $labelDark }}" for="title-{{ $lang }}">
                        العنوان ({{ strtoupper($lang) }})
                        @if($lang === 'ar') <span class="text-red-500">*</span> @endif
                    </label>
                    <input type="text" id="title-{{ $lang }}"
                           name="title[{{ $lang }}]"
                           value="{{ old("title.{$lang}", $event?->getTranslation('title', $lang, false)) }}"
                           class="{{ $inputClass }}" :class="{{ $inputDark }}"
                           dir="{{ $dir }}"
                           placeholder="أدخل عنوان الحدث..."
                           {{ $lang === 'ar' ? 'required' : '' }}>
                    @error("title.{$lang}") <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="{{ $label }}" :class="{{ $labelDark }}">الوصف ({{ strtoupper($lang) }})</label>
                    <x-admin.form.wysiwyg
                        :name="'description['.$lang.']'"
                        :value="old('description.'.$lang, $event?->getTranslation('description', $lang, false) ?? '')"
                        :locale="$lang"
                        height="200px"
                        toolbar="minimal"
                        placeholder="أدخل وصف الحدث..."
                    />
                    @error("description.{$lang}") <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                </div>

                <div class="seerah-content-field">
                    <label class="{{ $label }}" :class="{{ $labelDark }}">المحتوى التفصيلي ({{ strtoupper($lang) }})</label>
                    <x-admin.form.wysiwyg
                        :name="'content['.$lang.']'"
                        :value="old('content.'.$lang, $event?->getTranslation('content', $lang, false) ?? '')"
                        :locale="$lang"
                        height="480px"
                        toolbar="full"
                        :modules="true"
                        placeholder="أدخل المحتوى التفصيلي..."
                    />
                    @error("content.{$lang}") <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                </div>

                {{-- تنبيه الخلاف في التأريخ: يُعرض في صفحة الحدث كصندوق تحذير، وكشارة ⚠ على البطاقات --}}
                <div>
                    <label class="{{ $label }}" :class="{{ $labelDark }}" for="dispute-{{ $lang }}">
                        تنبيه الخلاف في التأريخ ({{ strtoupper($lang) }})
                    </label>
                    <textarea id="dispute-{{ $lang }}" name="dispute_note[{{ $lang }}]" rows="3" maxlength="1000"
                              class="{{ $inputClass }} resize-none" :class="{{ $inputDark }}" dir="{{ $dir }}"
                              placeholder="مثال: موافقة عام الفيل لسنة 570/571م هي المشهور، وقد رجّح باحثون سنة 571م.">{{ old("dispute_note.{$lang}", $event?->getTranslation('dispute_note', $lang, false)) }}</textarea>
                    <p class="mt-1 text-xs" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">اتركه فارغاً إن لم يكن في تأريخ الحدث خلاف.</p>
                    @error("dispute_note.{$lang}") <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <h3 class="text-base font-bold mb-1 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                        <i data-lucide="list" class="w-4 h-4 text-accent"></i>
                        تفاصيل إضافية ({{ strtoupper($lang) }})
                    </h3>
                    <p class="mb-3 text-xs" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">
                        أزواج «مفتاح / قيمة» تُعرض كقائمة في صفحة الحدث. مثال: «المكان» / «حمراء الأسد، على نحو 8 أميال جنوب المدينة».
                    </p>
                    <x-admin.form.repeater
                        :name="'metadata['.$lang.']'"
                        :rows="$localeRows('metadata', $lang)"
                        :fields="['key' => 'المفتاح', 'value' => 'القيمة']"
                        :dir="$dir"
                        add-label="إضافة تفصيل"
                        empty-text="لا توجد تفاصيل بعد." />
                    @error("metadata.{$lang}") <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <h3 class="text-base font-bold mb-1 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                        <i data-lucide="library" class="w-4 h-4 text-accent"></i>
                        المصادر والمراجع ({{ strtoupper($lang) }})
                    </h3>
                    <p class="mb-3 text-xs" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">
                        مثال: «الرحيق المختوم» / «صفي الرحمن المباركفوري» / «فصل غزوة بدر».
                    </p>
                    <x-admin.form.repeater
                        :name="'sources['.$lang.']'"
                        :rows="$localeRows('sources', $lang)"
                        :fields="['title' => 'اسم المرجع', 'author' => 'المؤلف', 'reference' => 'الموضع (فصل/جزء)']"
                        :dir="$dir"
                        add-label="إضافة مرجع"
                        empty-text="لا توجد مراجع بعد." />
                    @error("sources.{$lang}") <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                </div>
            </div>
            @endforeach
        </div>

        <!-- Dates -->
        <div class="{{ $card }}" :class="{{ $cardDark }}">
            <h2 class="text-xl font-bold mb-6 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                <i data-lucide="calendar" class="w-5 h-5 text-accent"></i>
                التواريخ
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label class="{{ $label }}" :class="{{ $labelDark }}" for="hijri_year">السنة الهجرية</label>
                    <input type="number" id="hijri_year" name="hijri_year" min="-100" max="100"
                           value="{{ old('hijri_year', $event?->hijri_year) }}"
                           class="{{ $inputClass }}" :class="{{ $inputDark }}" placeholder="مثال: 2 أو -13 قبل الهجرة">
                    @error('hijri_year') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="{{ $label }}" :class="{{ $labelDark }}" for="hijri_month">الشهر الهجري</label>
                    <select id="hijri_month" name="hijri_month" class="{{ $inputClass }}" :class="{{ $inputDark }}">
                        <option value="">غير محدد</option>
                        @foreach($hijriMonths as $number => $monthName)
                            <option value="{{ $number }}" @selected((string) old('hijri_month', $event?->hijri_month) === (string) $number)>{{ $number }}. {{ $monthName }}</option>
                        @endforeach
                    </select>
                    @error('hijri_month') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="{{ $label }}" :class="{{ $labelDark }}" for="hijri_day">اليوم الهجري</label>
                    <input type="number" id="hijri_day" name="hijri_day" min="1" max="30"
                           value="{{ old('hijri_day', $event?->hijri_day) }}"
                           class="{{ $inputClass }}" :class="{{ $inputDark }}" placeholder="1 - 30">
                    @error('hijri_day') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                </div>
                <div class="md:col-span-3">
                    <label class="{{ $label }}" :class="{{ $labelDark }}" for="gregorian_date">التاريخ الميلادي</label>
                    <input type="date" id="gregorian_date" name="gregorian_date"
                           value="{{ old('gregorian_date', $event?->gregorian_date?->format('Y-m-d')) }}"
                           class="{{ $inputClass }}" :class="{{ $inputDark }}">
                    @error('gregorian_date') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        <!-- Place -->
        <div class="{{ $card }}" :class="{{ $cardDark }}">
            <h2 class="text-xl font-bold mb-6 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                <i data-lucide="map-pin" class="w-5 h-5 text-accent"></i>
                الموقع
            </h2>
            <x-admin.place-picker
                name="location_id"
                :selected="old('location_id', $event?->location_id)"
                :selected-label="$event?->place?->name()"
                :label="__('locations.singular')"
                :hint="__('locations.hints.parent')" />
            @error('location_id') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
        </div>

        <!-- Statistics -->
        <div class="{{ $card }}" :class="{{ $cardDark }}">
            <h2 class="text-xl font-bold mb-2 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                <i data-lucide="bar-chart-3" class="w-5 h-5 text-accent"></i>
                الإحصائيات
            </h2>
            <p class="mb-4 text-xs" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">
                تظهر كبطاقات أعلى صفحة الحدث (أول 4 بطاقات). مثال: «عدد المسلمين» / «313».
            </p>
            <x-admin.form.repeater
                name="statistics"
                :rows="$oldStatistics ?? []"
                :fields="['label' => 'التسمية', 'value' => 'القيمة']"
                :max="12"
                empty-text="لا توجد إحصائيات بعد." />
            @error('statistics') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
        </div>

        <!-- Gallery + Media: same media-library contract as the article form -->
        <div x-data="seerahEventLibrary({{ Js::from(['gallery' => $galleryItems, 'media' => $mediaItems]) }})"
             @media-selected.window="onSelected($event.detail)"
             class="space-y-8">

            <div class="{{ $card }}" :class="{{ $cardDark }}">
                <div class="flex items-center justify-between gap-3 mb-6">
                    <h2 class="text-xl font-bold flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                        <i data-lucide="images" class="w-5 h-5 text-accent"></i>
                        معرض الصور
                    </h2>
                    <button type="button" @click="$dispatch('open-media-library', { target: 'seerah_gallery', multiple: true })"
                            class="px-4 py-2 rounded-xl text-sm font-semibold bg-gradient-to-r from-[#0056b3] to-[#218838] text-white flex items-center gap-2">
                        <i data-lucide="plus" class="w-4 h-4"></i> إضافة صور
                    </button>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4" x-show="gallery.length > 0">
                    <template x-for="(item, index) in gallery" :key="item.id">
                        <div class="rounded-xl border overflow-hidden" :class="darkMode ? 'border-gray-800' : 'border-gray-200'">
                            <div class="relative">
                                <img :src="item.url" alt="" loading="lazy" class="w-full aspect-video object-cover">
                                <div class="absolute top-2 end-2 flex items-center gap-1">
                                    <button type="button" x-show="index > 0" aria-label="تحريك لأعلى" @click="move(gallery, index, -1)"
                                            class="p-1.5 rounded-lg bg-black/60 text-white"><i data-lucide="arrow-up" class="w-4 h-4"></i></button>
                                    <button type="button" x-show="index < gallery.length - 1" aria-label="تحريك لأسفل" @click="move(gallery, index, 1)"
                                            class="p-1.5 rounded-lg bg-black/60 text-white"><i data-lucide="arrow-down" class="w-4 h-4"></i></button>
                                    <button type="button" aria-label="حذف" @click="gallery.splice(index, 1)"
                                            class="p-1.5 rounded-lg bg-red-500 text-white"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
                                </div>
                            </div>
                            <input type="hidden" :name="`gallery[${index}]`" :value="item.id">
                            <div class="p-3">
                                @foreach(enabled_content_locales() as $lang)
                                    <input type="text" x-show="activeTab === '{{ $lang }}'"
                                           x-model="item.caption['{{ $lang }}']"
                                           :name="`gallery_captions[${item.id}][{{ $lang }}]`"
                                           placeholder="التعليق ({{ strtoupper($lang) }})"
                                           aria-label="التعليق ({{ strtoupper($lang) }})"
                                           dir="{{ in_array($lang, ['ar', 'ur']) ? 'rtl' : 'ltr' }}"
                                           class="w-full px-3 py-2 text-sm rounded-lg border" :class="{{ $inputDark }}">
                                @endforeach
                            </div>
                        </div>
                    </template>
                </div>
                <template x-if="gallery.length === 0">
                    <input type="hidden" name="gallery" value="">
                </template>
                <p x-show="gallery.length === 0" class="text-sm" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">
                    لا توجد صور — قسم «معرض الصور» لن يظهر في صفحة الحدث.
                </p>
                @error('gallery') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                @error('gallery.*') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
            </div>

            <div class="{{ $card }}" :class="{{ $cardDark }}">
                <div class="flex items-center justify-between gap-3 mb-6">
                    <h2 class="text-xl font-bold flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                        <i data-lucide="video" class="w-5 h-5 text-accent"></i>
                        الوسائط المرفقة
                    </h2>
                    <button type="button" @click="$dispatch('open-media-library', { target: 'seerah_media', multiple: true })"
                            class="px-4 py-2 rounded-xl text-sm font-semibold bg-gradient-to-r from-[#0056b3] to-[#218838] text-white flex items-center gap-2">
                        <i data-lucide="plus" class="w-4 h-4"></i> إضافة وسائط
                    </button>
                </div>

                <div class="space-y-2" x-show="media.length > 0">
                    <template x-for="(item, index) in media" :key="item.id">
                        <div class="flex items-center gap-3 p-3 rounded-lg border" :class="darkMode ? 'bg-gray-800/50 border-gray-700' : 'bg-gray-50 border-gray-200'">
                            <template x-if="item.thumbnail_url">
                                <img :src="item.thumbnail_url" class="w-12 h-12 rounded-lg object-cover shrink-0" alt="">
                            </template>
                            <template x-if="!item.thumbnail_url">
                                <i data-lucide="file" class="w-8 h-8 text-gray-400 shrink-0"></i>
                            </template>
                            <div class="flex-1 min-w-0">
                                <p class="font-medium text-sm truncate" :class="darkMode ? 'text-white' : 'text-gray-900'" x-text="item.title"></p>
                                <p class="text-xs text-gray-500" x-text="item.type"></p>
                            </div>
                            <button type="button" aria-label="إزالة" @click="media.splice(index, 1)"
                                    class="p-2 text-red-500 hover:bg-red-500/10 rounded-lg shrink-0">
                                <i data-lucide="x" class="w-4 h-4"></i>
                            </button>
                            <input type="hidden" :name="`media_items[${index}]`" :value="item.id">
                        </div>
                    </template>
                </div>
                <template x-if="media.length === 0">
                    <input type="hidden" name="media_items" value="">
                </template>
                <p x-show="media.length === 0" class="text-sm" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">
                    لا توجد وسائط مرفقة.
                </p>
                @error('media_items') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                @error('media_items.*') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
            </div>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="space-y-6">

        <div class="{{ $card }}" :class="{{ $cardDark }}">
            <h3 class="text-lg font-bold mb-4 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                <i data-lucide="tag" class="w-5 h-5 text-accent"></i>
                التصنيف
            </h3>

            <div class="space-y-4">
                <div>
                    <label class="{{ $label }}" :class="{{ $labelDark }}" for="slug">
                        الرابط المختصر (Slug) <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="slug" name="slug" value="{{ old('slug', $event?->slug) }}"
                           class="{{ $inputClass }}" :class="{{ $inputDark }}"
                           pattern="[a-z0-9\-]+" dir="ltr" required>
                    <p class="mt-1 text-xs" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">أحرف إنجليزية صغيرة وأرقام وشرطات فقط</p>
                    @error('slug') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="{{ $label }}" :class="{{ $labelDark }}" for="event_type">نوع الحدث <span class="text-red-500">*</span></label>
                    <select id="event_type" name="event_type" required class="{{ $inputClass }}" :class="{{ $inputDark }}">
                        <option value="">اختر النوع</option>
                        @foreach($eventTypes as $type)
                            <option value="{{ $type->id }}" @selected((string) old('event_type', $event?->event_type) === (string) $type->id)>
                                {{ $type->name('ar') }}
                            </option>
                        @endforeach
                    </select>
                    @error('event_type') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="{{ $label }}" :class="{{ $labelDark }}" for="sm_id">{{ __('admin.milestone') }}</label>
                    <select id="sm_id" name="sm_id" class="{{ $inputClass }}" :class="{{ $inputDark }}">
                        <option value="">{{ __('admin.no_milestone') }}</option>
                        @foreach($milestones as $milestone)
                            <option value="{{ $milestone->id }}" @selected((string) old('sm_id', $event?->sm_id) === (string) $milestone->id)>
                                {{ $milestone->order }}. {{ $milestone->getTranslation('title', 'ar') }}@if($milestone->gregorianRangeLabel()) ({{ $milestone->gregorianRangeLabel() }})@endif
                            </option>
                        @endforeach
                    </select>
                    @error('sm_id') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="{{ $label }}" :class="{{ $labelDark }}" for="order">ترتيب العرض <span class="text-red-500">*</span></label>
                    <input type="number" id="order" name="order" min="0" required
                           value="{{ old('order', $event?->order ?? 0) }}"
                           class="{{ $inputClass }}" :class="{{ $inputDark }}">
                    <p class="mt-1 text-xs" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">ترتيب الحدث داخل حقبته؛ يتقدّم على التاريخ عند التساوي.</p>
                    @error('order') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="{{ $label }}" :class="{{ $labelDark }}" for="status">{{ __('admin.status') }}</label>
                    <select id="status" name="status" class="{{ $inputClass }}" :class="{{ $inputDark }}">
                        @foreach($statuses as $status)
                            <option value="{{ $status->value }}" @selected(old('status', $event?->status?->value ?? 'draft') === $status->value)>
                                {{ $status->labelArabic() }}
                            </option>
                        @endforeach
                    </select>
                    @error('status') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="{{ $label }}" :class="{{ $labelDark }}" for="era">الحقبة التاريخية</label>
                    <select id="era" name="era" class="{{ $inputClass }}" :class="{{ $inputDark }}">
                        <option value="">غير محدد</option>
                        @foreach($eras as $era)
                            <option value="{{ $era->value }}" @selected(old('era', $event?->era?->value) === $era->value)>{{ $era->labelArabic() }}</option>
                        @endforeach
                    </select>
                    @error('era') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        <div class="{{ $card }}" :class="{{ $cardDark }}">
            <h3 class="text-lg font-bold mb-1 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                <i data-lucide="image" class="w-5 h-5 text-accent"></i>
                الصورة المصغّرة
            </h3>
            <p class="mb-4 text-xs" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">تظهر على البطاقات والقوائم.</p>
            <x-admin.image-upload
                name="thumb"
                idName="thumb_id"
                removeName="remove_thumb"
                aspectClass="aspect-video"
                placeholderIcon="calendar"
                maxSize="10MB"
                formats="JPG, PNG, WebP"
                :currentImage="$event?->thumb_url ? \Illuminate\Support\Facades\Storage::disk('public')->url($event->thumb_url) : null"
            />
            @error('thumb') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
        </div>

        <div class="{{ $card }}" :class="{{ $cardDark }}">
            <h3 class="text-lg font-bold mb-1 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                <i data-lucide="image" class="w-5 h-5 text-accent"></i>
                صورة الغلاف
            </h3>
            <p class="mb-4 text-xs" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">تظهر أعلى صفحة الحدث.</p>
            <x-admin.image-upload
                name="cover"
                idName="cover_id"
                removeName="remove_cover"
                aspectClass="aspect-video"
                placeholderIcon="image"
                maxSize="10MB"
                formats="JPG, PNG, WebP"
                :currentImage="$event?->cover_url ? \Illuminate\Support\Facades\Storage::disk('public')->url($event->cover_url) : null"
            />
            @error('cover') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
        </div>

        @if($event)
        <div class="{{ $card }}" :class="{{ $cardDark }}">
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
        @endif

        <div class="{{ $card }}" :class="{{ $cardDark }}">
            <button type="submit"
                    class="w-full px-6 py-3 rounded-xl font-semibold transition-all hover:scale-105 bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-lg flex items-center justify-center gap-2">
                <i data-lucide="save" class="w-5 h-5"></i>
                {{ __('admin.save') }}
            </button>
            <a href="{{ $event ? route('admin.seerah-events.show', $event->id) : route('admin.seerah-events.index') }}"
               class="mt-4 w-full px-4 py-2.5 rounded-xl font-semibold text-center transition-all hover:scale-105 border flex items-center justify-center gap-2"
               :class="darkMode ? 'border-gray-700 bg-gray-800 hover:bg-gray-700 text-white' : 'border-gray-200 bg-white hover:bg-gray-50 text-gray-900'">
                {{ __('admin.cancel') }}
            </a>
        </div>
    </div>
</div>

<x-admin.media-picker-modal />

@once
@push('scripts')
<script>
    /**
     * Gallery + attached media for a Seerah event. The picker answers with a
     * `media-selected` event carrying the target it was opened for.
     */
    function seerahEventLibrary(initial) {
        const locales = @json(enabled_content_locales());
        const withCaption = (item) => ({
            ...item,
            caption: Object.fromEntries(locales.map((l) => [l, (item.caption ?? {})[l] ?? ''])),
        });

        return {
            gallery: (initial.gallery ?? []).map(withCaption),
            media: initial.media ?? [],

            onSelected(detail) {
                const items = detail.multiple ? detail.media : [detail.media];

                if (detail.target === 'seerah_gallery') {
                    items.filter((m) => !this.gallery.some((g) => g.id === m.id))
                        .forEach((m) => this.gallery.push(withCaption({ id: m.id, url: m.thumbnail_url || m.url })));
                } else if (detail.target === 'seerah_media') {
                    items.filter((m) => !this.media.some((x) => x.id === m.id))
                        .forEach((m) => this.media.push({
                            id: m.id,
                            title: m.title || m.original_filename || m.filename,
                            type: m.type,
                            thumbnail_url: m.thumbnail_url,
                        }));
                } else {
                    return;
                }

                this.$nextTick(() => window.lucide?.createIcons());
            },

            move(list, index, step) {
                [list[index + step], list[index]] = [list[index], list[index + step]];
            },
        };
    }
</script>
@endpush
@endonce
