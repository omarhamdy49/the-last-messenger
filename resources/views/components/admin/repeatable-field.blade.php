@props([
    'name' => 'items',
    'label' => '',
    'fields' => [], // [{name: 'field1', label: 'Label 1', type: 'text'}, ...]
    'values' => [],
    'addButtonText' => 'إضافة عنصر',
    'removeButtonText' => 'حذف',
    'emptyMessage' => 'لا توجد عناصر بعد',
    'minItems' => 0,
    'maxItems' => 999,
    'help' => null,
])

<div x-data="repeatableField({
    name: '{{ $name }}',
    fields: {{ json_encode($fields) }},
    values: {{ json_encode($values) }},
    minItems: {{ $minItems }},
    maxItems: {{ $maxItems }}
})" class="repeatable-field">

    <!-- Label & Help -->
    @if($label)
        <div class="flex items-center justify-between mb-3">
            <label class="block text-sm font-semibold"
                   :class="darkMode ? 'text-white' : 'text-gray-900'">
                {{ $label }}
            </label>
            @if($help)
                <div x-data="{ showHelp: false }" class="relative">
                    <button type="button"
                            @click="showHelp = !showHelp"
                            class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <i data-lucide="help-circle" class="w-4 h-4"></i>
                    </button>
                    <div x-show="showHelp"
                         x-transition
                         @click.away="showHelp = false"
                         class="absolute z-50 end-0 mt-2 w-64 p-3 rounded-lg shadow-lg text-sm"
                         :class="darkMode ? 'bg-gray-800 text-gray-300' : 'bg-white text-gray-600'">
                        {{ $help }}
                    </div>
                </div>
            @endif
        </div>
    @endif

    <!-- Items List -->
    <div class="space-y-3 mb-3">
        <template x-for="(item, index) in items" :key="item.id">
            <div class="relative p-4 rounded-lg border-2 transition-all"
                 :class="darkMode ?
                    'bg-gray-800 border-gray-700 hover:border-gray-600' :
                    'bg-gray-50 border-gray-200 hover:border-gray-300'">

                <!-- Item Fields -->
                <div class="grid grid-cols-1 gap-3"
                     :class="fields.length > 1 ? 'md:grid-cols-' + Math.min(fields.length, 3) : ''">
                    <template x-for="field in fields" :key="field.name">
                        <div>
                            <label class="block text-xs font-medium mb-1"
                                   :class="darkMode ? 'text-gray-400' : 'text-gray-600'"
                                   x-text="field.label"></label>

                            <!-- Text Input -->
                            <template x-if="field.type === 'text' || field.type === 'url'">
                                <input :type="field.type"
                                       :name="`${name}[${index}][${field.name}]`"
                                       x-model="item[field.name]"
                                       :placeholder="field.placeholder || ''"
                                       class="w-full px-3 py-2 rounded-lg border transition-colors focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                       :class="darkMode ?
                                           'bg-gray-700 border-gray-600 text-white' :
                                           'bg-white border-gray-300 text-gray-900'">
                            </template>

                            <!-- Textarea -->
                            <template x-if="field.type === 'textarea'">
                                <textarea :name="`${name}[${index}][${field.name}]`"
                                          x-model="item[field.name]"
                                          :placeholder="field.placeholder || ''"
                                          rows="2"
                                          class="w-full px-3 py-2 rounded-lg border transition-colors focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                          :class="darkMode ?
                                              'bg-gray-700 border-gray-600 text-white' :
                                              'bg-white border-gray-300 text-gray-900'"></textarea>
                            </template>

                            <!-- Number Input -->
                            <template x-if="field.type === 'number'">
                                <input type="number"
                                       :name="`${name}[${index}][${field.name}]`"
                                       x-model="item[field.name]"
                                       :placeholder="field.placeholder || ''"
                                       :min="field.min || ''"
                                       :max="field.max || ''"
                                       :step="field.step || '1'"
                                       class="w-full px-3 py-2 rounded-lg border transition-colors focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                       :class="darkMode ?
                                           'bg-gray-700 border-gray-600 text-white' :
                                           'bg-white border-gray-300 text-gray-900'">
                            </template>

                            <!-- Select -->
                            <template x-if="field.type === 'select'">
                                <select :name="`${name}[${index}][${field.name}]`"
                                        x-model="item[field.name]"
                                        class="w-full px-3 py-2 rounded-lg border transition-colors focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                        :class="darkMode ?
                                            'bg-gray-700 border-gray-600 text-white' :
                                            'bg-white border-gray-300 text-gray-900'">
                                    <template x-for="option in field.options" :key="option.value">
                                        <option :value="option.value" x-text="option.label"></option>
                                    </template>
                                </select>
                            </template>
                        </div>
                    </template>
                </div>

                <!-- Remove Button -->
                <button type="button"
                        @click="removeItem(index)"
                        x-show="items.length > minItems"
                        class="absolute top-2 end-2 p-1.5 rounded-lg transition-colors hover:bg-red-100 dark:hover:bg-red-900/20 text-red-600 dark:text-red-400"
                        title="{{ $removeButtonText }}">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>

                <!-- Drag Handle (if sortable) -->
                <div class="absolute top-2 start-2 p-1.5 text-gray-400 cursor-move"
                     title="اسحب لإعادة الترتيب">
                    <i data-lucide="grip-vertical" class="w-4 h-4"></i>
                </div>
            </div>
        </template>

        <!-- Empty State -->
        <div x-show="items.length === 0"
             class="text-center py-8 px-4 rounded-lg border-2 border-dashed"
             :class="darkMode ? 'border-gray-700 text-gray-500' : 'border-gray-300 text-gray-400'">
            <i data-lucide="inbox" class="w-12 h-12 mx-auto mb-2 opacity-50"></i>
            <p class="text-sm">{{ $emptyMessage }}</p>
        </div>
    </div>

    <!-- Add Button -->
    <button type="button"
            @click="addItem"
            x-show="items.length < maxItems"
            class="w-full px-4 py-2.5 rounded-lg font-semibold transition-all hover:shadow-lg flex items-center justify-center gap-2 bg-gradient-to-r from-[#0056b3] to-[#218838] text-white">
        <i data-lucide="plus-circle" class="w-5 h-5"></i>
        <span>{{ $addButtonText }}</span>
    </button>

    <!-- Items Count -->
    <div class="mt-2 text-xs text-center"
         :class="darkMode ? 'text-gray-500' : 'text-gray-400'">
        <span x-text="items.length"></span> {{ __('common.items') }}
        <template x-if="maxItems < 999">
            / <span x-text="maxItems"></span>
        </template>
    </div>
</div>

{{-- Repeatable Field script moved to _form-new.blade.php to prevent loading order issues --}}
@if(false)
@once
@push('scripts')
<script>
// Repeatable Field Component (DISABLED - Defined in main form)
if (typeof repeatableField === 'undefined') {
    window.repeatableField = function(config) {
        return {
            name: config.name,
            fields: config.fields,
            items: [],
            minItems: config.minItems || 0,
            maxItems: config.maxItems || 999,
            nextId: 1,

            init() {
                // Initialize with existing values or create minimum items
                if (config.values && config.values.length > 0) {
                    this.items = config.values.map((value, index) => ({
                        ...value,
                        id: this.nextId++
                    }));
                } else {
                    // Create minimum required items
                    for (let i = 0; i < this.minItems; i++) {
                        this.addItem();
                    }
                }

                this.$nextTick(() => {
                    if (typeof lucide !== 'undefined') {
                        lucide.createIcons();
                    }
                });
            },

            addItem() {
                if (this.items.length >= this.maxItems) {
                    return;
                }

                const newItem = { id: this.nextId++ };

                // Initialize all fields with empty values
                this.fields.forEach(field => {
                    newItem[field.name] = field.default || '';
                });

                this.items.push(newItem);

                this.$nextTick(() => {
                    if (typeof lucide !== 'undefined') {
                        lucide.createIcons();
                    }
                });
            },

            removeItem(index) {
                if (this.items.length <= this.minItems) {
                    return;
                }

                this.items.splice(index, 1);
            }
        };
    };
}
</script>
@endpush
@endonce
@endif
