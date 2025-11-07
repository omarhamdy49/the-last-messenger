{{--
/**
 * Article Preview Modal Component
 *
 * Full-screen modal for previewing article content in public-facing format.
 * Features language switching, responsive design, and dark mode support.
 *
 * @props
 * - article: Article model (required) - The article to preview
 *
 * @example
 * <x-admin.article-preview-modal :article="$article" />
 */
--}}

@props(['article'])

<div x-data="{
        showPreview: false,
        previewLang: '{{ app()->getLocale() }}',
        articleData: @js($article),

        init() {
            this.$watch('showPreview', value => {
                if (value) {
                    document.body.style.overflow = 'hidden';
                    this.$nextTick(() => lucide.createIcons());
                } else {
                    document.body.style.overflow = '';
                }
            });
        }
     }"
     @open-preview-modal.window="showPreview = true; previewLang = '{{ app()->getLocale() }}'"
     @keydown.escape.window="showPreview = false"
     class="relative z-50">

    <!-- Modal Backdrop & Container -->
    <div x-show="showPreview"
         x-cloak
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-black/80 backdrop-blur-md overflow-hidden">

        <!-- Modal Panel -->
        <div class="fixed inset-0 overflow-y-auto custom-scrollbar">
            <div class="min-h-full flex items-center justify-center p-4">
                <div @click.away="showPreview = false"
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100"
                     x-transition:leave="transition ease-in duration-200"
                     x-transition:leave-start="opacity-100 scale-100"
                     x-transition:leave-end="opacity-0 scale-95"
                     class="relative w-full max-w-5xl rounded-2xl shadow-2xl overflow-hidden"
                     :class="darkMode ? 'bg-gray-900' : 'bg-white'">

                    <!-- Modal Header - Sticky -->
                    <div class="sticky top-0 z-10 glass border-b"
                         :class="darkMode ? 'glass-dark border-gray-800' : 'glass-light border-gray-200'">
                        <div class="flex items-center justify-between p-6">
                            <!-- Title -->
                            <div class="flex-1">
                                <h2 class="text-2xl font-bold gradient-text mb-1">
                                    Article Preview
                                </h2>
                                <p class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                                    Preview how your article will appear to readers
                                </p>
                            </div>

                            <!-- Language Switcher -->
                            <div class="flex items-center gap-3 mx-6">
                                @foreach($article->translations as $translation)
                                    <button @click="previewLang = '{{ $translation->language }}'; $nextTick(() => lucide.createIcons())"
                                            class="px-4 py-2 rounded-xl font-semibold transition-all"
                                            :class="previewLang === '{{ $translation->language }}' ?
                                                'bg-gradient-to-r from-brand to-secondary text-white shadow-lg' :
                                                (darkMode ? 'text-gray-400 hover:text-white hover:bg-gray-800' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100')">
                                        {{ strtoupper($translation->language) }}
                                    </button>
                                @endforeach
                            </div>

                            <!-- Action Buttons -->
                            <div class="flex items-center gap-3">
                                <!-- Edit Button -->
                                <a href="{{ route('admin.articles.edit', $article->id) }}"
                                   class="px-4 py-2 rounded-xl font-semibold transition-all hover:shadow-lg
                                          bg-gradient-to-r from-brand to-secondary text-white hover:opacity-90">
                                    <i data-lucide="edit" class="w-4 h-4 inline-block -mt-1 me-2"></i>
                                    Edit
                                </a>

                                <!-- Close Button -->
                                <button @click="showPreview = false"
                                        class="p-2 rounded-xl hover:bg-red-500/10 text-red-500 transition-colors">
                                    <i data-lucide="x" class="w-6 h-6"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Body - Article Content -->
                    <div class="p-8 lg:p-12">
                        @foreach($article->translations as $translation)
                            <div x-show="previewLang === '{{ $translation->language }}'"
                                 x-cloak
                                 x-transition:enter="transition ease-out duration-300"
                                 x-transition:enter-start="opacity-0 transform translate-y-4"
                                 x-transition:enter-end="opacity-100 transform translate-y-0">

                                <!-- Article Meta -->
                                <div class="flex flex-wrap items-center gap-4 mb-6 pb-6 border-b"
                                     :class="darkMode ? 'border-gray-800' : 'border-gray-200'">
                                    <!-- Category Badge -->
                                    @if($article->category)
                                        <span class="px-4 py-2 rounded-full text-sm font-semibold
                                                     bg-gradient-to-r from-brand to-secondary text-white">
                                            <i data-lucide="folder" class="w-3 h-3 inline-block -mt-1 me-1"></i>
                                            {{ $article->category->name }}
                                        </span>
                                    @endif

                                    <!-- Author -->
                                    <div class="flex items-center gap-2">
                                        <i data-lucide="user" class="w-4 h-4" :class="darkMode ? 'text-gray-400' : 'text-gray-600'"></i>
                                        <span class="text-sm font-medium" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                                            {{ $article->author->name ?? 'Unknown' }}
                                        </span>
                                    </div>

                                    <!-- Date -->
                                    @if($article->published_at)
                                        <div class="flex items-center gap-2">
                                            <i data-lucide="calendar" class="w-4 h-4" :class="darkMode ? 'text-gray-400' : 'text-gray-600'"></i>
                                            <span class="text-sm font-medium" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                                                {{ $article->published_at->format('M d, Y') }}
                                            </span>
                                        </div>
                                    @endif

                                    <!-- Reading Time (estimate) -->
                                    <div class="flex items-center gap-2">
                                        <i data-lucide="clock" class="w-4 h-4" :class="darkMode ? 'text-gray-400' : 'text-gray-600'"></i>
                                        <span class="text-sm font-medium" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                                            {{ ceil(str_word_count(strip_tags($translation->content)) / 200) }} min read
                                        </span>
                                    </div>
                                </div>

                                <!-- Featured Image -->
                                @if($article->featured_image)
                                    <div class="mb-8 rounded-2xl overflow-hidden shadow-xl">
                                        <img src="{{ $article->featured_image }}"
                                             alt="{{ $translation->title }}"
                                             class="w-full h-auto object-cover">
                                    </div>
                                @endif

                                <!-- Article Title -->
                                <h1 class="text-4xl lg:text-5xl font-bold mb-4 leading-tight"
                                    :class="darkMode ? 'text-white' : 'text-gray-900'">
                                    {{ $translation->title }}
                                </h1>

                                <!-- Article Excerpt -->
                                @if($translation->excerpt)
                                    <div class="mb-8 p-6 rounded-xl"
                                         :class="darkMode ? 'bg-accent/10 border border-accent/20' : 'bg-gray-100 border border-gray-200'">
                                        <p class="text-xl leading-relaxed font-medium"
                                           :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                                            {{ $translation->excerpt }}
                                        </p>
                                    </div>
                                @endif

                                <!-- Article Content -->
                                <div class="prose prose-lg lg:prose-xl max-w-none"
                                     :class="darkMode ? 'prose-invert' : 'prose-gray'">
                                    {!! $translation->content !!}
                                </div>

                                <!-- Article Tags -->
                                @if($article->tags->count() > 0)
                                    <div class="mt-12 pt-8 border-t"
                                         :class="darkMode ? 'border-gray-800' : 'border-gray-200'">
                                        <h3 class="text-sm font-semibold mb-4 uppercase tracking-wide"
                                            :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                                            Tags
                                        </h3>
                                        <div class="flex flex-wrap gap-3">
                                            @foreach($article->tags as $tag)
                                                <span class="px-4 py-2 rounded-full text-sm font-semibold
                                                             bg-gradient-to-r from-brand/10 to-secondary/10
                                                             border border-accent/20"
                                                      :class="darkMode ? 'text-accent' : 'text-brand'">
                                                    #{{ $tag->name }}
                                                </span>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                <!-- Share Section -->
                                <div class="mt-12 pt-8 border-t"
                                     :class="darkMode ? 'border-gray-800' : 'border-gray-200'">
                                    <h3 class="text-sm font-semibold mb-4 uppercase tracking-wide"
                                        :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                                        Share this article
                                    </h3>
                                    <div class="flex items-center gap-3">
                                        <button class="p-3 rounded-xl hover:bg-accent/10 transition-colors group">
                                            <i data-lucide="facebook" class="w-5 h-5 group-hover:text-accent transition-colors"
                                               :class="darkMode ? 'text-gray-400' : 'text-gray-600'"></i>
                                        </button>
                                        <button class="p-3 rounded-xl hover:bg-accent/10 transition-colors group">
                                            <i data-lucide="twitter" class="w-5 h-5 group-hover:text-accent transition-colors"
                                               :class="darkMode ? 'text-gray-400' : 'text-gray-600'"></i>
                                        </button>
                                        <button class="p-3 rounded-xl hover:bg-accent/10 transition-colors group">
                                            <i data-lucide="linkedin" class="w-5 h-5 group-hover:text-accent transition-colors"
                                               :class="darkMode ? 'text-gray-400' : 'text-gray-600'"></i>
                                        </button>
                                        <button class="p-3 rounded-xl hover:bg-accent/10 transition-colors group">
                                            <i data-lucide="mail" class="w-5 h-5 group-hover:text-accent transition-colors"
                                               :class="darkMode ? 'text-gray-400' : 'text-gray-600'"></i>
                                        </button>
                                        <button class="p-3 rounded-xl hover:bg-accent/10 transition-colors group">
                                            <i data-lucide="link" class="w-5 h-5 group-hover:text-accent transition-colors"
                                               :class="darkMode ? 'text-gray-400' : 'text-gray-600'"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Modal Footer - Sticky -->
                    <div class="sticky bottom-0 glass border-t p-6"
                         :class="darkMode ? 'glass-dark border-gray-800' : 'glass-light border-gray-200'">
                        <div class="flex items-center justify-between">
                            <p class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                                This is a preview. Changes are not visible to public until published.
                            </p>
                            <div class="flex items-center gap-3">
                                <button @click="showPreview = false"
                                        class="px-6 py-2.5 rounded-xl font-semibold transition-all
                                               border hover:bg-accent/10 hover:border-accent"
                                        :class="darkMode ? 'border-gray-700 text-gray-300' : 'border-gray-300 text-gray-700'">
                                    Close
                                </button>
                                <a href="{{ route('admin.articles.edit', $article->id) }}"
                                   class="px-6 py-2.5 rounded-xl font-semibold transition-all hover:shadow-lg
                                          bg-gradient-to-r from-brand to-secondary text-white hover:opacity-90">
                                    <i data-lucide="edit" class="w-4 h-4 inline-block -mt-1 me-2"></i>
                                    Edit Article
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    /* Enhanced prose styles for preview */
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
        margin-top: 2em;
        margin-bottom: 1em;
    }

    .prose p {
        margin-bottom: 1.5em;
        line-height: 1.8;
    }

    .prose strong {
        font-weight: 600;
    }

    .prose a {
        color: #64CCC5;
        text-decoration: underline;
        transition: color 0.2s;
    }

    .prose a:hover {
        color: #0056b3;
    }

    .prose img {
        border-radius: 1rem;
        margin: 2rem auto;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    }

    .prose blockquote {
        border-left: 4px solid #64CCC5;
        padding-left: 1.5rem;
        font-style: italic;
        margin: 2rem 0;
    }

    .prose ul,
    .prose ol {
        margin: 1.5rem 0;
        padding-left: 1.5rem;
    }

    .prose li {
        margin-bottom: 0.5rem;
    }

    .prose code {
        background: rgba(100, 204, 197, 0.1);
        padding: 0.25rem 0.5rem;
        border-radius: 0.375rem;
        font-size: 0.875em;
        font-family: 'Courier New', monospace;
    }

    .prose pre {
        background: rgba(0, 0, 0, 0.05);
        border-radius: 0.75rem;
        padding: 1.5rem;
        overflow-x: auto;
        margin: 2rem 0;
    }

    .dark .prose pre {
        background: rgba(255, 255, 255, 0.05);
    }

    .prose pre code {
        background: transparent;
        padding: 0;
    }

    .prose table {
        width: 100%;
        border-collapse: collapse;
        margin: 2rem 0;
    }

    .prose th,
    .prose td {
        border: 1px solid rgba(100, 204, 197, 0.2);
        padding: 0.75rem;
    }

    .prose th {
        background: rgba(100, 204, 197, 0.1);
        font-weight: 600;
    }
</style>
@endpush
