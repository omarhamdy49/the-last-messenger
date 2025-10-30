{{-- Genealogy Tree Component (D3.js) --}}
@props(['rootPerson'])
<div x-data="genealogyTree()" {{ $attributes->merge(['class' => 'relative rounded-2xl shadow-xl overflow-hidden']) }} :class="darkMode ? 'bg-gray-900 border border-gray-800' : 'bg-white'">
    <div class="absolute top-4 right-4 z-10 flex gap-2">
        <button @click="zoomIn" class="p-2 rounded-lg glass text-white hover:bg-white/10"><i class="w-5 h-5" data-lucide="zoom-in"></i></button>
        <button @click="zoomOut" class="p-2 rounded-lg glass text-white hover:bg-white/10"><i class="w-5 h-5" data-lucide="zoom-out"></i></button>
        <button @click="resetZoom" class="p-2 rounded-lg glass text-white hover:bg-white/10"><i class="w-5 h-5" data-lucide="maximize-2"></i></button>
    </div>
    <div x-ref="treeContainer" class="w-full h-[600px]"></div>
</div>
@push('scripts')
<script src="https://d3js.org/d3.v7.min.js"></script>
<script>
function genealogyTree() {
    return {
        svg: null,
        zoom: null,
        init() {
            this.renderTree(@json($rootPerson));
        },
        renderTree(data) {
            // D3.js tree implementation
        },
        zoomIn() { this.zoom.scaleBy(this.svg.transition().duration(300), 1.3); },
        zoomOut() { this.zoom.scaleBy(this.svg.transition().duration(300), 0.7); },
        resetZoom() { this.svg.transition().duration(300).call(this.zoom.transform, d3.zoomIdentity); }
    };
}
</script>
@endpush
