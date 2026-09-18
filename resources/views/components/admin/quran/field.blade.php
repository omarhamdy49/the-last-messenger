{{--
    Plain labelled input. type: text|number|url|date|textarea|select|checkbox|file.
    `options` for select is value => label. Old input wins over `value`.
--}}
@props(['name', 'label', 'type' => 'text', 'value' => null, 'options' => [], 'help' => null, 'dir' => null, 'rows' => 4, 'placeholder' => null, 'blank' => true])
@php
    $dotted = str_replace(['[', ']'], ['.', ''], $name);
    $current = old($dotted, $value);
    $inputClass = 'w-full rounded-xl border px-4 py-2.5 text-sm focus:ring-2 focus:ring-accent focus:border-transparent';
    $theme = "darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'";
@endphp
@if ($type === 'checkbox')
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
            <select name="{{ $name }}" {{ $attributes->merge(['class' => $inputClass]) }} :class="{{ $theme }}">
                @if ($blank)<option value="">—</option>@endif
                @foreach ($options as $optionValue => $optionLabel)
                    <option value="{{ $optionValue }}" @selected((string) $current === (string) $optionValue)>{{ $optionLabel }}</option>
                @endforeach
            </select>
        @elseif ($type === 'file')
            <input type="file" name="{{ $name }}" {{ $attributes->merge(['class' => $inputClass]) }} :class="{{ $theme }}">
        @else
            <input type="{{ $type }}" name="{{ $name }}" value="{{ $current }}" @if ($dir) dir="{{ $dir }}" @endif placeholder="{{ $placeholder }}" {{ $attributes->merge(['class' => $inputClass]) }} :class="{{ $theme }}">
        @endif
        @if ($help)
            <span class="mt-1 block text-xs text-gray-500">{{ $help }}</span>
        @endif
        @error($dotted)
            <span class="mt-1 block text-xs text-red-600">{{ $message }}</span>
        @enderror
    </label>
@endif
