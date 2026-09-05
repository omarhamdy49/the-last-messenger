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
        // participant_id MUST be carried through. The requests validate it
        // (exists:muslims,id) and SeerahEventService persists it, so a row rebuilt
        // without it posts back a null and silently unlinks the person on save —
        // the same trap that blanks lessons and sources when they are not carried.
        $linkedIds = $event->participants
            ->pluck('participant_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        // Seerah must not import App\Domain\People — the PersonLookup contract is
        // the sanctioned way across that boundary (see the note on the interface).
        $linkedPeople = $linkedIds === []
            ? []
            : app(\App\Domain\Shared\Contracts\PersonLookup::class)->byIds($linkedIds);

        $oldParticipants = $event->participants
            ->map(function ($p) use ($linkedPeople) {
                $id = $p->participant_id ? (int) $p->participant_id : null;

                return [
                    'name' => ['ar' => $p->getTranslation('name', 'ar') ?? ''],
                    'description' => ['ar' => $p->getTranslation('description', 'ar') ?? ''],
                    'role' => $p->role->value,
                    'participant_id' => $id,
                    // Display only — never posted. Lets the editor see which person a
                    // row is linked to instead of a bare id.
                    'linked_name' => $id !== null && isset($linkedPeople[$id])
                        ? $linkedPeople[$id]->name('ar')
                        : null,
                ];
            })
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
{{--
    Companions are LINKED, never typed. Every free-typed name is a duplicate of a
    record that already exists in the muslims module (7,800+ of them), and a typo
    silently forks a person into two. So the picker is the primary path and the
    row shows the linked record.

    Free text stays available for the people the module cannot hold — tribes
    (جرهم، خزاعة), and pre-Islamic or opposing figures with no record (أبو جهل،
    هرقل، عمرو بن لحي). Those rows are flagged «يحتاج مراجعة» rather than deleted,
    so the gap is visible and can be closed later by adding the record.
--}}
<div class="rounded-2xl border p-6"
     :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'"
     x-data="seerahParticipants({{ Js::from(array_values($oldParticipants ?? [])) }})"
     @muslim-picked.window="onPicked($event.detail)">

    <div class="flex flex-wrap items-start justify-between gap-3 mb-5">
        <div class="min-w-0">
            <h2 class="text-xl font-bold flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                <i data-lucide="users" class="w-5 h-5 text-accent"></i>
                الشخصيات والصحابة
            </h2>
            <p class="text-xs mt-1" :class="darkMode ? 'text-gray-500' : 'text-gray-500'">
                اربط كل صحابي بسجلّه في وحدة المسلمين. الاسم الحر للقبائل ومَن لا سجلّ له.
            </p>
        </div>

        {{-- Counts, so the review state of the whole section reads at a glance. --}}
        <div class="flex items-center gap-2 text-xs font-semibold" x-show="rows.length > 0">
            <span class="px-2.5 py-1 rounded-lg inline-flex items-center gap-1.5 tabular-nums"
                  :class="darkMode ? 'bg-emerald-500/15 text-emerald-300' : 'bg-emerald-50 text-emerald-700'">
                <i data-lucide="link" class="w-3.5 h-3.5"></i>
                <span x-text="linkedCount"></span> مرتبط
            </span>
            <span x-show="unlinkedCount > 0"
                  class="px-2.5 py-1 rounded-lg inline-flex items-center gap-1.5 tabular-nums"
                  :class="darkMode ? 'bg-amber-500/15 text-amber-300' : 'bg-amber-50 text-amber-700'">
                <i data-lucide="alert-triangle" class="w-3.5 h-3.5"></i>
                <span x-text="unlinkedCount"></span> يحتاج مراجعة
            </span>
        </div>
    </div>

    <div class="flex flex-wrap items-center gap-2 mb-5">
        <button type="button" @click="pickNew()"
                class="px-4 py-2.5 rounded-xl text-sm font-semibold bg-gradient-to-r from-[#0056b3] to-[#218838] text-white flex items-center gap-2 transition-transform hover:scale-[1.02] focus:outline-none focus-visible:ring-2 focus-visible:ring-accent">
            <i data-lucide="user-plus" class="w-4 h-4"></i>
            اختيار من وحدة المسلمين
        </button>

        <button type="button" @click="addFreeText()"
                class="px-4 py-2.5 rounded-xl text-sm font-semibold border transition-colors flex items-center gap-2 focus:outline-none focus-visible:ring-2 focus-visible:ring-accent"
                :class="darkMode ? 'border-gray-700 text-gray-300 hover:bg-gray-800' : 'border-gray-300 text-gray-700 hover:bg-gray-50'">
            <i data-lucide="type" class="w-4 h-4"></i>
            اسم حر (قبيلة أو بلا سجلّ)
        </button>
    </div>

    <div class="space-y-3">
        <template x-for="(row, index) in rows" :key="row.uid">
            <div class="rounded-xl border overflow-hidden transition-colors"
                 :class="row.participant_id
                     ? (darkMode ? 'border-gray-800 bg-gray-900/40' : 'border-gray-200 bg-white')
                     : (darkMode ? 'border-amber-500/30 bg-amber-500/5' : 'border-amber-300 bg-amber-50/40')">

                <!-- Identity row -->
                <div class="flex flex-wrap items-center gap-3 p-4 pb-3">
                    <div class="w-9 h-9 shrink-0 rounded-full grid place-items-center"
                         :class="row.participant_id
                             ? 'bg-gradient-to-br from-[#0056b3] to-[#218838]'
                             : (darkMode ? 'bg-amber-500/20' : 'bg-amber-200')">
                        <i :data-lucide="row.participant_id ? 'user-check' : 'help-circle'"
                           class="w-4 h-4" :class="row.participant_id ? 'text-white' : 'text-amber-700'"></i>
                    </div>

                    <div class="min-w-0 flex-1">
                        {{-- Linked: the record's own name, read-only. Editing it here would
                             fork the person, which is the whole point of linking. --}}
                        <template x-if="row.participant_id">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="font-semibold text-sm truncate"
                                          :class="darkMode ? 'text-white' : 'text-gray-900'"
                                          x-text="row.linked_name || row.name.ar"></span>
                                    <span class="px-1.5 py-0.5 rounded text-[11px] font-semibold tabular-nums"
                                          :class="darkMode ? 'bg-emerald-500/15 text-emerald-300' : 'bg-emerald-50 text-emerald-700'"
                                          x-text="'#' + row.participant_id"></span>
                                </div>
                                <p class="text-[11px] mt-0.5" :class="darkMode ? 'text-gray-500' : 'text-gray-500'">
                                    مرتبط بسجلّ في وحدة المسلمين
                                </p>
                            </div>
                        </template>

                        {{-- Unlinked: free text, and say plainly why it is flagged. --}}
                        <template x-if="! row.participant_id">
                            <div class="min-w-0">
                                <label class="sr-only" :for="`participant-name-${index}`">اسم الشخصية</label>
                                <input type="text" :id="`participant-name-${index}`"
                                       x-model="row.name.ar" :name="`participants[${index}][name][ar]`"
                                       placeholder="اسم القبيلة أو الشخصية" dir="rtl"
                                       class="{{ $inputClass }} w-full" :class="{{ $inputDark }}">
                                <p class="text-[11px] mt-1 flex items-center gap-1"
                                   :class="darkMode ? 'text-amber-400' : 'text-amber-700'">
                                    <i data-lucide="alert-triangle" class="w-3 h-3"></i>
                                    غير مرتبط بسجلّ — للمراجعة
                                </p>
                            </div>
                        </template>
                    </div>

                    <label class="sr-only" :for="`participant-role-${index}`">دور الشخصية في الحدث</label>
                    <select :id="`participant-role-${index}`"
                            x-model="row.role" :name="`participants[${index}][role]`"
                            class="{{ $inputClass }} sm:w-44" :class="{{ $inputDark }}">
                        @foreach($participantRoles as $role)
                            <option value="{{ $role->value }}">{{ $role->labelArabic() }}</option>
                        @endforeach
                    </select>

                    <div class="flex items-center gap-1 shrink-0">
                        <button type="button" @click="pickFor(index)"
                                class="px-2.5 py-2 rounded-lg text-xs font-semibold transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-accent"
                                :class="darkMode ? 'bg-gray-800 text-gray-200 hover:bg-gray-700' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                                :aria-label="row.participant_id ? 'تغيير السجلّ المرتبط' : 'ربط بسجلّ'"
                                x-text="row.participant_id ? 'تغيير' : 'ربط'"></button>

                        <button type="button" x-show="row.participant_id"
                                @click="unlink(index)"
                                class="px-2.5 py-2 rounded-lg text-xs font-semibold text-amber-600 hover:bg-amber-500/10 transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-accent"
                                aria-label="فكّ الربط">فكّ</button>

                        <button type="button" @click="rows.splice(index, 1)"
                                class="p-2 rounded-lg text-red-500 hover:bg-red-500/10 transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-red-400"
                                aria-label="حذف الشخصية">
                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                        </button>
                    </div>
                </div>

                <!-- Role description -->
                <div class="px-4 pb-4">
                    {{-- Posted for linked rows too: the muslims record holds the person's
                         biography, this holds what they did in THIS event. --}}
                    <template x-if="row.participant_id">
                        <input type="hidden" :name="`participants[${index}][name][ar]`"
                               :value="row.linked_name || row.name.ar">
                    </template>
                    <input type="hidden" :name="`participants[${index}][participant_id]`"
                           :value="row.participant_id ?? ''">

                    <label class="block text-xs font-semibold mb-1.5"
                           :class="darkMode ? 'text-gray-400' : 'text-gray-600'"
                           :for="`participant-desc-${index}`">
                        دوره في هذا الحدث
                    </label>
                    <textarea :id="`participant-desc-${index}`"
                              x-model="row.description.ar" :name="`participants[${index}][description][ar]`"
                              rows="2" placeholder="سطر واحد: ما الذي فعله في هذا الحدث تحديدًا" dir="rtl"
                              class="{{ $inputClass }} resize-y w-full" :class="{{ $inputDark }}"></textarea>
                </div>
            </div>
        </template>

        {{-- Empty state carries the action, not just the message. --}}
        <div x-show="rows.length === 0"
             class="rounded-xl border border-dashed py-10 px-6 text-center"
             :class="darkMode ? 'border-gray-700' : 'border-gray-300'">
            <div class="inline-flex items-center justify-center w-12 h-12 rounded-full mb-3"
                 :class="darkMode ? 'bg-gray-800' : 'bg-gray-100'">
                <i data-lucide="users" class="w-6 h-6" :class="darkMode ? 'text-gray-600' : 'text-gray-400'"></i>
            </div>
            <p class="text-sm mb-3" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                لا توجد شخصيات بعد — قسم «الشخصيات» لن يظهر في صفحة الحدث.
            </p>
            <button type="button" @click="pickNew()"
                    class="px-4 py-2 rounded-xl text-sm font-semibold bg-gradient-to-r from-[#0056b3] to-[#218838] text-white inline-flex items-center gap-2">
                <i data-lucide="user-plus" class="w-4 h-4"></i>
                اختيار من وحدة المسلمين
            </button>
        </div>
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

@once
@push('scripts')
<script>
    /**
     * Participant rows for a Seerah event.
     *
     * `uid` exists only to key the x-for. Keying on the array index made Alpine reuse
     * a DOM node when a row was removed, so the row below inherited the deleted row's
     * linked badge — the link looked moved rather than deleted.
     */
    function seerahParticipants(initial) {
        let seq = 0;

        return {
            rows: (initial ?? []).map((row) => ({
                uid: ++seq,
                name: row.name ?? { ar: '' },
                description: row.description ?? { ar: '' },
                role: row.role ?? 'companion',
                participant_id: row.participant_id ?? null,
                linked_name: row.linked_name ?? null,
            })),

            // Which row the picker was opened for; -1 means "append a new row".
            pickingIndex: -1,

            get linkedCount() {
                return this.rows.filter((r) => r.participant_id).length;
            },

            get unlinkedCount() {
                return this.rows.length - this.linkedCount;
            },

            /** Ids already on this event, so the picker can grey them out. */
            takenIds() {
                return this.rows.map((r) => r.participant_id).filter(Boolean);
            },

            openPicker(index) {
                this.pickingIndex = index;
                this.$dispatch('open-muslim-picker', {
                    field: 'seerah-participant',
                    title: 'اختيار شخصية من وحدة المسلمين',
                    takenIds: this.takenIds(),
                });
            },

            pickNew() { this.openPicker(-1); },
            pickFor(index) { this.openPicker(index); },

            addFreeText() {
                this.rows.push({
                    uid: ++seq,
                    name: { ar: '' },
                    description: { ar: '' },
                    role: 'other',
                    participant_id: null,
                    linked_name: null,
                });
                this.$nextTick(() => window.lucide?.createIcons());
            },

            onPicked(detail) {
                if (detail?.field !== 'seerah-participant') { return; }

                const name = detail.name_ar || detail.name || '';

                if (this.pickingIndex === -1) {
                    this.rows.push({
                        uid: ++seq,
                        name: { ar: name },
                        description: { ar: '' },
                        role: (detail.roles ?? []).some((r) => String(r).includes('صحاب'))
                            ? 'companion'
                            : 'other',
                        participant_id: detail.id,
                        linked_name: name,
                    });
                } else {
                    const row = this.rows[this.pickingIndex];
                    if (row) {
                        row.participant_id = detail.id;
                        row.linked_name = name;
                        // Keep whatever the editor already typed; only fill a blank.
                        if (!row.name.ar) { row.name.ar = name; }
                    }
                }

                this.pickingIndex = -1;
                this.$nextTick(() => window.lucide?.createIcons());
            },

            unlink(index) {
                const row = this.rows[index];
                if (!row) { return; }
                // Keep the name as free text so unlinking never loses what was there.
                if (!row.name.ar) { row.name.ar = row.linked_name ?? ''; }
                row.participant_id = null;
                row.linked_name = null;
                this.$nextTick(() => window.lucide?.createIcons());
            },
        };
    }
</script>
@endpush
@endonce
