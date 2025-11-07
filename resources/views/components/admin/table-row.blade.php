{{--
/**
 * Table Row Component
 *
 * Dynamic table row with checkboxes, data cells, badges, and action dropdowns.
 * Netflix-style hover effects and alternating row colors.
 *
 * @props
 * - columns: array (required) - Column definitions
 * - actions: array (optional) - Row actions ['edit', 'delete', 'view']
 * - hasBulkSelect: bool (optional) - Show checkbox
 * - editRoute: string (optional) - Edit route pattern
 * - deleteRoute: string (optional) - Delete route pattern
 * - viewRoute: string (optional) - View route pattern
 *
 * @example
 * <x-admin.table-row
 *     :columns="$columns"
 *     :actions="['edit', 'delete', 'view']"
 *     :has-bulk-select="true"
 *     edit-route="/admin/articles/{id}/edit"
 * />
 */
--}}

@props([
    'columns' => [],
    'actions' => [],
    'hasBulkSelect' => false,
    'editRoute' => null,
    'deleteRoute' => null,
    'viewRoute' => null
])

<tr x-data="{
        actionsOpen: false,
        deleteConfirm: false
    }"
    class="border-b transition-all duration-300 hover:scale-[1.01] hover:shadow-lg group"
    :class="[
        darkMode ? 'border-gray-800 hover:bg-gray-800/50' : 'border-gray-200 hover:bg-gray-50',
        index % 2 === 0 ? (darkMode ? 'bg-gray-900/30' : 'bg-white') : (darkMode ? 'bg-gray-900/50' : 'bg-gray-50/50'),
        selectedRows.includes(row.id) ? (darkMode ? 'bg-blue-900/20 border-blue-700' : 'bg-blue-50 border-blue-300') : ''
    ]">

    <!-- Checkbox Cell -->
    @if($hasBulkSelect)
        <td class="px-6 py-4">
            <div class="flex items-center justify-center">
                <input type="checkbox"
                       :checked="selectedRows.includes(row.id)"
                       @change="toggleRowSelection(row.id)"
                       class="w-5 h-5 rounded-md cursor-pointer transition-all
                              border-2 focus:ring-2 focus:ring-accent/50
                              checked:bg-gradient-to-br checked:from-[#0056b3] checked:to-[#218838]
                              checked:border-transparent"
                       :class="darkMode ? 'border-gray-600 bg-gray-700' : 'border-gray-300 bg-white'">
            </div>
        </td>
    @endif

    <!-- Data Cells -->
    @foreach($columns as $column)
        @php
            $key = $column['key'] ?? '';
            $type = $column['type'] ?? 'text';
            $badge = $column['badge'] ?? false;
            $badgeColors = $column['badgeColors'] ?? [
                'published' => 'from-green-500 to-emerald-600',
                'draft' => 'from-gray-500 to-gray-600',
                'pending' => 'from-yellow-500 to-orange-600',
                'archived' => 'from-red-500 to-rose-600',
            ];
            $truncate = $column['truncate'] ?? null;
            $align = $column['align'] ?? 'left';
        @endphp

        <td class="px-6 py-4 text-{{ $align }}"
            :class="darkMode ? 'text-gray-300' : 'text-gray-700'">

            @if($type === 'image')
                <!-- Image Type -->
                <template x-if="row.{{ $key }}">
                    <img :src="row.{{ $key }}"
                         :alt="row.title || 'Image'"
                         class="w-12 h-12 rounded-lg object-cover shadow-md">
                </template>
                <template x-if="!row.{{ $key }}">
                    <div class="w-12 h-12 rounded-lg flex items-center justify-center"
                         :class="darkMode ? 'bg-gray-800' : 'bg-gray-200'">
                        <i data-lucide="image" class="w-6 h-6" :class="darkMode ? 'text-gray-600' : 'text-gray-400'"></i>
                    </div>
                </template>

            @elseif($badge)
                <!-- Badge Type -->
                <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-bold text-white shadow-lg
                             bg-gradient-to-r transition-all hover:scale-105"
                      :class="{
                          @foreach($badgeColors as $status => $colors)
                              '{{ $colors }}': row.{{ $key }}?.toLowerCase() === '{{ $status }}',
                          @endforeach
                      }">
                    <span class="w-2 h-2 rounded-full bg-white/80 animate-pulse"></span>
                    <span x-text="row.{{ $key }}"></span>
                </span>

            @elseif($type === 'date')
                <!-- Date Type -->
                <span class="text-sm font-medium"
                      x-text="new Date(row.{{ $key }}).toLocaleDateString('en-US', {
                          year: 'numeric',
                          month: 'short',
                          day: 'numeric'
                      })">
                </span>

            @elseif($type === 'datetime')
                <!-- DateTime Type -->
                <div class="text-sm">
                    <div class="font-medium"
                         x-text="new Date(row.{{ $key }}).toLocaleDateString('en-US', {
                             month: 'short',
                             day: 'numeric',
                             year: 'numeric'
                         })">
                    </div>
                    <div class="text-xs" :class="darkMode ? 'text-gray-500' : 'text-gray-500'"
                         x-text="new Date(row.{{ $key }}).toLocaleTimeString('en-US', {
                             hour: '2-digit',
                             minute: '2-digit'
                         })">
                    </div>
                </div>

            @elseif($type === 'boolean')
                <!-- Boolean Type -->
                <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-bold shadow-md"
                      :class="row.{{ $key }} ?
                          'bg-gradient-to-r from-green-500 to-emerald-600 text-white' :
                          (darkMode ? 'bg-gray-700 text-gray-300' : 'bg-gray-200 text-gray-700')">
                    <i :data-lucide="row.{{ $key }} ? 'check-circle' : 'x-circle'" class="w-3 h-3"></i>
                    <span x-text="row.{{ $key }} ? 'Yes' : 'No'"></span>
                </span>

            @elseif($type === 'number')
                <!-- Number Type -->
                <span class="text-sm font-bold gradient-text" x-text="row.{{ $key }}?.toLocaleString()"></span>

            @else
                <!-- Text Type (Default) -->
                <span class="text-sm font-medium"
                      :class="darkMode ? 'text-white' : 'text-gray-900'"
                      @if($truncate)
                          :title="row.{{ $key }}"
                          x-text="row.{{ $key }}?.length > {{ $truncate }} ?
                              row.{{ $key }}.substring(0, {{ $truncate }}) + '...' :
                              row.{{ $key }}"
                      @else
                          x-text="row.{{ $key }}"
                      @endif>
                </span>
            @endif
        </td>
    @endforeach

    <!-- Actions Cell -->
    @if(!empty($actions))
        <td class="px-6 py-4 text-center">
            <div class="relative inline-block" @click.away="actionsOpen = false">

                <!-- Actions Trigger Button -->
                <button @click="actionsOpen = !actionsOpen"
                        class="p-2 rounded-lg transition-all hover:scale-110 group/btn"
                        :class="actionsOpen ?
                            'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-lg' :
                            (darkMode ? 'bg-gray-800 text-gray-300 hover:bg-gray-700' : 'bg-gray-100 text-gray-700 hover:bg-gray-200')">
                    <i data-lucide="more-vertical" class="w-5 h-5"></i>
                </button>

                <!-- Actions Dropdown Menu -->
                <div x-show="actionsOpen"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 scale-95 translate-y-2"
                     x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100 scale-100"
                     x-transition:leave-end="opacity-0 scale-95"
                     class="absolute right-0 mt-2 w-48 rounded-xl shadow-2xl glass z-50 overflow-hidden border"
                     :class="darkMode ? 'bg-gray-800/95 border-gray-700' : 'bg-white/95 border-gray-200'">

                    @if(in_array('view', $actions))
                        <a :href="'{{ $viewRoute }}'.replace('{id}', row.id)"
                           @click="actionsOpen = false"
                           class="flex items-center gap-3 px-4 py-3 transition-colors group/item"
                           :class="darkMode ? 'hover:bg-gray-700 text-white' : 'hover:bg-gray-50 text-gray-900'">
                            <i data-lucide="eye" class="w-5 h-5 text-blue-500 group-hover/item:scale-110 transition-transform"></i>
                            <span class="font-medium">View Details</span>
                        </a>
                    @endif

                    @if(in_array('edit', $actions))
                        <a :href="'{{ $editRoute }}'.replace('{id}', row.id)"
                           @click="actionsOpen = false"
                           class="flex items-center gap-3 px-4 py-3 transition-colors group/item"
                           :class="darkMode ? 'hover:bg-gray-700 text-white' : 'hover:bg-gray-50 text-gray-900'">
                            <i data-lucide="edit" class="w-5 h-5 text-accent group-hover/item:scale-110 transition-transform"></i>
                            <span class="font-medium">Edit</span>
                        </a>
                    @endif

                    @if(in_array('duplicate', $actions))
                        <button @click="duplicateRow(row.id); actionsOpen = false;"
                                class="w-full flex items-center gap-3 px-4 py-3 transition-colors group/item"
                                :class="darkMode ? 'hover:bg-gray-700 text-white' : 'hover:bg-gray-50 text-gray-900'">
                            <i data-lucide="copy" class="w-5 h-5 text-green-500 group-hover/item:scale-110 transition-transform"></i>
                            <span class="font-medium">Duplicate</span>
                        </button>
                    @endif

                    @if(in_array('delete', $actions))
                        <div class="border-t" :class="darkMode ? 'border-gray-700' : 'border-gray-200'"></div>

                        <button @click="deleteConfirm = true; actionsOpen = false;"
                                class="w-full flex items-center gap-3 px-4 py-3 transition-colors group/item"
                                :class="darkMode ? 'hover:bg-red-900/30 text-red-400 hover:text-red-300' : 'hover:bg-red-50 text-red-600 hover:text-red-700'">
                            <i data-lucide="trash-2" class="w-5 h-5 group-hover/item:scale-110 transition-transform"></i>
                            <span class="font-medium">Delete</span>
                        </button>
                    @endif
                </div>

                <!-- Delete Confirmation Modal -->
                <div x-show="deleteConfirm"
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     x-transition:leave="transition ease-in duration-200"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0"
                     class="fixed inset-0 bg-black/50 backdrop-blur-sm z-[100] flex items-center justify-center p-4"
                     @click.self="deleteConfirm = false">

                    <div class="rounded-2xl p-6 max-w-md w-full shadow-2xl transform transition-all"
                         :class="darkMode ? 'bg-gray-900 border border-gray-800' : 'bg-white'"
                         x-transition:enter="transition ease-out duration-300"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100">

                        <!-- Warning Icon -->
                        <div class="flex items-center justify-center w-16 h-16 rounded-full bg-red-100 mx-auto mb-4">
                            <i data-lucide="alert-triangle" class="w-8 h-8 text-red-600"></i>
                        </div>

                        <!-- Title & Message -->
                        <h3 class="text-xl font-bold text-center mb-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                            Confirm Deletion
                        </h3>
                        <p class="text-center mb-6" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                            Are you sure you want to delete this item? This action cannot be undone.
                        </p>

                        <!-- Action Buttons -->
                        <div class="flex gap-3">
                            <button @click="deleteConfirm = false"
                                    class="flex-1 px-4 py-2.5 rounded-xl font-semibold transition-all hover:scale-105"
                                    :class="darkMode ? 'bg-gray-800 text-white hover:bg-gray-700' : 'bg-gray-200 text-gray-900 hover:bg-gray-300'">
                                Cancel
                            </button>
                            <button @click="deleteRow(row.id); deleteConfirm = false;"
                                    class="flex-1 px-4 py-2.5 rounded-xl font-semibold transition-all hover:scale-105
                                           bg-red-600 text-white hover:bg-red-700 shadow-lg">
                                Delete
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </td>
    @endif
</tr>

<script>
// Add these methods to the parent dataTable component
function deleteRow(id) {
    this.loading = true;
    // Simulate API call
    setTimeout(() => {
        this.rawData = this.rawData.filter(row => row.id !== id);
        this.filterData();
        alert('Item deleted successfully!');
    }, 500);
}

function duplicateRow(id) {
    const row = this.rawData.find(r => r.id === id);
    if (row) {
        const newRow = { ...row, id: Date.now() };
        this.rawData.push(newRow);
        this.filterData();
        alert('Item duplicated successfully!');
    }
}
</script>
