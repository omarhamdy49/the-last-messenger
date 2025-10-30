{{-- Timeline Component --}}
@props(['events', 'phaseMarkers' => true])
<div {{ $attributes->merge(['class' => 'relative space-y-8']) }}>
    <div class="absolute left-2 top-0 bottom-0 w-0.5 bg-gradient-to-b from-[#0056b3] via-[#218838] to-[#64CCC5]"></div>
    @foreach($events as $event)
    <x-cards.timeline-event-card :event="$event" :showPhase="$phaseMarkers" />
    @endforeach
</div>
