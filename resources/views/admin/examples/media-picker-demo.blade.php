@extends('layouts.admin')

@section('page-header')
<div class="flex items-center justify-between">
    <div>
        <h1 class="text-3xl font-bold gradient-text mb-2">
            Media Picker Demo
        </h1>
        <p class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
            Interactive examples of the Media Picker component integration
        </p>
    </div>
</div>
@endsection

@section('content')
<div class="space-y-8">

    <!-- Example 1: Single Image Selection -->
    <section class="p-6 rounded-xl border"
             :class="darkMode ? 'bg-gray-900 border-gray-800' : 'bg-white border-gray-200'">
        <h2 class="text-xl font-bold mb-4 gradient-text">
            Example 1: Single Image Selection
        </h2>

        <div x-data="{
            selectedImage: null,
            imageId: null
        }" class="space-y-4">

            <button @click="MediaPicker.open({
                mode: 'single',
                type: 'image',
                onSelect: (media) => {
                    selectedImage = media;
                    imageId = media.id;
                }
            })"
                    class="px-6 py-3 rounded-lg font-semibold bg-gradient-to-r from-[#0056b3] to-[#218838] text-white hover:scale-105 transition-all shadow-lg shadow-accent/30 inline-flex items-center gap-2">
                <i data-lucide="image-plus" class="w-5 h-5"></i>
                <span>Choose Featured Image</span>
            </button>

            <!-- Preview -->
            <div x-show="selectedImage" class="mt-4">
                <p class="text-sm font-medium mb-2"
                   :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                    Selected Image:
                </p>
                <div class="flex items-start gap-4 p-4 rounded-lg border"
                     :class="darkMode ? 'bg-gray-800 border-gray-700' : 'bg-gray-50 border-gray-200'">
                    <img :src="selectedImage?.url"
                         :alt="selectedImage?.alt_text"
                         class="w-32 h-32 object-cover rounded-lg">
                    <div class="flex-1">
                        <p class="font-semibold mb-1"
                           :class="darkMode ? 'text-white' : 'text-gray-900'"
                           x-text="selectedImage?.original_filename"></p>
                        <p class="text-sm"
                           :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                            ID: <code x-text="imageId"></code>
                        </p>
                        <p class="text-sm"
                           :class="darkMode ? 'text-gray-400' : 'text-gray-600'"
                           x-text="selectedImage?.width + ' x ' + selectedImage?.height + ' px'">
                        </p>
                    </div>
                    <button @click="selectedImage = null; imageId = null"
                            class="p-2 rounded-lg transition-colors"
                            :class="darkMode ? 'hover:bg-gray-700 text-gray-400' : 'hover:bg-gray-200 text-gray-600'">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>
            </div>

            <!-- Code Example -->
            <details class="mt-4">
                <summary class="cursor-pointer font-semibold"
                         :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                    View Code
                </summary>
                <pre class="mt-2 p-4 rounded-lg overflow-x-auto text-sm"
                     :class="darkMode ? 'bg-gray-950 text-gray-300' : 'bg-gray-100 text-gray-800'"><code>&lt;button @click="MediaPicker.open({
    mode: 'single',
    type: 'image',
    onSelect: (media) => {
        selectedImage = media;
        imageId = media.id;
    }
})"&gt;
    Choose Featured Image
&lt;/button&gt;</code></pre>
            </details>
        </div>
    </section>

    <!-- Example 2: Multiple Images Selection -->
    <section class="p-6 rounded-xl border"
             :class="darkMode ? 'bg-gray-900 border-gray-800' : 'bg-white border-gray-200'">
        <h2 class="text-xl font-bold mb-4 gradient-text">
            Example 2: Multiple Images (Gallery)
        </h2>

        <div x-data="{
            galleryImages: []
        }" class="space-y-4">

            <button @click="MediaPicker.open({
                mode: 'multiple',
                type: 'image',
                onSelect: (images) => {
                    galleryImages = images;
                }
            })"
                    class="px-6 py-3 rounded-lg font-semibold bg-gradient-to-r from-[#0056b3] to-[#218838] text-white hover:scale-105 transition-all shadow-lg shadow-accent/30 inline-flex items-center gap-2">
                <i data-lucide="images" class="w-5 h-5"></i>
                <span>Build Gallery</span>
            </button>

            <!-- Gallery Preview -->
            <div x-show="galleryImages.length > 0" class="mt-4">
                <div class="flex items-center justify-between mb-3">
                    <p class="text-sm font-medium"
                       :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                        Gallery (<span x-text="galleryImages.length"></span> images)
                    </p>
                    <button @click="galleryImages = []"
                            class="text-sm text-red-500 hover:text-red-600 font-medium">
                        Clear Gallery
                    </button>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
                    <template x-for="(image, index) in galleryImages" :key="image.id">
                        <div class="group relative aspect-square rounded-lg overflow-hidden border-2"
                             :class="darkMode ? 'border-gray-700' : 'border-gray-200'">
                            <img :src="image.url"
                                 :alt="image.alt_text"
                                 class="w-full h-full object-cover">

                            <!-- Index Badge -->
                            <div class="absolute top-2 left-2 w-6 h-6 rounded-full bg-black/50 backdrop-blur-sm flex items-center justify-center">
                                <span class="text-white text-xs font-bold" x-text="index + 1"></span>
                            </div>

                            <!-- Remove Button -->
                            <button @click="galleryImages.splice(index, 1)"
                                    class="absolute top-2 right-2 w-6 h-6 rounded-full bg-red-500 text-white flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                                <i data-lucide="x" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Code Example -->
            <details class="mt-4">
                <summary class="cursor-pointer font-semibold"
                         :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                    View Code
                </summary>
                <pre class="mt-2 p-4 rounded-lg overflow-x-auto text-sm"
                     :class="darkMode ? 'bg-gray-950 text-gray-300' : 'bg-gray-100 text-gray-800'"><code>&lt;button @click="MediaPicker.open({
    mode: 'multiple',
    type: 'image',
    onSelect: (images) => {
        galleryImages = images;
    }
})"&gt;
    Build Gallery
&lt;/button&gt;</code></pre>
            </details>
        </div>
    </section>

    <!-- Example 3: Video Selection -->
    <section class="p-6 rounded-xl border"
             :class="darkMode ? 'bg-gray-900 border-gray-800' : 'bg-white border-gray-200'">
        <h2 class="text-xl font-bold mb-4 gradient-text">
            Example 3: Video Selection
        </h2>

        <div x-data="{
            selectedVideo: null
        }" class="space-y-4">

            <button @click="MediaPicker.open({
                mode: 'single',
                type: 'video',
                onSelect: (media) => {
                    selectedVideo = media;
                }
            })"
                    class="px-6 py-3 rounded-lg font-semibold bg-gradient-to-r from-[#0056b3] to-[#218838] text-white hover:scale-105 transition-all shadow-lg shadow-accent/30 inline-flex items-center gap-2">
                <i data-lucide="video" class="w-5 h-5"></i>
                <span>Choose Video</span>
            </button>

            <!-- Video Preview -->
            <div x-show="selectedVideo" class="mt-4">
                <p class="text-sm font-medium mb-2"
                   :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                    Selected Video:
                </p>
                <div class="p-4 rounded-lg border"
                     :class="darkMode ? 'bg-gray-800 border-gray-700' : 'bg-gray-50 border-gray-200'">
                    <video :src="selectedVideo?.url"
                           controls
                           class="w-full max-w-2xl rounded-lg">
                    </video>
                    <p class="mt-2 font-semibold"
                       :class="darkMode ? 'text-white' : 'text-gray-900'"
                       x-text="selectedVideo?.original_filename"></p>
                </div>
            </div>

            <!-- Code Example -->
            <details class="mt-4">
                <summary class="cursor-pointer font-semibold"
                         :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                    View Code
                </summary>
                <pre class="mt-2 p-4 rounded-lg overflow-x-auto text-sm"
                     :class="darkMode ? 'bg-gray-950 text-gray-300' : 'bg-gray-100 text-gray-800'"><code>&lt;button @click="MediaPicker.open({
    mode: 'single',
    type: 'video',
    onSelect: (media) => {
        selectedVideo = media;
    }
})"&gt;
    Choose Video
&lt;/button&gt;</code></pre>
            </details>
        </div>
    </section>

    <!-- Example 4: Form Integration -->
    <section class="p-6 rounded-xl border"
             :class="darkMode ? 'bg-gray-900 border-gray-800' : 'bg-white border-gray-200'">
        <h2 class="text-xl font-bold mb-4 gradient-text">
            Example 4: Form Integration
        </h2>

        <form class="space-y-6" x-data="{
            featuredImage: null,
            thumbnails: []
        }">
            <!-- Featured Image -->
            <div>
                <label class="block text-sm font-semibold mb-2"
                       :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                    Featured Image
                </label>

                <input type="hidden"
                       name="featured_image_id"
                       :value="featuredImage?.id">

                <button type="button"
                        @click="MediaPicker.open({
                            mode: 'single',
                            type: 'image',
                            onSelect: (media) => { featuredImage = media; }
                        })"
                        class="px-4 py-2 rounded-lg border-2 border-dashed font-medium transition-colors inline-flex items-center gap-2"
                        :class="darkMode ?
                            'border-gray-700 hover:border-gray-600 text-gray-300' :
                            'border-gray-300 hover:border-gray-400 text-gray-700'">
                    <i data-lucide="image-plus" class="w-5 h-5"></i>
                    <span>Choose Image</span>
                </button>

                <div x-show="featuredImage" class="mt-3">
                    <div class="relative inline-block">
                        <img :src="featuredImage?.url"
                             class="w-32 h-32 object-cover rounded-lg">
                        <button type="button"
                                @click="featuredImage = null"
                                class="absolute -top-2 -right-2 w-6 h-6 rounded-full bg-red-500 text-white flex items-center justify-center hover:bg-red-600">
                            <i data-lucide="x" class="w-4 h-4"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Thumbnails -->
            <div>
                <label class="block text-sm font-semibold mb-2"
                       :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                    Product Thumbnails
                </label>

                <template x-for="(thumb, index) in thumbnails" :key="index">
                    <input type="hidden"
                           name="thumbnails[]"
                           :value="thumb.id">
                </template>

                <button type="button"
                        @click="MediaPicker.open({
                            mode: 'multiple',
                            type: 'image',
                            onSelect: (images) => { thumbnails = images; }
                        })"
                        class="px-4 py-2 rounded-lg border-2 border-dashed font-medium transition-colors inline-flex items-center gap-2"
                        :class="darkMode ?
                            'border-gray-700 hover:border-gray-600 text-gray-300' :
                            'border-gray-300 hover:border-gray-400 text-gray-700'">
                    <i data-lucide="images" class="w-5 h-5"></i>
                    <span>Choose Thumbnails</span>
                </button>

                <div x-show="thumbnails.length > 0" class="mt-3 flex flex-wrap gap-2">
                    <template x-for="(thumb, index) in thumbnails" :key="thumb.id">
                        <div class="relative">
                            <img :src="thumb.url"
                                 class="w-20 h-20 object-cover rounded-lg">
                            <button type="button"
                                    @click="thumbnails.splice(index, 1)"
                                    class="absolute -top-2 -right-2 w-5 h-5 rounded-full bg-red-500 text-white flex items-center justify-center hover:bg-red-600">
                                <i data-lucide="x" class="w-3 h-3"></i>
                            </button>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Submit Button -->
            <div>
                <button type="submit"
                        class="px-6 py-3 rounded-lg font-semibold bg-gradient-to-r from-[#0056b3] to-[#218838] text-white hover:scale-105 transition-all shadow-lg shadow-accent/30">
                    Submit Form
                </button>
            </div>
        </form>
    </section>

    <!-- Example 5: Vanilla JavaScript -->
    <section class="p-6 rounded-xl border"
             :class="darkMode ? 'bg-gray-900 border-gray-800' : 'bg-white border-gray-200'">
        <h2 class="text-xl font-bold mb-4 gradient-text">
            Example 5: Vanilla JavaScript Integration
        </h2>

        <button id="vanilla-js-btn"
                class="px-6 py-3 rounded-lg font-semibold bg-gradient-to-r from-[#0056b3] to-[#218838] text-white hover:scale-105 transition-all shadow-lg shadow-accent/30">
            Choose Media (Vanilla JS)
        </button>

        <div id="vanilla-js-preview" class="mt-4"></div>

        <!-- Code Example -->
        <details class="mt-4">
            <summary class="cursor-pointer font-semibold"
                     :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                View Code
            </summary>
            <pre class="mt-2 p-4 rounded-lg overflow-x-auto text-sm"
                 :class="darkMode ? 'bg-gray-950 text-gray-300' : 'bg-gray-100 text-gray-800'"><code>document.getElementById('vanilla-js-btn').addEventListener('click', () => {
    MediaPicker.open({
        mode: 'single',
        type: 'image',
        onSelect: (media) => {
            document.getElementById('vanilla-js-preview').innerHTML = `
                &lt;img src="${media.url}" class="w-32 h-32 object-cover rounded-lg" /&gt;
            `;
        }
    });
});</code></pre>
        </details>
    </section>

</div>

<!-- Include Media Picker Modal -->
<x-admin.media-picker-modal />
@endsection

@push('scripts')
<script>
// Vanilla JavaScript example
document.addEventListener('DOMContentLoaded', () => {
    const vanillaBtn = document.getElementById('vanilla-js-btn');
    const vanillaPreview = document.getElementById('vanilla-js-preview');

    if (vanillaBtn) {
        vanillaBtn.addEventListener('click', () => {
            MediaPicker.open({
                mode: 'single',
                type: 'image',
                onSelect: (media) => {
                    vanillaPreview.innerHTML = `
                        <div class="p-4 rounded-lg border bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700">
                            <img src="${media.url}" alt="${media.alt_text || ''}" class="w-32 h-32 object-cover rounded-lg mb-2" />
                            <p class="text-sm font-medium">${media.original_filename}</p>
                            <p class="text-xs text-gray-500">ID: ${media.id}</p>
                        </div>
                    `;
                    lucide.createIcons();
                }
            });
        });
    }
});
</script>
@endpush
