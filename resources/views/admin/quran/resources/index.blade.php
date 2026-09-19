@extends('layouts.admin')

@section('page-header')
    <x-admin.quran.page-header :title="$kind->label()" icon="library" :subtitle="__('quran.manage.kind_help.'.$kind->value)">
        @can('sync quran')
            @if ($kind->qfCataloguePath())
                <x-admin.quran.button variant="secondary" icon="cloud-download" :href="route('admin.quran.resources.catalogue', $kind->routeSegment())">{{ __('quran.manage.qf_catalogue') }}</x-admin.quran.button>
            @endif
            @if ($kind === \App\Domain\Quran\Enums\ResourceKind::CHAPTER_RECITER)
                <x-admin.quran.button variant="secondary" icon="radio-tower" :href="route('admin.quran.resources.mp3quran')">{{ __('quran.manage.mp3quran_catalogue') }}</x-admin.quran.button>
            @endif
        @endcan
        @can('edit quran')
            <x-admin.quran.button icon="plus" :href="route('admin.quran.resources.create', $kind->routeSegment())">{{ __('quran.manage.add_manual') }}</x-admin.quran.button>
        @endcan
    </x-admin.quran.page-header>
@endsection

@section('content')
    <x-admin.quran.flash />

    @php
        $hasFilters = collect(request()->only(['search', 'language', 'status', 'source', 'style', 'sort']))->filter(fn ($v) => filled($v))->isNotEmpty();
        $sortOptions = collect(\App\Domain\Quran\Repositories\EloquentResourceRepository::ADMIN_SORTS)->mapWithKeys(fn ($s) => [$s => __('quran.manage.sort_'.$s)])->all();
        // Chapter reciters hold one file per surah, so their record count is a share of 114.
        $outOf = $kind === \App\Domain\Quran\Enums\ResourceKind::CHAPTER_RECITER ? 114 : null;
        $toggleLabels = ['is_active' => __('quran.manage.active'), 'sync_enabled' => __('quran.manage.sync_enabled')];
    @endphp

    <x-admin.quran.panel class="!p-4 sm:!p-5">
        <form method="GET" class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-[minmax(0,2fr)_repeat(5,minmax(0,1fr))_auto] xl:items-end">
            <x-admin.quran.field name="search" type="search" :label="__('quran.manage.search')" :value="request('search')" />
            <x-admin.quran.field name="language" type="select" :label="__('quran.manage.language')" :value="request('language')" :options="$languages" />
            <x-admin.quran.field name="status" type="select" :label="__('quran.manage.status')" :value="request('status')"
                :options="['active' => __('quran.manage.active'), 'inactive' => __('quran.manage.inactive'), 'hidden' => __('quran.manage.hidden'), 'sync' => __('quran.manage.sync_enabled')]" />
            <x-admin.quran.field name="source" type="select" :label="__('quran.manage.source')" :value="request('source')"
                :options="['qf' => __('quran.manage.source_qf'), 'manual' => __('quran.manage.source_manual')]" />
            @if ($kind->isReciter())
                <x-admin.quran.field name="style" type="select" :label="__('quran.manage.style')" :value="request('style')"
                    :options="collect(\App\Domain\Quran\Enums\RecitationStyle::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])->all()" />
            @endif
            <x-admin.quran.field name="sort" type="select" :label="__('quran.manage.sort')" :value="request('sort', 'name')" :options="$sortOptions" :blank="false" />
            <x-admin.quran.button icon="filter" class="h-[42px] justify-center">{{ __('quran.manage.filter') }}</x-admin.quran.button>
        </form>

        <div class="mt-4 flex flex-wrap items-center justify-between gap-2 border-t border-gray-100 pt-3 text-sm dark:border-gray-800">
            <p class="text-gray-600 dark:text-gray-400">
                {{ __('quran.manage.total') }}:
                <span class="font-bold tabular-nums text-gray-900 dark:text-white">{{ number_format($resources->total()) }}</span>
            </p>
            @if ($hasFilters)
                <a href="{{ route('admin.quran.resources.index', $kind->routeSegment()) }}"
                   class="inline-flex min-h-[2.75rem] items-center gap-1.5 rounded-lg px-2 font-semibold text-accent hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent">
                    <i data-lucide="x" class="h-4 w-4" aria-hidden="true"></i>{{ __('quran.manage.reset_filters') }}
                </a>
            @endif
        </div>
    </x-admin.quran.panel>

    <x-admin.quran.table :empty="$resources->isEmpty()" :empty-text="__('quran.manage.no_resources')" class="shadow-sm">
        <x-slot:head>
            <th class="w-12 !text-center">#</th>
            <th>{{ __('quran.manage.name') }}</th>
            <th>{{ __('quran.manage.language') }}</th>
            @if ($kind->isReciter())<th>{{ __('quran.manage.style') }}</th>@endif
            <th>{{ __('quran.manage.records') }}</th>
            <th>{{ __('quran.manage.last_synced') }}</th>
            <th class="!text-center">{{ __('quran.manage.active') }}</th>
            <th class="!text-center">{{ __('quran.manage.sync_enabled') }}</th>
            <th class="!text-center">{{ __('quran.manage.default') }}</th>
            <th class="!text-end">{{ __('quran.manage.actions') }}</th>
        </x-slot:head>

        @foreach ($resources as $resource)
            @php
                $name = $resource->displayName();
                $image = filled($resource->image)
                    ? (str_starts_with($resource->image, 'http') ? $resource->image : Storage::disk('public')->url($resource->image))
                    : null;
                $showUrl = route('admin.quran.resources.show', $resource->id);
            @endphp
            <tr class="group transition-colors hover:bg-gray-50 dark:hover:bg-gray-800/40 [&>td]:!align-middle">
                <td class="text-center text-xs tabular-nums text-gray-500 dark:text-gray-500">{{ $resource->display_order }}</td>

                <td class="min-w-[16rem]">
                    <div class="flex items-center gap-3">
                        {{-- The initial sits under the photo, so a missing or broken image still reads as a person. --}}
                        <a href="{{ $showUrl }}" tabindex="-1" aria-hidden="true"
                           class="relative grid h-11 w-11 shrink-0 place-items-center overflow-hidden rounded-xl bg-gradient-to-br from-[#0056b3] to-[#218838] text-sm font-bold text-white ring-1 ring-black/5 dark:ring-white/10">
                            {{ mb_substr($name, 0, 1) }}
                            @if ($image)
                                <img src="{{ $image }}" alt="" width="44" height="44" loading="lazy" decoding="async"
                                     onerror="this.remove()" class="absolute inset-0 h-full w-full object-cover">
                            @endif
                        </a>
                        <div class="min-w-0">
                            <a href="{{ $showUrl }}" title="{{ $name }}"
                               class="block truncate font-semibold text-gray-900 transition-colors hover:text-accent group-hover:text-accent dark:text-white">{{ $name }}</a>
                            <div class="mt-0.5 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-gray-500 dark:text-gray-400">
                                @if ($resource->parent_id)
                                    <a href="{{ route('admin.quran.resources.show', $resource->parent_id) }}" title="{{ __('quran.manage.recording_of') }} #{{ $resource->parent_id }}"
                                       class="inline-flex items-center gap-1 rounded-md bg-violet-50 px-1.5 py-0.5 font-medium text-violet-800 hover:underline dark:bg-violet-500/10 dark:text-violet-300">
                                        <i data-lucide="corner-down-right" class="h-3 w-3 rtl:-scale-x-100" aria-hidden="true"></i>{{ __('quran.manage.recording') }}
                                    </a>
                                @endif
                                @if ($resource->author_name && $resource->author_name !== $name)
                                    <span class="truncate">{{ $resource->author_name }}</span>
                                @endif
                                <span dir="ltr" @class([
                                    'inline-flex items-center gap-1 rounded-md px-1.5 py-0.5 font-medium',
                                    'bg-blue-50 text-blue-800 dark:bg-blue-500/10 dark:text-blue-300' => $resource->isFromQf(),
                                    'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300' => ! $resource->isFromQf(),
                                ])>
                                    <i data-lucide="{{ $resource->isFromQf() ? 'cloud' : 'pencil-line' }}" class="h-3 w-3" aria-hidden="true"></i>
                                    {{ $resource->isFromQf() ? 'QF #'.$resource->qf_id : __('quran.manage.source_manual') }}
                                </span>
                            </div>
                        </div>
                    </div>
                </td>

                <td>
                    @if ($resource->language_iso)
                        <span dir="ltr" class="inline-flex rounded-md border border-gray-200 px-1.5 py-0.5 font-mono text-xs uppercase text-gray-700 dark:border-gray-700 dark:text-gray-300">{{ $resource->language_iso }}</span>
                    @else
                        <span class="text-gray-400" aria-hidden="true">—</span>
                    @endif
                </td>

                @if ($kind->isReciter())
                    <td class="whitespace-nowrap">
                        @if ($resource->style)
                            <span class="font-medium">{{ $resource->style->label() }}</span>
                        @else
                            <span class="text-gray-400" aria-hidden="true">—</span>
                        @endif
                        @if ($resource->qirat)<span class="ms-1 text-xs text-gray-500 dark:text-gray-400">{{ $resource->qirat }}</span>@endif
                    </td>
                @endif

                <td class="whitespace-nowrap">
                    <span class="font-semibold tabular-nums">{{ number_format($resource->records_count) }}</span>
                    @if ($outOf)
                        <span class="text-xs tabular-nums text-gray-500 dark:text-gray-400">/ {{ $outOf }}</span>
                        <div class="mt-1.5 h-1 w-20 overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700"
                             role="img" aria-label="{{ $resource->records_count }} / {{ $outOf }}">
                            <div class="h-full rounded-full bg-gradient-to-r from-[#0056b3] to-[#218838] rtl:bg-gradient-to-l"
                                 style="width: {{ min(100, round($resource->records_count / $outOf * 100)) }}%"></div>
                        </div>
                    @endif
                </td>

                <td class="whitespace-nowrap text-gray-600 dark:text-gray-400">
                    @if ($resource->last_synced_at)
                        <time datetime="{{ $resource->last_synced_at->toIso8601String() }}" title="{{ $resource->last_synced_at->format('Y-m-d H:i') }}">{{ $resource->last_synced_at->diffForHumans() }}</time>
                    @else
                        <span class="text-gray-400">{{ __('quran.manage.never') }}</span>
                    @endif
                </td>

                {{-- Active / sync: real switches. The form still posts and reloads, so the state shown is always the saved one. --}}
                @foreach ($toggleLabels as $field => $label)
                    @php
                        $on = (bool) $resource->{$field};
                        $locked = $field === 'sync_enabled' && $resource->qf_id === null;
                    @endphp
                    <td class="text-center">
                        @can('edit quran')
                            <form method="POST" action="{{ route('admin.quran.resources.toggle', [$resource->id, $field]) }}" class="inline-flex">@csrf
                                <button type="submit" role="switch" aria-checked="{{ $on ? 'true' : 'false' }}" aria-label="{{ $label }} — {{ $name }}"
                                        title="{{ $locked ? __('quran.manage.source_manual') : __('quran.manage.toggle') }}" @disabled($locked)
                                        @class([
                                            "relative inline-flex h-6 w-11 items-center rounded-full p-0.5 transition-colors duration-200 before:absolute before:-inset-2.5 before:content-['']",
                                            'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent focus-visible:ring-offset-2 dark:focus-visible:ring-offset-gray-900',
                                            'disabled:cursor-not-allowed disabled:opacity-40',
                                            'justify-end bg-emerald-600' => $on,
                                            'justify-start bg-gray-300 dark:bg-gray-700' => ! $on,
                                        ])>
                                    <span class="h-5 w-5 rounded-full bg-white shadow-sm"></span>
                                </button>
                            </form>
                        @else
                            <x-admin.quran.badge :on="$on" />
                        @endcan
                    </td>
                @endforeach

                {{-- Default is one-of-many, so it reads as a star rather than a third switch. --}}
                <td class="text-center">
                    @can('edit quran')
                        <form method="POST" action="{{ route('admin.quran.resources.toggle', [$resource->id, 'is_default']) }}" class="inline-flex">@csrf
                            <button type="submit" aria-pressed="{{ $resource->is_default ? 'true' : 'false' }}" aria-label="{{ __('quran.manage.default') }} — {{ $name }}" title="{{ __('quran.manage.default') }}"
                                    @class([
                                        'grid h-11 w-11 place-items-center rounded-xl transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent',
                                        'text-amber-500 hover:bg-amber-50 dark:hover:bg-amber-500/10' => $resource->is_default,
                                        'text-gray-300 hover:bg-gray-100 hover:text-amber-500 dark:text-gray-600 dark:hover:bg-gray-800' => ! $resource->is_default,
                                    ])>
                                <i data-lucide="star" @class(['h-5 w-5', 'fill-current' => $resource->is_default]) aria-hidden="true"></i>
                            </button>
                        </form>
                    @else
                        <x-admin.quran.badge :on="$resource->is_default" />
                    @endcan
                </td>

                <td>
                    <div class="flex items-center justify-end gap-1">
                        <a href="{{ $showUrl }}" aria-label="{{ __('quran.manage.view') }} — {{ $name }}" title="{{ __('quran.manage.view') }}"
                           class="grid h-11 w-11 place-items-center rounded-xl text-gray-500 transition-colors hover:bg-gray-100 hover:text-accent focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent dark:text-gray-400 dark:hover:bg-gray-800">
                            <i data-lucide="eye" class="h-[1.125rem] w-[1.125rem]" aria-hidden="true"></i>
                        </a>
                        @can('edit quran')
                            <a href="{{ route('admin.quran.resources.edit', $resource->id) }}" aria-label="{{ __('quran.manage.edit') }} — {{ $name }}" title="{{ __('quran.manage.edit') }}"
                               class="grid h-11 w-11 place-items-center rounded-xl text-gray-500 transition-colors hover:bg-gray-100 hover:text-accent focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent dark:text-gray-400 dark:hover:bg-gray-800">
                                <i data-lucide="pencil" class="h-[1.125rem] w-[1.125rem]" aria-hidden="true"></i>
                            </a>
                        @endcan
                    </div>
                </td>
            </tr>
        @endforeach

        @if ($resources->hasPages())
            <x-slot:footer>{{ $resources->links() }}</x-slot:footer>
        @endif
    </x-admin.quran.table>
@endsection
