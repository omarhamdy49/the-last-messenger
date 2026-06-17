{{--
    Mushaf reading view — line-for-line, page-by-page layout matching the printed
    mushaf (quran.com reading mode). Words and their line/page placement come from
    the server-side `$mushaf` layout (quran_words); rendered in the site's own
    Uthmani font (Amiri), NOT a glyph font. Verse-end markers render as our rosette
    with the verse number; numbers localise to Arabic-Indic digits in ar/ur.

    Lines are justified to fill the page width, except the last (partial) line of
    each page which is centred — mirroring the printed mushaf.

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

<div class="mushaf" dir="rtl"
     :class="$store.theme.dark ? 'text-gray-100 mushaf-dark' : 'text-gray-900'"
     :style="`font-size: ${fontSize}px`">

    @if(! empty($surah['bismillahPre']) && (int) ($surah['number'] ?? 0) !== 1 && (int) ($surah['number'] ?? 0) !== 9)
        <p class="mushaf-bismillah">بِسۡمِ ٱللَّهِ ٱلرَّحۡمَٰنِ ٱلرَّحِيمِ</p>
    @endif

    @foreach($mushaf as $page)
        <section class="mushaf-page" data-page="{{ $page['page'] }}">
            @foreach($page['lines'] as $line)
                <div class="mushaf-line {{ $loop->last ? 'mushaf-line-end' : '' }}">@foreach($line['words'] as $w)@if($w['type'] === 'end')<span class="ayah-end" role="img" aria-label="{{ __('quran.public.verse_label') }} {{ $w['verse'] }}"><span class="ayah-end-num">{{ $toDigits($w['verse']) }}</span></span>@else <span class="qw" data-word-location="{{ $w['location'] }}" data-verse="{{ $w['verse'] }}">{{ $w['text'] }}</span> @endif @endforeach</div>
            @endforeach
            <div class="mushaf-pageno"><span class="mushaf-pageno-badge">{{ $toDigits($page['page']) }}</span></div>
        </section>
    @endforeach
</div>

<script>
    (function () {
        if (window.__mushafBound) { return; }
        window.__mushafBound = true;

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

        {{-- Fit-to-width: scale each full line's font so it fills the column with
             natural spacing — avoids the big justify gaps of a non-glyph font.
             Batched (reset → read → write) to limit layout thrashing. --}}
        const MIN = 0.78, MAX = 1.4;
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
            measured.forEach((m) => {
                if (!m.base || !m.target || !m.natural) { return; }
                const ratio = Math.max(MIN, Math.min(m.target / m.natural, MAX));
                m.l.style.fontSize = (m.base * ratio).toFixed(2) + 'px';
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
