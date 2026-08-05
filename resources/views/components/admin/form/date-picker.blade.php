@props([
    'name' => '',
    'label' => '',
    'value' => '',
    'required' => false,
    'disabled' => false,
    'readonly' => false,
    'helpText' => '',
    'icon' => 'calendar',
    'min' => null,
    'max' => null,
    'format' => 'Y-m-d',
    'time' => false,
])

@php
    $selectedValue = old($name, $value);
    $inputType = $time ? 'datetime-local' : 'date';
@endphp

<div class="form-field"
     x-data="{
         darkMode: localStorage.getItem('darkMode') === 'true',
         hasError: false,
         isFocused: false,
         isOpen: false,
         selectedDate: '{{ $selectedValue }}',
         displayDate: '',

         init() {
             @if($errors->has($name))
                 this.hasError = true;
             @endif

             // Format display date
             if (this.selectedDate) {
                 this.formatDisplayDate();
             }
         },

         formatDisplayDate() {
             if (!this.selectedDate) {
                 this.displayDate = '';
                 return;
             }

             try {
                 const date = new Date(this.selectedDate);
                 const options = {
                     year: 'numeric',
                     month: 'long',
                     day: 'numeric',
                     @if($time)
                         hour: '2-digit',
                         minute: '2-digit'
                     @endif
                 };

                 this.displayDate = date.toLocaleDateString('{{ app()->getLocale() }}', options);
             } catch (e) {
                 this.displayDate = this.selectedDate;
             }
         },

         openPicker() {
             if ({{ $disabled || $readonly ? 'true' : 'false' }}) return;
             this.$refs.dateInput.showPicker();
         },

         handleChange() {
             this.formatDisplayDate();
         },

         clearDate() {
             this.selectedDate = '';
             this.displayDate = '';
             this.$refs.dateInput.value = '';
         }
     }">

    <!-- Label -->
    @if($label)
        <label class="block mb-2 font-semibold"
               :class="darkMode ? 'text-gray-200' : 'text-gray-800'">
            {{ $label }}
            @if($required)
                <span class="text-red-500">*</span>
            @endif
        </label>
    @endif

    <!-- Date Input Container -->
    <div class="relative">
        <!-- Hidden Native Date Input -->
        <input type="{{ $inputType }}"
               x-ref="dateInput"
               name="{{ $name }}"
               x-model="selectedDate"
               @change="handleChange()"
               @if($required) required @endif
               @if($disabled) disabled @endif
               @if($readonly) readonly @endif
               @if($min) min="{{ $min }}" @endif
               @if($max) max="{{ $max }}" @endif
               class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
               style="z-index: -1;">

        <!-- Custom Date Display -->
        <button type="button"
                @click="openPicker()"
                @focus="isFocused = true"
                @blur="isFocused = false"
                class="w-full px-4 py-3 rounded-lg border-2 transition-all duration-300 font-cairo text-left flex items-center justify-between gap-3"
                :class="[
                    darkMode ? 'bg-gray-900 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900',
                    isFocused ? 'border-[#64CCC5] shadow-glow ring-4 ring-[#64CCC5]/20' : '',
                    hasError ? 'border-red-500' : '',
                    '{{ $disabled || $readonly ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer' }}'
                ]">

            <div class="flex items-center gap-3 flex-1 min-w-0">
                @if($icon)
                    <i data-lucide="{{ $icon }}" class="w-5 h-5 flex-shrink-0"
                       :class="darkMode ? 'text-gray-400' : 'text-gray-500'"></i>
                @endif

                <span class="truncate"
                      :class="selectedDate ? '' : (darkMode ? 'text-gray-500' : 'text-gray-400')">
                    <span x-show="!selectedDate">Select date{{ $time ? ' and time' : '' }}</span>
                    <span x-show="selectedDate" x-text="displayDate"></span>
                </span>
            </div>

            <div class="flex items-center gap-2 flex-shrink-0">
                <!-- Clear Button (when date is selected) -->
                <button type="button"
                        x-show="selectedDate"
                        @click.stop="clearDate()"
                        class="p-1 rounded-lg transition-colors hover:bg-red-500/10 text-red-500">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>

                <!-- Calendar Icon -->
                <i data-lucide="calendar-days" class="w-5 h-5"
                   :class="darkMode ? 'text-gray-400' : 'text-gray-500'"></i>
            </div>
        </button>
    </div>

    <!-- Quick Date Selection Buttons -->
    @if(!$time)
        <div class="mt-3 flex flex-wrap gap-2">
            <button type="button"
                    @click="selectedDate = new Date().toISOString().split('T')[0]; handleChange()"
                    class="px-3 py-1.5 text-sm rounded-lg border transition-all duration-300"
                    :class="darkMode ? 'border-gray-700 text-gray-300 hover:bg-gray-800' : 'border-gray-300 text-gray-700 hover:bg-gray-100'">
                <i data-lucide="calendar-check" class="w-4 h-4 inline mr-1"></i>
                Today
            </button>

            <button type="button"
                    @click="selectedDate = new Date(Date.now() + 86400000).toISOString().split('T')[0]; handleChange()"
                    class="px-3 py-1.5 text-sm rounded-lg border transition-all duration-300"
                    :class="darkMode ? 'border-gray-700 text-gray-300 hover:bg-gray-800' : 'border-gray-300 text-gray-700 hover:bg-gray-100'">
                <i data-lucide="calendar-plus" class="w-4 h-4 inline mr-1"></i>
                Tomorrow
            </button>

            <button type="button"
                    @click="selectedDate = new Date(Date.now() + 7 * 86400000).toISOString().split('T')[0]; handleChange()"
                    class="px-3 py-1.5 text-sm rounded-lg border transition-all duration-300"
                    :class="darkMode ? 'border-gray-700 text-gray-300 hover:bg-gray-800' : 'border-gray-300 text-gray-700 hover:bg-gray-100'">
                <i data-lucide="calendar-range" class="w-4 h-4 inline mr-1"></i>
                Next Week
            </button>
        </div>
    @endif

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
