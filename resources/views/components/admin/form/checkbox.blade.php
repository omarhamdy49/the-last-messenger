@props([
    'name' => '',
    'label' => '',
    'value' => '1',
    'checked' => false,
    'disabled' => false,
    'helpText' => '',
    'description' => '',
])

@php
    $isChecked = old($name, $checked);
@endphp

<div class="form-field"
     x-data="{
         isChecked: {{ $isChecked ? 'true' : 'false' }},
         hasError: false,

         init() {
             @if($errors->has($name))
                 this.hasError = true;
             @endif
         }
     }">

    <label class="flex items-start gap-3 cursor-pointer group"
           :class="'{{ $disabled ? 'opacity-50 cursor-not-allowed' : '' }}'">

        <!-- Hidden input for unchecked state -->
        <input type="hidden" name="{{ $name }}" value="0">

        <!-- Checkbox Input -->
        <div class="relative flex-shrink-0 mt-1">
            <input type="checkbox"
                   name="{{ $name }}"
                   value="{{ $value }}"
                   x-model="isChecked"
                   @if($disabled) disabled @endif
                   class="sr-only peer">

            <!-- Custom Checkbox -->
            <div class="w-6 h-6 rounded-md border-2 transition-all duration-300 flex items-center justify-center"
                 :class="[
                     isChecked
                         ? 'bg-gradient-to-br from-[#0056b3] to-[#218838] border-transparent shadow-glow'
                         : (darkMode ? 'bg-gray-900 border-gray-700 group-hover:border-gray-600' : 'bg-white border-gray-300 group-hover:border-gray-400'),
                     hasError ? 'border-red-500' : ''
                 ]">

                <!-- Checkmark Icon -->
                <i x-show="isChecked"
                   x-transition:enter="transition ease-out duration-200"
                   x-transition:enter-start="opacity-0 scale-50"
                   x-transition:enter-end="opacity-100 scale-100"
                   data-lucide="check"
                   class="w-4 h-4 text-white"></i>
            </div>
        </div>

        <!-- Label and Description -->
        <div class="flex-1">
            <div class="font-semibold"
                 :class="darkMode ? 'text-gray-200' : 'text-gray-800'">
                {{ $label }}
            </div>

            @if($description)
                <p class="text-sm mt-1"
                   :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                    {{ $description }}
                </p>
            @endif
        </div>
    </label>

    <!-- Error Message -->
    @if($errors->has($name))
        <div class="mt-2 ml-9 flex items-start gap-2 text-red-500 text-sm error-message animate-fadeInUp">
            <i data-lucide="alert-circle" class="w-4 h-4 mt-0.5 flex-shrink-0"></i>
            <span>{{ $errors->first($name) }}</span>
        </div>
    @endif

    <!-- Help Text -->
    @if($helpText)
        <p class="mt-2 ml-9 text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
            <i data-lucide="info" class="w-4 h-4 inline mr-1"></i>
            {{ $helpText }}
        </p>
    @endif
</div>
