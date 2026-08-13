<style>
    /* Rich-text body authored in the admin Quill editor. Plain CSS rather than
       `prose` utilities because @tailwindcss/typography is not a dependency of
       this project. Shared by the article and timeline event views. */
    .seerah-content > *:first-child { margin-top: 0; }
    .seerah-content > *:last-child { margin-bottom: 0; }
    .seerah-content p { margin: 0 0 1.25em; }
    .seerah-content h2 { font-size: 1.5em; font-weight: 700; margin: 1.6em 0 .6em; line-height: 1.35; }
    .seerah-content h3 { font-size: 1.25em; font-weight: 700; margin: 1.4em 0 .5em; line-height: 1.4; }
    .seerah-content h4 { font-size: 1.1em; font-weight: 600; margin: 1.2em 0 .4em; }
    .seerah-content ul,
    .seerah-content ol { margin: 0 0 1.25em; padding-inline-start: 1.5em; }
    .seerah-content ul { list-style: disc; }
    .seerah-content ol { list-style: decimal; }
    .seerah-content li { margin-bottom: .4em; }
    .seerah-content blockquote {
        margin: 1.5em 0;
        padding: .75em 1.25em;
        border-inline-start: 4px solid #218838;
        background: rgba(33, 136, 56, .06);
        border-radius: 0 .75rem .75rem 0;
        font-style: italic;
    }
    .seerah-content a { color: #0056b3; text-decoration: underline; }
    .seerah-content a:hover { opacity: .8; }
    .dark .seerah-content blockquote { background: rgba(33, 136, 56, .12); }
    .dark .seerah-content a { color: #64CCC5; }
</style>
