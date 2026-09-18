{{--
    Shared "Ask Quran" answer renderer.

    Defines window.renderQuranAnswer(markdown, verseBase, trustHtml=false) —
    converts the model's Markdown answer into safe, premium HTML and turns inline
    verse markers ([[2:255]] / [[2:255-257]]) into "go to verse" links on our surah
    reader (/quran/{surah}#v{ayah}). XSS-safe: the source is HTML-escaped first; the
    only markup emitted is our own tags and anchors whose href is built from digits.

    trustHtml=false (the default, and what the PUBLIC page always uses) never emits
    raw input — model answers are markdown and flow into a cross-user cache, so they
    must not be trusted. Pass trustHtml=true ONLY for already-sanitized, admin-only
    content (the moderation preview), where stored HTML should render as authored.

    Inlined (no build step) so it works identically on the Vite-compiled public
    site and the CDN-driven admin panel. Include once per page that renders an
    answer; the guard makes a second include a no-op.
--}}
<style>
    .qa-prose { font-size: 15px; line-height: 1.85; word-break: break-word; }
    .qa-prose > *:first-child { margin-top: 0; }
    .qa-prose > *:last-child { margin-bottom: 0; }
    .qa-prose p { margin: 0 0 0.85rem; }
    .qa-prose h3, .qa-prose h4, .qa-prose h5 { font-weight: 700; line-height: 1.4; margin: 1.25rem 0 0.6rem; }
    .qa-prose h3 { font-size: 1.15rem; }
    .qa-prose h4 { font-size: 1.05rem; }
    .qa-prose h5 { font-size: 0.95rem; }
    .qa-prose strong { font-weight: 700; }
    .qa-prose ul, .qa-prose ol { margin: 0 0 0.85rem; padding-inline-start: 1.4rem; }
    .qa-prose li { margin: 0.3rem 0; }
    .qa-prose ul li { list-style: none; position: relative; }
    .qa-prose ul li::before { content: ''; position: absolute; inset-inline-start: -1rem; top: 0.65em; width: 6px; height: 6px; border-radius: 9999px; background: #64CCC5; }
    .qa-prose ol { list-style: decimal; }
    .qa-prose code { font-family: ui-monospace, monospace; font-size: 0.85em; padding: 0.1em 0.4em; border-radius: 6px; background: rgba(100,204,197,0.15); }
    .qa-prose blockquote {
        margin: 0.85rem 0; padding: 0.75rem 1rem; border-inline-start: 3px solid #64CCC5; border-radius: 0 0.75rem 0.75rem 0;
        background: rgba(100,204,197,0.08); font-family: 'Amiri', serif; font-size: 1.15rem; line-height: 2;
    }
    .qa-prose a.qa-verse {
        display: inline-flex; align-items: center; gap: 0.25rem; margin: 0 0.15rem; padding: 0.05rem 0.45rem;
        border-radius: 9999px; font-size: 0.78em; font-weight: 700; text-decoration: none; vertical-align: middle;
        white-space: nowrap; background: linear-gradient(135deg, rgba(0,86,179,0.12), rgba(33,136,56,0.12));
        color: var(--sx-accent); border: 1px solid var(--sx-line-strong); transition: all 0.18s ease;
    }
    .qa-prose a.qa-verse:hover { background: var(--sx-grad); color: #fff; border-color: transparent; transform: translateY(-1px); }
    .qa-prose a.qa-verse svg { width: 0.85em; height: 0.85em; }
    .qa-prose.qa-dark code { background: rgba(100,204,197,0.18); }
    .qa-prose.qa-dark a.qa-verse { color: #7fe0d9; background: rgba(100,204,197,0.12); border-color: rgba(100,204,197,0.30); }
    .qa-prose.qa-dark a.qa-verse:hover { color: #06212a; background: #64CCC5; }
</style>
<script>
    if (!window.renderQuranAnswer) {
        window.renderQuranAnswer = function (markdown, verseBase, trustHtml) {
            if (!markdown) return '';

            const esc = (s) => s
                .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
            const base = String(verseBase || '/quran').replace(/\/+$/, '');

            // [[surah:ayah]] / [[surah:ayah-ayah]] -> go-to-verse link.
            const linkVerses = (text) => text.replace(
                /\[\[\s*(\d{1,3}):(\d{1,3})(?:\s*-\s*(\d{1,3}))?\s*\]\]/g,
                (m, s, a, b) => {
                    const label = b ? (s + ':' + a + '-' + b) : (s + ':' + a);
                    return '<a href="' + base + '/' + s + '#v' + a + '" class="qa-verse">'
                        + '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>'
                        + label + '</a>';
                });

            // Already-sanitized, admin-only HTML (the moderation preview): render as
            // authored, only linkifying verse markers. Never reached on the public
            // path — model answers there go through the escaping Markdown pipeline.
            if (trustHtml && /<\/?(p|div|h[1-6]|ul|ol|li|blockquote|strong|em|b|i|br|a|span|table)\b/i.test(markdown)) {
                return linkVerses(markdown);
            }

            // Inline spans, applied to already-escaped text.
            const inline = (text) => linkVerses(text)
                .replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>')
                .replace(/(^|[^*])\*([^*\n]+)\*(?!\*)/g, '$1<em>$2</em>')
                .replace(/`([^`]+)`/g, '<code>$1</code>');

            const lines = esc(markdown).replace(/\r\n?/g, '\n').split('\n');
            const out = [];
            let i = 0;

            const isHeading = (t) => /^(#{1,6})\s+/.test(t);
            // '>' has already been HTML-escaped to '&gt;' by this point.
            const isQuote = (t) => /^&gt;\s?/.test(t);
            const isUl = (t) => /^[-*]\s+/.test(t);
            const isOl = (t) => /^\d+\.\s+/.test(t);

            while (i < lines.length) {
                const t = lines[i].trim();
                if (t === '') { i++; continue; }

                const h = t.match(/^(#{1,6})\s+(.*)$/);
                if (h) {
                    // '#' and '##' render as the prominent <h3>; deeper levels step down.
                    const level = Math.min(Math.max(h[1].length, 2) + 1, 5);
                    out.push('<h' + level + '>' + inline(h[2]) + '</h' + level + '>');
                    i++; continue;
                }

                if (isQuote(t)) {
                    const buf = [];
                    while (i < lines.length && isQuote(lines[i].trim())) {
                        buf.push(inline(lines[i].trim().replace(/^&gt;\s?/, '')));
                        i++;
                    }
                    out.push('<blockquote>' + buf.join('<br>') + '</blockquote>');
                    continue;
                }

                if (isUl(t)) {
                    const buf = [];
                    while (i < lines.length && isUl(lines[i].trim())) {
                        buf.push('<li>' + inline(lines[i].trim().replace(/^[-*]\s+/, '')) + '</li>');
                        i++;
                    }
                    out.push('<ul>' + buf.join('') + '</ul>');
                    continue;
                }

                if (isOl(t)) {
                    const buf = [];
                    while (i < lines.length && isOl(lines[i].trim())) {
                        buf.push('<li>' + inline(lines[i].trim().replace(/^\d+\.\s+/, '')) + '</li>');
                        i++;
                    }
                    out.push('<ol>' + buf.join('') + '</ol>');
                    continue;
                }

                const buf = [];
                while (i < lines.length) {
                    const cur = lines[i].trim();
                    if (cur === '' || isHeading(cur) || isQuote(cur) || isUl(cur) || isOl(cur)) break;
                    buf.push(inline(cur));
                    i++;
                }
                out.push('<p>' + buf.join('<br>') + '</p>');
            }

            return out.join('');
        };
    }
</script>
