{{-- Sources & references. @props sources, index --}}
@props(['sources', 'index' => null])

@if (collect($sources)->isNotEmpty())
    <x-public.seerah.section id="sources" icon="library" :title="__('seerah.public.section_sources')" :index="$index">
        <ol class="sx-sources">
            @foreach ($sources as $source)
                <li>
                    <div class="min-w-0">
                        <strong class="break-words">{{ $source['title'] }}</strong>
                        @if (! empty($source['author']) || ! empty($source['reference']))
                            <span class="break-words">{{ collect([$source['author'] ?? null, $source['reference'] ?? null])->filter()->implode(' — ') }}</span>
                        @endif
                    </div>
                </li>
            @endforeach
        </ol>
    </x-public.seerah.section>
@endif
