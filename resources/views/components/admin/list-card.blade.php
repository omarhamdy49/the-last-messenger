{{--
    Mobile card row for the admin list tables.

    The tables live inside `overflow-x-auto`, which on a phone means the editor
    scrolls sideways through seven columns to reach the actions — usable, but not
    what the rest of the panel feels like. Below `md` each list renders this card
    instead: the identity line stays put, the secondary columns become a wrapped
    meta row, and the actions sit where a thumb reaches them.

    Slots: default = badges/meta chips, `actions` = the row's action buttons.
--}}
@props([
    'icon' => 'circle',
    'gradient' => 'from-[#0056b3] to-[#218838]',
    'title' => '',
    'subtitle' => null,
    'value' => null,
    'href' => null,
])

<div class="p-4 rounded-xl border transition-colors"
     :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
    <div class="flex items-start gap-3">
        @if ($value !== null)
            <label class="pt-1 flex-shrink-0">
                <input type="checkbox" value="{{ $value }}" x-model.number="selected"
                       class="w-4 h-4 rounded text-accent border-gray-300 focus:ring-accent">
            </label>
        @endif

        <div class="w-10 h-10 rounded-lg bg-gradient-to-br {{ $gradient }} flex items-center justify-center flex-shrink-0">
            <i data-lucide="{{ $icon }}" class="w-5 h-5 text-white"></i>
        </div>

        <div class="min-w-0 flex-1">
            <h4 class="font-semibold break-words" :class="darkMode ? 'text-white' : 'text-gray-900'">
                @if ($href)
                    <a href="{{ $href }}" class="transition-colors hover:text-accent hover:underline underline-offset-4 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent rounded">{{ $title }}</a>
                @else
                    {{ $title }}
                @endif
            </h4>
            @if ($subtitle)
                <p class="text-xs mt-0.5 break-words" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">
                    {{ $subtitle }}
                </p>
            @endif
        </div>
    </div>

    @if (trim($slot) !== '')
        <div class="mt-3 flex flex-wrap items-center gap-2">
            {{ $slot }}
        </div>
    @endif

    @isset($actions)
        <div class="mt-3 pt-3 flex items-center gap-1 border-t"
             :class="darkMode ? 'border-gray-800' : 'border-gray-100'">
            {{ $actions }}
        </div>
    @endisset
</div>
