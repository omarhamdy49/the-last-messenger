@extends('layouts.admin')

@section('page-header')
<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
    <div>
        <h1 class="text-3xl font-bold gradient-text flex items-center gap-3">
            <div class="p-3 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] shadow-lg">
                <i data-lucide="milestone" class="w-7 h-7 text-white"></i>
            </div>
            {{ __('admin.seerah_milestones') }}
        </h1>
        <p class="mt-2 text-sm" :class="darkMode ? 'text-gray-300' : 'text-gray-600'">
            {{ __('admin.seerah_milestones_subtitle') }}
        </p>
    </div>
    <div class="flex flex-wrap items-center gap-3">
        <a href="{{ route('admin.seerah-milestones.create') }}"
           class="px-6 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 flex items-center gap-2 shadow-lg bg-gradient-to-r from-[#0056b3] to-[#218838] text-white">
            <i data-lucide="plus" class="w-5 h-5"></i>
            <span>{{ __('admin.add_milestone') }}</span>
        </a>
    </div>
</div>
@endsection

@section('content')
<div x-data="seerahMilestoneIndex()">

    @if(session('error'))
    <div class="mb-6 p-4 rounded-xl border bg-red-50 border-red-200 text-red-800 dark:bg-red-900/30 dark:border-red-800 dark:text-red-300 flex items-center gap-2">
        <i data-lucide="alert-circle" class="w-5 h-5"></i>
        <span>{{ session('error') }}</span>
    </div>
    @endif

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <x-admin.stats-card icon="milestone" title="{{ __('admin.total') }}" :value="$stats['total'] ?? 0" :gradient="true" />
        <x-admin.stats-card icon="check-circle" title="{{ __('admin.active') }}" :value="$stats['active'] ?? 0" delay="delay-100" />
        <x-admin.stats-card icon="circle-slash" title="{{ __('admin.inactive') }}" :value="$stats['inactive'] ?? 0" delay="delay-200" />
    </div>

    <div class="mb-6 p-6 rounded-2xl border" :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
        <form method="GET" action="{{ route('admin.seerah-milestones.index') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="md:col-span-2">
                <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ __('admin.search') }}</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('admin.search_placeholder') }}"
                       class="w-full px-4 py-2.5 text-base sm:text-sm rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                       :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
            </div>
            <div class="flex items-end gap-3">
                <select name="status" class="w-full px-4 py-2.5 text-base sm:text-sm rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                        :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                    <option value="">{{ __('admin.all') }}</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>{{ __('admin.active') }}</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>{{ __('admin.inactive') }}</option>
                </select>
                <button type="submit" class="px-5 py-2.5 rounded-xl font-semibold bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-lg whitespace-nowrap">{{ __('admin.filter') }}</button>
            </div>
        </form>
    </div>

    <div x-show="selected.length > 0" x-cloak x-transition class="mb-4 flex flex-wrap items-center gap-3 px-4 py-3 rounded-xl border"
         :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
        <span class="text-sm font-medium" :class="darkMode ? 'text-gray-300' : 'text-gray-700'"><span x-text="selected.length"></span> {{ __('admin.selected') }}</span>
        <button type="button" @click="bulkDelete()" class="px-3 py-1.5 rounded-lg text-sm font-medium bg-red-500 text-white hover:bg-red-600 flex items-center gap-1">
            <i data-lucide="trash-2" class="w-4 h-4"></i> {{ __('admin.delete_selected') }}
        </button>
        <button type="button" @click="selected = []" class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">{{ __('admin.clear') }}</button>
    </div>

    {{-- Table above `md`, card stack below it — see components/admin/list-card. --}}
    <div class="hidden md:block rounded-2xl border overflow-hidden" :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                        <th class="px-4 py-4 text-start"><input type="checkbox" @change="toggleAll($event)" class="w-4 h-4 rounded text-accent border-gray-300 focus:ring-accent"></th>
                        <th class="px-4 py-4 text-start text-sm font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">#</th>
                        <th class="px-6 py-4 text-start text-sm font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ __('admin.title') }}</th>
                        <th class="px-6 py-4 text-start text-sm font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ __('admin.date_range') }}</th>
                        <th class="px-6 py-4 text-start text-sm font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ __('admin.events') }}</th>
                        <th class="px-6 py-4 text-start text-sm font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ __('admin.status') }}</th>
                        <th class="px-6 py-4 text-start text-sm font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ __('admin.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y" :class="darkMode ? 'divide-gray-800' : 'divide-gray-100'">
                    @forelse($milestones as $milestone)
                    <tr class="transition-colors" :class="darkMode ? 'hover:bg-gray-800/50' : 'hover:bg-gray-50'">
                        <td class="px-4 py-4"><input type="checkbox" value="{{ $milestone->id }}" x-model.number="selected" class="w-4 h-4 rounded text-accent border-gray-300 focus:ring-accent"></td>
                        <td class="px-4 py-4 text-sm font-bold" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">{{ $milestone->order }}</td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg bg-gradient-to-br {{ $milestone->gradient ?: 'from-[#0056b3] to-[#218838]' }} flex items-center justify-center flex-shrink-0">
                                    <i data-lucide="{{ $milestone->icon ?: 'milestone' }}" class="w-5 h-5 text-white"></i>
                                </div>
                                <div class="min-w-0 max-w-xs">
                                    <h4 class="font-semibold truncate" :class="darkMode ? 'text-white' : 'text-gray-900'"><a href="{{ route('admin.seerah-milestones.show', $milestone->id) }}" class="transition-colors hover:text-accent hover:underline underline-offset-4 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent rounded">{{ $milestone->getTranslation('title', 'ar') }}</a></h4>
                                    <p class="text-xs truncate" :class="darkMode ? 'text-gray-500' : 'text-gray-400'"><code class="px-1.5 py-0.5 rounded text-[11px]" :class="darkMode ? 'bg-gray-800' : 'bg-gray-100'">{{ $milestone->slug }}</code></p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                            <div class="whitespace-nowrap">{{ $milestone->hijriRangeLabel() ?: '—' }}</div>
                            <div class="text-xs" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">{{ $milestone->gregorianRangeLabel() }}</div>
                        </td>
                        <td class="px-6 py-4 text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">{{ $milestone->events_count ?? 0 }}</td>
                        <td class="px-6 py-4">
                            @if($milestone->is_active)
                            <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400"><i data-lucide="check-circle" class="w-3 h-3"></i> {{ __('admin.active') }}</span>
                            @else
                            <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-400"><i data-lucide="circle-slash" class="w-3 h-3"></i> {{ __('admin.inactive') }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex flex-wrap items-center gap-2">
                                <form action="{{ route('admin.seerah-milestones.toggle-active', $milestone->id) }}" method="POST" class="inline">@csrf
                                    <button type="submit" class="p-2 rounded-lg transition-colors" :class="darkMode ? 'hover:bg-gray-700 text-gray-400 hover:text-white' : 'hover:bg-gray-100 text-gray-600 hover:text-gray-900'" title="{{ $milestone->is_active ? __('admin.deactivate') : __('admin.activate') }}">
                                        <i data-lucide="{{ $milestone->is_active ? 'toggle-right' : 'toggle-left' }}" class="w-4 h-4"></i>
                                    </button>
                                </form>
                                <a href="{{ route('admin.seerah-milestones.show', $milestone->id) }}" class="p-2 rounded-lg transition-colors" :class="darkMode ? 'hover:bg-gray-700 text-gray-400 hover:text-white' : 'hover:bg-gray-100 text-gray-600 hover:text-gray-900'" title="{{ __('admin.view') }}"><i data-lucide="eye" class="w-4 h-4"></i></a>
                                <a href="{{ route('admin.seerah-milestones.edit', $milestone->id) }}" class="p-2 rounded-lg transition-colors" :class="darkMode ? 'hover:bg-gray-700 text-gray-400 hover:text-white' : 'hover:bg-gray-100 text-gray-600 hover:text-gray-900'" title="{{ __('admin.edit') }}"><i data-lucide="edit-2" class="w-4 h-4"></i></a>
                                <form action="{{ route('admin.seerah-milestones.destroy', $milestone->id) }}" method="POST" class="inline"
                                      @submit.prevent="$dispatch('show-confirm', { title: '{{ __('admin.confirm_delete') }}', message: '{{ __('admin.delete_warning') }}', type: 'danger', onConfirm: () => $el.submit() })">@csrf @method('DELETE')
                                    <button type="submit" class="p-2 rounded-lg transition-colors text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20" title="{{ __('admin.delete') }}"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center">
                            <i data-lucide="milestone" class="w-16 h-16 mx-auto mb-4" :class="darkMode ? 'text-gray-600' : 'text-gray-400'"></i>
                            <p class="text-lg font-medium mb-4" :class="darkMode ? 'text-gray-300' : 'text-gray-600'">{{ __('admin.no_milestones') }}</p>
                            <a href="{{ route('admin.seerah-milestones.create') }}" class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] text-white font-semibold hover:scale-105 transition-all"><i data-lucide="plus" class="w-5 h-5"></i> {{ __('admin.add_milestone') }}</a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Mobile card stack (< md) -->
    <div class="md:hidden space-y-3">
        @forelse($milestones as $milestone)
            @php $eventCount = $milestone->events_count ?? 0; @endphp
            <x-admin.list-card
                :value="$milestone->id"
                :icon="$milestone->icon ?: 'milestone'"
                :gradient="$milestone->gradient ?: 'from-[#0056b3] to-[#218838]'"
                :title="$milestone->getTranslation('title', 'ar')"
                :href="route('admin.seerah-milestones.show', $milestone->id)"
                :subtitle="$milestone->slug">

                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold"
                      :class="darkMode ? 'bg-gray-800 text-gray-300' : 'bg-gray-100 text-gray-700'">
                    <i data-lucide="hash" class="w-3 h-3"></i> {{ $milestone->order }}
                </span>

                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold"
                      :class="darkMode ? 'bg-gray-800 text-gray-300' : 'bg-gray-100 text-gray-700'">
                    <i data-lucide="calendar" class="w-3 h-3"></i>
                    {{ $milestone->hijriRangeLabel() ?: '—' }}
                </span>

                {{-- A milestone with no events renders an empty chapter on /seerah. --}}
                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold tabular-nums
                    {{ $eventCount === 0
                        ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'
                        : 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300' }}"
                      title="{{ $eventCount === 0 ? 'لا توجد أحداث — الحقبة ستظهر فارغة' : $eventCount.' حدث' }}">
                    <i data-lucide="calendar-days" class="w-3 h-3"></i> {{ $eventCount }}
                </span>

                @if($milestone->is_active)
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                        <i data-lucide="check-circle" class="w-3 h-3"></i> {{ __('admin.active') }}
                    </span>
                @else
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-400">
                        <i data-lucide="circle-slash" class="w-3 h-3"></i> {{ __('admin.inactive') }}
                    </span>
                @endif

                <x-slot:actions>
                    <form action="{{ route('admin.seerah-milestones.toggle-active', $milestone->id) }}" method="POST" class="inline">@csrf
                        <button type="submit" class="p-2.5 rounded-lg transition-colors" :class="darkMode ? 'hover:bg-gray-700 text-gray-400' : 'hover:bg-gray-100 text-gray-600'"
                                title="{{ $milestone->is_active ? __('admin.deactivate') : __('admin.activate') }}">
                            <i data-lucide="{{ $milestone->is_active ? 'toggle-right' : 'toggle-left' }}" class="w-4 h-4"></i>
                        </button>
                    </form>
                    <a href="{{ route('admin.seerah-milestones.show', $milestone->id) }}" class="p-2.5 rounded-lg transition-colors" :class="darkMode ? 'hover:bg-gray-700 text-gray-400' : 'hover:bg-gray-100 text-gray-600'" title="{{ __('admin.view') }}"><i data-lucide="eye" class="w-4 h-4"></i></a>
                    <a href="{{ route('admin.seerah-milestones.edit', $milestone->id) }}" class="p-2.5 rounded-lg transition-colors" :class="darkMode ? 'hover:bg-gray-700 text-gray-400' : 'hover:bg-gray-100 text-gray-600'" title="{{ __('admin.edit') }}"><i data-lucide="edit-2" class="w-4 h-4"></i></a>
                    <form action="{{ route('admin.seerah-milestones.destroy', $milestone->id) }}" method="POST" class="inline"
                          @submit.prevent="$dispatch('show-confirm', { title: '{{ __('admin.confirm_delete') }}', message: '{{ __('admin.delete_warning') }}', type: 'danger', onConfirm: () => $el.submit() })">@csrf @method('DELETE')
                        <button type="submit" class="p-2.5 rounded-lg transition-colors text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20" title="{{ __('admin.delete') }}"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
                    </form>
                </x-slot:actions>
            </x-admin.list-card>
        @empty
            <div class="p-8 rounded-2xl border text-center" :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
                <i data-lucide="milestone" class="w-12 h-12 mx-auto mb-3" :class="darkMode ? 'text-gray-600' : 'text-gray-400'"></i>
                <p class="text-base font-medium mb-4" :class="darkMode ? 'text-gray-300' : 'text-gray-600'">{{ __('admin.no_milestones') }}</p>
                <a href="{{ route('admin.seerah-milestones.create') }}" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] text-white font-semibold"><i data-lucide="plus" class="w-5 h-5"></i> {{ __('admin.add_milestone') }}</a>
            </div>
        @endforelse
    </div>

    @if($milestones->hasPages())
    <div class="mt-8">{{ $milestones->withQueryString()->links() }}</div>
    @endif
</div>
@endsection

@push('scripts')
<script>
    function seerahMilestoneIndex() {
        return {
            selected: [],
            // x-model bound rows: set `selected` and let Alpine mirror it. Reading
            // <tbody> here would miss the mobile card stack rendered outside it.
            pageIds: {{ Js::from($milestones->pluck('id')->all()) }},
            toggleAll(e) {
                this.selected = e.target.checked ? [...this.pageIds] : [];
            },
            bulkDelete() {
                if (this.selected.length === 0) return;
                this.$dispatch('show-confirm', {
                    title: '{{ __('admin.confirm_delete') }}', message: '{{ __('admin.delete_warning') }}', type: 'danger',
                    onConfirm: () => this.postBulk('{{ route('admin.seerah-milestones.bulk-delete') }}'),
                });
            },
            postBulk(url) {
                const form = document.createElement('form');
                form.method = 'POST'; form.action = url;
                form.innerHTML = '<input type="hidden" name="_token" value="{{ csrf_token() }}">';
                this.selected.forEach(id => { const i = document.createElement('input'); i.type = 'hidden'; i.name = 'ids[]'; i.value = id; form.appendChild(i); });
                document.body.appendChild(form); form.submit();
            },
        };
    }
    document.addEventListener('DOMContentLoaded', () => lucide.createIcons());
</script>
@endpush
