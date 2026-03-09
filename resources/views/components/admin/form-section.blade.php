@props([
    'title' => '',
    'description' => '',
    'collapsible' => false,
    'collapsed' => false,
    'icon' => null,
])

<div class="form-section rounded-xl overflow-hidden glass"
     :class="darkMode ? 'glass-dark' : 'glass-light'"
     x-data="{
         isCollapsed: {{ $collapsed ? 'true' : 'false' }},
         isCollapsible: {{ $collapsible ? 'true' : 'false' }}
     }">

    @if($title)
        <div class="px-6 py-4 border-b cursor-pointer select-none"
             :class="[
                 darkMode ? 'border-gray-700 bg-gray-900/50' : 'border-gray-200 bg-gray-50/50',
                 isCollapsible ? 'hover:bg-gradient-to-r hover:from-[#0056b3]/5 hover:to-[#218838]/5' : ''
             ]"
             @if($collapsible)
                 @click="isCollapsed = !isCollapsed"
             @endif>

            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    @if($icon)
                        <div class="w-10 h-10 rounded-lg flex items-center justify-center bg-gradient-to-br from-[#0056b3] to-[#218838]">
                            <i data-lucide="{{ $icon }}" class="w-5 h-5 text-white"></i>
                        </div>
                    @endif

                    <div>
                        <h3 class="font-bold text-lg"
                            :class="darkMode ? 'text-white' : 'text-gray-900'">
                            {{ $title }}
                        </h3>

                        @if($description)
                            <p class="text-sm mt-1"
                               :class="darkMode ? 'text-gray-300' : 'text-gray-600'">
                                {{ $description }}
                            </p>
                        @endif
                    </div>
                </div>

                @if($collapsible)
                    <button type="button"
                            class="p-2 rounded-lg transition-transform duration-300"
                            :class="[
                                darkMode ? 'hover:bg-gray-800 text-gray-300' : 'hover:bg-gray-100 text-gray-600',
                                isCollapsed ? '' : 'rotate-180'
                            ]">
                        <i data-lucide="chevron-down" class="w-5 h-5"></i>
                    </button>
                @endif
            </div>
        </div>
    @endif

    <div class="p-6 space-y-6"
         x-show="!isCollapsed"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 -translate-y-4"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 -translate-y-4">

        {{ $slot }}
    </div>

</div>
