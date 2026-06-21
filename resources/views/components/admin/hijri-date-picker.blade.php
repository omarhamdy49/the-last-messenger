@props([
    'name' => 'hijri_date',
    'value' => '',
    'label' => null,
    'placeholder' => null,
    'id' => null,
])

{{--
    Reusable Hijri (tabular) calendar picker for admin forms.
    Usage: <x-admin.hijri-date-picker name="hijri_date" :value="$model->hijri_date ?? ''" :label="__('articles.fields.hijri_date')" />
    Submits a human Arabic date string (e.g. "15 رمضان 1445") via a hidden input named {{ $name }}.
--}}

<div x-data="hijriDatePicker({ value: @js((string) $value) })" @click.away="isOpen = false" class="relative">
    @if ($label)
        <label class="block text-xs font-medium mb-1 text-gray-600 dark:text-gray-300">{{ $label }}</label>
    @endif

    {{-- Submitted value --}}
    <input type="hidden" name="{{ $name }}" @if ($id) id="{{ $id }}" @endif x-model="displayValue">

    {{-- Trigger --}}
    <button type="button" @click="toggle()"
            class="w-full flex items-center justify-between gap-2 px-3 py-2 rounded-lg border text-sm text-start transition-colors
                   bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-white
                   focus:outline-none focus:ring-2 focus:ring-[#64CCC5]">
        <span x-text="displayValue || @js($placeholder ?? __('admin.hijri_pick'))"
              :class="displayValue ? '' : 'text-gray-400'"></span>
        <i data-lucide="calendar-days" class="w-4 h-4 text-gray-400 shrink-0"></i>
    </button>

    {{-- Calendar dropdown --}}
    <div x-show="isOpen" x-cloak
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 -translate-y-1"
         x-transition:enter-end="opacity-100 translate-y-0"
         class="absolute z-50 mt-2 w-72 max-w-[90vw] rounded-xl shadow-2xl border p-3
                bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700">

        {{-- Header: month name + editable year, with month nav --}}
        <div class="flex items-center justify-between gap-2 mb-3">
            <button type="button" @click="prevMonth()" aria-label="prev"
                    class="w-8 h-8 rounded-lg flex items-center justify-center text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                <i data-lucide="chevron-right" class="w-4 h-4"></i>
            </button>
            <div class="flex items-center gap-1.5">
                <span class="text-sm font-bold text-gray-900 dark:text-white" x-text="monthName(viewMonth)"></span>
                <input type="number" min="1" max="1700" x-model.number="viewYear" @change="clampYear()"
                       class="w-16 px-2 py-1 rounded-md text-sm text-center text-gray-900 dark:text-white
                              bg-gray-100 dark:bg-gray-700 border border-transparent focus:border-[#64CCC5] focus:outline-none">
            </div>
            <button type="button" @click="nextMonth()" aria-label="next"
                    class="w-8 h-8 rounded-lg flex items-center justify-center text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                <i data-lucide="chevron-left" class="w-4 h-4"></i>
            </button>
        </div>

        {{-- Weekday header --}}
        <div class="grid grid-cols-7 gap-1 mb-1">
            <template x-for="w in weekdays" :key="w">
                <div class="text-[11px] font-semibold text-center text-gray-400 py-1" x-text="w"></div>
            </template>
        </div>

        {{-- Days grid --}}
        <div class="grid grid-cols-7 gap-1">
            <template x-for="(cell, idx) in grid()" :key="idx">
                <div>
                    <template x-if="cell">
                        <button type="button" @click="pick(cell)"
                                class="w-full h-8 rounded-lg text-sm flex items-center justify-center transition-colors"
                                :class="isSelected(cell)
                                    ? 'bg-gradient-to-l from-[#0056b3] to-[#218838] text-white font-bold shadow'
                                    : (isToday(cell)
                                        ? 'ring-1 ring-[#64CCC5] text-gray-900 dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700'
                                        : 'text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700')"
                                x-text="cell"></button>
                    </template>
                </div>
            </template>
        </div>

        {{-- Footer --}}
        <div class="flex items-center justify-between mt-3 pt-2 border-t border-gray-100 dark:border-gray-700">
            <button type="button" @click="goToday()" class="text-xs font-semibold text-[#0056b3] dark:text-[#64CCC5] hover:underline">{{ __('admin.hijri_today') }}</button>
            <button type="button" @click="clear()" class="text-xs font-semibold text-gray-500 hover:text-red-500">{{ __('admin.hijri_clear') }}</button>
        </div>
    </div>
</div>

@once
    @push('scripts')
    <script>
        // Tabular (civil) Hijri calendar picker — pure arithmetic, valid for any
        // year incl. ancient dates. Registered once; reusable across all modules.
        function hijriDatePicker(config) {
            const monthsAr = ['محرم', 'صفر', 'ربيع الأول', 'ربيع الآخر', 'جمادى الأولى', 'جمادى الآخرة', 'رجب', 'شعبان', 'رمضان', 'شوال', 'ذو القعدة', 'ذو الحجة'];
            const weekdaysAr = ['أحد', 'إثن', 'ثلا', 'أرب', 'خمي', 'جمع', 'سبت'];

            const g2jd = (y, m, d) => {
                const a = Math.floor((14 - m) / 12), yy = y + 4800 - a, mm = m + 12 * a - 3;
                return d + Math.floor((153 * mm + 2) / 5) + 365 * yy + Math.floor(yy / 4) - Math.floor(yy / 100) + Math.floor(yy / 400) - 32045;
            };
            const jd2h = (jd) => {
                let l = jd - 1948440 + 10632;
                const n = Math.floor((l - 1) / 10631);
                l = l - 10631 * n + 354;
                const j = Math.floor((10985 - l) / 5316) * Math.floor((50 * l) / 17719) + Math.floor(l / 5670) * Math.floor((43 * l) / 15238);
                l = l - Math.floor((30 - j) / 15) * Math.floor((17719 * j) / 50) - Math.floor(j / 16) * Math.floor((15238 * j) / 43) + 29;
                const m = Math.floor((24 * l) / 709);
                const d = l - Math.floor((709 * m) / 24);
                const y = 30 * n + j - 30;
                return { y, m, d };
            };
            const h2jd = (y, m, d) => Math.floor((11 * y + 3) / 30) + 354 * y + 30 * m - Math.floor((m - 1) / 2) + d + 1948440 - 385;
            const todayH = () => { const t = new Date(); return jd2h(g2jd(t.getFullYear(), t.getMonth() + 1, t.getDate())); };

            return {
                weekdays: weekdaysAr,
                isOpen: false,
                viewYear: 1446,
                viewMonth: 1,
                selected: null,
                displayValue: '',

                init() {
                    const parsed = this.parse(config.value);
                    if (parsed) {
                        this.selected = parsed;
                        this.viewYear = parsed.y;
                        this.viewMonth = parsed.m;
                        this.displayValue = this.fmt(parsed);
                    } else {
                        const t = todayH();
                        this.viewYear = t.y;
                        this.viewMonth = t.m;
                    }
                    this.$nextTick(() => { if (window.lucide) window.lucide.createIcons(); });
                },

                parse(v) {
                    if (!v || typeof v !== 'string') return null;
                    const parts = v.replace(/هـ|ه\.?/g, '').trim().split(/\s+/).filter(Boolean);
                    if (parts.length < 3) return null;
                    const toLatin = (s) => s.replace(/[٠-٩]/g, (c) => '٠١٢٣٤٥٦٧٨٩'.indexOf(c));
                    const d = parseInt(toLatin(parts[0]), 10);
                    const y = parseInt(toLatin(parts[parts.length - 1]), 10);
                    const m = monthsAr.indexOf(parts.slice(1, -1).join(' ')) + 1;
                    if (!d || !y || m < 1) return null;
                    return { y, m, d };
                },
                fmt(s) { return `${s.d} ${monthsAr[s.m - 1]} ${s.y}`; },
                monthName(m) { return monthsAr[m - 1]; },

                toggle() { this.isOpen = !this.isOpen; if (this.isOpen) this.$nextTick(() => { if (window.lucide) window.lucide.createIcons(); }); },
                clampYear() { this.viewYear = Math.min(1700, Math.max(1, this.viewYear || 1)); },

                daysInMonth(y, m) { return (m === 12 ? h2jd(y + 1, 1, 1) : h2jd(y, m + 1, 1)) - h2jd(y, m, 1); },
                firstWeekday(y, m) { return (h2jd(y, m, 1) + 1) % 7; },
                grid() {
                    const days = this.daysInMonth(this.viewYear, this.viewMonth);
                    const lead = this.firstWeekday(this.viewYear, this.viewMonth);
                    const cells = [];
                    for (let i = 0; i < lead; i++) cells.push(null);
                    for (let d = 1; d <= days; d++) cells.push(d);
                    return cells;
                },

                prevMonth() { if (this.viewMonth === 1) { this.viewMonth = 12; this.viewYear--; } else this.viewMonth--; },
                nextMonth() { if (this.viewMonth === 12) { this.viewMonth = 1; this.viewYear++; } else this.viewMonth++; },
                pick(d) { this.selected = { y: this.viewYear, m: this.viewMonth, d }; this.displayValue = this.fmt(this.selected); this.isOpen = false; },
                isSelected(d) { return this.selected && this.selected.y === this.viewYear && this.selected.m === this.viewMonth && this.selected.d === d; },
                isToday(d) { const t = todayH(); return t.y === this.viewYear && t.m === this.viewMonth && t.d === d; },
                goToday() { const t = todayH(); this.viewYear = t.y; this.viewMonth = t.m; },
                clear() { this.selected = null; this.displayValue = ''; this.isOpen = false; },
            };
        }
    </script>
    @endpush
@endonce
