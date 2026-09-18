{{--
    YouTube videos: one framed player, or a stage plus a queue for two or more.
    Iframes are created on demand (the poster is a button) so the page doesn't load
    YouTube's player bundle per video. Expects $videos: list of {id,title,thumb,embedUrl,watchUrl}.
--}}
<x-public.editorial.section id="videos" icon="list-video" :title="count($videos) > 1 ? __('articles.public.video_playlist') : __('articles.public.video')">
    <div x-data="{ vi: 0, started: false, videos: @js($videos), get current() { return this.videos[this.vi]; }, play(i) { this.vi = i; this.started = true; } }"
         @class(['grid gap-4', 'lg:grid-cols-[minmax(0,1fr)_18rem]' => count($videos) > 1])>
        <div class="min-w-0">
            <div class="relative aspect-video overflow-hidden rounded-2xl bg-slate-950">
                <template x-if="!started">
                    <button type="button" @click="started = true" class="group absolute inset-0 h-full w-full" :aria-label="current.title">
                        <img :src="current.thumb" alt="" loading="lazy" class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-[1.03]">
                        <span class="absolute inset-0 grid place-items-center bg-black/25">
                            <span class="grid h-16 w-16 place-items-center rounded-full text-white shadow-2xl transition-transform group-hover:scale-110" style="background: var(--sx-grad)"><i data-lucide="play" class="ms-0.5 h-7 w-7" aria-hidden="true"></i></span>
                        </span>
                    </button>
                </template>
                <template x-if="started">
                    <iframe :key="current.id" :src="current.embedUrl + '&autoplay=1'" :title="current.title" class="absolute inset-0 h-full w-full" frameborder="0"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                            referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>
                </template>
            </div>
            <div class="mt-3 flex items-start justify-between gap-3">
                <p class="sx-clamp-2 min-w-0 font-bold text-[color:var(--sx-ink)]" x-text="current.title" :title="current.title"></p>
                <a :href="current.watchUrl" target="_blank" rel="noopener noreferrer" class="sx-btn shrink-0 !min-h-9 !px-3 !text-xs">
                    <i data-lucide="external-link" aria-hidden="true"></i>{{ __('articles.public.watch_on_youtube') }}
                </a>
            </div>
        </div>

        @if (count($videos) > 1)
            <ol class="sx-video-queue sx-scroll" aria-label="{{ __('articles.public.video_playlist') }}">
                <template x-for="(v, i) in videos" :key="v.id">
                    <li>
                        <button type="button" @click="play(i)" :aria-current="(vi === i).toString()" :title="v.title">
                            <span class="relative aspect-video w-24 shrink-0 overflow-hidden rounded-lg bg-[color:var(--sx-surface-2)]">
                                <img :src="v.thumb" alt="" loading="lazy" class="h-full w-full object-cover">
                            </span>
                            <span class="min-w-0">
                                <span class="block text-xs font-bold tabular-nums text-[color:var(--sx-muted)]" x-text="i + 1"></span>
                                <span class="sx-clamp-2 text-sm font-bold text-[color:var(--sx-ink)]" x-text="v.title"></span>
                            </span>
                        </button>
                    </li>
                </template>
            </ol>
        @endif
    </div>
</x-public.editorial.section>
