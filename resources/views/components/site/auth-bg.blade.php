{{--
    Full-viewport "living geometry" background for guest/auth pages.
    Brand gradient + a slowly drifting/breathing Islamic eight-point (khatam) girih
    tessellation and a soft light. All motion is GPU-only (transform/opacity) and fully
    disabled under prefers-reduced-motion, where it stays a clean static gradient.
    Also defines the .auth-stagger field-cascade used by the auth card.
--}}
@once
{{-- Inline (not @push('head')) on purpose: this component renders in the layout body,
     after the head's @stack('head') has already output, so a push would be dropped.
     A <style> in the body is valid and applies globally. --}}
<style>
    .auth-fullbg .auth-girih { position: absolute; inset: -4rem; width: calc(100% + 8rem); height: calc(100% + 8rem); }
    .auth-glow {
        background:
            radial-gradient(38% 42% at 18% 12%, rgba(255,255,255,.18), transparent 60%),
            radial-gradient(46% 46% at 84% 88%, rgba(100,204,197,.16), transparent 60%);
    }

    @media (prefers-reduced-motion: no-preference) {
        /* Girih drifts exactly one tile (56px) → seamless infinite loop. */
        .auth-fullbg .auth-girih { animation: authGirihDrift 60s linear infinite; will-change: transform; }
        @keyframes authGirihDrift { from { transform: translate3d(0,0,0); } to { transform: translate3d(56px,56px,0); } }

        .auth-fullbg .auth-girih rect[fill^="url"] { animation: authGirihBreathe 10s ease-in-out infinite; }
        @keyframes authGirihBreathe { 0%,100% { opacity: .09; } 50% { opacity: .16; } }

        .auth-glow { animation: authGlowDrift 18s ease-in-out infinite alternate; will-change: transform, opacity; }
        @keyframes authGlowDrift { from { transform: translate3d(0,0,0); opacity: .95; } to { transform: translate3d(6%,4%,0); opacity: .6; } }

        /* Auth-card field cascade (expo-out, no bounce). */
        .auth-stagger > * { animation: authFieldIn .7s cubic-bezier(.16,1,.3,1) both; }
        .auth-stagger > *:nth-child(1) { animation-delay: .05s; }
        .auth-stagger > *:nth-child(2) { animation-delay: .11s; }
        .auth-stagger > *:nth-child(3) { animation-delay: .17s; }
        .auth-stagger > *:nth-child(4) { animation-delay: .23s; }
        .auth-stagger > *:nth-child(5) { animation-delay: .29s; }
        .auth-stagger > *:nth-child(6) { animation-delay: .35s; }
        .auth-stagger > *:nth-child(7) { animation-delay: .41s; }
        .auth-stagger > *:nth-child(8) { animation-delay: .47s; }
        @keyframes authFieldIn { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: none; } }
    }
</style>
@endonce

<div class="auth-fullbg fixed inset-0 z-0 overflow-hidden pointer-events-none
            bg-gradient-to-br from-[#0056b3] via-[#0a6aa6] to-[#218838]
            dark:from-[#06243f] dark:via-[#0a2e3a] dark:to-[#0b3320]" aria-hidden="true">

    {{-- Islamic eight-point (khatam) girih tessellation --}}
    <svg class="auth-girih text-white" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
        <defs>
            <pattern id="auth-girih-pat" width="56" height="56" patternUnits="userSpaceOnUse">
                <g fill="none" stroke="currentColor" stroke-width="1">
                    <rect x="14" y="14" width="28" height="28"/>
                    <rect x="14" y="14" width="28" height="28" transform="rotate(45 28 28)"/>
                </g>
                <circle cx="28" cy="28" r="2" fill="currentColor" stroke="none" opacity="0.7"/>
            </pattern>
        </defs>
        <rect width="100%" height="100%" fill="url(#auth-girih-pat)" opacity="0.10"/>
    </svg>

    {{-- Soft drifting light --}}
    <div class="auth-glow absolute inset-0"></div>
</div>
