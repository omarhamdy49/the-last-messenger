@props([
    'name',
    'rows' => [],
    'fields' => [],
    'max' => 20,
    'dir' => 'rtl',
    'addLabel' => 'إضافة',
    'emptyText' => null,
])

{{--
    Repeatable rows of plain text inputs, posted as {name}[i][field].

    `fields` maps field key => placeholder. Rows can be added, removed and moved.

    An emptied list still posts `{name}` as an empty value, so the server can tell
    "the editor cleared this" from "this form never had the section" — without it
    an emptied list is absent from the request and the stored rows survive.
--}}
@php
    $blank = array_fill_keys(array_keys($fields), '');
    $initial = collect($rows)
        ->filter(fn ($row) => is_array($row))
        ->map(fn (array $row) => array_merge($blank, array_intersect_key($row, $blank)))
        ->values()
        ->all();
@endphp

<div x-data="{ rows: {{ Js::from($initial) }}, max: {{ (int) $max }} }" {{ $attributes->merge(['class' => 'space-y-3']) }}>
    <template x-for="(row, index) in rows" :key="index">
        <div class="flex flex-col sm:flex-row gap-3 items-start">
            @foreach($fields as $field => $placeholder)
                <input type="text"
                       x-model="row['{{ $field }}']"
                       :name="`{{ $name }}[${index}][{{ $field }}]`"
                       placeholder="{{ $placeholder }}"
                       aria-label="{{ $placeholder }}"
                       dir="{{ $dir }}"
                       class="w-full sm:flex-1 px-4 py-3 rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                       :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
            @endforeach
            <div class="flex items-center gap-1 shrink-0">
                <button type="button" x-show="index > 0" aria-label="تحريك لأعلى"
                        @click="[rows[index - 1], rows[index]] = [rows[index], rows[index - 1]]"
                        class="p-2 rounded-lg hover:bg-black/10 transition-all">
                    <i data-lucide="arrow-up" class="w-4 h-4"></i>
                </button>
                <button type="button" x-show="index < rows.length - 1" aria-label="تحريك لأسفل"
                        @click="[rows[index + 1], rows[index]] = [rows[index], rows[index + 1]]"
                        class="p-2 rounded-lg hover:bg-black/10 transition-all">
                    <i data-lucide="arrow-down" class="w-4 h-4"></i>
                </button>
                <button type="button" aria-label="حذف" @click="rows.splice(index, 1)"
                        class="p-2 rounded-lg text-red-500 hover:bg-red-500/10 transition-all">
                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                </button>
            </div>
        </div>
    </template>

    <template x-if="rows.length === 0">
        <input type="hidden" name="{{ $name }}" value="">
    </template>

    @if($emptyText)
        <p x-show="rows.length === 0" class="text-sm" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">{{ $emptyText }}</p>
    @endif

    <button type="button" x-show="rows.length < max"
            @click="rows.push({{ Js::from($blank) }}); $nextTick(() => window.lucide?.createIcons())"
            class="px-4 py-2 rounded-xl text-sm font-semibold bg-gradient-to-r from-[#0056b3] to-[#218838] text-white inline-flex items-center gap-2">
        <i data-lucide="plus" class="w-4 h-4"></i> {{ $addLabel }}
    </button>
</div>
