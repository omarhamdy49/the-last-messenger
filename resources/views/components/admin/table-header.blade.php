{{--
/**
 * Table Header Component
 *
 * Sortable table header with RTL support and gradient active states.
 *
 * @props
 * - columns: array (required) - Column definitions
 * - hasBulkSelect: bool (optional) - Show select all checkbox
 * - hasActions: bool (optional) - Show actions column
 *
 * @example
 * <x-admin.table-header
 *     :columns="$columns"
 *     :has-bulk-select="true"
 *     :has-actions="true"
 * />
 */
--}}

@props([
    'columns' => [],
    'hasBulkSelect' => false,
    'hasActions' => false
])

<thead class="glass border-b"
       :class="darkMode ? 'bg-gray-800/80 border-gray-700' : 'bg-gray-100/80 border-gray-300'">
    <tr>
        <!-- Bulk Select Checkbox -->
        @if($hasBulkSelect)
            <th class="px-6 py-4 text-left" style="width: 60px;">
                <div class="flex items-center justify-center">
                    <input type="checkbox"
                           x-model="selectAll"
                           @change="toggleSelectAll"
                           class="w-5 h-5 rounded-md cursor-pointer transition-all
                                  border-2 focus:ring-2 focus:ring-accent/50
                                  checked:bg-gradient-to-br checked:from-[#0056b3] checked:to-[#218838]
                                  checked:border-transparent"
                           :class="darkMode ? 'border-gray-600 bg-gray-700' : 'border-gray-300 bg-white'">
                </div>
            </th>
        @endif

        <!-- Column Headers -->
        @foreach($columns as $column)
            @php
                $key = $column['key'] ?? '';
                $label = $column['label'] ?? ucfirst($key);
                $sortable = $column['sortable'] ?? false;
                $width = $column['width'] ?? 'auto';
                $align = $column['align'] ?? 'left';
            @endphp

            <th class="px-6 py-4 text-{{ $align }} {{ $sortable ? 'cursor-pointer select-none group' : '' }}"
                @if($width !== 'auto') style="width: {{ $width }};" @endif
                @if($sortable)
                    @click="sortBy('{{ $key }}')"
                @endif>

                <div class="flex items-center gap-2 {{ $align === 'center' ? 'justify-center' : ($align === 'right' ? 'justify-end' : 'justify-start') }}">
                    <!-- Label -->
                    <span class="font-bold text-sm uppercase tracking-wider transition-colors"
                          :class="sortColumn === '{{ $key }}' ?
                              'text-transparent bg-clip-text bg-gradient-to-r from-[#0056b3] to-[#218838]' :
                              (darkMode ? 'text-gray-300 group-hover:text-white' : 'text-gray-700 group-hover:text-gray-900')">
                        {{ $label }}
                    </span>

                    <!-- Sort Icons -->
                    @if($sortable)
                        <div class="flex flex-col gap-0.5">
                            <!-- Sort Ascending Icon -->
                            <i data-lucide="chevron-up"
                               class="w-3 h-3 transition-all"
                               :class="sortColumn === '{{ $key }}' && sortDirection === 'asc' ?
                                   'text-[#0056b3] opacity-100' :
                                   (darkMode ? 'text-gray-600 opacity-50 group-hover:opacity-100' : 'text-gray-400 opacity-50 group-hover:opacity-100')">
                            </i>

                            <!-- Sort Descending Icon -->
                            <i data-lucide="chevron-down"
                               class="w-3 h-3 transition-all -mt-1"
                               :class="sortColumn === '{{ $key }}' && sortDirection === 'desc' ?
                                   'text-[#218838] opacity-100' :
                                   (darkMode ? 'text-gray-600 opacity-50 group-hover:opacity-100' : 'text-gray-400 opacity-50 group-hover:opacity-100')">
                            </i>
                        </div>
                    @endif
                </div>
            </th>
        @endforeach

        <!-- Actions Column -->
        @if($hasActions)
            <th class="px-6 py-4 text-center" style="width: 100px;">
                <span class="font-bold text-sm uppercase tracking-wider"
                      :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                    Actions
                </span>
            </th>
        @endif
    </tr>
</thead>
