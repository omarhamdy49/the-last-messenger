@props([
    'name' => '',
    'label' => '',
    'options' => [],
    'value' => '',
    'required' => false,
    'disabled' => false,
    'helpText' => '',
    'inline' => false,
])

@php
    $selectedValue = old($name, $value);
@endphp

<div class="form-field"
     x-data="{
         selectedValue: '{{ $selectedValue }}',
         hasError: false,

         init() {
             @if($errors->has($name))
                 this.hasError = true;
             @endif
         }
     }">

    <!-- Label -->
    @if($label)
        <label class="block mb-3 font-semibold"
               :class="darkMode ? 'text-gray-200' : 'text-gray-800'">
            {{ $label }}
            @if($required)
                <span class="text-red-500">*</span>
            @endif
        </label>
    @endif

    <!-- Radio Options -->
    <div class="{{ $inline ? 'flex flex-wrap gap-4' : 'space-y-3' }}">
        @foreach($options as $optionValue => $optionData)
            @php
                $optionLabel = is_array($optionData) ? $optionData['label'] : $optionData;
                $optionDescription = is_array($optionData) ? ($optionData['description'] ?? '') : '';
                $optionIcon = is_array($optionData) ? ($optionData['icon'] ?? null) : null;
            @endphp

            <label class="flex items-start gap-3 cursor-pointer group p-4 rounded-lg border-2 transition-all duration-300"
                   :class="[
                       selectedValue === '{{ $optionValue }}'
                           ? 'border-[#64CCC5] bg-gradient-to-r from-[#0056b3]/5 to-[#218838]/5 shadow-soft'
                           : (darkMode ? 'border-gray-800 bg-gray-900/50 hover:border-gray-700' : 'border-gray-200 bg-gray-50/50 hover:border-gray-300'),
                       '{{ $disabled ? 'opacity-50 cursor-not-allowed' : '' }}'
                   ]">

                <!-- Radio Input -->
                <div class="relative flex-shrink-0 mt-0.5">
                    <input type="radio"
                           name="{{ $name }}"
                           value="{{ $optionValue }}"
                           x-model="selectedValue"
                           @if($required) required @endif
                           @if($disabled) disabled @endif
                           class="sr-only peer">

                    <!-- Custom Radio Button -->
                    <div class="w-6 h-6 rounded-full border-2 transition-all duration-300 flex items-center justify-center"
                         :class="[
                             selectedValue === '{{ $optionValue }}'
                                 ? 'bg-gradient-to-br from-[#0056b3] to-[#218838] border-transparent shadow-glow'
                                 : (darkMode ? 'bg-gray-900 border-gray-700 group-hover:border-gray-600' : 'bg-white border-gray-300 group-hover:border-gray-400'),
                             hasError ? 'border-red-500' : ''
                         ]">

                        <!-- Inner Circle -->
                        <div x-show="selectedValue === '{{ $optionValue }}'"
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0 scale-0"
                             x-transition:enter-end="opacity-100 scale-100"
                             class="w-2 h-2 rounded-full bg-white">
                        </div>
                    </div>
                </div>

                <!-- Option Content -->
                <div class="flex-1 {{ $inline ? 'min-w-0' : '' }}">
                    <div class="flex items-center gap-2">
                        @if($optionIcon)
                            <i data-lucide="{{ $optionIcon }}" class="w-5 h-5"
                               :class="selectedValue === '{{ $optionValue }}'
                                   ? 'text-[#64CCC5]'
                                   : (darkMode ? 'text-gray-400' : 'text-gray-500')"></i>
                        @endif

                        <span class="font-semibold"
                              :class="[
                                  selectedValue === '{{ $optionValue }}'
                                      ? 'gradient-text'
                                      : (darkMode ? 'text-gray-200' : 'text-gray-800')
                              ]">
                            {{ $optionLabel }}
                        </span>
                    </div>

                    @if($optionDescription)
                        <p class="text-sm mt-1"
                           :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                            {{ $optionDescription }}
                        </p>
                    @endif
                </div>
            </label>
        @endforeach
    </div>

    <!-- Error Message -->
    @if($errors->has($name))
        <div class="mt-2 flex items-start gap-2 text-red-500 text-sm error-message animate-fadeInUp">
            <i data-lucide="alert-circle" class="w-4 h-4 mt-0.5 flex-shrink-0"></i>
            <span>{{ $errors->first($name) }}</span>
        </div>
    @endif

    <!-- Help Text -->
    @if($helpText)
        <p class="mt-2 text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
            <i data-lucide="info" class="w-4 h-4 inline mr-1"></i>
            {{ $helpText }}
        </p>
    @endif
</div>
