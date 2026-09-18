{{--
    The shared editor behaviour for create and edit: dirty tracking, the section
    rail's scroll spy, and the leave guard.

    Extracted so the create page gets the same rail and sticky save bar as edit
    instead of a lone button at the bottom of a 500-line form.

    @param \Illuminate\Support\Collection $rail
--}}
@push('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('muslimEditor', () => ({
            submitting: false,
            dirty: false,
            active: '{{ $rail->first()['id'] }}',

            init() {
                lucide.createIcons();
                this.watchChanges();
                this.watchScroll();
                this.warnOnLeave();
            },

            /** Any input touching the form marks it dirty. */
            watchChanges() {
                const form = document.getElementById('muslim-form');
                if (! form) return;

                ['input', 'change'].forEach((event) => {
                    form.addEventListener(event, () => { this.dirty = true; }, { passive: true });
                });
            },

            /**
             * Keep the rail in step with what is on screen.
             *
             * A scroll listener on the real scroll container, not an
             * IntersectionObserver: the admin layout scrolls an inner <main>,
             * and an observer rooted on the viewport did not track it here.
             * Picking the last section past the marker line is also easier to
             * reason about than intersection ratios.
             */
            watchScroll() {
                const sections = [...document.querySelectorAll('.form-section[id]')];
                if (sections.length < 2) return;

                const scroller = document.querySelector('main') ?? window;
                let queued = false;

                const update = () => {
                    queued = false;
                    // A quarter down the viewport reads as "the section I am on".
                    const marker = window.innerHeight * 0.25;
                    let current = sections[0].id;

                    for (const section of sections) {
                        if (section.getBoundingClientRect().top <= marker) {
                            current = section.id;
                        }
                    }

                    // At the bottom the marker can never reach the final
                    // section, so it would never light up. Treat "scrolled to
                    // the end" as being on the last one.
                    const atEnd = scroller === window
                        ? window.innerHeight + window.scrollY >= document.body.scrollHeight - 4
                        : scroller.scrollTop + scroller.clientHeight >= scroller.scrollHeight - 4;

                    this.active = atEnd ? sections[sections.length - 1].id : current;
                };

                scroller.addEventListener('scroll', () => {
                    // rAF-throttled: scroll fires far more often than a frame.
                    if (queued) return;
                    queued = true;
                    requestAnimationFrame(update);
                }, { passive: true });

                update();
            },

            /**
             * Guard against losing edits to a stray back/close. Cleared on
             * submit, otherwise saving would trip the warning on its own
             * navigation.
             */
            warnOnLeave() {
                const form = document.getElementById('muslim-form');
                form?.addEventListener('submit', () => { this.dirty = false; });

                window.addEventListener('beforeunload', (event) => {
                    if (! this.dirty) return;
                    event.preventDefault();
                    event.returnValue = '';
                });
            },
        }));
    });
</script>
@endpush
