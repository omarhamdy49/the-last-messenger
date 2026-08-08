{{--
    One descendant node, rendered recursively.

    A nested <ul>/<li> rather than an SVG diagram: the tree is data an editor
    reads and acts on, so it has to be keyboard-navigable and legible to a
    screen reader. Indentation carries the depth, `aria-expanded` carries the
    collapse state.

    @param array $node  id, slug, name_ar, name_en, is_published, hide_from_tree, depth, children
--}}
@php
    $nodeName = app()->getLocale() === 'ar'
        ? ($node['name_ar'] ?: $node['name_en'])
        : ($node['name_en'] ?: $node['name_ar']);
    $hasChildren = ($node['children'] ?? []) !== [];
@endphp

<li x-data="{ open: true }">
    <div class="flex items-center gap-2 py-1.5">
        {{-- Disclosure. Always rendered so rows stay aligned; inert on leaves. --}}
        @if($hasChildren)
            <button type="button" @click="open = ! open"
                    :aria-expanded="open ? 'true' : 'false'"
                    class="w-7 h-7 shrink-0 grid place-items-center rounded-lg transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-accent"
                    :class="darkMode ? 'hover:bg-gray-700 text-gray-400' : 'hover:bg-gray-200 text-gray-500'"
                    :aria-label="open ? '{{ __('sahaba.show_less') }}' : '{{ __('sahaba.show_more') }}'">
                <i data-lucide="chevron-down" class="w-4 h-4 transition-transform duration-200"
                   :class="open ? '' : (document.dir === 'rtl' ? 'rotate-90' : '-rotate-90')"></i>
            </button>
        @else
            <span class="w-7 h-7 shrink-0 grid place-items-center" aria-hidden="true">
                <span class="w-1.5 h-1.5 rounded-full"
                      :class="darkMode ? 'bg-gray-700' : 'bg-gray-300'"></span>
            </span>
        @endif

        <a href="{{ route('admin.genealogy.show', $node['id']) }}"
           class="min-w-0 flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-sm transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-accent"
           :class="darkMode ? 'hover:bg-gray-800 text-gray-200' : 'hover:bg-gray-100 text-gray-800'">
            <span class="font-semibold truncate">{{ $nodeName ?: '—' }}</span>

            {{-- State is never carried by colour alone: each badge has a word. --}}
            @unless($node['is_published'])
                <span class="shrink-0 px-1.5 py-0.5 rounded text-[11px] font-semibold"
                      :class="darkMode ? 'bg-gray-700 text-gray-400' : 'bg-gray-200 text-gray-600'">
                    {{ __('sahaba.unpublished') }}
                </span>
            @endunless

            @if($node['hide_from_tree'])
                <span class="shrink-0 px-1.5 py-0.5 rounded text-[11px] font-semibold"
                      :class="darkMode ? 'bg-amber-500/20 text-amber-400' : 'bg-amber-100 text-amber-700'">
                    {{ __('sahaba.hide_from_tree') }}
                </span>
            @endif

            @if($hasChildren)
                <span class="shrink-0 text-[11px] tabular-nums" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">
                    ({{ count($node['children']) }})
                </span>
            @endif
        </a>
    </div>

    @if($hasChildren)
        {{-- Plain x-show: x-collapse leaves this one toggle behind the state it
             is bound to (collapse renders visible, re-expand renders hidden). --}}
        <ul x-show="open" class="ms-4 ps-3 border-s"
            :class="darkMode ? 'border-gray-800' : 'border-gray-200'">
            @foreach($node['children'] as $child)
                @include('admin.genealogy._node', ['node' => $child])
            @endforeach
        </ul>
    @endif
</li>
