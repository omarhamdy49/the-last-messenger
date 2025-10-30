{{-- Data Table Component --}}
@props(['columns', 'rows', 'actions' => [], 'searchable' => true, 'sortable' => true])
<div x-data="dataTable()" {{ $attributes->merge(['class' => 'rounded-2xl shadow-xl overflow-hidden']) }} :class="darkMode ? 'bg-gray-900 border border-gray-800' : 'bg-white'">
    @if($searchable)
    <div class="p-4 border-b" :class="darkMode ? 'border-gray-800' : 'border-gray-200'">
        <div class="flex items-center gap-4">
            <div class="flex-1 relative">
                <input type="search" x-model="searchQuery" placeholder="{{ __('admin.search') }}" class="w-full px-4 py-2 pl-10 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#0056b3]" :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-gray-50 border-gray-200'">
                <i class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-500" data-lucide="search"></i>
            </div>
            @if(count($actions) > 0)
            <button x-show="selected.length > 0" class="px-4 py-2 bg-gradient-to-r from-[#0056b3] to-[#218838] text-white rounded-lg font-semibold">
                {{ __('admin.bulk_actions') }} (<span x-text="selected.length"></span>)
            </button>
            @endif
        </div>
    </div>
    @endif
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead :class="darkMode ? 'bg-gray-800' : 'bg-gray-50'">
                <tr>
                    @if(count($actions) > 0)
                    <th class="px-4 py-3 text-left"><input type="checkbox" @change="toggleAll" class="rounded"></th>
                    @endif
                    @foreach($columns as $key => $column)
                    <th class="px-4 py-3 text-left text-sm font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                        @if($sortable)<button @click="sort('{{ $key }}')" class="flex items-center gap-2">{{ $column }} <i class="w-4 h-4" data-lucide="chevrons-up-down"></i></button>@else{{ $column }}@endif
                    </th>
                    @endforeach
                    <th class="px-4 py-3 text-right text-sm font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ __('admin.actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y" :class="darkMode ? 'divide-gray-800' : 'divide-gray-200'">
                @foreach($rows as $row)
                <tr :class="darkMode ? 'hover:bg-gray-800' : 'hover:bg-gray-50'" class="transition">
                    @if(count($actions) > 0)
                    <td class="px-4 py-3"><input type="checkbox" :value="{{ $row->id }}" x-model="selected" class="rounded"></td>
                    @endif
                    @foreach($columns as $key => $column)
                    <td class="px-4 py-3 text-sm" :class="darkMode ? 'text-gray-300' : 'text-gray-900'">{{ $row->$key }}</td>
                    @endforeach
                    <td class="px-4 py-3 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <a href="{{ route('admin.' . strtolower(class_basename($row)) . '.edit', $row->id) }}" class="p-2 rounded-lg hover:bg-white/10 transition"><i class="w-4 h-4" data-lucide="edit"></i></a>
                            <button @click="deleteItem({{ $row->id }})" class="p-2 rounded-lg hover:bg-red-500/10 text-red-500 transition"><i class="w-4 h-4" data-lucide="trash-2"></i></button>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@push('scripts')
<script>
function dataTable() {
    return {
        searchQuery: '',
        selected: [],
        sortKey: null,
        sortOrder: 'asc',
        toggleAll(event) {
            this.selected = event.target.checked ? @json($rows->pluck('id')) : [];
        },
        sort(key) {
            this.sortKey = key;
            this.sortOrder = this.sortOrder === 'asc' ? 'desc' : 'asc';
        },
        deleteItem(id) {
            if (confirm('{{ __('admin.confirm_delete') }}')) {
                // Delete logic
            }
        }
    };
}
</script>
@endpush
