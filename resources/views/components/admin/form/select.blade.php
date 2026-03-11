@props([
    'name' => '',
    'label' => '',
    'value' => '',
    'options' => [],
    'placeholder' => 'Select an option',
    'required' => false,
    'disabled' => false,
    'multiple' => false,
    'helpText' => '',
    'icon' => null,
    'searchable' => false,
])

@php
    $selectedValue = old($name, $value);
    if ($multiple && !is_array($selectedValue)) {
        $selectedValue = $selectedValue ? [$selectedValue] : [];
    }
@endphp

<div class="form-field"
     x-data="{
         darkMode: localStorage.getItem('darkMode') !== 'false',
         hasError: false,
         isFocused: false,
         isOpen: false,
         searchQuery: '',
         selectedValues: {{ $multiple ? json_encode($selectedValue) : "'" . $selectedValue . "'" }},
         filteredOptions: {},
         searchable: {{ $searchable ? 'true' : 'false' }},
         multiple: {{ $multiple ? 'true' : 'false' }},

         init() {
             // Initialize filtered options
             this.filteredOptions = @json($options);

             // Check for errors
             @if($errors->has($name))
                 this.hasError = true;
             @endif

             // Close dropdown when clicking outside
             document.addEventListener('click', (e) => {
                 if (!this.$el.contains(e.target)) {
                     this.isOpen = false;
                 }
             });
         },

         filterOptions() {
             if (!this.searchable || !this.searchQuery) {
                 this.filteredOptions = @json($options);
                 return;
             }

             const query = this.searchQuery.toLowerCase();
             const allOptions = @json($options);
             this.filteredOptions = {};

             Object.keys(allOptions).forEach(key => {
                 const label = allOptions[key].toLowerCase();
                 if (label.includes(query) || key.toLowerCase().includes(query)) {
                     this.filteredOptions[key] = allOptions[key];
                 }
             });
         },

         selectOption(value) {
             if (this.multiple) {
                 const index = this.selectedValues.indexOf(value);
                 if (index > -1) {
                     this.selectedValues.splice(index, 1);
                 } else {
                     this.selectedValues.push(value);
                 }
             } else {
                 this.selectedValues = value;
                 this.isOpen = false;
             }
         },

         isSelected(value) {
             return this.multiple
                 ? this.selectedValues.includes(value)
                 : this.selectedValues === value;
         },

         getSelectedLabel() {
             if (this.multiple) {
                 const allOptions = @json($options);
                 const labels = this.selectedValues.map(val => allOptions[val] || val);
                 return labels.length > 0 ? labels.join(', ') : '{{ $placeholder }}';
             }

             const allOptions = @json($options);
             return allOptions[this.selectedValues] || '{{ $placeholder }}';
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

    <!-- Hidden input to store selected value(s) -->
    <template x-if="!multiple">
        <input type="hidden"
               name="{{ $name }}"
               :value="selectedValues"
               @if($required) required @endif>
    </template>

    <template x-if="multiple">
        <template x-for="(value, index) in selectedValues" :key="index">
            <input type="hidden"
                   :name="'{{ $name }}[' + index + ']'"
                   :value="value">
        </template>
    </template>

    <!-- Select Dropdown -->
    <div class="relative">
        <!-- Trigger Button -->
        <button type="button"
                @click="isOpen = !isOpen; isFocused = !isOpen"
                @if($disabled) disabled @endif
                class="w-full px-4 py-3 rounded-lg border-2 transition-all duration-300 font-cairo text-start text-base sm:text-sm flex items-center justify-between"
                :class="[
                    darkMode ? 'bg-gray-900 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900',
                    isFocused ? 'border-[#64CCC5] shadow-glow ring-4 ring-[#64CCC5]/20' : '',
                    hasError ? 'border-red-500' : '',
                    '{{ $disabled ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer' }}'
                ]">

            <div class="flex items-center gap-3 flex-1 min-w-0">
                @if($icon)
                    <i data-lucide="{{ $icon }}" class="w-5 h-5 flex-shrink-0"
                       :class="darkMode ? 'text-gray-400' : 'text-gray-500'"></i>
                @endif

                <span class="truncate"
                      :class="selectedValues && selectedValues.length > 0 ? '' : (darkMode ? 'text-gray-500' : 'text-gray-400')"
                      x-text="getSelectedLabel()">
                </span>
            </div>

            <i data-lucide="chevron-down" class="w-5 h-5 flex-shrink-0 transition-transform duration-300"
               :class="[
                   darkMode ? 'text-gray-400' : 'text-gray-500',
                   isOpen ? 'rotate-180' : ''
               ]"></i>
        </button>

        <!-- Dropdown Menu -->
        <div x-show="isOpen"
             x-cloak
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 translate-y-2"
             class="absolute z-50 w-full mt-2 rounded-lg shadow-2xl overflow-hidden border glass"
             :class="darkMode ? 'glass-dark border-gray-700' : 'glass-light border-gray-200'">

            <!-- Search Input (if searchable) -->
            @if($searchable)
                <div class="p-3 border-b"
                     :class="darkMode ? 'border-gray-700' : 'border-gray-200'">
                    <input type="text"
                           x-model="searchQuery"
                           @input="filterOptions()"
                           placeholder="{{ __('admin.search') }}"
                           class="w-full px-3 py-2 rounded-lg border transition-colors font-cairo text-base sm:text-sm"
                           :class="darkMode ? 'bg-gray-900 border-gray-700 text-white placeholder-gray-400' : 'bg-white border-gray-300 text-gray-900 placeholder-gray-400'">
                </div>
            @endif

            <!-- Options List -->
            <div class="max-h-60 overflow-y-auto custom-scrollbar">
                <template x-for="(optionLabel, optionValue) in filteredOptions" :key="optionValue">
                    <button type="button"
                            @click="selectOption(optionValue)"
                            class="w-full px-4 py-3 text-start flex items-center justify-between transition-colors"
                            :class="[
                                isSelected(optionValue)
                                    ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white'
                                    : (darkMode ? 'text-gray-300 hover:bg-gray-800' : 'text-gray-700 hover:bg-gray-100')
                            ]">
                        <span x-text="optionLabel"></span>
                        <i x-show="isSelected(optionValue)"
                           data-lucide="check"
                           class="w-5 h-5 text-white"></i>
                    </button>
                </template>

                <!-- No results message -->
                <div x-show="Object.keys(filteredOptions).length === 0"
                     class="px-4 py-8 text-center"
                     :class="darkMode ? 'text-gray-400' : 'text-gray-500'">
                    <i data-lucide="search-x" class="w-12 h-12 mx-auto mb-2 opacity-50"></i>
                    <p>{{ __('admin.no_results') }}</p>
                </div>
            </div>
        </div>
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
