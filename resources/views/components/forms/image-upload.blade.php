{{-- Image Upload Component --}}
@props(['name', 'label', 'current' => null, 'multiple' => false])
<div x-data="imageUpload()" {{ $attributes->merge(['class' => 'space-y-3']) }}>
    <label class="block text-sm font-semibold mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ $label }}</label>
    <div @dragover.prevent="dragging = true" @dragleave.prevent="dragging = false" @drop.prevent="handleDrop($event)" class="relative border-2 border-dashed rounded-xl p-8 text-center transition-all cursor-pointer hover:border-[#0056b3]" :class="[dragging ? 'border-[#0056b3] bg-[#0056b3]/5' : (darkMode ? 'border-gray-700 bg-gray-800/50' : 'border-gray-300 bg-gray-50')]" @click="$refs.fileInput.click()">
        <input type="file" x-ref="fileInput" @change="handleFiles($event)" {{ $multiple ? 'multiple' : '' }} accept="image/*" class="hidden" name="{{ $name }}">
        <div x-show="!previews.length" class="space-y-3">
            <div class="w-16 h-16 bg-gradient-to-br from-[#0056b3] to-[#218838] rounded-xl flex items-center justify-center mx-auto">
                <i class="w-8 h-8 text-white" data-lucide="upload"></i>
            </div>
            <p class="font-medium" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ __('forms.drag_drop_or_click') }}</p>
            <p class="text-sm" :class="darkMode ? 'text-gray-500' : 'text-gray-600'">PNG, JPG, WebP (max 5MB)</p>
        </div>
        <div x-show="previews.length" class="grid grid-cols-3 gap-4">
            <template x-for="(preview, index) in previews" :key="index">
                <div class="relative group">
                    <img :src="preview" class="w-full h-32 object-cover rounded-lg">
                    <button type="button" @click.stop="removeImage(index)" class="absolute top-2 right-2 w-8 h-8 bg-red-500 rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition">
                        <i class="w-4 h-4 text-white" data-lucide="x"></i>
                    </button>
                </div>
            </template>
        </div>
    </div>
    @if($current)<div class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">{{ __('forms.current_image') }}: <img src="{{ $current }}" class="inline-block w-20 h-20 object-cover rounded-lg ml-2"></div>@endif
</div>
@push('scripts')
<script>
function imageUpload() {
    return {
        dragging: false,
        previews: [],
        handleFiles(event) {
            const files = event.target.files;
            this.processFiles(files);
        },
        handleDrop(event) {
            this.dragging = false;
            const files = event.dataTransfer.files;
            this.processFiles(files);
        },
        processFiles(files) {
            Array.from(files).forEach(file => {
                if (file.size > 5 * 1024 * 1024) {
                    alert('File size exceeds 5MB');
                    return;
                }
                const reader = new FileReader();
                reader.onload = (e) => {
                    this.previews.push(e.target.result);
                };
                reader.readAsDataURL(file);
            });
        },
        removeImage(index) {
            this.previews.splice(index, 1);
        }
    };
}
</script>
@endpush
