@extends('layouts.admin')

@php
    $kind = \App\Domain\Quran\Enums\ResourceKind::CHAPTER_RECITER;
    $canEdit = auth('admin')->user()?->can('edit quran') ?? false;
@endphp

@section('page-header')
    <x-admin.quran.page-header :title="__('quran.manage.mp3quran_catalogue')" icon="radio-tower" :subtitle="__('quran.manage.mp3quran_catalogue_help')"
        :crumbs="[$kind->label() => route('admin.quran.resources.index', $kind->routeSegment())]">
        <x-admin.quran.button variant="secondary" icon="refresh-cw" :href="route('admin.quran.resources.mp3quran', ['refresh' => 1])">{{ __('quran.manage.refresh_list') }}</x-admin.quran.button>
    </x-admin.quran.page-header>
@endsection

@section('content')
    <x-admin.quran.flash />

    @if ($error)
        <div class="mb-4 rounded-xl border border-red-300 bg-red-50 p-4 text-sm text-red-800 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-200" role="alert">{{ $error }}</div>
    @endif

    <div x-data="{
            q: '', show: 'all', visible: {{ count($rows) }},
            linking: null, target: '',
            linkUrl: @js(route('admin.quran.resources.link-source', '__ID__')),
            match(el) {
                const pass = this.show === 'all' || (this.show === 'new') === (el.dataset.linked === '0');
                return pass && (!this.q.trim() || el.dataset.search.includes(this.q.trim().toLowerCase()));
            },
            recount() { this.$nextTick(() => this.visible = [...this.$refs.list.children].filter((el) => this.match(el)).length); },
            link(ref, label) { this.linking = { ref, label }; this.target = ''; this.$nextTick(() => this.$refs.target?.focus()); },
         }"
         @keydown.escape.window="linking = null">

        <x-admin.quran.panel class="!p-4 sm:!p-5">
            <div class="grid gap-3 lg:grid-cols-[minmax(0,1fr)_auto_minmax(0,20rem)] lg:items-center">
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    {{ __('quran.manage.total') }}:
                    <span class="font-bold tabular-nums text-gray-900 dark:text-white">{{ number_format(count($rows)) }}</span>
                    <span class="mx-1" aria-hidden="true">·</span>
                    {{ __('quran.manage.already_linked') }}:
                    <span class="font-bold tabular-nums text-gray-900 dark:text-white">{{ number_format($linked->count()) }}</span>
                </p>

                <div class="inline-flex w-fit rounded-xl border border-gray-200 p-1 dark:border-gray-700" role="group" aria-label="{{ __('quran.manage.filter') }}">
                    @foreach (['all' => __('quran.manage.coverage_all'), 'new' => __('quran.manage.not_yet_imported'), 'linked' => __('quran.manage.already_linked')] as $value => $label)
                        <button type="button" @click="show = '{{ $value }}'; recount()" :aria-pressed="(show === '{{ $value }}').toString()"
                                class="min-h-[2.5rem] rounded-lg px-3 text-sm font-semibold transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent"
                                :class="show === '{{ $value }}' ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow' : 'text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white'">{{ $label }}</button>
                    @endforeach
                </div>

                <label class="relative block">
                    <span class="sr-only">{{ __('quran.manage.search') }}</span>
                    <i data-lucide="search" class="pointer-events-none absolute start-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" aria-hidden="true"></i>
                    <input type="search" x-model="q" @input="recount()" placeholder="{{ __('quran.manage.search_reciter_placeholder') }}"
                           class="w-full rounded-xl border border-gray-300 bg-white py-2.5 pe-3 ps-9 text-sm text-gray-900 focus:border-transparent focus:ring-2 focus:ring-accent dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                </label>
            </div>
        </x-admin.quran.panel>

        <ul x-ref="list" class="mb-24 grid gap-2 xl:grid-cols-2">
            @foreach ($rows as $row)
                @php
                    $owner = $linked->get($row['ref_id']);
                    $label = $row['reciter'].' — '.$row['recording'];
                @endphp
                <li x-show="match($el)" data-linked="{{ $owner ? '1' : '0' }}"
                    data-search="{{ mb_strtolower($row['ref_id'].' '.$row['reciter'].' '.$row['recording']) }}"
                    class="flex flex-wrap items-center gap-3 rounded-xl border border-gray-200 bg-white p-2.5 transition-colors hover:border-accent/60 dark:border-gray-800 dark:bg-gray-900/50 sm:flex-nowrap">

                    @if ($row['sample_url'])
                        <x-admin.quran.track-button :id="$row['ref_id']" :label="$label" />
                    @else
                        <span class="h-11 w-11 shrink-0"></span>
                    @endif

                    <div class="min-w-0 flex-1 basis-48">
                        <p class="truncate font-semibold text-gray-900 dark:text-white" title="{{ $row['reciter'] }}">{{ $row['reciter'] }}</p>
                        <p class="mt-0.5 truncate text-xs text-gray-500 dark:text-gray-400" title="{{ $row['recording'] }}">
                            <span dir="ltr" class="font-mono">#{{ $row['ref_id'] }}</span> · {{ $row['recording'] }}
                        </p>
                    </div>

                    <div class="flex shrink-0 items-center gap-1.5 text-xs font-semibold">
                        <span class="rounded-full bg-gray-100 px-2 py-1 tabular-nums text-gray-700 dark:bg-gray-800 dark:text-gray-300" dir="ltr">{{ $row['files_count'] }} / 114</span>
                        @if ($row['has_timestamps'])
                            <span class="inline-flex items-center gap-1 rounded-full bg-green-100 px-2 py-1 text-green-800 dark:bg-green-500/10 dark:text-green-300">
                                <i data-lucide="timer" class="h-3.5 w-3.5" aria-hidden="true"></i>{{ __('quran.manage.timestamps') }}
                            </span>
                        @endif
                    </div>

                    <div class="flex shrink-0 items-center gap-1.5">
                        @if ($owner)
                            <a href="{{ route('admin.quran.resources.show', $owner->id) }}"
                               class="inline-flex min-h-[2.75rem] items-center gap-1.5 rounded-xl bg-blue-50 px-3 text-sm font-semibold text-blue-800 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent dark:bg-blue-500/10 dark:text-blue-300">
                                <i data-lucide="link" class="h-4 w-4" aria-hidden="true"></i><span class="max-w-[10rem] truncate">{{ $owner->name }}</span>
                            </a>
                        @elseif ($canEdit)
                            <form method="POST" action="{{ route('admin.quran.resources.mp3quran.import') }}">
                                @csrf
                                <input type="hidden" name="ref_id" value="{{ $row['ref_id'] }}">
                                <x-admin.quran.button variant="secondary" icon="download" class="min-h-[2.75rem] dark:border-gray-700 dark:text-gray-200" aria-label="{{ __('quran.manage.import_as_new') }} — {{ $label }}">{{ __('quran.manage.import_as_new') }}</x-admin.quran.button>
                            </form>
                            <button type="button" @click="link(@js($row['ref_id']), @js($label))" title="{{ __('quran.manage.link_to_existing') }}" aria-label="{{ __('quran.manage.link_to_existing') }} — {{ $label }}"
                                    class="grid h-11 w-11 place-items-center rounded-xl border border-gray-300 text-gray-600 hover:border-accent hover:text-accent focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent dark:border-gray-700 dark:text-gray-300">
                                <i data-lucide="link" class="h-4 w-4" aria-hidden="true"></i>
                            </button>
                        @endif
                    </div>
                </li>
            @endforeach
        </ul>

        <p x-show="visible === 0" x-cloak class="-mt-20 mb-24 py-8 text-center text-sm text-gray-500 dark:text-gray-400">{{ __('quran.manage.empty') }}</p>

        @if ($canEdit)
            {{-- One shared dialog: a select per row would put ~6,000 options on the page. --}}
            <div x-show="linking" x-cloak class="fixed inset-0 z-40 grid place-items-center p-4" role="dialog" aria-modal="true" aria-labelledby="link-title">
                <div class="absolute inset-0 bg-gray-950/50" @click="linking = null"></div>
                <form method="POST" :action="linkUrl.replace('__ID__', target)" class="relative w-full max-w-md rounded-2xl bg-white p-5 shadow-2xl dark:bg-gray-900">
                    @csrf
                    <input type="hidden" name="external_source" value="mp3quran">
                    <input type="hidden" name="external_ref_id" :value="linking?.ref">

                    <h2 id="link-title" class="text-lg font-bold text-gray-900 dark:text-white">{{ __('quran.manage.link_to_existing') }}</h2>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400" x-text="linking?.label"></p>

                    <label class="mt-4 block">
                        <span class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('quran.manage.reciter') }}</span>
                        <select x-ref="target" x-model="target" required
                                class="w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-transparent focus:ring-2 focus:ring-accent dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                            <option value="">—</option>
                            @foreach ($reciters as $reciter)
                                <option value="{{ $reciter->id }}">{{ $reciter->displayName() }}@if ($reciter->external_source) ({{ $reciter->external_source->label() }} #{{ $reciter->external_ref_id }})@endif</option>
                            @endforeach
                        </select>
                        <span class="mt-1 block text-xs text-gray-500 dark:text-gray-400">{{ __('quran.manage.link_to_existing_help') }}</span>
                    </label>

                    <div class="mt-5 flex justify-end gap-3">
                        <button type="button" @click="linking = null"
                                class="min-h-[2.75rem] rounded-xl border border-gray-300 px-4 text-sm font-semibold text-gray-700 hover:border-accent focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent dark:border-gray-700 dark:text-gray-200">{{ __('quran.manage.cancel') }}</button>
                        <x-admin.quran.button icon="link" class="min-h-[2.75rem]" x-bind:disabled="!target">{{ __('quran.manage.link_source') }}</x-admin.quran.button>
                    </div>
                </form>
            </div>
        @endif
    </div>

    <x-admin.quran.track-player :tracks="collect($rows)->filter(fn ($r) => $r['sample_url'])->map(fn ($r) => [
        'id' => (int) $r['ref_id'],
        'title' => $r['reciter'].' — '.$r['recording'],
        'url' => $r['sample_url'],
    ])->values()->all()" />
@endsection
