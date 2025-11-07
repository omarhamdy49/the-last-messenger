{{--
    Article Form with Media Picker Integration
    Example implementation showing best practices
--}}

<form action="{{ $article->exists ? route('admin.articles.update', $article) : route('admin.articles.store') }}"
      method="POST"
      x-data="articleForm()"
      class="space-y-8">

    @csrf
    @if($article->exists)
        @method('PUT')
    @endif

    <!-- Title Section -->
    <div class="p-6 rounded-xl border"
         :class="$root.darkMode ? 'bg-gray-900 border-gray-800' : 'bg-white border-gray-200'">
        <h2 class="text-xl font-bold mb-6 gradient-text">
            Basic Information
        </h2>

        <div class="space-y-6">
            <!-- Title -->
            <div>
                <label class="block text-sm font-semibold mb-2"
                       :class="$root.darkMode ? 'text-gray-300' : 'text-gray-700'">
                    Title <span class="text-red-500">*</span>
                </label>
                <input type="text"
                       name="title"
                       value="{{ old('title', $article->title ?? '') }}"
                       required
                       class="w-full px-4 py-2.5 rounded-lg border focus:ring-2 focus:ring-accent/50 focus:border-accent transition-all"
                       :class="$root.darkMode ?
                           'bg-gray-800 border-gray-700 text-white' :
                           'bg-white border-gray-300 text-gray-900'">
                @error('title')
                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>

            <!-- Slug -->
            <div>
                <label class="block text-sm font-semibold mb-2"
                       :class="$root.darkMode ? 'text-gray-300' : 'text-gray-700'">
                    Slug
                </label>
                <input type="text"
                       name="slug"
                       value="{{ old('slug', $article->slug ?? '') }}"
                       class="w-full px-4 py-2.5 rounded-lg border focus:ring-2 focus:ring-accent/50 focus:border-accent transition-all"
                       :class="$root.darkMode ?
                           'bg-gray-800 border-gray-700 text-white' :
                           'bg-white border-gray-300 text-gray-900'">
                @error('slug')
                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </div>

    <!-- Media Section -->
    <div class="p-6 rounded-xl border"
         :class="$root.darkMode ? 'bg-gray-900 border-gray-800' : 'bg-white border-gray-200'">
        <h2 class="text-xl font-bold mb-6 gradient-text">
            Media
        </h2>

        <div class="space-y-6">
            <!-- Featured Image -->
            <div>
                <label class="block text-sm font-semibold mb-2"
                       :class="$root.darkMode ? 'text-gray-300' : 'text-gray-700'">
                    Featured Image
                </label>

                <!-- Hidden input for form submission -->
                <input type="hidden"
                       name="featured_image_id"
                       :value="featuredImage?.id"
                       x-model="featuredImageId">

                <!-- Choose Button -->
                <button type="button"
                        @click="chooseFeaturedImage()"
                        class="px-4 py-3 rounded-lg border-2 border-dashed font-medium transition-all hover:scale-[1.02] inline-flex items-center gap-2"
                        :class="$root.darkMode ?
                            'border-gray-700 hover:border-gray-600 text-gray-300 hover:bg-gray-800' :
                            'border-gray-300 hover:border-gray-400 text-gray-700 hover:bg-gray-50'">
                    <i data-lucide="image-plus" class="w-5 h-5"></i>
                    <span x-text="featuredImage ? 'Change Image' : 'Choose Featured Image'"></span>
                </button>

                @error('featured_image_id')
                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                @enderror

                <!-- Image Preview -->
                <div x-show="featuredImage"
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100"
                     class="mt-4">
                    <div class="relative inline-block group">
                        <img :src="featuredImage?.url"
                             :alt="featuredImage?.alt_text"
                             class="w-64 h-48 object-cover rounded-lg border-2 border-accent shadow-lg shadow-accent/20">

                        <!-- Overlay on hover -->
                        <div class="absolute inset-0 bg-black/50 rounded-lg opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2">
                            <button type="button"
                                    @click="viewImage(featuredImage.url)"
                                    class="p-2 rounded-lg bg-white/90 hover:bg-white transition-colors">
                                <i data-lucide="eye" class="w-5 h-5 text-gray-900"></i>
                            </button>
                            <button type="button"
                                    @click="removeFeaturedImage()"
                                    class="p-2 rounded-lg bg-red-500 hover:bg-red-600 transition-colors">
                                <i data-lucide="trash-2" class="w-5 h-5 text-white"></i>
                            </button>
                        </div>

                        <!-- Image Info -->
                        <div class="mt-2">
                            <p class="text-sm font-medium"
                               :class="$root.darkMode ? 'text-gray-300' : 'text-gray-700'"
                               x-text="featuredImage?.original_filename"></p>
                            <p class="text-xs"
                               :class="$root.darkMode ? 'text-gray-500' : 'text-gray-500'">
                                <span x-text="featuredImage?.width"></span> x <span x-text="featuredImage?.height"></span> px
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Gallery Images -->
            <div>
                <div class="flex items-center justify-between mb-2">
                    <label class="block text-sm font-semibold"
                           :class="$root.darkMode ? 'text-gray-300' : 'text-gray-700'">
                        Gallery Images
                    </label>
                    <span x-show="galleryImages.length > 0"
                          class="text-xs px-2 py-1 rounded-full bg-accent/20 text-accent font-semibold"
                          x-text="galleryImages.length + ' images'">
                    </span>
                </div>

                <!-- Hidden inputs for form submission -->
                <template x-for="(image, index) in galleryImages" :key="image.id">
                    <input type="hidden" name="gallery_images[]" :value="image.id">
                </template>

                <!-- Choose Button -->
                <button type="button"
                        @click="chooseGalleryImages()"
                        class="px-4 py-3 rounded-lg border-2 border-dashed font-medium transition-all hover:scale-[1.02] inline-flex items-center gap-2"
                        :class="$root.darkMode ?
                            'border-gray-700 hover:border-gray-600 text-gray-300 hover:bg-gray-800' :
                            'border-gray-300 hover:border-gray-400 text-gray-700 hover:bg-gray-50'">
                    <i data-lucide="images" class="w-5 h-5"></i>
                    <span x-text="galleryImages.length > 0 ? 'Manage Gallery' : 'Add Gallery Images'"></span>
                </button>

                @error('gallery_images')
                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                @enderror

                <!-- Gallery Grid -->
                <div x-show="galleryImages.length > 0"
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     class="mt-4">

                    <div class="flex items-center justify-between mb-3">
                        <p class="text-sm font-medium"
                           :class="$root.darkMode ? 'text-gray-400' : 'text-gray-600'">
                            Drag to reorder
                        </p>
                        <button type="button"
                                @click="clearGallery()"
                                class="text-sm text-red-500 hover:text-red-600 font-medium transition-colors">
                            Clear All
                        </button>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
                        <template x-for="(image, index) in galleryImages" :key="image.id">
                            <div class="group relative aspect-square rounded-lg overflow-hidden border-2 transition-all hover:scale-105"
                                 :class="$root.darkMode ? 'border-gray-700' : 'border-gray-200'">

                                <img :src="image.url"
                                     :alt="image.alt_text"
                                     class="w-full h-full object-cover">

                                <!-- Index Badge -->
                                <div class="absolute top-2 left-2 w-6 h-6 rounded-full bg-gradient-to-r from-[#0056b3] to-[#218838] flex items-center justify-center shadow-lg">
                                    <span class="text-white text-xs font-bold" x-text="index + 1"></span>
                                </div>

                                <!-- Remove Button -->
                                <button type="button"
                                        @click="removeFromGallery(index)"
                                        class="absolute top-2 right-2 w-7 h-7 rounded-full bg-red-500 text-white flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity shadow-lg hover:bg-red-600">
                                    <i data-lucide="x" class="w-4 h-4"></i>
                                </button>

                                <!-- Filename on hover -->
                                <div class="absolute bottom-0 left-0 right-0 p-2 bg-gradient-to-t from-black/80 to-transparent opacity-0 group-hover:opacity-100 transition-opacity">
                                    <p class="text-white text-xs truncate font-medium"
                                       x-text="image.original_filename"></p>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Content Section -->
    <div class="p-6 rounded-xl border"
         :class="$root.darkMode ? 'bg-gray-900 border-gray-800' : 'bg-white border-gray-200'">
        <h2 class="text-xl font-bold mb-6 gradient-text">
            Content
        </h2>

        <div>
            <label class="block text-sm font-semibold mb-2"
                   :class="$root.darkMode ? 'text-gray-300' : 'text-gray-700'">
                Article Content <span class="text-red-500">*</span>
            </label>
            <textarea name="content"
                      rows="15"
                      required
                      class="w-full px-4 py-2.5 rounded-lg border focus:ring-2 focus:ring-accent/50 focus:border-accent transition-all font-mono text-sm"
                      :class="$root.darkMode ?
                          'bg-gray-800 border-gray-700 text-white' :
                          'bg-white border-gray-300 text-gray-900'">{{ old('content', $article->content ?? '') }}</textarea>
            @error('content')
                <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <!-- Actions -->
    <div class="flex items-center justify-between gap-4">
        <a href="{{ route('admin.articles.index') }}"
           class="px-6 py-3 rounded-lg font-semibold transition-all hover:scale-105"
           :class="$root.darkMode ?
               'bg-gray-800 hover:bg-gray-700 text-white' :
               'bg-gray-100 hover:bg-gray-200 text-gray-900'">
            Cancel
        </a>

        <button type="submit"
                class="px-8 py-3 rounded-lg font-semibold bg-gradient-to-r from-[#0056b3] to-[#218838] text-white hover:scale-105 transition-all shadow-lg shadow-accent/30 inline-flex items-center gap-2">
            <i data-lucide="save" class="w-5 h-5"></i>
            <span>{{ $article->exists ? 'Update Article' : 'Create Article' }}</span>
        </button>
    </div>
</form>

<!-- Include Media Picker Modal -->
<x-admin.media-picker-modal />

@push('scripts')
<script>
function articleForm() {
    return {
        featuredImage: @json($article->featuredImage ?? null),
        featuredImageId: {{ old('featured_image_id', $article->featured_image_id ?? 'null') }},
        galleryImages: @json($article->galleryImages ?? []),

        init() {
            // Initialize Lucide icons
            this.$nextTick(() => {
                lucide.createIcons();
            });
        },

        chooseFeaturedImage() {
            MediaPicker.open({
                mode: 'single',
                type: 'image',
                onSelect: (media) => {
                    this.featuredImage = media;
                    this.featuredImageId = media.id;
                    this.$nextTick(() => {
                        lucide.createIcons();
                    });
                }
            });
        },

        removeFeaturedImage() {
            if (confirm('Are you sure you want to remove the featured image?')) {
                this.featuredImage = null;
                this.featuredImageId = null;
            }
        },

        viewImage(url) {
            window.open(url, '_blank');
        },

        chooseGalleryImages() {
            MediaPicker.open({
                mode: 'multiple',
                type: 'image',
                onSelect: (images) => {
                    this.galleryImages = images;
                    this.$nextTick(() => {
                        lucide.createIcons();
                    });
                }
            });
        },

        removeFromGallery(index) {
            if (confirm('Remove this image from gallery?')) {
                this.galleryImages.splice(index, 1);
            }
        },

        clearGallery() {
            if (confirm('Are you sure you want to clear the entire gallery?')) {
                this.galleryImages = [];
            }
        }
    };
}
</script>
@endpush
