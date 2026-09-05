@props(['name', 'label', 'checked' => false, 'help' => null])
<label class="flex items-start gap-3 cursor-pointer">
    {{-- Hidden 0 first so unchecking actually posts false. --}}
    <input type="hidden" name="{{ $name }}" value="0">
    <input type="checkbox" name="{{ $name }}" value="1" @checked(old($name, $checked))
           class="mt-1 w-5 h-5 rounded border-gray-300 text-[#0056b3] focus:ring-accent">
    <span>
        <span class="font-medium" :class="darkMode ? 'text-gray-200' : 'text-gray-800'">{{ $label }}</span>
        @if($help)<span class="block text-xs mt-0.5" :class="darkMode ? 'text-gray-500' : 'text-gray-500'">{{ $help }}</span>@endif
    </span>
</label>
