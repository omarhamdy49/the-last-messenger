{{-- On/off pill. --}}
@props(['on' => false, 'onLabel' => null, 'offLabel' => null])
@if ($on)
    <span class="inline-flex rounded-full bg-green-100 px-2 py-0.5 text-xs font-semibold text-green-800">{{ $onLabel ?? __('quran.manage.yes') }}</span>
@else
    <span class="inline-flex rounded-full bg-gray-200 px-2 py-0.5 text-xs font-semibold text-gray-700">{{ $offLabel ?? __('quran.manage.no') }}</span>
@endif
