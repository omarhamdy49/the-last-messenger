@extends('layouts.admin')

@section('page-header')
    <x-admin.quran.page-header :title="$mushaf->name" icon="book" :subtitle="$mushaf->description"
        :crumbs="[__('quran.manage.mushafs') => route('admin.quran.mushafs.index')]">
        @can('sync quran')
            <form method="POST" action="{{ route('admin.quran.mushafs.fonts', $mushaf->id) }}">@csrf
                <x-admin.quran.button variant="secondary" icon="download">{{ __('quran.manage.download_fonts') }}</x-admin.quran.button>
            </form>
            <form method="POST" action="{{ route('admin.quran.mushafs.resync', $mushaf->id) }}">@csrf
                <x-admin.quran.button variant="secondary" icon="refresh-cw">{{ __('quran.manage.resync') }}</x-admin.quran.button>
            </form>
        @endcan
    </x-admin.quran.page-header>
@endsection

@section('content')
    <x-admin.quran.flash />

    @can('edit quran')
        <x-admin.quran.panel :title="__('quran.manage.settings')" icon="settings">
            <form method="POST" action="{{ route('admin.quran.mushafs.update', $mushaf->id) }}" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                @csrf
                @method('PUT')
                <div class="md:col-span-2"><x-admin.quran.field name="name" :label="__('quran.manage.name')" :value="$mushaf->name" required maxlength="120" /></div>
                <x-admin.quran.field name="is_active" type="checkbox" :label="__('quran.manage.active')" :value="$mushaf->is_active" />
                <x-admin.quran.field name="is_default" type="checkbox" :label="__('quran.manage.default')" :value="$mushaf->is_default" />
                <div><x-admin.quran.button icon="save">{{ __('quran.manage.save') }}</x-admin.quran.button></div>
            </form>
        </x-admin.quran.panel>
    @endcan

    @php
        // How this mushaf is drawn. The QCF families are glyph fonts, one file per page,
        // and the word "text" is a private-use code point that only means something in
        // that page's font. The others are ordinary Unicode in one font file.
        // QF spells families both ways (`v4_tajweed` in the catalogue, `v4-tajweed` after a sync).
        $family = str_replace('-', '_', (string) $mushaf->default_font_name);
        $fontsPath = asset(trim((string) config('quran.assets.fonts_public_path'), '/'));
        $cdn = rtrim((string) config('quran.qf.fonts_base'), '/');
        $glyph = match (true) {
            $family === 'v1' => ['code' => 'text', 'sources' => ["{$fontsPath}/v1/woff2/p{$pageNumber}.woff2", "{$cdn}/v1/woff2/p{$pageNumber}.woff2"]],
            // V4 shares V2's code points; its colour (tajweed) fonts live on the CDN only,
            // so the local V2 file is the fallback and the page still reads without them.
            $family === 'v4_tajweed' => ['code' => 'text', 'sources' => ["{$cdn}/v4/colrv1/woff2/p{$pageNumber}.woff2", "{$fontsPath}/v2/woff2/p{$pageNumber}.woff2", "{$cdn}/v2/woff2/p{$pageNumber}.woff2"]],
            $family === 'v2' || $family === '' => ['code' => 'text', 'sources' => ["{$fontsPath}/v2/woff2/p{$pageNumber}.woff2", "{$cdn}/v2/woff2/p{$pageNumber}.woff2"]],
            default => null,
        };
        $unicodeFont = str_contains($family, 'indopak')
            ? "{$fontsPath}/indopak/indopak-nastaleeq-waqf-lazim-v4.2.1.woff2"
            : "{$fontsPath}/hafs/UthmanicHafs1Ver18.woff2";

        // A FontFace source list: the browser takes the first URL that loads.
        $fontSrc = collect($glyph['sources'] ?? [])->map(fn (string $url): string => "url({$url}) format('woff2')")->implode(', ');

        // Verse and page numbers the way the reader prints them.
        $toDigits = static fn (int|string $n): string => in_array(app()->getLocale(), ['ar', 'ur'], true)
            ? strtr((string) $n, ['0' => '٠', '1' => '١', '2' => '٢', '3' => '٣', '4' => '٤', '5' => '٥', '6' => '٦', '7' => '٧', '8' => '٨', '9' => '٩'])
            : (string) $n;

        $words = collect($lines)->flatten(1);
        $firstKey = $words->first()['verse_key'] ?? null;
        $lastKey = $words->last()['verse_key'] ?? null;
        $linesPerPage = max(1, (int) $mushaf->lines_per_page);
        $hasLayout = $lines !== [];
    @endphp

    <x-admin.quran.panel class="!p-0 overflow-hidden">
        <dl class="grid grid-cols-2 divide-x divide-y divide-gray-100 dark:divide-gray-800 sm:grid-cols-3 lg:grid-cols-6 lg:divide-y-0 rtl:divide-x-reverse">
            @foreach ([
                ['icon' => 'file-stack', 'label' => __('quran.manage.pages'), 'value' => number_format($mushaf->pages_count).' × '.$mushaf->lines_per_page],
                ['icon' => 'type', 'label' => __('quran.manage.font'), 'value' => $mushaf->default_font_name ?: '—', 'ltr' => true],
                ['icon' => 'book-open', 'label' => __('quran.manage.qirat'), 'value' => $mushaf->qirat_name ?: '—'],
                ['icon' => 'layout-list', 'label' => __('quran.manage.layout_words'), 'value' => number_format($mushaf->records_count)],
                ['icon' => 'download', 'label' => __('quran.manage.download_fonts'), 'value' => $mushaf->fonts_downloaded_at?->diffForHumans() ?? __('quran.manage.not_downloaded')],
                ['icon' => 'refresh-cw', 'label' => __('quran.manage.last_synced'), 'value' => $mushaf->last_synced_at?->diffForHumans() ?? __('quran.manage.never')],
            ] as $fact)
                <div class="p-4">
                    <dt class="flex items-center gap-1.5 text-xs font-semibold text-gray-500 dark:text-gray-400">
                        <i data-lucide="{{ $fact['icon'] }}" class="h-3.5 w-3.5 text-accent" aria-hidden="true"></i>{{ $fact['label'] }}
                    </dt>
                    <dd class="mt-1 truncate font-bold tabular-nums text-gray-900 dark:text-white" @if ($fact['ltr'] ?? false) dir="ltr" @endif title="{{ $fact['value'] }}">{{ $fact['value'] }}</dd>
                </div>
            @endforeach
        </dl>
    </x-admin.quran.panel>

    <x-admin.quran.panel :title="__('quran.manage.page_viewer')" icon="file-text">
        {{-- A plain element, not the component tag: Blade does not run directives inside a
             component's attributes, and the font loader below needs a conditional. --}}
        <div x-data="{
                mode: @js($glyph ? 'glyph' : 'text'), ready: false, failed: false, active: null,
                // One size for the whole page, small enough for its widest line — a printed
                // mushaf never changes size between lines (same rule as the public reader).
                fit() {
                    const root = this.$refs.mushaf;
                    if (!root) { return; }
                    root.style.fontSize = '1.75rem';
                    let ratio = 1;
                    root.querySelectorAll('.mushaf-line').forEach((l) => { if (l.scrollWidth > l.clientWidth) { ratio = Math.min(ratio, l.clientWidth / l.scrollWidth); } });
                    if (ratio < 1) { root.style.fontSize = (parseFloat(getComputedStyle(root).fontSize) * ratio * 0.99).toFixed(2) + 'px'; }
                },
                init() {
                    const refit = () => this.$nextTick(() => this.fit());
                    this.$watch('mode', refit); this.$watch('ready', refit);
                    window.addEventListener('resize', refit);
                    (document.fonts ? document.fonts.ready : Promise.resolve()).then(refit);

                    const sources = @js($glyph && $hasLayout ? $fontSrc : '');
                    if (!sources) { return; }
                    new FontFace('mushaf-preview', sources).load()
                        .then((face) => { document.fonts.add(face); this.ready = true; })
                        .catch(() => { this.failed = true; this.mode = 'text'; });
                },
             }">

        <div class="mb-5 flex flex-wrap items-end justify-between gap-3">
            <form method="GET" class="flex items-end gap-2">
                <x-admin.quran.button variant="secondary" icon="chevron-right" class="h-[42px] rtl:flex-row-reverse {{ $pageNumber <= 1 ? 'pointer-events-none opacity-40' : '' }}"
                    :href="route('admin.quran.mushafs.show', [$mushaf->id, 'page' => max(1, $pageNumber - 1)])" aria-label="{{ __('quran.manage.previous') }}"><span class="sr-only sm:not-sr-only">{{ __('quran.manage.previous') }}</span></x-admin.quran.button>
                <div class="w-28"><x-admin.quran.field name="page" type="number" :label="__('quran.manage.page')" :value="$pageNumber" min="1" :max="$mushaf->pages_count" inputmode="numeric" /></div>
                <x-admin.quran.button variant="secondary" class="h-[42px]">{{ __('quran.manage.go') }}</x-admin.quran.button>
                <x-admin.quran.button variant="secondary" icon="chevron-left" class="h-[42px] flex-row-reverse rtl:flex-row {{ $pageNumber >= $mushaf->pages_count ? 'pointer-events-none opacity-40' : '' }}"
                    :href="route('admin.quran.mushafs.show', [$mushaf->id, 'page' => min($mushaf->pages_count, $pageNumber + 1)])" aria-label="{{ __('quran.manage.next') }}"><span class="sr-only sm:not-sr-only">{{ __('quran.manage.next') }}</span></x-admin.quran.button>
            </form>

            @if ($glyph && $hasLayout)
                <div class="inline-flex rounded-xl border border-gray-200 p-1 dark:border-gray-700" role="group" aria-label="{{ __('quran.manage.preview_mode') }}">
                    @foreach (['glyph' => __('quran.manage.preview_glyphs'), 'text' => __('quran.manage.preview_text')] as $value => $label)
                        <button type="button" @click="mode = '{{ $value }}'" :aria-pressed="(mode === '{{ $value }}').toString()" @if ($value === 'glyph') :disabled="failed" @endif
                                class="min-h-[2.5rem] rounded-lg px-3 text-sm font-semibold transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent disabled:cursor-not-allowed disabled:opacity-40"
                                :class="mode === '{{ $value }}' ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow' : 'text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white'">{{ $label }}</button>
                    @endforeach
                </div>
            @endif
        </div>

        @if (! $hasLayout)
            {{-- Nothing to draw: say why, and put the fix right here. --}}
            <div class="grid justify-items-center gap-3 rounded-2xl border border-dashed border-gray-300 px-6 py-12 text-center dark:border-gray-700">
                <span class="grid h-14 w-14 place-items-center rounded-2xl bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300"><i data-lucide="file-question" class="h-6 w-6" aria-hidden="true"></i></span>
                <p class="max-w-md text-sm text-gray-600 dark:text-gray-400">{{ __('quran.manage.page_not_synced') }}</p>
                @can('sync quran')
                    <form method="POST" action="{{ route('admin.quran.mushafs.resync', $mushaf->id) }}">@csrf
                        <x-admin.quran.button icon="refresh-cw" class="min-h-[2.75rem]">{{ __('quran.manage.resync') }}</x-admin.quran.button>
                    </form>
                @endcan
            </div>
        @else
            <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_20rem]">
                {{-- The leaf, in the public reader's own markup and styles (partials.quran.mushaf-styles):
                     centred one-line rows at one shared size, rosette verse markers, page badge.
                     Lines with no words are the printed ornament (surah banner, basmala, the
                     framed opening pages) and are not drawn as blanks — a new surah gets its
                     banner instead, exactly where the reader shows one. --}}
                <div class="mushaf-card mx-auto w-full">
                    <p x-show="failed" x-cloak role="status" class="mb-3 rounded-xl border border-amber-300 bg-amber-50 p-3 text-sm text-amber-900 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-200">{{ __('quran.manage.preview_font_failed') }}</p>
                    <p x-show="mode === 'glyph' && !ready && !failed" x-cloak role="status" class="mb-2 text-center text-xs text-gray-500 dark:text-gray-400">{{ __('quran.manage.loading') }}</p>

                    <div class="rounded-2xl border border-gray-200 bg-white px-4 py-6 shadow-sm dark:border-gray-800 dark:bg-gray-900 sm:px-8">
                        <div x-ref="mushaf" class="mushaf text-gray-900 dark:text-gray-100" dir="rtl" lang="ar"
                             :class="[darkMode && 'mushaf-dark', mode === 'glyph' && ready && 'glyph-mode']"
                             style="font-size: 1.75rem; font-family: 'mushaf-unicode', 'Amiri', serif">
                            <section class="mushaf-page" data-page="{{ $pageNumber }}">
                                @foreach ($lines as $lineNumber => $lineWords)
                                    @php
                                        $first = $lineWords[0];
                                        [$chapterId, $verseNumber] = array_map('intval', explode(':', $first['verse_key']));
                                        $opensSurah = $verseNumber === 1 && $first['position'] === 1;
                                    @endphp
                                    @if ($opensSurah)
                                        <div class="my-3 flex items-center gap-3 text-[0.5em] font-bold text-[#9a7b1f] dark:text-[#e7c66a]" style="font-family: 'Cairo', sans-serif">
                                            <span class="h-px flex-1 bg-current opacity-30"></span>
                                            <a href="{{ route('admin.quran.chapters.show', $chapterId) }}" class="rounded-full border border-current/40 px-4 py-1 hover:underline">{{ $chapterId }} · {{ $chapters->get($chapterId)?->name_arabic }}</a>
                                            <span class="h-px flex-1 bg-current opacity-30"></span>
                                        </div>
                                        @if ($chapterId !== 1 && $chapterId !== 9)
                                            <p class="mushaf-bismillah !mb-2">بِسۡمِ ٱللَّهِ ٱلرَّحۡمَٰنِ ٱلرَّحِيمِ</p>
                                        @endif
                                    @endif
                                    <div class="mushaf-line" data-line="{{ $lineNumber }}">@foreach ($lineWords as $word)@php $verse = (int) explode(':', $word['verse_key'])[1]; @endphp @if ($word['char_type'] === 'end')<span class="ayah-end" role="img" aria-label="{{ $word['verse_key'] }}" title="{{ $word['verse_key'] }} · {{ __('quran.manage.preview_lines') }} {{ $lineNumber }}"><span class="ayah-end-num">{{ $toDigits($verse) }}</span><span class="qw-glyph" style="font-family: 'mushaf-preview'">{{ $glyph ? $word['text'] : '' }}</span></span>@else<span class="qw" tabindex="0" title="{{ $word['verse_key'] }}:{{ $word['position'] }} · {{ __('quran.manage.preview_lines') }} {{ $lineNumber }}"
                                              @mouseenter="active = @js($word['verse_key'])" @mouseleave="active = null" @focus="active = @js($word['verse_key'])" @blur="active = null"
                                              :class="active === @js($word['verse_key']) && 'qw-verse-active'"><span class="qw-uni">{{ $glyph ? ($word['text_qpc_hafs'] ?? $word['text']) : $word['text'] }}</span><span class="qw-glyph" style="font-family: 'mushaf-preview'">{{ $glyph ? $word['text'] : '' }}</span></span>@endif @endforeach</div>
                                @endforeach
                                <div class="mushaf-pageno"><span class="mushaf-pageno-badge">{{ $toDigits($pageNumber) }}</span></div>
                            </section>
                        </div>
                    </div>
                </div>

                {{-- Everything the layout stores for this page, so a preview that looks wrong
                     can be checked against the numbers. --}}
                <aside class="space-y-4">
                    <dl class="grid grid-cols-3 gap-2 text-center">
                        @foreach ([__('quran.manage.verses') => $page?->verses_count ?? $words->pluck('verse_key')->unique()->count(), __('quran.manage.words') => $words->where('char_type', 'word')->count(), __('quran.manage.preview_lines') => count($lines)] as $label => $value)
                            <div class="rounded-xl border border-gray-200 p-3 dark:border-gray-800">
                                <dd class="text-xl font-bold tabular-nums text-gray-900 dark:text-white">{{ number_format($value) }}</dd>
                                <dt class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{{ $label }}</dt>
                            </div>
                        @endforeach
                    </dl>

                    <div>
                        <h3 class="mb-2 text-sm font-bold text-gray-900 dark:text-white">{{ __('quran.manage.verse_range') }}</h3>
                        <ul class="grid gap-2">
                            @forelse (($page?->verse_mapping ?? []) as $chapterId => $range)
                                @php $chapter = $chapters->get((int) $chapterId); @endphp
                                <li class="flex items-center gap-3 rounded-xl border border-gray-200 p-2.5 dark:border-gray-800">
                                    <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-gradient-to-br from-[#0056b3] to-[#218838] text-sm font-bold tabular-nums text-white">{{ $chapterId }}</span>
                                    <a href="{{ route('admin.quran.chapters.show', (int) $chapterId) }}" class="min-w-0 flex-1 truncate font-semibold text-gray-900 hover:text-accent dark:text-white">{{ $chapter?->displayName() ?? $chapterId }}</a>
                                    <span class="shrink-0 rounded-full bg-gray-100 px-2 py-1 text-xs font-semibold tabular-nums text-gray-700 dark:bg-gray-800 dark:text-gray-300" dir="ltr">{{ $range }}</span>
                                </li>
                            @empty
                                <li class="text-sm text-gray-500 dark:text-gray-400" dir="ltr">{{ $firstKey }} – {{ $lastKey }}</li>
                            @endforelse
                        </ul>
                    </div>

                    @if ($page)
                        <dl class="grid gap-1.5 rounded-xl bg-gray-50 p-3 text-xs dark:bg-gray-800/50">
                            @foreach (['verse_id' => $page->first_verse_id.' – '.$page->last_verse_id, 'word_id' => $page->first_word_id.' – '.$page->last_word_id, 'verse_key' => $firstKey.' – '.$lastKey] as $label => $value)
                                <div class="flex items-center justify-between gap-3">
                                    <dt class="font-mono text-gray-500 dark:text-gray-400" dir="ltr">{{ $label }}</dt>
                                    <dd class="font-mono font-semibold tabular-nums text-gray-800 dark:text-gray-200" dir="ltr">{{ $value }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    @endif
                </aside>
            </div>
        @endif
        </div>
    </x-admin.quran.panel>

    @if ($hasLayout)
        @push('styles')
            <style>
                @font-face { font-family: 'mushaf-unicode'; src: url('{{ $unicodeFont }}') format('woff2'); font-display: swap; }
                @include('partials.quran.mushaf-styles')
                /* The admin layout sets its UI font on every element, so the mushaf face has to be
                   named on the words themselves — inheriting it from .mushaf is not enough. */
                .mushaf .qw-uni, .mushaf .mushaf-bismillah { font-family: 'mushaf-unicode', 'Amiri', serif; }
                /* The preview's words are inspected, not tapped. */
                .mushaf .qw, .mushaf .ayah-end { cursor: default; }
                .mushaf .qw:focus-visible { outline: 2px solid #64ccc5; outline-offset: 2px; }
            </style>
        @endpush
    @endif
@endsection
