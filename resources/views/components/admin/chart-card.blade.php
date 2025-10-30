{{-- Chart Card Component --}}
@props(['title', 'type' => 'line', 'data' => [], 'height' => 300])
<div {{ $attributes->merge(['class' => 'p-6 rounded-2xl shadow-xl']) }} :class="darkMode ? 'bg-gray-900 border border-gray-800' : 'bg-white'">
    <div class="flex items-center justify-between mb-6">
        <h3 class="text-xl font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $title }}</h3>
        <div class="flex gap-2">
            <button class="p-2 rounded-lg hover:bg-white/10 transition"><i class="w-4 h-4" data-lucide="download"></i></button>
            <button class="p-2 rounded-lg hover:bg-white/10 transition"><i class="w-4 h-4" data-lucide="maximize-2"></i></button>
        </div>
    </div>
    <canvas id="chart-{{ Str::slug($title) }}" style="height: {{ $height }}px;"></canvas>
</div>
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
new Chart(document.getElementById('chart-{{ Str::slug($title) }}'), {
    type: '{{ $type }}',
    data: @json($data),
    options: {
        responsive: true,
        maintainAspectRatio: false
    }
});
</script>
@endpush
