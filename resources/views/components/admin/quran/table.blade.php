{{-- Data table: `head` slot holds <th> cells, default slot holds <tr> rows, optional `footer`. --}}
@props(['empty' => false, 'emptyText' => null])
<div {{ $attributes->merge(['class' => 'mb-6 rounded-2xl border overflow-x-auto']) }} :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
    @if ($empty)
        <p class="p-6 text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">{{ $emptyText ?? __('quran.manage.empty') }}</p>
    @else
        <table class="w-full text-sm">
            <thead :class="darkMode ? 'bg-gray-800/60 text-gray-300' : 'bg-gray-50 text-gray-600'">
                <tr class="[&>th]:px-4 [&>th]:py-3 [&>th]:text-start [&>th]:font-semibold">{{ $head }}</tr>
            </thead>
            <tbody class="divide-y [&_td]:px-4 [&_td]:py-3 [&_td]:align-top" :class="darkMode ? 'divide-gray-800 text-gray-200' : 'divide-gray-100 text-gray-800'">
                {{ $slot }}
            </tbody>
        </table>
    @endif
    @isset($footer)
        <div class="p-4">{{ $footer }}</div>
    @endisset
</div>
