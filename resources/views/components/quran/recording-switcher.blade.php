{{--
    Recording switcher — one card per recording (style · riwayah) of a reciter
    profile. Plain links, not a form: every recording stays deep-linkable and
    works without JS, and the #recordings fragment returns the reader here.

    @props
    - variants:   Collection<Reciter> with `surahs_count` (withCount)
    - selectedId: id of the recording being played
    - slug:       the profile's canonical slug
--}}
@props(['variants', 'selectedId', 'slug'])

@if($variants->count() > 1)
@php $styleIcons = ['murattal' => 'mic', 'mujawwad' => 'sparkles', 'muallim' => 'graduation-cap']; @endphp
<section id="recordings" class="mb-10 scroll-mt-24" aria-labelledby="recordings-heading">
    <div class="flex flex-wrap items-end justify-between gap-2 mb-5">
        <div>
            <h2 id="recordings-heading" class="sx-section-title flex items-center gap-3">
                <span class="w-1 h-7 rounded-full" style="background: var(--sx-grad)"></span>
                {{ __('quran.recordings.choose') }}
            </h2>
            <p class="mt-1.5 ms-4 text-sm text-[color:var(--sx-ink-2)]">{{ __('quran.recordings.choose_hint') }}</p>
        </div>
        <span class="text-sm font-semibold text-[color:var(--sx-muted)] tabular-nums">{{ $variants->count() }}</span>
    </div>

    <ul class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3" role="list">
        @foreach($variants as $variant)
            @php $isSelected = $variant->id === $selectedId; @endphp
            <li>
                <a href="{{ route('public.quran.reciter', ['slug' => $slug, 'recording' => $variant->id]) }}#recordings"
                   @if($isSelected) aria-current="true" @endif
                   class="group relative flex items-center gap-4 min-h-[4.5rem] rounded-2xl p-4 border transition-all duration-300 focus:outline-none focus-visible:ring-2 focus-visible:ring-[color:var(--sx-accent)] focus-visible:ring-offset-2 dark:focus-visible:ring-offset-gray-950 motion-reduce:transition-none
                          {{ $isSelected
                              ? 'border-transparent [background:var(--sx-grad)] text-white shadow-[var(--sx-shadow)]'
                              : 'bg-[color:var(--sx-surface)] border-[color:var(--sx-line)] text-[color:var(--sx-ink)] shadow-sm hover:shadow-md hover:-translate-y-0.5 hover:border-[color:var(--sx-accent)] motion-reduce:transform-none' }}">
                    <span class="w-11 h-11 shrink-0 rounded-xl flex items-center justify-center transition-colors
                                 {{ $isSelected ? 'bg-white/20 text-white' : 'bg-[color:var(--sx-accent-soft)] text-[color:var(--sx-accent)] group-hover:[background:var(--sx-grad)] group-hover:text-white' }}">
                        <i data-lucide="{{ $styleIcons[$variant->style] ?? 'music-4' }}" class="w-5 h-5" aria-hidden="true"></i>
                    </span>

                    <span class="flex-1 min-w-0">
                        <span class="block font-bold leading-snug">
                            {{ $variant->style ? __('quran.recordings.'.$variant->style) : __('quran.recordings.unspecified') }}
                        </span>
                        @if($variant->riwayah)
                            <span class="mt-0.5 flex items-center gap-1.5 text-sm {{ $isSelected ? 'text-white/85' : 'text-[color:var(--sx-ink-2)]' }}">
                                <i data-lucide="book-open" class="w-3.5 h-3.5 shrink-0" aria-hidden="true"></i>
                                <span class="truncate" dir="auto">{{ $variant->riwayah }}</span>
                            </span>
                        @endif
                    </span>

                    <span class="shrink-0 flex flex-col items-end gap-1">
                        <span class="inline-flex items-center gap-1 text-xs font-semibold px-2.5 py-1 rounded-full tabular-nums
                                     {{ $isSelected ? 'bg-white/20 text-white' : 'bg-[color:var(--sx-surface-2)] text-[color:var(--sx-ink-2)]' }}">
                            <i data-lucide="audio-lines" class="w-3.5 h-3.5" aria-hidden="true"></i>
                            {{ __('quran.recordings.surahs_count', ['count' => $variant->surahs_count]) }}
                        </span>
                        @if($isSelected)
                            <span class="inline-flex items-center gap-1 text-xs font-semibold text-white">
                                <i data-lucide="check-circle-2" class="w-3.5 h-3.5" aria-hidden="true"></i>
                                {{ __('quran.recordings.selected') }}
                            </span>
                        @endif
                    </span>
                </a>
            </li>
        @endforeach
    </ul>
</section>
@endif
