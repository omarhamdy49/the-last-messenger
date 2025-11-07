@extends('layouts.admin')

@section('page-header')
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <!-- Back Button & Title -->
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.articles.index') }}"
               class="p-2 rounded-xl hover:bg-accent/10 transition-colors group"
               :class="darkMode ? 'text-gray-400 hover:text-accent' : 'text-gray-600 hover:text-accent'">
                <i data-lucide="arrow-left" class="w-6 h-6"></i>
            </a>

            <div>
                <h1 class="text-3xl font-bold gradient-text mb-1">
                    {{ $article->translations->where('language', app()->getLocale())->first()->title ?? 'Article Details' }}
                </h1>
                <p class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                    Created {{ $article->created_at->diffForHumans() }}
                </p>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex items-center gap-3 flex-wrap">
            <!-- Status Badge -->
            <span class="px-4 py-2 rounded-full text-sm font-semibold
                         @if($article->status === 'published') bg-gradient-to-r from-brand to-secondary text-white
                         @elseif($article->status === 'draft') bg-gray-500 text-white
                         @elseif($article->status === 'scheduled') bg-yellow-500 text-white
                         @else bg-red-500 text-white
                         @endif">
                <i data-lucide="{{ $article->status === 'published' ? 'check-circle' : ($article->status === 'scheduled' ? 'clock' : 'file-text') }}"
                   class="w-4 h-4 inline-block -mt-1"></i>
                {{ ucfirst($article->status) }}
            </span>

            <!-- Preview Button -->
            <button @click="$dispatch('open-preview-modal', { articleId: {{ $article->id }} })"
                    class="px-6 py-2.5 rounded-xl font-semibold transition-all hover:shadow-lg
                           glass border"
                    :class="darkMode ? 'glass-dark text-white hover:border-accent' : 'glass-light text-gray-900 hover:border-accent'">
                <i data-lucide="eye" class="w-4 h-4 inline-block -mt-1 me-2"></i>
                Preview
            </button>

            <!-- Edit Button -->
            <a href="{{ route('admin.articles.edit', $article->id) }}"
               class="px-6 py-2.5 rounded-xl font-semibold transition-all hover:shadow-lg
                      bg-gradient-to-r from-brand to-secondary text-white hover:opacity-90">
                <i data-lucide="edit" class="w-4 h-4 inline-block -mt-1 me-2"></i>
                Edit
            </a>

            <!-- More Actions Dropdown -->
            <div x-data="{ open: false }" class="relative">
                <button @click="open = !open"
                        class="p-2.5 rounded-xl glass border transition-all"
                        :class="darkMode ? 'glass-dark hover:border-accent' : 'glass-light hover:border-accent'">
                    <i data-lucide="more-vertical" class="w-5 h-5"></i>
                </button>

                <!-- Dropdown Menu -->
                <div x-show="open"
                     @click.away="open = false"
                     x-cloak
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100 scale-100"
                     x-transition:leave-end="opacity-0 scale-95"
                     class="absolute end-0 mt-2 w-56 rounded-xl shadow-xl overflow-hidden z-50 glass border"
                     :class="darkMode ? 'glass-dark' : 'glass-light'">

                    <a href="{{ route('admin.articles.duplicate', $article->id) }}"
                       class="flex items-center gap-3 px-4 py-3 hover:bg-accent/10 transition-colors"
                       :class="darkMode ? 'text-gray-300 hover:text-accent' : 'text-gray-700 hover:text-accent'">
                        <i data-lucide="copy" class="w-4 h-4"></i>
                        <span>Duplicate</span>
                    </a>

                    <a href="{{ route('admin.articles.export', $article->id) }}"
                       class="flex items-center gap-3 px-4 py-3 hover:bg-accent/10 transition-colors"
                       :class="darkMode ? 'text-gray-300 hover:text-accent' : 'text-gray-700 hover:text-accent'">
                        <i data-lucide="download" class="w-4 h-4"></i>
                        <span>Export</span>
                    </a>

                    <div class="border-t" :class="darkMode ? 'border-gray-800' : 'border-gray-200'"></div>

                    <form action="{{ route('admin.articles.destroy', $article->id) }}" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                onclick="return confirm('Are you sure you want to delete this article?')"
                                class="flex items-center gap-3 px-4 py-3 hover:bg-red-500/10 text-red-500 w-full transition-colors">
                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                            <span>Delete</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <div class="max-w-7xl mx-auto">
        <!-- Statistics Widget -->
        <x-admin.article-stats :article="$article" />

        <!-- Main Content Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mt-8">
            <!-- Left Column - Article Content -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Featured Image Card -->
                @if($article->featured_image)
                    <div class="rounded-2xl overflow-hidden shadow-lg glass border"
                         :class="darkMode ? 'glass-dark' : 'glass-light'">
                        <div class="aspect-video relative group">
                            <img src="{{ $article->featured_image }}"
                                 alt="{{ $article->translations->first()->title }}"
                                 class="w-full h-full object-cover">
                            <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                        </div>
                    </div>
                @endif

                <!-- Language Tabs for Content -->
                <div class="rounded-2xl overflow-hidden shadow-lg glass border"
                     :class="darkMode ? 'glass-dark' : 'glass-light'"
                     x-data="{ activeTab: '{{ app()->getLocale() }}' }">

                    <!-- Tab Headers -->
                    <div class="flex gap-2 p-4 border-b overflow-x-auto custom-scrollbar"
                         :class="darkMode ? 'border-gray-800' : 'border-gray-200'">
                        @foreach($article->translations as $translation)
                            <button @click="activeTab = '{{ $translation->language }}'"
                                    class="px-6 py-2 rounded-xl font-semibold whitespace-nowrap transition-all"
                                    :class="activeTab === '{{ $translation->language }}' ?
                                        'bg-gradient-to-r from-brand to-secondary text-white shadow-lg' :
                                        (darkMode ? 'text-gray-400 hover:text-white hover:bg-gray-800' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100')">
                                {{ strtoupper($translation->language) }}
                            </button>
                        @endforeach
                    </div>

                    <!-- Tab Content -->
                    <div class="p-6">
                        @foreach($article->translations as $translation)
                            <div x-show="activeTab === '{{ $translation->language }}'"
                                 x-cloak
                                 x-transition:enter="transition ease-out duration-300"
                                 x-transition:enter-start="opacity-0 transform translate-y-4"
                                 x-transition:enter-end="opacity-100 transform translate-y-0">

                                <!-- Title -->
                                <h2 class="text-2xl font-bold mb-4"
                                    :class="darkMode ? 'text-white' : 'text-gray-900'">
                                    {{ $translation->title }}
                                </h2>

                                <!-- Excerpt -->
                                @if($translation->excerpt)
                                    <div class="mb-6 p-4 rounded-xl"
                                         :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-100'">
                                        <p class="text-lg leading-relaxed"
                                           :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                                            {{ $translation->excerpt }}
                                        </p>
                                    </div>
                                @endif

                                <!-- Content -->
                                <div class="prose prose-lg max-w-none"
                                     :class="darkMode ? 'prose-invert' : 'prose-gray'">
                                    {!! $translation->content !!}
                                </div>

                                <!-- SEO Meta -->
                                @if($translation->meta_title || $translation->meta_description)
                                    <div class="mt-6 pt-6 border-t"
                                         :class="darkMode ? 'border-gray-800' : 'border-gray-200'">
                                        <h3 class="text-sm font-semibold mb-3 uppercase tracking-wide"
                                            :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                                            SEO Metadata
                                        </h3>
                                        @if($translation->meta_title)
                                            <p class="mb-2">
                                                <span class="font-semibold"
                                                      :class="darkMode ? 'text-gray-300' : 'text-gray-700'">Title:</span>
                                                <span :class="darkMode ? 'text-gray-400' : 'text-gray-600'">{{ $translation->meta_title }}</span>
                                            </p>
                                        @endif
                                        @if($translation->meta_description)
                                            <p>
                                                <span class="font-semibold"
                                                      :class="darkMode ? 'text-gray-300' : 'text-gray-700'">Description:</span>
                                                <span :class="darkMode ? 'text-gray-400' : 'text-gray-600'">{{ $translation->meta_description }}</span>
                                            </p>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Right Column - Sidebar -->
            <div class="space-y-6">
                <!-- Publishing Info Card -->
                <div class="rounded-2xl overflow-hidden shadow-lg glass border p-6"
                     :class="darkMode ? 'glass-dark' : 'glass-light'">
                    <h3 class="text-lg font-bold mb-4"
                        :class="darkMode ? 'text-white' : 'text-gray-900'">
                        Publishing Details
                    </h3>

                    <div class="space-y-4">
                        <!-- Author -->
                        <div class="flex items-center gap-3">
                            <div class="p-2 rounded-lg bg-gradient-to-br from-brand to-secondary">
                                <i data-lucide="user" class="w-4 h-4 text-white"></i>
                            </div>
                            <div>
                                <p class="text-xs" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">Author</p>
                                <p class="font-semibold" :class="darkMode ? 'text-white' : 'text-gray-900'">
                                    {{ $article->author->name ?? 'Unknown' }}
                                </p>
                            </div>
                        </div>

                        <!-- Published Date -->
                        @if($article->published_at)
                            <div class="flex items-center gap-3">
                                <div class="p-2 rounded-lg bg-gradient-to-br from-brand to-secondary">
                                    <i data-lucide="calendar" class="w-4 h-4 text-white"></i>
                                </div>
                                <div>
                                    <p class="text-xs" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">Published</p>
                                    <p class="font-semibold" :class="darkMode ? 'text-white' : 'text-gray-900'">
                                        {{ $article->published_at->format('M d, Y') }}
                                    </p>
                                </div>
                            </div>
                        @endif

                        <!-- Last Updated -->
                        <div class="flex items-center gap-3">
                            <div class="p-2 rounded-lg bg-gradient-to-br from-brand to-secondary">
                                <i data-lucide="clock" class="w-4 h-4 text-white"></i>
                            </div>
                            <div>
                                <p class="text-xs" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">Last Updated</p>
                                <p class="font-semibold" :class="darkMode ? 'text-white' : 'text-gray-900'">
                                    {{ $article->updated_at->diffForHumans() }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Category Card -->
                @if($article->category)
                    <div class="rounded-2xl overflow-hidden shadow-lg glass border p-6"
                         :class="darkMode ? 'glass-dark' : 'glass-light'">
                        <h3 class="text-lg font-bold mb-4"
                            :class="darkMode ? 'text-white' : 'text-gray-900'">
                            Category
                        </h3>
                        <a href="{{ route('admin.categories.show', $article->category->id) }}"
                           class="flex items-center gap-3 p-3 rounded-xl hover:bg-accent/10 transition-colors group">
                            <div class="p-2 rounded-lg bg-gradient-to-br from-brand to-secondary">
                                <i data-lucide="folder" class="w-4 h-4 text-white"></i>
                            </div>
                            <span class="font-semibold group-hover:text-accent transition-colors"
                                  :class="darkMode ? 'text-white' : 'text-gray-900'">
                                {{ $article->category->name }}
                            </span>
                        </a>
                    </div>
                @endif

                <!-- Tags Card -->
                @if($article->tags->count() > 0)
                    <div class="rounded-2xl overflow-hidden shadow-lg glass border p-6"
                         :class="darkMode ? 'glass-dark' : 'glass-light'">
                        <h3 class="text-lg font-bold mb-4"
                            :class="darkMode ? 'text-white' : 'text-gray-900'">
                            Tags
                        </h3>
                        <div class="flex flex-wrap gap-2">
                            @foreach($article->tags as $tag)
                                <a href="{{ route('admin.tags.show', $tag->id) }}"
                                   class="px-4 py-2 rounded-full text-sm font-semibold hover:shadow-lg transition-all
                                          bg-gradient-to-r from-brand to-secondary text-white hover:opacity-90">
                                    {{ $tag->name }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Publish Scheduler -->
                <x-admin.publish-scheduler :article="$article" />
            </div>
        </div>
    </div>

    <!-- Preview Modal -->
    <x-admin.article-preview-modal :article="$article" />
@endsection

@push('styles')
<style>
    /* Prose styles for article content */
    .prose {
        color: inherit;
    }

    .prose h1,
    .prose h2,
    .prose h3,
    .prose h4,
    .prose h5,
    .prose h6 {
        color: inherit;
        font-weight: 700;
    }

    .prose strong {
        font-weight: 600;
    }

    .prose a {
        color: #64CCC5;
        text-decoration: underline;
    }

    .prose a:hover {
        color: #0056b3;
    }

    .prose img {
        border-radius: 1rem;
        margin: 2rem auto;
    }

    .prose blockquote {
        border-left: 4px solid #64CCC5;
        padding-left: 1rem;
        font-style: italic;
    }

    .dark .prose blockquote {
        border-left-color: #64CCC5;
    }

    .prose code {
        background: rgba(100, 204, 197, 0.1);
        padding: 0.25rem 0.5rem;
        border-radius: 0.375rem;
        font-size: 0.875em;
    }

    .prose pre {
        background: rgba(0, 0, 0, 0.05);
        border-radius: 0.75rem;
        padding: 1rem;
    }

    .dark .prose pre {
        background: rgba(255, 255, 255, 0.05);
    }
</style>
@endpush
