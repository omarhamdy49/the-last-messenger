{{--
    The mushaf page look: centred one-line rows, the rosette verse marker, the page-number
    badge, and the glyph / Unicode switch. Plain CSS rules (no <style> tag) so both the
    public reader and the admin mushaf preview include it inside their own style block and
    cannot drift apart.
--}}
    /* Mushaf glyph mode (QCF V2): each word shows its page-font glyph instead of Unicode. */
    .mushaf .qw-glyph { display: none; }
    .mushaf.glyph-mode .qw-uni, .mushaf.glyph-mode .ayah-end-num { display: none; }
    .mushaf.glyph-mode .qw-glyph { display: inline; }
    .mushaf.glyph-mode .ayah-end { background-image: none; width: auto; height: auto; }

    /* ---- Mushaf reading view (line-for-line, like a printed mushaf) ---- */
    /* Width cap lives here (not a Tailwind arbitrary class) so it works without a
       Vite rebuild and keeps lines narrow enough to fill via fit-to-width. */
    .mushaf-card { max-width: 40rem; }
    .mushaf {
        -webkit-font-smoothing: antialiased;
        text-rendering: optimizeLegibility;
    }
    .mushaf-bismillah {
        text-align: center;
        font-size: 1.4em;
        line-height: 2;
        margin-bottom: 1.75rem;
        opacity: 0.92;
    }
    .mushaf-page + .mushaf-page { margin-top: 0.25rem; }
    /* Each printed-mushaf line stays on one line; a script scales its font so the
       line fills the column width with natural word spacing (no justify gaps). */
    .mushaf-line {
        white-space: nowrap;
        text-align: center;
        line-height: 2.5;
    }
    /* The last (partial) line of a page wraps/centres naturally, no fit-scaling. */
    .mushaf-line.mushaf-line-end {
        white-space: normal;
        text-align: center;
    }
    .mushaf .qw {
        cursor: pointer;
        border-radius: 0.25em;
        padding: 0.02em 0.04em;
        transition: background-color 0.18s ease, color 0.18s ease;
    }
    /* Whole-ayah lift on hover (set by the partial's delegated listener). */
    .mushaf .qw.qw-verse-active {
        background-color: rgba(100, 204, 197, 0.16);
        color: #0056b3;
    }
    .mushaf-dark .qw.qw-verse-active { color: #a7f3ef; }

    /* Ayah being recited — stronger than the hover lift, and it stays put. */
    .mushaf .qw.qw-verse-playing {
        background-color: rgba(33, 136, 56, 0.16);
        color: #17693a;
    }
    .mushaf-dark .qw.qw-verse-playing {
        background-color: rgba(100, 204, 197, 0.2);
        color: #7ee7dd;
    }
    .mushaf .ayah-end.ayah-end-playing { filter: drop-shadow(0 0 4px rgba(33, 136, 56, 0.5)); }
    .mushaf-dark .ayah-end.ayah-end-playing { filter: drop-shadow(0 0 4px rgba(100, 204, 197, 0.55)); }
    .verse-card.is-playing { box-shadow: 0 0 0 2px #218838, 0 10px 30px -10px rgba(33, 136, 56, 0.45); }

    /* Verse-end rosette — our ornament (not a glyph font), holds the verse number. */
    .mushaf .ayah-end {
        position: relative;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 2em;
        height: 2em;
        margin: 0 0.12em;
        vertical-align: middle;
        user-select: none;
        cursor: pointer;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 48 48'%3E%3Cg fill='none' stroke='%23c9a227' stroke-width='1.1'%3E%3Ccircle cx='24' cy='24' r='15'/%3E%3Ccircle cx='24' cy='24' r='20' stroke-width='1' stroke-dasharray='0.6 2.6' stroke-linecap='round'/%3E%3C/g%3E%3C/svg%3E");
        background-size: contain;
        background-repeat: no-repeat;
        background-position: center;
    }
    .mushaf .ayah-end-num {
        font-family: 'Cairo', sans-serif;
        font-size: 0.46em;
        font-weight: 700;
        line-height: 1;
        color: #b08515;
    }
    .mushaf-dark .ayah-end-num { color: #e7c66a; }

    .mushaf-pageno {
        text-align: center;
        margin-top: 1.5rem;
    }
    .mushaf-pageno-badge {
        display: inline-block;
        min-width: 2.25rem;
        padding: 0.15rem 0.6rem;
        font-family: 'Cairo', sans-serif;
        font-size: 0.8rem;
        font-weight: 600;
        color: #9a7b1f;
        border: 1px solid rgba(201, 162, 39, 0.4);
        border-radius: 9999px;
    }
    .mushaf-dark .mushaf-pageno-badge { color: #e7c66a; border-color: rgba(201, 162, 39, 0.35); }
    .mushaf-page:not(:last-child) .mushaf-pageno {
        padding-bottom: 1.5rem;
        border-bottom: 1px solid rgba(120, 120, 120, 0.16);
    }
