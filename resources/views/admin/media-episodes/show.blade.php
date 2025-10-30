@extends('layouts.admin')

@section('title', $episode->title)

@section('content')
<div class="container mx-auto px-4 py-8" x-data="{ darkMode: localStorage.getItem('darkMode') === 'true' }">
    <!-- Breadcrumb Navigation -->
    <nav class="mb-6">
        <ol class="flex items-center gap-2 text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
            <li><a href="{{ route('admin.dashboard') }}" class="hover:text-[#0056b3]">Home</a></li>
            <li><i data-lucide="chevron-right" class="w-4 h-4"></i></li>
            <li><a href="{{ route('admin.media-episodes.index') }}" class="hover:text-[#0056b3]">Media Episodes</a></li>
            <li><i data-lucide="chevron-right" class="w-4 h-4"></i></li>
            <li><a href="{{ route('admin.media-programs.show', $episode->program) }}" class="hover:text-[#0056b3]">
                {{ $episode->program->title['en'] ?? $episode->program->title['ar'] }}
            </a></li>
            <li><i data-lucide="chevron-right" class="w-4 h-4"></i></li>
            <li class="font-semibold" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $episode->title }}</li>
        </ol>
    </nav>

    <!-- Header -->
    <div class="flex justify-between items-start mb-8">
        <div>
            <div class="flex items-center gap-3 mb-2">
                <h1 class="text-4xl font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">
                    {{ $episode->title }}
                </h1>
                <span class="px-3 py-1 rounded-full text-sm font-semibold"
                      :class="{
                          'bg-green-100 text-green-800': '{{ $episode->status }}' === 'published',
                          'bg-orange-100 text-orange-800': '{{ $episode->status }}' === 'scheduled',
                          'bg-gray-100 text-gray-800': '{{ $episode->status }}' === 'draft'
                      }">
                    {{ ucfirst($episode->status) }}
                </span>
            </div>
            <div class="flex items-center gap-4 mt-2" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                <span class="flex items-center gap-2">
                    <i data-lucide="layers" class="w-4 h-4"></i>
                    <a href="{{ route('admin.media-programs.show', $episode->program) }}" class="text-[#0056b3] hover:text-[#64CCC5]">
                        {{ $episode->program->title['en'] ?? $episode->program->title['ar'] }}
                    </a>
                </span>
                <span class="flex items-center gap-2">
                    <i data-lucide="hash" class="w-4 h-4"></i>
                    Episode {{ $episode->episode_number }}
                </span>
                @if($episode->duration)
                    <span class="flex items-center gap-2">
                        <i data-lucide="clock" class="w-4 h-4"></i>
                        {{ $episode->duration }}
                    </span>
                @endif
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex gap-2">
            <a href="{{ route('admin.media-episodes.edit', $episode) }}"
               class="bg-gradient-to-r from-[#0056b3] to-[#218838] text-white px-6 py-3 rounded-lg font-semibold hover:shadow-lg transition flex items-center gap-2">
                <i data-lucide="edit" class="w-5 h-5"></i>
                Edit Episode
            </a>
            <button @click="confirmDelete()"
                    class="bg-red-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-red-700 transition flex items-center gap-2">
                <i data-lucide="trash-2" class="w-5 h-5"></i>
                Delete
            </button>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="glass rounded-lg p-6 transition-colors" :class="darkMode ? 'bg-gray-900/50' : 'bg-white/80'">
            <div class="flex items-center gap-3 mb-2">
                <i data-lucide="eye" class="w-5 h-5 text-[#0056b3]"></i>
                <p class="text-sm font-semibold" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">Total Views</p>
            </div>
            <p class="text-4xl font-bold text-[#0056b3]">{{ number_format($episode->views_count ?? 0) }}</p>
        </div>
        <div class="glass rounded-lg p-6 transition-colors" :class="darkMode ? 'bg-gray-900/50' : 'bg-white/80'">
            <div class="flex items-center gap-3 mb-2">
                <i data-lucide="download" class="w-5 h-5 text-[#218838]"></i>
                <p class="text-sm font-semibold" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">Downloads</p>
            </div>
            <p class="text-4xl font-bold text-[#218838]">{{ number_format($episode->downloads_count ?? 0) }}</p>
        </div>
        <div class="glass rounded-lg p-6 transition-colors" :class="darkMode ? 'bg-gray-900/50' : 'bg-white/80'">
            <div class="flex items-center gap-3 mb-2">
                <i data-lucide="activity" class="w-5 h-5 text-[#64CCC5]"></i>
                <p class="text-sm font-semibold" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">Completion Rate</p>
            </div>
            <p class="text-4xl font-bold text-[#64CCC5]">{{ $episode->completion_rate ?? 0 }}%</p>
        </div>
        <div class="glass rounded-lg p-6 transition-colors" :class="darkMode ? 'bg-gray-900/50' : 'bg-white/80'">
            <div class="flex items-center gap-3 mb-2">
                <i data-lucide="calendar" class="w-5 h-5 text-orange-500"></i>
                <p class="text-sm font-semibold" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">Released</p>
            </div>
            <p class="text-lg font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">
                {{ $episode->released_at ? $episode->released_at->format('M d, Y') : 'Not released' }}
            </p>
        </div>
    </div>

    <!-- Video Player -->
    <div class="glass rounded-lg overflow-hidden mb-8 transition-colors" :class="darkMode ? 'bg-gray-900/50' : 'bg-white/80'">
        <div class="bg-black aspect-video">
            @if($episode->video_source === 'youtube')
                @php
                    // Extract YouTube ID
                    $youtubeId = null;
                    if (preg_match('/youtu\.be\/([a-zA-Z0-9_-]+)/', $episode->video_url, $matches)) {
                        $youtubeId = $matches[1];
                    } elseif (preg_match('/[?&]v=([a-zA-Z0-9_-]+)/', $episode->video_url, $matches)) {
                        $youtubeId = $matches[1];
                    }
                @endphp
                @if($youtubeId)
                    <iframe width="100%" height="100%"
                            src="https://www.youtube.com/embed/{{ $youtubeId }}"
                            frameborder="0" allowfullscreen class="w-full h-full"></iframe>
                @else
                    <div class="w-full h-full flex items-center justify-center text-red-500">
                        <p>Invalid YouTube URL</p>
                    </div>
                @endif
            @elseif($episode->video_source === 'vimeo')
                @php
                    // Extract Vimeo ID
                    $vimeoId = null;
                    if (preg_match('/vimeo\.com\/(\d+)/', $episode->video_url, $matches)) {
                        $vimeoId = $matches[1];
                    }
                @endphp
                @if($vimeoId)
                    <iframe width="100%" height="100%"
                            src="https://player.vimeo.com/video/{{ $vimeoId }}"
                            frameborder="0" allowfullscreen class="w-full h-full"></iframe>
                @else
                    <div class="w-full h-full flex items-center justify-center text-red-500">
                        <p>Invalid Vimeo URL</p>
                    </div>
                @endif
            @elseif($episode->video_source === 'direct')
                <video width="100%" height="100%" controls class="w-full h-full"
                       @if($episode->thumbnail_url) poster="{{ $episode->thumbnail_url }}" @endif>
                    <source src="{{ $episode->video_url }}" type="video/mp4">
                    Your browser does not support the video tag.
                </video>
            @else
                <div class="w-full h-full flex items-center justify-center text-gray-400">
                    <div class="text-center">
                        <i data-lucide="video-off" class="w-16 h-16 mx-auto mb-4"></i>
                        <p>Video source not configured</p>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Episode Metadata -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        <!-- Left Column: Details -->
        <div class="glass rounded-lg p-8 transition-colors" :class="darkMode ? 'bg-gray-900/50' : 'bg-white/80'">
            <h2 class="text-2xl font-bold mb-6" :class="darkMode ? 'text-white' : 'text-gray-900'">
                Episode Details
            </h2>

            <div class="space-y-4">
                <div class="flex justify-between py-3 border-b transition-colors" :class="darkMode ? 'border-gray-700' : 'border-gray-200'">
                    <span class="font-semibold" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">Program</span>
                    <a href="{{ route('admin.media-programs.show', $episode->program) }}" class="text-[#0056b3] hover:text-[#64CCC5]">
                        {{ $episode->program->title['en'] ?? $episode->program->title['ar'] }}
                    </a>
                </div>
                <div class="flex justify-between py-3 border-b transition-colors" :class="darkMode ? 'border-gray-700' : 'border-gray-200'">
                    <span class="font-semibold" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">Episode Number</span>
                    <span :class="darkMode ? 'text-white' : 'text-gray-900'">#{{ $episode->episode_number }}</span>
                </div>
                <div class="flex justify-between py-3 border-b transition-colors" :class="darkMode ? 'border-gray-700' : 'border-gray-200'">
                    <span class="font-semibold" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">Video Source</span>
                    <span :class="darkMode ? 'text-white' : 'text-gray-900'">{{ ucfirst($episode->video_source) }}</span>
                </div>
                <div class="flex justify-between py-3 border-b transition-colors" :class="darkMode ? 'border-gray-700' : 'border-gray-200'">
                    <span class="font-semibold" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">Duration</span>
                    <span :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $episode->duration ?? 'N/A' }}</span>
                </div>
                <div class="flex justify-between py-3 border-b transition-colors" :class="darkMode ? 'border-gray-700' : 'border-gray-200'">
                    <span class="font-semibold" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">Display Order</span>
                    <span :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $episode->display_order ?? 0 }}</span>
                </div>
                <div class="flex justify-between py-3 border-b transition-colors" :class="darkMode ? 'border-gray-700' : 'border-gray-200'">
                    <span class="font-semibold" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">Created At</span>
                    <span :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $episode->created_at->format('M d, Y H:i') }}</span>
                </div>
                <div class="flex justify-between py-3 transition-colors">
                    <span class="font-semibold" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">Last Updated</span>
                    <span :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $episode->updated_at->format('M d, Y H:i') }}</span>
                </div>
            </div>
        </div>

        <!-- Right Column: Thumbnail -->
        <div class="glass rounded-lg p-8 transition-colors" :class="darkMode ? 'bg-gray-900/50' : 'bg-white/80'">
            <h2 class="text-2xl font-bold mb-6" :class="darkMode ? 'text-white' : 'text-gray-900'">
                Thumbnail
            </h2>

            @if($episode->thumbnail_url)
                <img src="{{ $episode->thumbnail_url }}" alt="{{ $episode->title }}"
                     class="w-full rounded-lg shadow-lg">
            @else
                <div class="w-full aspect-video bg-gray-200 rounded-lg flex items-center justify-center"
                     :class="darkMode ? 'bg-gray-800' : 'bg-gray-200'">
                    <div class="text-center" :class="darkMode ? 'text-gray-600' : 'text-gray-400'">
                        <i data-lucide="image-off" class="w-16 h-16 mx-auto mb-4"></i>
                        <p>No thumbnail available</p>
                    </div>
                </div>
            @endif

            <!-- Video URL -->
            <div class="mt-6">
                <h3 class="text-lg font-bold mb-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                    Video URL
                </h3>
                <div class="p-3 rounded-lg border break-all text-sm"
                     :class="darkMode ? 'bg-gray-800 border-gray-700 text-gray-300' : 'bg-gray-50 border-gray-200 text-gray-700'">
                    <a href="{{ $episode->video_url }}" target="_blank" class="text-[#0056b3] hover:underline">
                        {{ $episode->video_url }}
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Descriptions (Multi-language) -->
    @php
        $hasDescriptions = false;
        foreach(['ar', 'en', 'fr', 'tr', 'ur'] as $lang) {
            if (!empty($episode->description[$lang])) {
                $hasDescriptions = true;
                break;
            }
        }
    @endphp

    @if($hasDescriptions)
        <div class="glass rounded-lg p-8 transition-colors" :class="darkMode ? 'bg-gray-900/50' : 'bg-white/80'">
            <h2 class="text-2xl font-bold mb-6" :class="darkMode ? 'text-white' : 'text-gray-900'">
                Descriptions
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @foreach(['ar' => 'العربية', 'en' => 'English', 'fr' => 'Français', 'tr' => 'Türkçe', 'ur' => 'اردو'] as $lang => $label)
                    @if(!empty($episode->description[$lang]))
                        <div class="p-6 rounded-lg border transition-colors"
                             :class="darkMode ? 'bg-gray-800/50 border-gray-700' : 'bg-gray-50 border-gray-200'">
                            <h3 class="font-bold mb-3 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                                <i data-lucide="globe" class="w-4 h-4 text-[#0056b3]"></i>
                                {{ $label }}
                            </h3>
                            <p class="leading-relaxed" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                                {{ $episode->description[$lang] }}
                            </p>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    @endif

    <!-- Delete Form (Hidden) -->
    <form id="deleteForm" action="{{ route('admin.media-episodes.destroy', $episode) }}" method="POST" class="hidden">
        @csrf
        @method('DELETE')
    </form>
</div>

@push('scripts')
<script>
function confirmDelete() {
    if (confirm('Are you sure you want to delete this episode? This action cannot be undone.')) {
        document.getElementById('deleteForm').submit();
    }
}

lucide.createIcons();
</script>
@endpush
@endsection
