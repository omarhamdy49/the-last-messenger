@extends('layouts.admin')

@section('page-header')
<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
    <div>
        <h1 class="text-3xl font-bold gradient-text flex items-center gap-3">
            <div class="p-3 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] shadow-lg">
                <i data-lucide="tags" class="w-7 h-7 text-white"></i>
            </div>
            {{ __('admin.seerah_event_types') }}
        </h1>
        <p class="mt-2 text-sm" :class="darkMode ? 'text-gray-300' : 'text-gray-600'">
            {{ __('admin.seerah_event_types_subtitle') }}
        </p>
    </div>

    <div class="flex flex-wrap items-center gap-3">
        <a href="{{ route('admin.seerah-event-types.create') }}"
           class="px-6 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 flex items-center gap-2 shadow-lg
                  bg-gradient-to-r from-[#0056b3] to-[#218838] text-white">
            <i data-lucide="plus" class="w-5 h-5"></i>
            <span>{{ __('admin.add_event_type') }}</span>
        </a>
    </div>
</div>
@endsection

@section('content')
<div x-data="seerahTypeIndex()">

    @if(session('error'))
    <div class="mb-6 p-4 rounded-xl border bg-red-50 border-red-200 text-red-800 dark:bg-red-900/30 dark:border-red-800 dark:text-red-300 flex items-center gap-2">
        <i data-lucide="alert-circle" class="w-5 h-5"></i>
        <span>{{ session('error') }}</span>
    </div>
    @endif

    <!-- Stats -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <x-admin.stats-card icon="tags" title="{{ __('admin.total') }}" :value="$stats['total'] ?? 0" :gradient="true" />
        <x-admin.stats-card icon="check-circle" title="{{ __('admin.active') }}" :value="$stats['active'] ?? 0" delay="delay-100" />
        <x-admin.stats-card icon="circle-slash" title="{{ __('admin.inactive') }}" :value="$stats['inactive'] ?? 0" delay="delay-200" />
    </div>

    <!-- Filters -->
    <div class="mb-6 p-6 rounded-2xl border" :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
        <form method="GET" action="{{ route('admin.seerah-event-types.index') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="md:col-span-2">
                <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ __('admin.search') }}</label>
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="{{ __('admin.search_placeholder') }}"
                       class="w-full px-4 py-2.5 text-base sm:text-sm rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                       :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
            </div>
            <div class="flex items-end gap-3">
                <select name="status"
                        class="w-full px-4 py-2.5 text-base sm:text-sm rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                        :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                    <option value="">{{ __('admin.all') }}</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>{{ __('admin.active') }}</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>{{ __('admin.inactive') }}</option>
                </select>
                <button type="submit" class="px-5 py-2.5 rounded-xl font-semibold bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-lg whitespace-nowrap">
                    {{ __('admin.filter') }}
                </button>
            </div>
        </form>
    </div>

    <!-- Bulk toolbar -->
    <div x-show="selected.length > 0" x-cloak x-transition
         class="mb-4 flex flex-wrap items-center gap-3 px-4 py-3 rounded-xl border"
         :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
        <span class="text-sm font-medium" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
            <span x-text="selected.length"></span> {{ __('admin.selected') }}
        </span>
        <button type="button" @click="bulkDelete()"
                class="px-3 py-1.5 rounded-lg text-sm font-medium bg-red-500 text-white hover:bg-red-600 flex items-center gap-1">
            <i data-lucide="trash-2" class="w-4 h-4"></i> {{ __('admin.delete_selected') }}
        </button>
        <button type="button" @click="selected = []" class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">
            {{ __('admin.clear') }}
        </button>
    </div>

    {{-- Table above `md`, card stack below it — see components/admin/list-card. --}}
    <div class="hidden md:block rounded-2xl border overflow-hidden" :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                        <th class="px-4 py-4 text-start">
                            <input type="checkbox" @change="toggleAll($event)"
                                   class="w-4 h-4 rounded text-accent border-gray-300 focus:ring-accent">
                        </th>
                        <th class="px-6 py-4 text-start text-sm font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ __('admin.name') }}</th>
                        <th class="px-6 py-4 text-start text-sm font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ __('admin.slug') }}</th>
                        <th class="px-6 py-4 text-start text-sm font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ __('admin.color') }}</th>
                        <th class="px-6 py-4 text-start text-sm font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ __('admin.events') }}</th>
                        <th class="px-6 py-4 text-start text-sm font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ __('admin.status') }}</th>
                        <th class="px-6 py-4 text-start text-sm font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ __('admin.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y" :class="darkMode ? 'divide-gray-800' : 'divide-gray-100'">
                    @forelse($types as $type)
                    <tr class="transition-colors" :class="darkMode ? 'hover:bg-gray-800/50' : 'hover:bg-gray-50'">
                        <td class="px-4 py-4">
                            <input type="checkbox" value="{{ $type->id }}" x-model.number="selected"
                                   class="w-4 h-4 rounded text-accent border-gray-300 focus:ring-accent">
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg bg-gradient-to-br {{ $type->gradient ?: 'from-[#0056b3] to-[#218838]' }} flex items-center justify-center flex-shrink-0">
                                    <i data-lucide="{{ $type->icon ?: 'tag' }}" class="w-5 h-5 text-white"></i>
                                </div>
                                <div class="min-w-0">
                                    <h4 class="font-semibold truncate" :class="darkMode ? 'text-white' : 'text-gray-900'">
                                        {{ $type->getTranslation('name', 'ar') }}
                                    </h4>
                                    <p class="text-xs truncate" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">
                                        {{ $type->getTranslation('name', 'en') }}
                                    </p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                            <code class="px-2 py-1 rounded text-xs" :class="darkMode ? 'bg-gray-800' : 'bg-gray-100'">{{ $type->slug }}</code>
                        </td>
                        <td class="px-6 py-4 text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                            {{ $type->color ?: '—' }}
                        </td>
                        <td class="px-6 py-4 text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                            {{ $type->events_count ?? 0 }}
                        </td>
                        <td class="px-6 py-4">
                            @if($type->is_active)
                            <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                                <i data-lucide="check-circle" class="w-3 h-3"></i> {{ __('admin.active') }}
                            </span>
                            @else
                            <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-400">
                                <i data-lucide="circle-slash" class="w-3 h-3"></i> {{ __('admin.inactive') }}
                            </span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex flex-wrap items-center gap-2">
                                <form action="{{ route('admin.seerah-event-types.toggle-active', $type->id) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="p-2 rounded-lg transition-colors"
                                            :class="darkMode ? 'hover:bg-gray-700 text-gray-400 hover:text-white' : 'hover:bg-gray-100 text-gray-600 hover:text-gray-900'"
                                            title="{{ $type->is_active ? __('admin.deactivate') : __('admin.activate') }}">
                                        <i data-lucide="{{ $type->is_active ? 'toggle-right' : 'toggle-left' }}" class="w-4 h-4"></i>
                                    </button>
                                </form>
                                <a href="{{ route('admin.seerah-event-types.show', $type->id) }}"
                                   class="p-2 rounded-lg transition-colors"
                                   :class="darkMode ? 'hover:bg-gray-700 text-gray-400 hover:text-white' : 'hover:bg-gray-100 text-gray-600 hover:text-gray-900'"
                                   title="{{ __('admin.view') }}">
                                    <i data-lucide="eye" class="w-4 h-4"></i>
                                </a>
                                <a href="{{ route('admin.seerah-event-types.edit', $type->id) }}"
                                   class="p-2 rounded-lg transition-colors"
                                   :class="darkMode ? 'hover:bg-gray-700 text-gray-400 hover:text-white' : 'hover:bg-gray-100 text-gray-600 hover:text-gray-900'"
                                   title="{{ __('admin.edit') }}">
                                    <i data-lucide="edit-2" class="w-4 h-4"></i>
                                </a>
                                <form action="{{ route('admin.seerah-event-types.destroy', $type->id) }}" method="POST" class="inline"
                                      @submit.prevent="$dispatch('show-confirm', { title: '{{ __('admin.confirm_delete') }}', message: '{{ __('admin.delete_warning') }}', type: 'danger', onConfirm: () => $el.submit() })">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-2 rounded-lg transition-colors text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20" title="{{ __('admin.delete') }}">
                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center">
                            <i data-lucide="tags" class="w-16 h-16 mx-auto mb-4" :class="darkMode ? 'text-gray-600' : 'text-gray-400'"></i>
                            <p class="text-lg font-medium mb-4" :class="darkMode ? 'text-gray-300' : 'text-gray-600'">
                                {{ __('admin.no_event_types') }}
                            </p>
                            <a href="{{ route('admin.seerah-event-types.create') }}"
                               class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] text-white font-semibold hover:scale-105 transition-all">
                                <i data-lucide="plus" class="w-5 h-5"></i>
                                {{ __('admin.add_event_type') }}
                            </a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Mobile card stack (< md) -->
    <div class="md:hidden space-y-3">
        @forelse($types as $type)
            @php $eventCount = $type->events_count ?? 0; @endphp
            <x-admin.list-card
                :value="$type->id"
                :icon="$type->icon ?: 'tag'"
                :gradient="$type->gradient ?: 'from-[#0056b3] to-[#218838]'"
                :title="$type->getTranslation('name', 'ar')"
                :subtitle="$type->slug">

                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold tabular-nums"
                      :class="darkMode ? 'bg-gray-800 text-gray-300' : 'bg-gray-100 text-gray-700'">
                    <i data-lucide="calendar-days" class="w-3 h-3"></i> {{ $eventCount }}
                </span>

                @if($type->color)
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold"
                          :class="darkMode ? 'bg-gray-800 text-gray-300' : 'bg-gray-100 text-gray-700'">
                        <span class="w-3 h-3 rounded-full border border-black/10" style="background: {{ $type->color }}"></span>
                        {{ $type->color }}
                    </span>
                @endif

                @if($type->is_active)
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                        <i data-lucide="check-circle" class="w-3 h-3"></i> {{ __('admin.active') }}
                    </span>
                @else
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-400">
                        <i data-lucide="circle-slash" class="w-3 h-3"></i> {{ __('admin.inactive') }}
                    </span>
                @endif

                <x-slot:actions>
                    <form action="{{ route('admin.seerah-event-types.toggle-active', $type->id) }}" method="POST" class="inline">@csrf
                        <button type="submit" class="p-2.5 rounded-lg transition-colors" :class="darkMode ? 'hover:bg-gray-700 text-gray-400' : 'hover:bg-gray-100 text-gray-600'"
                                title="{{ $type->is_active ? __('admin.deactivate') : __('admin.activate') }}">
                            <i data-lucide="{{ $type->is_active ? 'toggle-right' : 'toggle-left' }}" class="w-4 h-4"></i>
                        </button>
                    </form>
                    <a href="{{ route('admin.seerah-event-types.show', $type->id) }}" class="p-2.5 rounded-lg transition-colors" :class="darkMode ? 'hover:bg-gray-700 text-gray-400' : 'hover:bg-gray-100 text-gray-600'" title="{{ __('admin.view') }}"><i data-lucide="eye" class="w-4 h-4"></i></a>
                    <a href="{{ route('admin.seerah-event-types.edit', $type->id) }}" class="p-2.5 rounded-lg transition-colors" :class="darkMode ? 'hover:bg-gray-700 text-gray-400' : 'hover:bg-gray-100 text-gray-600'" title="{{ __('admin.edit') }}"><i data-lucide="edit-2" class="w-4 h-4"></i></a>
                    <form action="{{ route('admin.seerah-event-types.destroy', $type->id) }}" method="POST" class="inline"
                          @submit.prevent="$dispatch('show-confirm', { title: '{{ __('admin.confirm_delete') }}', message: '{{ __('admin.delete_warning') }}', type: 'danger', onConfirm: () => $el.submit() })">@csrf @method('DELETE')
                        <button type="submit" class="p-2.5 rounded-lg transition-colors text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20" title="{{ __('admin.delete') }}"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
                    </form>
                </x-slot:actions>
            </x-admin.list-card>
        @empty
            <div class="p-8 rounded-2xl border text-center" :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
                <i data-lucide="tags" class="w-12 h-12 mx-auto mb-3" :class="darkMode ? 'text-gray-600' : 'text-gray-400'"></i>
                <p class="text-base font-medium mb-4" :class="darkMode ? 'text-gray-300' : 'text-gray-600'">{{ __('admin.no_event_types') }}</p>
                <a href="{{ route('admin.seerah-event-types.create') }}" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] text-white font-semibold"><i data-lucide="plus" class="w-5 h-5"></i> {{ __('admin.add_event_type') }}</a>
            </div>
        @endforelse
    </div>

    @if($types->hasPages())
    <div class="mt-8">{{ $types->withQueryString()->links() }}</div>
    @endif
</div>
@endsection

@push('scripts')
<script>
    function seerahTypeIndex() {
        return {
            selected: [],
            // x-model bound rows: set `selected` and let Alpine mirror it. Reading
            // <tbody> here would miss the mobile card stack rendered outside it.
            pageIds: {{ Js::from(collect($types)->pluck('id')->all()) }},
            toggleAll(e) {
                this.selected = e.target.checked ? [...this.pageIds] : [];
            },
            bulkDelete() {
                if (this.selected.length === 0) return;
                this.$dispatch('show-confirm', {
                    title: '{{ __('admin.confirm_delete') }}',
                    message: '{{ __('admin.delete_warning') }}',
                    type: 'danger',
                    onConfirm: () => this.postBulk('{{ route('admin.seerah-event-types.bulk-delete') }}'),
                });
            },
            postBulk(url) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = url;
                form.innerHTML = '<input type="hidden" name="_token" value="{{ csrf_token() }}">';
                this.selected.forEach(id => {
                    const i = document.createElement('input');
                    i.type = 'hidden'; i.name = 'ids[]'; i.value = id;
                    form.appendChild(i);
                });
                document.body.appendChild(form);
                form.submit();
            },
        };
    }
    document.addEventListener('DOMContentLoaded', () => lucide.createIcons());
</script>
@endpush
