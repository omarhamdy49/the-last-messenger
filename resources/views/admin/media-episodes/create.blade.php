@extends('layouts.admin')

@section('title', 'Create Media Episode')

@section('content')
<div class="container mx-auto px-4 py-8" x-data="episodeFormManager()" x-init="init()">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">
            Create New Episode
        </h1>
        <p class="mt-2" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
            Add a new video episode to a program
        </p>
    </div>

    <!-- Form -->
    <form action="{{ route('admin.media-episodes.store') }}" method="POST" enctype="multipart/form-data"
          class="max-w-6xl space-y-8">
        @csrf

        <!-- Basic Information -->
        <div class="glass rounded-lg p-8 transition-colors" :class="darkMode ? 'bg-gray-900/50' : 'bg-white/80'">
            <h2 class="text-2xl font-bold mb-6" :class="darkMode ? 'text-white' : 'text-gray-900'">
                Basic Information
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Program Selection -->
                <div>
                    <label class="block text-sm font-semibold mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                        Program <span class="text-red-500">*</span>
                    </label>
                    <select name="media_program_id" x-model="selectedProgram" @change="suggestEpisodeNumber()" required
                            class="w-full px-4 py-2 rounded-lg border focus:outline-none focus:ring-2 focus:ring-[#0056b3] transition-colors"
                            :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                        <option value="">Select a program</option>
                        @foreach($programs as $program)
                            <option value="{{ $program->id }}">{{ $program->title['en'] ?? $program->title['ar'] }}</option>
                        @endforeach
                    </select>
                    @error('media_program_id')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Episode Number -->
                <div>
                    <label class="block text-sm font-semibold mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                        Episode Number <span class="text-red-500">*</span>
                    </label>
                    <input type="number" name="episode_number" x-model="episodeNumber" required min="1"
                           class="w-full px-4 py-2 rounded-lg border focus:outline-none focus:ring-2 focus:ring-[#0056b3] transition-colors"
                           :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                    @error('episode_number')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Title -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                        Episode Title <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="title" value="{{ old('title') }}" required
                           class="w-full px-4 py-2 rounded-lg border focus:outline-none focus:ring-2 focus:ring-[#0056b3] transition-colors"
                           :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                    @error('title')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Description (Multi-language with Tabs) -->
        <div class="glass rounded-lg p-8 transition-colors" :class="darkMode ? 'bg-gray-900/50' : 'bg-white/80'">
            <h2 class="text-2xl font-bold mb-6" :class="darkMode ? 'text-white' : 'text-gray-900'">
                Description
            </h2>

            <!-- Language Tabs -->
            <div class="flex gap-2 mb-6 border-b transition-colors" :class="darkMode ? 'border-gray-700' : 'border-gray-200'">
                @foreach(['ar' => 'العربية', 'en' => 'English', 'fr' => 'Français', 'tr' => 'Türkçe', 'ur' => 'اردو'] as $lang => $label)
                    <button type="button"
                            @click="activeLanguage = '{{ $lang }}'"
                            :class="activeLanguage === '{{ $lang }}' ? 'border-b-2 border-[#0056b3] font-bold' : ''"
                            class="px-4 py-2 transition"
                            :class="darkMode ? 'text-white hover:text-[#64CCC5]' : 'text-gray-700 hover:text-[#0056b3]'">
                        {{ $label }}
                    </button>
                @endforeach
            </div>

            <!-- Tab Content -->
            @foreach(['ar', 'en', 'fr', 'tr', 'ur'] as $lang)
                <div x-show="activeLanguage === '{{ $lang }}'">
                    <div>
                        <label class="block text-sm font-semibold mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            Description ({{ strtoupper($lang) }})
                        </label>
                        <textarea name="description[{{ $lang }}]" rows="4"
                                  class="w-full px-4 py-2 rounded-lg border focus:outline-none focus:ring-2 focus:ring-[#0056b3] transition-colors"
                                  :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">{{ old("description.$lang") }}</textarea>
                        @error("description.$lang")
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Video Configuration (COMPLEX SECTION) -->
        <div class="glass rounded-lg p-8 transition-colors" :class="darkMode ? 'bg-gray-900/50 border border-[#0056b3]/30' : 'bg-white/80 border border-[#0056b3]/20'">
            <h2 class="text-2xl font-bold mb-6 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                <i data-lucide="video" class="w-6 h-6 text-[#0056b3]"></i>
                Video Configuration
            </h2>

            <div class="space-y-6">
                <!-- Video Source Selector -->
                <div>
                    <label class="block text-sm font-semibold mb-3" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                        Video Source <span class="text-red-500">*</span>
                    </label>
                    <div class="flex gap-4">
                        <label class="flex items-center cursor-pointer">
                            <input type="radio" name="video_source" value="youtube" x-model="videoSource" required class="mr-2">
                            <div class="flex items-center gap-2">
                                <i data-lucide="youtube" class="w-5 h-5 text-red-600"></i>
                                <span :class="darkMode ? 'text-white' : 'text-gray-900'">YouTube</span>
                            </div>
                        </label>
                        <label class="flex items-center cursor-pointer">
                            <input type="radio" name="video_source" value="vimeo" x-model="videoSource" class="mr-2">
                            <div class="flex items-center gap-2">
                                <i data-lucide="video" class="w-5 h-5 text-blue-600"></i>
                                <span :class="darkMode ? 'text-white' : 'text-gray-900'">Vimeo</span>
                            </div>
                        </label>
                        <label class="flex items-center cursor-pointer">
                            <input type="radio" name="video_source" value="direct" x-model="videoSource" class="mr-2">
                            <div class="flex items-center gap-2">
                                <i data-lucide="file-video" class="w-5 h-5 text-green-600"></i>
                                <span :class="darkMode ? 'text-white' : 'text-gray-900'">Direct File</span>
                            </div>
                        </label>
                    </div>
                    @error('video_source')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Video URL Input -->
                <div>
                    <label class="block text-sm font-semibold mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                        Video URL <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="video_url" x-model="videoUrl" @input.debounce.500ms="updatePreview()" required
                           :placeholder="getUrlPlaceholder()"
                           class="w-full px-4 py-2 rounded-lg border focus:outline-none focus:ring-2 focus:ring-[#0056b3] transition-colors"
                           :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                    <p class="text-sm mt-2" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                        <span x-show="videoSource === 'youtube'">
                            <strong>YouTube:</strong> https://youtube.com/watch?v=dQw4w9WgXcQ or https://youtu.be/dQw4w9WgXcQ
                        </span>
                        <span x-show="videoSource === 'vimeo'">
                            <strong>Vimeo:</strong> https://vimeo.com/123456789
                        </span>
                        <span x-show="videoSource === 'direct'">
                            <strong>Direct File:</strong> https://example.com/video.mp4 (supports .mp4, .webm, .ogg)
                        </span>
                    </p>
                    @error('video_url')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Live Video Preview -->
                <div>
                    <label class="block text-sm font-semibold mb-3" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                        Video Preview
                    </label>
                    <div class="bg-black rounded-lg overflow-hidden aspect-video">
                        <!-- YouTube Preview -->
                        <template x-if="videoSource === 'youtube' && videoUrl && extractYouTubeId()">
                            <iframe width="100%" height="100%"
                                    :src="'https://www.youtube.com/embed/' + extractYouTubeId()"
                                    frameborder="0" allowfullscreen class="w-full h-full"></iframe>
                        </template>

                        <!-- Vimeo Preview -->
                        <template x-if="videoSource === 'vimeo' && videoUrl && extractVimeoId()">
                            <iframe width="100%" height="100%"
                                    :src="'https://player.vimeo.com/video/' + extractVimeoId()"
                                    frameborder="0" allowfullscreen class="w-full h-full"></iframe>
                        </template>

                        <!-- Direct Video Preview -->
                        <template x-if="videoSource === 'direct' && videoUrl && isValidDirectUrl()">
                            <video width="100%" height="100%" controls class="w-full h-full" :src="videoUrl">
                                Your browser does not support the video tag.
                            </video>
                        </template>

                        <!-- Placeholder -->
                        <template x-if="!videoUrl || (!extractYouTubeId() && !extractVimeoId() && !isValidDirectUrl())">
                            <div class="w-full h-full flex items-center justify-center bg-gray-900">
                                <div class="text-center text-gray-400">
                                    <i data-lucide="video" class="w-12 h-12 mx-auto mb-2"></i>
                                    <p>Enter a valid video URL to see preview</p>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Duration & Thumbnail -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Duration -->
                    <div>
                        <label class="block text-sm font-semibold mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            Duration (HH:MM:SS)
                        </label>
                        <input type="text" name="duration" value="{{ old('duration') }}" placeholder="00:45:30"
                               pattern="[0-9]{2}:[0-9]{2}:[0-9]{2}"
                               class="w-full px-4 py-2 rounded-lg border focus:outline-none focus:ring-2 focus:ring-[#0056b3] transition-colors"
                               :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                        <p class="text-sm mt-1" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                            Leave empty for auto-detection from video metadata
                        </p>
                        @error('duration')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Thumbnail Upload -->
                    <div>
                        <label class="block text-sm font-semibold mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            Thumbnail Image
                        </label>
                        <input type="file" name="thumbnail" accept="image/jpeg,image/png,image/webp"
                               @change="previewThumbnail($event)"
                               class="w-full px-4 py-2 rounded-lg border focus:outline-none focus:ring-2 focus:ring-[#0056b3] transition-colors"
                               :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                        <p class="text-sm mt-1" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                            JPG, PNG, or WebP. For YouTube/Vimeo, auto-fetched from service.
                        </p>
                        @error('thumbnail')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror

                        <!-- Thumbnail Preview -->
                        <div x-show="thumbnailPreview" class="mt-4">
                            <img :src="thumbnailPreview" class="max-w-xs rounded-lg shadow-md">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Publishing Options -->
        <div class="glass rounded-lg p-8 transition-colors" :class="darkMode ? 'bg-gray-900/50' : 'bg-white/80'">
            <h2 class="text-2xl font-bold mb-6" :class="darkMode ? 'text-white' : 'text-gray-900'">
                Publishing
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Status -->
                <div>
                    <label class="block text-sm font-semibold mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                        Status
                    </label>
                    <select name="status" x-model="status"
                            class="w-full px-4 py-2 rounded-lg border focus:outline-none focus:ring-2 focus:ring-[#0056b3] transition-colors"
                            :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                        <option value="draft">Draft</option>
                        <option value="scheduled">Scheduled</option>
                        <option value="published">Published</option>
                    </select>
                    @error('status')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Release Date -->
                <div x-show="status === 'scheduled'">
                    <label class="block text-sm font-semibold mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                        Release Date <span class="text-red-500" x-show="status === 'scheduled'">*</span>
                    </label>
                    <input type="datetime-local" name="released_at" value="{{ old('released_at') }}"
                           :required="status === 'scheduled'"
                           class="w-full px-4 py-2 rounded-lg border focus:outline-none focus:ring-2 focus:ring-[#0056b3] transition-colors"
                           :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                    <p class="text-sm mt-1" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                        Required if status is 'Scheduled'
                    </p>
                    @error('released_at')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Display Order -->
                <div>
                    <label class="block text-sm font-semibold mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                        Display Order
                    </label>
                    <input type="number" name="display_order" value="{{ old('display_order', 0) }}" min="0"
                           class="w-full px-4 py-2 rounded-lg border focus:outline-none focus:ring-2 focus:ring-[#0056b3] transition-colors"
                           :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                    @error('display_order')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Submit Buttons -->
        <div class="flex gap-4">
            <button type="submit"
                    class="bg-gradient-to-r from-[#0056b3] to-[#218838] text-white px-8 py-3 rounded-lg font-semibold hover:shadow-lg transition flex items-center gap-2">
                <i data-lucide="save" class="w-5 h-5"></i>
                Create Episode
            </button>
            <a href="{{ route('admin.media-episodes.index') }}"
               class="px-8 py-3 rounded-lg font-semibold border transition"
               :class="darkMode ? 'border-gray-700 text-white hover:bg-gray-800' : 'border-gray-300 text-gray-900 hover:bg-gray-100'">
                Cancel
            </a>
        </div>
    </form>
</div>

@push('scripts')
<script>
function episodeFormManager() {
    return {
        activeLanguage: 'en',
        darkMode: localStorage.getItem('darkMode') === 'true',
        videoSource: 'youtube',
        videoUrl: '',
        status: 'draft',
        selectedProgram: '',
        episodeNumber: 1,
        thumbnailPreview: '',

        init() {
            this.$nextTick(() => lucide.createIcons());
        },

        getUrlPlaceholder() {
            const placeholders = {
                youtube: 'https://youtube.com/watch?v=dQw4w9WgXcQ',
                vimeo: 'https://vimeo.com/123456789',
                direct: 'https://example.com/video.mp4'
            };
            return placeholders[this.videoSource] || '';
        },

        extractYouTubeId() {
            if (!this.videoUrl) return null;

            // Handle youtu.be format
            const shortMatch = this.videoUrl.match(/youtu\.be\/([a-zA-Z0-9_-]+)/);
            if (shortMatch) return shortMatch[1];

            // Handle youtube.com format
            const longMatch = this.videoUrl.match(/[?&]v=([a-zA-Z0-9_-]+)/);
            if (longMatch) return longMatch[1];

            return null;
        },

        extractVimeoId() {
            if (!this.videoUrl) return null;
            const match = this.videoUrl.match(/vimeo\.com\/(\d+)/);
            return match ? match[1] : null;
        },

        isValidDirectUrl() {
            if (!this.videoUrl) return false;
            return /\.(mp4|webm|ogg)$/i.test(this.videoUrl) ||
                   (this.videoUrl.startsWith('http') && this.videoSource === 'direct');
        },

        updatePreview() {
            this.$nextTick(() => lucide.createIcons());
        },

        previewThumbnail(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    this.thumbnailPreview = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        },

        async suggestEpisodeNumber() {
            if (!this.selectedProgram) return;

            try {
                const response = await fetch(`/su/portal/v1/admin/media-episodes/suggest-number?program_id=${this.selectedProgram}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });

                if (response.ok) {
                    const data = await response.json();
                    this.episodeNumber = data.suggested_number || 1;
                }
            } catch (error) {
                console.error('Error suggesting episode number:', error);
            }
        }
    };
}

lucide.createIcons();
</script>
@endpush
@endsection
