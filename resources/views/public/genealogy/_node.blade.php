{{--
    Recursive genealogy tree node.

    Props:
        $node  — plain array: id, slug, name, title, type, typeLabel, isRoot, isProphet, children[]
        $depth — current depth (0 = root)
--}}
@php
    $children = $node['children'] ?? [];
    $isRoot   = ! empty($node['isRoot']);
    // Avatar palette cycles by depth + position for visual variety (matches prototype gradients).
    $palettes = [
        'from-[#0056b3] to-[#218838]',
        'from-pink-500 to-rose-500',
        'from-purple-500 to-pink-500',
        'from-amber-500 to-orange-500',
        'from-teal-500 to-cyan-500',
        'from-indigo-500 to-purple-500',
        'from-emerald-500 to-teal-500',
        'from-blue-400 to-cyan-400',
    ];
    $palette = $isRoot ? $palettes[0] : $palettes[($depth + ($index ?? 0)) % count($palettes)];
    $avatarIcon = $node['gender'] === 'female' ? 'user' : 'user';
@endphp

<div class="flex flex-col items-center shrink-0">
    {{-- Person node card --}}
    <button type="button"
            @click="openPerson({{ (int) $node['id'] }})"
            class="group relative node-card rounded-2xl border border-transparent
                   bg-white dark:bg-[#16213e] shadow-lg hover:shadow-2xl
                   {{ $isRoot ? 'p-6 sm:p-8' : 'p-4 sm:p-5' }} transition-all">
        <div class="flex flex-col items-center gap-3">
            <div class="{{ $isRoot ? 'w-20 h-20 sm:w-24 sm:h-24 genealogy-glow' : 'w-14 h-14 sm:w-16 sm:h-16' }}
                        bg-gradient-to-br {{ $palette }} rounded-full flex items-center justify-center shadow-lg">
                <i data-lucide="{{ $isRoot ? 'crown' : $avatarIcon }}"
                   class="{{ $isRoot ? 'w-10 h-10 sm:w-12 sm:h-12' : 'w-7 h-7 sm:w-8 sm:h-8' }} text-white"></i>
            </div>
            <div class="text-center max-w-[10rem]">
                <h3 class="{{ $isRoot ? 'text-xl sm:text-2xl' : 'text-sm sm:text-base' }} font-extrabold leading-tight
                           text-gray-900 dark:text-white line-clamp-2">
                    {{ $node['name'] }}
                </h3>
                @if (! empty($node['title']) || $isRoot)
                    <p class="text-xs mt-1 font-semibold text-accent line-clamp-1">
                        {{ $isRoot ? __('genealogy.public.prophet_relation') : $node['title'] }}
                    </p>
                @elseif (! empty($node['typeLabel']))
                    <p class="text-xs mt-1 text-gray-500 dark:text-gray-400 line-clamp-1">{{ $node['typeLabel'] }}</p>
                @endif
            </div>
        </div>
    </button>

    {{-- Children --}}
    @if (! empty($children))
        <div class="relative pt-8 mt-2">
            {{-- Vertical connector from this node down to the horizontal bar --}}
            <span class="absolute top-0 start-1/2 -translate-x-1/2 w-px h-8 bg-gradient-to-b from-primary/60 to-secondary/60"></span>
            {{-- Horizontal bar spanning children (only when more than one) --}}
            @if (count($children) > 1)
                <span class="absolute top-8 inset-x-6 h-px bg-gradient-to-r from-transparent via-primary/50 to-transparent"></span>
            @endif

            <div class="flex flex-wrap justify-center gap-6 sm:gap-8 pt-8">
                @foreach ($children as $i => $child)
                    <div class="relative flex flex-col items-center">
                        {{-- Vertical connector up to the horizontal bar --}}
                        <span class="absolute -top-8 start-1/2 -translate-x-1/2 w-px h-8 bg-gradient-to-b from-primary/60 to-secondary/60"></span>
                        @include('public.genealogy._node', ['node' => $child, 'depth' => $depth + 1, 'index' => $i])
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
