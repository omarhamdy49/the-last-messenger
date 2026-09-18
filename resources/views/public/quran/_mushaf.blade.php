{{--
    Mushaf reading view — line-for-line, page-by-page layout matching the printed
    mushaf (quran.com reading mode). Words and their line/page placement come from
    the server-side `$mushaf` layout (quran_words); rendered in the site's own
    Uthmani font (Amiri), NOT a glyph font. Verse-end markers render as our rosette
    with the verse number; numbers localise to Arabic-Indic digits in ar/ur.

    Every line shares one font size, chosen so the widest line fits the column —
    a printed mushaf never changes size between lines. Lines are centred, so a
    short one simply does not reach the edge.

    Expects: $mushaf (pages → lines → words), $surah.
--}}
@php
    $isRtlLocale = in_array(app()->getLocale(), ['ar', 'ur'], true);
    $toDigits = static function ($n) use ($isRtlLocale): string {
        $n = (string) $n;
        return $isRtlLocale
            ? strtr($n, ['0' => '٠', '1' => '١', '2' => '٢', '3' => '٣', '4' => '٤', '5' => '٥', '6' => '٦', '7' => '٧', '8' => '٨', '9' => '٩'])
            : $n;
    };
@endphp

@php
    // Glyph mode needs every word to carry its QCF code; otherwise the toggle stays hidden.
    $hasGlyphs = collect($mushaf)->flatMap(fn ($page) => collect($page['lines'])->flatMap(fn ($line) => $line['words']))
        ->every(fn ($w) => ! empty($w['code']) && ! empty($w['glyphPage']));
    $glyphPages = $hasGlyphs ? collect($mushaf)->flatMap(fn ($page) => collect($page['lines'])->flatMap(fn ($line) => array_column($line['words'], 'glyphPage')))->unique()->values()->all() : [];
@endphp

<div class="mushaf" dir="rtl"
     data-glyph-pages="{{ implode(',', $glyphPages) }}"
     :class="[$store.theme.dark ? 'text-gray-100 mushaf-dark' : 'text-gray-900', glyphMode && {{ $hasGlyphs ? 'true' : 'false' }} ? 'glyph-mode' : '']"
     :style="`font-size: ${fontSize}px`">

    @if(! empty($surah['bismillahPre']) && (int) ($surah['number'] ?? 0) !== 1 && (int) ($surah['number'] ?? 0) !== 9)
        <p class="mushaf-bismillah">بِسۡمِ ٱللَّهِ ٱلرَّحۡمَٰنِ ٱلرَّحِيمِ</p>
    @endif

    @foreach($mushaf as $page)
        <section class="mushaf-page" data-page="{{ $page['page'] }}">
            @foreach($page['lines'] as $line)
                <div class="mushaf-line {{ $loop->last ? 'mushaf-line-end' : '' }}">@foreach($line['words'] as $w)@if($w['type'] === 'end')<span class="ayah-end" role="img" data-verse="{{ $w['verse'] }}" aria-label="{{ __('quran.public.verse_label') }} {{ $w['verse'] }}"><span class="ayah-end-num">{{ $toDigits($w['verse']) }}</span>@if($hasGlyphs)<span class="qw-glyph" style="font-family: 'qcf-v2-p{{ $w['glyphPage'] }}'">{{ $w['code'] }}</span>@endif</span>@else <span class="qw" data-word-location="{{ $w['location'] }}" data-verse="{{ $w['verse'] }}"><span class="qw-uni">{{ $w['text'] }}</span>@if($hasGlyphs)<span class="qw-glyph" style="font-family: 'qcf-v2-p{{ $w['glyphPage'] }}'">{{ $w['code'] }}</span>@endif</span> @endif @endforeach</div>
            @endforeach
            <div class="mushaf-pageno"><span class="mushaf-pageno-badge">{{ $toDigits($page['page']) }}</span></div>
        </section>
    @endforeach
</div>

<script>
    (function () {
        if (window.__mushafBound) { return; }
        window.__mushafBound = true;

        {{-- QCF page fonts, loaded once each and only in glyph mode: the local copy first
             (quran:qf:download-assets), the Quran Foundation CDN when it is missing. --}}
        const loadedPages = new Set();
        window.quranLoadGlyphFonts = () => {
            const root = document.querySelector('.mushaf');
            const pages = (root?.dataset.glyphPages || '').split(',').filter(Boolean);
            const local = @js(asset(trim((string) config('quran.assets.fonts_public_path'), '/').'/v2/woff2'));
            const cdn = @js(rtrim((string) config('quran.qf.fonts_base'), '/').'/v2/woff2');
            return Promise.all(pages.filter((p) => !loadedPages.has(p)).map((page) => {
                loadedPages.add(page);
                const face = new FontFace('qcf-v2-p' + page, `url(${local}/p${page}.woff2) format('woff2'), url(${cdn}/p${page}.woff2) format('woff2')`);
                return face.load().then((f) => document.fonts.add(f)).catch(() => loadedPages.delete(page));
            }));
        };

        {{-- Verse-group hover highlight: hovering any word lifts the whole ayah. --}}
        let current = null;
        const paint = (verse, on) => {
            document.querySelectorAll('.mushaf .qw[data-verse="' + verse + '"]')
                .forEach((el) => el.classList.toggle('qw-verse-active', on));
        };
        document.addEventListener('mouseover', (e) => {
            const word = e.target.closest('.mushaf .qw');
            const verse = word ? word.dataset.verse : null;
            if (verse === current) { return; }
            if (current !== null) { paint(current, false); }
            current = verse;
            if (verse !== null) { paint(verse, true); }
        });

        {{-- Fit-to-width: pick the one font size at which every line still fits the
             column, with natural spacing — avoids the big justify gaps of a non-glyph
             font. Batched (reset → read → write) to limit layout thrashing. --}}
        {{-- The floor has to clear a phone: a printed mushaf line holds ~10 words,
             and at 28px on a 360px screen that needs roughly 0.4. Clamping at 0.78
             left the line wider than the card, and `nowrap` pushed the rest of the
             ayah off the screen. A line never has to overflow — it can always be
             smaller — so the floor is only here to stop absurd shrinking. --}}
        const MIN = 0.3, MAX = 1.4;
        const fit = () => {
            const root = document.querySelector('.mushaf');
            if (!root || !root.clientWidth) { return; }
            const lines = Array.prototype.slice.call(
                document.querySelectorAll('.mushaf-line:not(.mushaf-line-end)')
            );
            if (!lines.length) { return; }

            lines.forEach((l) => { l.style.fontSize = ''; });
            const measured = lines.map((l) => {
                const base = parseFloat(getComputedStyle(l).fontSize) || 0;
                const target = l.clientWidth;
                const range = document.createRange();
                range.selectNodeContents(l);
                const natural = range.getBoundingClientRect().width;
                return { l, base, target, natural };
            });
            {{-- One size for the whole mushaf, not one per line. Scaling each line to
                 fill its own width made neighbouring lines visibly different sizes,
                 which no printed mushaf does. The page takes the smallest scale any
                 line needs, so every line shares it and none overflows; a short line
                 simply does not reach the edge. 0.5% under the exact fit absorbs the
                 rounding between the measured width and the re-laid-out one. --}}
            let ratio = MAX;
            measured.forEach((m) => {
                if (!m.base || !m.target || !m.natural) { return; }
                ratio = Math.min(ratio, (m.target / m.natural) * 0.995);
            });
            ratio = Math.max(MIN, Math.min(ratio, MAX));
            measured.forEach((m) => { m.l.style.fontSize = (m.base * ratio).toFixed(2) + 'px'; });

            // One corrective pass for anything the estimate still left too wide, measured
            // from the element itself so it cannot be off again. Rare, and it only ever
            // shrinks — the shared size is the floor for the rest.
            measured.forEach((m) => {
                if (!m.l.clientWidth || m.l.scrollWidth <= m.l.clientWidth) { return; }
                const size = parseFloat(m.l.style.fontSize) || m.base;
                m.l.style.fontSize = (size * (m.l.clientWidth / m.l.scrollWidth)).toFixed(2) + 'px';
            });
        };

        let timer = null;
        const schedule = () => { clearTimeout(timer); timer = setTimeout(fit, 60); };

        window.addEventListener('resize', schedule);
        (document.fonts ? document.fonts.ready : Promise.resolve()).then(schedule);

        const root = document.querySelector('.mushaf');
        if (root) {
            {{-- Re-fit when the view becomes visible and when the base font size changes. --}}
            new IntersectionObserver((entries) => {
                if (entries.some((e) => e.isIntersecting)) { schedule(); }
            }).observe(root);
            new MutationObserver(schedule).observe(root, { attributes: true, attributeFilter: ['style'] });
        }
        schedule();
    })();
</script>
