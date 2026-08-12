@php
    /**
     * Advanced Seerah-event fields shared by create + edit.
     *
     * Covers everything the public event page renders that the base form did not
     * expose: era/phase taxonomy, the "حدث مفصلي" flag, the statistics cards, the
     * inner timeline sub-points, the characters list, and the image gallery.
     *
     * ponytail: repeater rows are Arabic-only. The whole Seerah corpus is Arabic
     * and the site defaults to it; the validation rules already accept any locale
     * key, so adding en/fr/tr/ur later is a view change alone.
     */
    $event = $event ?? null;

    $oldTimeline = old('timeline_items');
    if ($oldTimeline === null && $event) {
        $oldTimeline = $event->timelineItems
            ->sortBy('display_order')
            ->map(fn ($item) => [
                'title' => ['ar' => $item->getTranslation('title', 'ar') ?? ''],
                'description' => ['ar' => $item->getTranslation('description', 'ar') ?? ''],
            ])
            ->values()
            ->all();
    }

    $oldParticipants = old('participants');
    if ($oldParticipants === null && $event) {
        $oldParticipants = $event->participants
            ->map(fn ($p) => [
                'name' => ['ar' => $p->getTranslation('name', 'ar') ?? ''],
                'description' => ['ar' => $p->getTranslation('description', 'ar') ?? ''],
                'role' => $p->role->value,
            ])
            ->values()
            ->all();
    }

    $oldStatistics = old('statistics');
    if ($oldStatistics === null && $event && is_array($event->statistics)) {
        $oldStatistics = collect($event->statistics)
            ->filter(fn ($v) => is_scalar($v))
            ->map(fn ($v, $k) => ['label' => (string) $k, 'value' => (string) $v])
            ->values()
            ->all();
    }

    $oldLessons = old('lessons') ?? ($event ? $event->lessonsFor('ar') : []);
    $oldSources = old('sources') ?? ($event ? $event->sourcesFor('ar') : []);

    $existingGallery = collect($event?->gallery ?? [])
        ->filter(fn ($p) => is_string($p) && trim($p) !== '')
        ->values();

    $inputClass = 'w-full px-4 py-3 rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all';
    $inputDark = "darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'";
@endphp

<!-- Advanced taxonomy -->
<div class="rounded-2xl border p-6" :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
    <h2 class="text-xl font-bold mb-6 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
        <i data-lucide="layers" class="w-5 h-5 text-accent"></i>
        التصنيف الزمني المتقدم
    </h2>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">الحقبة التاريخية (Era)</label>
            <select name="era" class="{{ $inputClass }}" :class="{{ $inputDark }}">
                <option value="">غير محدد</option>
                @foreach($eras as $era)
                    <option value="{{ $era->value }}" {{ old('era', $event?->era?->value) === $era->value ? 'selected' : '' }}>
                        {{ $era->labelArabic() }}
                    </option>
                @endforeach
            </select>
            @error('era')<p class="mt-1 text-sm text-red-500">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">المرحلة (Phase)</label>
            <select name="phase" class="{{ $inputClass }}" :class="{{ $inputDark }}">
                <option value="">غير محدد</option>
                @foreach($phases as $phase)
                    <option value="{{ $phase->value }}" {{ old('phase', $event?->phase?->value) === $phase->value ? 'selected' : '' }}>
                        {{ $phase->labelArabic() }}
                    </option>
                @endforeach
            </select>
            @error('phase')<p class="mt-1 text-sm text-red-500">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">الشهر الهجري</label>
            <input type="number" name="hijri_month" min="1" max="12"
                   value="{{ old('hijri_month', $event?->hijri_month) }}"
                   class="{{ $inputClass }}" :class="{{ $inputDark }}" placeholder="1 - 12">
            @error('hijri_month')<p class="mt-1 text-sm text-red-500">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">اليوم الهجري</label>
            <input type="number" name="hijri_day" min="1" max="30"
                   value="{{ old('hijri_day', $event?->hijri_day) }}"
                   class="{{ $inputClass }}" :class="{{ $inputDark }}" placeholder="1 - 30">
            @error('hijri_day')<p class="mt-1 text-sm text-red-500">{{ $message }}</p>@enderror
        </div>

        <div class="md:col-span-2">
            <label class="flex items-center gap-3 cursor-pointer p-3 rounded-xl transition-all"
                   :class="darkMode ? 'hover:bg-gray-800' : 'hover:bg-gray-50'">
                <input type="checkbox" name="is_pivotal" value="1"
                       {{ old('is_pivotal', $event?->is_pivotal) ? 'checked' : '' }}
                       class="w-5 h-5 rounded text-amber-500 border-gray-300 focus:ring-amber-500">
                <span :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                    حدث مفصلي — يظهر بشارة مميزة على الخط الزمني العام
                </span>
            </label>
        </div>
    </div>
</div>

<!-- Statistics -->
<div class="rounded-2xl border p-6"
     :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'"
     x-data="{ rows: {{ Js::from(array_values($oldStatistics ?? [])) }} }">
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-xl font-bold flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
            <i data-lucide="bar-chart-3" class="w-5 h-5 text-accent"></i>
            الإحصائيات
        </h2>
        <button type="button" @click="rows.push({ label: '', value: '' })"
                class="px-4 py-2 rounded-xl text-sm font-semibold bg-gradient-to-r from-[#0056b3] to-[#218838] text-white flex items-center gap-2">
            <i data-lucide="plus" class="w-4 h-4"></i> إضافة
        </button>
    </div>

    <p class="mb-4 text-xs" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">
        تظهر كبطاقات أعلى صفحة الحدث (أول 4 بطاقات). مثال: «عدد المسلمين» / «313».
    </p>

    <div class="space-y-3">
        <template x-for="(row, index) in rows" :key="index">
            <div class="flex flex-col sm:flex-row gap-3 items-start">
                <input type="text" x-model="row.label" :name="`statistics[${index}][label]`"
                       placeholder="التسمية" dir="rtl"
                       class="{{ $inputClass }} sm:flex-1" :class="{{ $inputDark }}">
                <input type="text" x-model="row.value" :name="`statistics[${index}][value]`"
                       placeholder="القيمة" dir="rtl"
                       class="{{ $inputClass }} sm:flex-1" :class="{{ $inputDark }}">
                <button type="button" @click="rows.splice(index, 1)"
                        class="px-3 py-3 rounded-xl text-red-500 hover:bg-red-500/10 transition-all shrink-0">
                    <i data-lucide="trash-2" class="w-5 h-5"></i>
                </button>
            </div>
        </template>
        <p x-show="rows.length === 0" class="text-sm" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">
            لا توجد إحصائيات بعد.
        </p>
    </div>
</div>

<!-- Inner timeline -->
<div class="rounded-2xl border p-6"
     :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'"
     x-data="{ rows: {{ Js::from(array_values($oldTimeline ?? [])) }} }">
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-xl font-bold flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
            <i data-lucide="list-ordered" class="w-5 h-5 text-accent"></i>
            الخط الزمني الداخلي
        </h2>
        <button type="button" @click="rows.push({ title: { ar: '' }, description: { ar: '' } })"
                class="px-4 py-2 rounded-xl text-sm font-semibold bg-gradient-to-r from-[#0056b3] to-[#218838] text-white flex items-center gap-2">
            <i data-lucide="plus" class="w-4 h-4"></i> إضافة نقطة
        </button>
    </div>

    <p class="mb-4 text-xs" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">
        النقاط المرحلية داخل الحدث (عادة 4–6 نقاط)، تُعرض كقسم قابل للتوسيع في صفحة الحدث. الترتيب هو ترتيب الظهور.
    </p>

    <div class="space-y-4">
        <template x-for="(row, index) in rows" :key="index">
            <div class="rounded-xl border p-4" :class="darkMode ? 'border-gray-800 bg-gray-900/40' : 'border-gray-200 bg-gray-50'">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-sm font-bold" :class="darkMode ? 'text-gray-400' : 'text-gray-500'"
                          x-text="`النقطة ${index + 1}`"></span>
                    <div class="flex items-center gap-1">
                        <button type="button" x-show="index > 0"
                                @click="[rows[index - 1], rows[index]] = [rows[index], rows[index - 1]]"
                                class="p-2 rounded-lg hover:bg-black/10 transition-all" title="تحريك لأعلى">
                            <i data-lucide="arrow-up" class="w-4 h-4"></i>
                        </button>
                        <button type="button" x-show="index < rows.length - 1"
                                @click="[rows[index + 1], rows[index]] = [rows[index], rows[index + 1]]"
                                class="p-2 rounded-lg hover:bg-black/10 transition-all" title="تحريك لأسفل">
                            <i data-lucide="arrow-down" class="w-4 h-4"></i>
                        </button>
                        <button type="button" @click="rows.splice(index, 1)"
                                class="p-2 rounded-lg text-red-500 hover:bg-red-500/10 transition-all" title="حذف">
                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                        </button>
                    </div>
                </div>

                <input type="text" x-model="row.title.ar" :name="`timeline_items[${index}][title][ar]`"
                       placeholder="عنوان النقطة" dir="rtl"
                       class="{{ $inputClass }} mb-3" :class="{{ $inputDark }}">
                <textarea x-model="row.description.ar" :name="`timeline_items[${index}][description][ar]`"
                          rows="3" placeholder="الوصف (يمكن أن يبدأ بـ «التاريخ: … — المكان: …»)" dir="rtl"
                          class="{{ $inputClass }} resize-y" :class="{{ $inputDark }}"></textarea>
            </div>
        </template>
        <p x-show="rows.length === 0" class="text-sm" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">
            لا توجد نقاط بعد — قسم «الخط الزمني» لن يظهر في صفحة الحدث.
        </p>
    </div>
</div>

<!-- Participants / characters -->
<div class="rounded-2xl border p-6"
     :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'"
     x-data="{ rows: {{ Js::from(array_values($oldParticipants ?? [])) }} }">
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-xl font-bold flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
            <i data-lucide="users" class="w-5 h-5 text-accent"></i>
            الشخصيات
        </h2>
        <button type="button" @click="rows.push({ name: { ar: '' }, description: { ar: '' }, role: 'other' })"
                class="px-4 py-2 rounded-xl text-sm font-semibold bg-gradient-to-r from-[#0056b3] to-[#218838] text-white flex items-center gap-2">
            <i data-lucide="plus" class="w-4 h-4"></i> إضافة شخصية
        </button>
    </div>

    <div class="space-y-4">
        <template x-for="(row, index) in rows" :key="index">
            <div class="rounded-xl border p-4" :class="darkMode ? 'border-gray-800 bg-gray-900/40' : 'border-gray-200 bg-gray-50'">
                <div class="flex flex-col sm:flex-row gap-3 mb-3">
                    <input type="text" x-model="row.name.ar" :name="`participants[${index}][name][ar]`"
                           placeholder="الاسم" dir="rtl"
                           class="{{ $inputClass }} sm:flex-1" :class="{{ $inputDark }}">
                    <select x-model="row.role" :name="`participants[${index}][role]`"
                            class="{{ $inputClass }} sm:w-48" :class="{{ $inputDark }}">
                        @foreach($participantRoles as $role)
                            <option value="{{ $role->value }}">{{ $role->labelArabic() }}</option>
                        @endforeach
                    </select>
                    <button type="button" @click="rows.splice(index, 1)"
                            class="px-3 py-3 rounded-xl text-red-500 hover:bg-red-500/10 transition-all shrink-0">
                        <i data-lucide="trash-2" class="w-5 h-5"></i>
                    </button>
                </div>
                <textarea x-model="row.description.ar" :name="`participants[${index}][description][ar]`"
                          rows="2" placeholder="نبذة قصيرة عن دور الشخصية في هذا الحدث" dir="rtl"
                          class="{{ $inputClass }} resize-y" :class="{{ $inputDark }}"></textarea>
            </div>
        </template>
        <p x-show="rows.length === 0" class="text-sm" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">
            لا توجد شخصيات بعد — قسم «الشخصيات» لن يظهر في صفحة الحدث.
        </p>
    </div>
</div>

<!-- Lessons -->
<div class="rounded-2xl border p-6"
     :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'"
     x-data="{ rows: {{ Js::from(array_values($oldLessons ?? [])) }} }">
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-xl font-bold flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
            <i data-lucide="lightbulb" class="w-5 h-5 text-accent"></i>
            الدروس والعبر
        </h2>
        <button type="button" @click="rows.push({ title: '', description: '' })"
                class="px-4 py-2 rounded-xl text-sm font-semibold bg-gradient-to-r from-[#0056b3] to-[#218838] text-white flex items-center gap-2">
            <i data-lucide="plus" class="w-4 h-4"></i> إضافة درس
        </button>
    </div>
    <p class="mb-4 text-xs" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">
        تظهر كبطاقات في قسم «الدروس والعبر» بصفحة الحدث.
    </p>
    <div class="space-y-3">
        <template x-for="(row, index) in rows" :key="index">
            <div class="flex flex-col gap-3 p-3 rounded-xl border" :class="darkMode ? 'border-gray-800' : 'border-gray-200'">
                <div class="flex gap-3 items-start">
                    <input type="text" x-model="row.title" :name="`lessons[${index}][title]`" dir="rtl"
                           placeholder="عنوان الدرس" class="{{ $inputClass }} flex-1" :class="{{ $inputDark }}">
                    <button type="button" @click="rows.splice(index, 1)" aria-label="حذف الدرس"
                            class="px-3 py-3 rounded-xl text-red-500 hover:bg-red-500/10 transition-all shrink-0">
                        <i data-lucide="trash-2" class="w-5 h-5"></i>
                    </button>
                </div>
                <textarea x-model="row.description" :name="`lessons[${index}][description]`" rows="2" dir="rtl"
                          placeholder="شرح الدرس" class="{{ $inputClass }} resize-none" :class="{{ $inputDark }}"></textarea>
            </div>
        </template>
    </div>
    <p x-show="rows.length === 0" class="text-sm" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">
        لا توجد دروس بعد — قسم «الدروس والعبر» لن يظهر في صفحة الحدث.
    </p>
</div>

<!-- Sources -->
<div class="rounded-2xl border p-6"
     :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'"
     x-data="{ rows: {{ Js::from(array_values($oldSources ?? [])) }} }">
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-xl font-bold flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
            <i data-lucide="library" class="w-5 h-5 text-accent"></i>
            المصادر والمراجع
        </h2>
        <button type="button" @click="rows.push({ title: '', author: '', reference: '' })"
                class="px-4 py-2 rounded-xl text-sm font-semibold bg-gradient-to-r from-[#0056b3] to-[#218838] text-white flex items-center gap-2">
            <i data-lucide="plus" class="w-4 h-4"></i> إضافة مرجع
        </button>
    </div>
    <p class="mb-4 text-xs" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">
        توثيق المحتوى. مثال: «الرحيق المختوم» / «صفي الرحمن المباركفوري» / «فصل غزوة بدر».
    </p>
    <div class="space-y-3">
        <template x-for="(row, index) in rows" :key="index">
            <div class="flex flex-col sm:flex-row gap-3 items-start">
                <input type="text" x-model="row.title" :name="`sources[${index}][title]`" dir="rtl"
                       placeholder="اسم المرجع" class="{{ $inputClass }} sm:flex-1" :class="{{ $inputDark }}">
                <input type="text" x-model="row.author" :name="`sources[${index}][author]`" dir="rtl"
                       placeholder="المؤلف" class="{{ $inputClass }} sm:flex-1" :class="{{ $inputDark }}">
                <input type="text" x-model="row.reference" :name="`sources[${index}][reference]`" dir="rtl"
                       placeholder="الموضع (فصل/جزء)" class="{{ $inputClass }} sm:flex-1" :class="{{ $inputDark }}">
                <button type="button" @click="rows.splice(index, 1)" aria-label="حذف المرجع"
                        class="px-3 py-3 rounded-xl text-red-500 hover:bg-red-500/10 transition-all shrink-0">
                    <i data-lucide="trash-2" class="w-5 h-5"></i>
                </button>
            </div>
        </template>
    </div>
    <p x-show="rows.length === 0" class="text-sm" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">
        لا توجد مراجع بعد — قسم «المصادر والمراجع» لن يظهر في صفحة الحدث.
    </p>
</div>

<!-- Gallery -->
<div class="rounded-2xl border p-6"
     :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'"
     x-data="{ slots: 0 }">
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-xl font-bold flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
            <i data-lucide="images" class="w-5 h-5 text-accent"></i>
            معرض الصور
        </h2>
        <button type="button" @click="slots = Math.min(slots + 1, 8)" :disabled="slots >= 8"
                class="px-4 py-2 rounded-xl text-sm font-semibold bg-gradient-to-r from-[#0056b3] to-[#218838] text-white flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
            <i data-lucide="plus" class="w-4 h-4"></i> إضافة صورة
        </button>
    </div>

    @if($existingGallery->isNotEmpty())
        {{-- Surviving paths are re-posted; unchecking one removes it on save. --}}
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 mb-6">
            @foreach($existingGallery as $path)
                <label class="relative block rounded-xl overflow-hidden border cursor-pointer group"
                       :class="darkMode ? 'border-gray-800' : 'border-gray-200'">
                    <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($path) }}"
                         alt="" loading="lazy" class="w-full aspect-video object-cover">
                    <span class="absolute top-2 start-2 bg-black/60 rounded-lg p-1.5">
                        <input type="checkbox" name="existing_gallery[]" value="{{ $path }}" checked
                               class="w-4 h-4 rounded text-accent border-gray-300 focus:ring-accent">
                    </span>
                    <span class="absolute bottom-0 inset-x-0 bg-black/60 text-white text-[11px] px-2 py-1">
                        أزل العلامة للحذف عند الحفظ
                    </span>
                </label>
            @endforeach
        </div>
    @endif

    {{--
        One shared <x-admin.image-upload> per slot rather than a bare multi-file
        input, so the gallery gets the same preview, media-library button and
        validation copy as every other image field in the portal. PHP collects
        the repeated `gallery[]` name into an array, and empty slots arrive as
        UPLOAD_ERR_NO_FILE which Laravel drops.

        ponytail: slots are rendered server-side and revealed with Alpine rather
        than cloned by x-for — the component stamps a random media-library target
        id at render time, so cloned copies would all answer the same pick event.
        Raise SLOTS if 8 images per event stops being enough.
    --}}
    @php $gallerySlots = 8; @endphp
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        @for ($i = 0; $i < $gallerySlots; $i++)
            <div x-show="slots > {{ $i }}" x-cloak>
                <x-admin.image-upload
                    name="gallery[]"
                    idName="gallery_ids[]"
                    aspectClass="aspect-video"
                    placeholderIcon="image-plus"
                    maxSize="10MB"
                    formats="JPG, PNG, WebP"
                />
            </div>
        @endfor
    </div>

    <p x-show="slots === 0" class="text-sm" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">
        لا توجد صور جديدة — اضغط «إضافة صورة» لإضافة صورة إلى المعرض.
    </p>
    <p class="mt-3 text-xs" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">
        حتى 20 صورة، بحد أقصى 10 ميجابايت لكل صورة. JPG, PNG, WebP.
    </p>
    @error('gallery')<p class="mt-1 text-sm text-red-500">{{ $message }}</p>@enderror
    @error('gallery.*')<p class="mt-1 text-sm text-red-500">{{ $message }}</p>@enderror
</div>
