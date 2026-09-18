{{--
    Plain labelled input. type: text|number|url|date|textarea|select|checkbox|switch|file.
    `switch` is a checkbox drawn as a full-width toggle tile (same 0/1 contract).
    `options` for select is value => label. Old input wins over `value`.
--}}
@props(['name', 'label', 'type' => 'text', 'value' => null, 'options' => [], 'help' => null, 'dir' => null, 'rows' => 4, 'placeholder' => null, 'blank' => true])
@php
    $dotted = str_replace(['[', ']'], ['.', ''], $name);
    $current = old($dotted, $value);
    $inputClass = 'w-full rounded-xl border px-4 py-2.5 text-sm focus:ring-2 focus:ring-accent focus:border-transparent';
    // Selects render taller than inputs with the same padding; pin one height so grid rows align.
    $controlHeight = 'h-11';
    $theme = "darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'";
@endphp
@if ($type === 'switch')
    <label class="flex min-h-[52px] cursor-pointer items-center justify-between gap-3 rounded-xl border px-4 py-3 transition-colors focus-within:ring-2 focus-within:ring-accent"
           :class="darkMode ? 'border-gray-700 hover:bg-gray-800' : 'border-gray-200 hover:bg-gray-50'">
        <span class="min-w-0">
            <span class="block text-sm font-semibold" :class="darkMode ? 'text-gray-200' : 'text-gray-800'">{{ $label }}</span>
            @if ($help)<span class="mt-0.5 block text-xs text-gray-500">{{ $help }}</span>@endif
        </span>
        <input type="hidden" name="{{ $name }}" value="0">
        <input type="checkbox" name="{{ $name }}" value="1" @checked((bool) $current) {{ $attributes }} class="peer sr-only">
        <span aria-hidden="true" class="relative h-6 w-11 shrink-0 rounded-full bg-gray-300 transition-colors duration-200 peer-checked:bg-[#218838] after:absolute after:top-0.5 after:start-0.5 after:h-5 after:w-5 after:rounded-full after:bg-white after:shadow after:transition-transform after:duration-200 peer-checked:after:translate-x-5 rtl:peer-checked:after:-translate-x-5 motion-reduce:transition-none motion-reduce:after:transition-none"></span>
    </label>
@elseif ($type === 'checkbox')
    <label class="inline-flex items-center gap-2 py-2">
        <input type="hidden" name="{{ $name }}" value="0">
        <input type="checkbox" name="{{ $name }}" value="1" @checked((bool) $current) {{ $attributes }} class="w-4 h-4 rounded text-accent border-gray-300 focus:ring-accent">
        <span class="text-sm" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ $label }}</span>
    </label>
@else
    <label class="block">
        <span class="block text-sm font-medium mb-1" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ $label }}</span>
        @if ($type === 'textarea')
            <textarea name="{{ $name }}" rows="{{ $rows }}" @if ($dir) dir="{{ $dir }}" @endif placeholder="{{ $placeholder }}" {{ $attributes->merge(['class' => $inputClass]) }} :class="{{ $theme }}">{{ $current }}</textarea>
        @elseif ($type === 'select')
            <select name="{{ $name }}" {{ $attributes->merge(['class' => $inputClass.' '.$controlHeight]) }} :class="{{ $theme }}">
                @if ($blank)<option value="">—</option>@endif
                @foreach ($options as $optionValue => $optionLabel)
                    <option value="{{ $optionValue }}" @selected((string) $current === (string) $optionValue)>{{ $optionLabel }}</option>
                @endforeach
            </select>
        @elseif ($type === 'file')
            <input type="file" name="{{ $name }}" {{ $attributes->merge(['class' => $inputClass]) }} :class="{{ $theme }}">
        @else
            <input type="{{ $type }}" name="{{ $name }}" value="{{ $current }}" @if ($dir) dir="{{ $dir }}" @endif placeholder="{{ $placeholder }}" {{ $attributes->merge(['class' => $inputClass.' '.$controlHeight]) }} :class="{{ $theme }}">
        @endif
        @if ($help)
            <span class="mt-1 block text-xs text-gray-500">{{ $help }}</span>
        @endif
        @error($dotted)
            <span class="mt-1 block text-xs text-red-600">{{ $message }}</span>
        @enderror
    </label>
@endif
