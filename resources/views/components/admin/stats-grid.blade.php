{{-- Stats Grid Component --}}
@props(['stats'])
<div {{ $attributes->merge(['class' => 'grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6']) }}>
    @foreach($stats as $stat)
    <x-stats-card :icon="$stat['icon']" :value="$stat['value']" :label="$stat['label']" :color="$stat['color'] ?? 'blue'" />
    @endforeach
</div>
