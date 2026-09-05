@props(['name', 'label', 'value' => null, 'type' => 'text', 'dir' => null, 'required' => false, 'help' => null, 'rows' => 4])
<div>
    <label for="{{ $name }}" class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
        {{ $label }}@if($required)<span class="text-red-500 ms-1">*</span>@endif
    </label>
    @if($type === 'textarea')
        <textarea id="{{ $name }}" name="{{ $name }}" rows="{{ $rows }}" @if($dir)dir="{{ $dir }}"@endif
                  class="w-full px-4 py-2.5 rounded-xl border resize-y @error($name) border-red-400 @enderror"
                  :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">{{ old($name, $value) }}</textarea>
    @else
        <input type="{{ $type }}" id="{{ $name }}" name="{{ $name }}" value="{{ old($name, $value) }}" @if($dir)dir="{{ $dir }}"@endif
               class="w-full px-4 py-2.5 rounded-xl border @error($name) border-red-400 @enderror"
               :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
    @endif
    @if($help)<p class="mt-1 text-xs" :class="darkMode ? 'text-gray-500' : 'text-gray-500'">{{ $help }}</p>@endif
    @error($name)<p class="mt-1 text-sm text-red-500">{{ $message }}</p>@enderror
</div>
