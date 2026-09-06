{{--
    Content Tab Component
    Multi-language block builder for article content with TOC and summary
--}}

@props([
    'locales' => array_keys(config('app.supported_locales', ['ar' => [], 'en' => []])),
    'article' => null
])

{{-- ═══════ TAB: CONTENT (Block Builder) ═══════ --}}
<div x-show="activeTab === 'content'" x-transition class="space-y-5">

    {{-- Language Sub-Tabs --}}
    <div class="flex gap-2 overflow-x-auto tabs-scroll pb-1">
        @foreach($locales as $locale)
            <button type="button"
                    @click="activeLang = '{{ $locale }}'"
                    :class="activeLang === '{{ $locale }}'
                        ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-sm'
                        : (darkMode ? 'bg-gray-700 text-gray-300 hover:bg-gray-600' : 'bg-gray-100 text-gray-600 hover:bg-gray-200')"
                    class="px-4 py-1.5 rounded-full text-sm font-medium transition-all whitespace-nowrap flex items-center gap-1.5">
                {{ strtoupper($locale) }}
                <span x-show="getBlocks('{{ $locale }}').length > 0"
                      class="px-1.5 py-0.5 text-xs rounded-full bg-white/20"
                      x-text="getBlocks('{{ $locale }}').length"></span>
            </button>
        @endforeach
    </div>

    {{-- Per-Locale Content Panels --}}
    @foreach($locales as $locale)
    <div x-show="activeLang === '{{ $locale }}'" x-transition class="space-y-4">

        {{-- ═══ BLOCK-BASED CONTENT BUILDER ═══ --}}
        <div>
            <label class="block text-sm font-semibold mb-2"
                   :class="darkMode ? 'text-white' : 'text-gray-900'">
                {{ __('articles.fields.body') }} ({{ strtoupper($locale) }})
                <span class="text-red-500">*</span>
            </label>

            {{-- Add Block Toolbar --}}
            <div class="flex flex-wrap gap-2 p-3 rounded-t-lg border border-b-0"
                 :class="darkMode ? 'bg-gray-900/50 border-gray-700' : 'bg-gray-50 border-gray-200'">
                <span class="text-xs font-medium self-center me-2" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">
                    {{ __('articles.blocks.add') }}:
                </span>
                <button type="button" @click="addBlock('wysiwyg', '{{ $locale }}')"
                        class="px-3 py-1.5 rounded-md text-xs font-medium transition-colors flex items-center gap-1.5"
                        :class="darkMode ? 'bg-gray-700 text-gray-300 hover:bg-gray-600' : 'bg-white text-gray-700 hover:bg-gray-100 shadow-sm'">
                    <i data-lucide="type" class="w-3.5 h-3.5"></i>{{ __('articles.blocks.text') }}
                </button>
                {{-- Quran, Hadith and Sahabi are inserted from inside the WYSIWYG
                     toolbar (.ql-module-btn--*), so they embed in the text where the
                     writer is typing. Standalone block buttons for them were a second,
                     duplicate insertion path and are gone. --}}
                <button type="button" @click="openModuleSelector('seerah', '{{ $locale }}')"
                        class="px-3 py-1.5 rounded-md text-xs font-medium transition-colors flex items-center gap-1.5 bg-purple-100 text-purple-700 hover:bg-purple-200 dark:bg-purple-900/30 dark:text-purple-400 dark:hover:bg-purple-900/50">
                    <i data-lucide="map-pin" class="w-3.5 h-3.5"></i>{{ __('articles.modules.seerah') }}
                </button>
            </div>

            {{-- Blocks Container --}}
            <div class="border rounded-b-lg min-h-[200px] p-3 space-y-3"
                 :class="darkMode ? 'border-gray-700 bg-gray-800/50' : 'border-gray-200 bg-white'">

                {{-- Empty State --}}
                <div x-show="!getBlocks('{{ $locale }}').length"
                     class="text-center py-12 border-2 border-dashed rounded-lg"
                     :class="darkMode ? 'border-gray-700' : 'border-gray-300'">
                    <i data-lucide="layers" class="w-10 h-10 mx-auto mb-2 text-gray-400"></i>
                    <p class="text-sm mb-2" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">{{ __('articles.blocks.empty') }}</p>
                    <button type="button" @click="addBlock('wysiwyg', '{{ $locale }}')"
                            class="text-sm text-[#0056b3] hover:underline">{{ __('articles.blocks.add_first') }}</button>
                </div>

                {{-- Block List --}}
                <template x-for="(block, blockIdx) in getBlocks('{{ $locale }}')" :key="block.id">
                    <div class="group relative rounded-lg border transition-all"
                         :class="{
                            'border-gray-600 bg-gray-800/30': darkMode && block.type === 'wysiwyg',
                            'border-gray-200 bg-white': !darkMode && block.type === 'wysiwyg',
                            'border-green-300 dark:border-green-800 bg-green-50 dark:bg-green-900/20': block.type === 'verse',
                            'border-blue-300 dark:border-blue-800 bg-blue-50 dark:bg-blue-900/20': block.type === 'hadith',
                            'border-amber-300 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/20': block.type === 'sahabi',
                            'border-purple-300 dark:border-purple-800 bg-purple-50 dark:bg-purple-900/20': block.type === 'seerah'
                         }">

                        {{-- Block Controls --}}
                        <div class="absolute -start-10 top-1/2 -translate-y-1/2 flex-col gap-1 hidden group-hover:flex z-10">
                            <button type="button" @click="moveBlock('{{ $locale }}', blockIdx, -1)"
                                    :disabled="blockIdx === 0"
                                    class="p-1 rounded bg-white dark:bg-gray-700 shadow border border-gray-200 dark:border-gray-600 text-gray-500 hover:text-gray-700 disabled:opacity-30">
                                <i data-lucide="chevron-up" class="w-3.5 h-3.5"></i>
                            </button>
                            <button type="button" @click="moveBlock('{{ $locale }}', blockIdx, 1)"
                                    :disabled="blockIdx === getBlocks('{{ $locale }}').length - 1"
                                    class="p-1 rounded bg-white dark:bg-gray-700 shadow border border-gray-200 dark:border-gray-600 text-gray-500 hover:text-gray-700 disabled:opacity-30">
                                <i data-lucide="chevron-down" class="w-3.5 h-3.5"></i>
                            </button>
                        </div>

                        {{-- Block Header Bar (clickable to collapse/expand) --}}
                        <div class="flex items-center justify-between px-3 py-1.5 border-b text-xs cursor-pointer select-none"
                             :class="[darkMode ? 'border-gray-700' : 'border-gray-200', block._collapsed ? 'border-b-0' : '']"
                             @click="block._collapsed = !block._collapsed; $nextTick(() => lucide.createIcons())">
                            <div class="flex items-center gap-2 font-medium"
                                 :class="{
                                    'text-gray-500': block.type === 'wysiwyg',
                                    'text-green-600 dark:text-green-400': block.type === 'verse',
                                    'text-blue-600 dark:text-blue-400': block.type === 'hadith',
                                    'text-amber-600 dark:text-amber-400': block.type === 'sahabi',
                                    'text-purple-600 dark:text-purple-400': block.type === 'seerah'
                                 }">
                                <i :data-lucide="block._collapsed ? 'chevron-down' : 'chevron-up'" class="w-3.5 h-3.5 transition-transform"></i>
                                <i :data-lucide="block.type === 'wysiwyg' ? 'type' : block.type === 'verse' ? 'book-open' : block.type === 'hadith' ? 'quote' : block.type === 'sahabi' ? 'users' : 'map-pin'" class="w-3.5 h-3.5"></i>
                                <span x-text="block.type === 'wysiwyg' ? '{{ __('articles.blocks.text') }}' : block.type === 'verse' ? '{{ __('articles.modules.quran') }}' : block.type === 'hadith' ? '{{ __('articles.modules.hadith') }}' : block.type === 'sahabi' ? '{{ __('articles.modules.sahabah') }}' : '{{ __('articles.modules.seerah') }}'"></span>
                                {{-- Collapsed preview snippet --}}
                                <span x-show="block._collapsed && block.type === 'wysiwyg'" class="text-gray-400 dark:text-gray-500 truncate max-w-[200px]"
                                      x-text="(block.content || '').replace(/<[^>]*>/g, '').substring(0, 50) + '...'"></span>
                                <span x-show="block._collapsed && block.type === 'verse'" class="text-gray-400 dark:text-gray-500"
                                      x-text="block.surah_name || ''"></span>
                                <span x-show="block._collapsed && block.type === 'hadith'" class="text-gray-400 dark:text-gray-500 truncate max-w-[200px]"
                                      x-text="(block.text || '').substring(0, 50) + '...'"></span>
                                <span x-show="block._collapsed && block.type === 'sahabi'" class="text-gray-400 dark:text-gray-500"
                                      x-text="block.name || ''"></span>
                                <span x-show="block._collapsed && block.type === 'seerah'" class="text-gray-400 dark:text-gray-500"
                                      x-text="block.title || ''"></span>
                            </div>
                            <div class="flex items-center gap-1" @click.stop>
                                <button type="button" @click="moveBlock('{{ $locale }}', blockIdx, -1)"
                                        :disabled="blockIdx === 0"
                                        class="p-1 rounded hover:bg-black/10 dark:hover:bg-white/10 disabled:opacity-30">
                                    <i data-lucide="arrow-up" class="w-3.5 h-3.5"></i>
                                </button>
                                <button type="button" @click="moveBlock('{{ $locale }}', blockIdx, 1)"
                                        :disabled="blockIdx === getBlocks('{{ $locale }}').length - 1"
                                        class="p-1 rounded hover:bg-black/10 dark:hover:bg-white/10 disabled:opacity-30">
                                    <i data-lucide="arrow-down" class="w-3.5 h-3.5"></i>
                                </button>
                                <button type="button" @click="removeBlock('{{ $locale }}', blockIdx)"
                                        class="p-1 rounded text-red-500 hover:bg-red-100 dark:hover:bg-red-900/30">
                                    <i data-lucide="x" class="w-3.5 h-3.5"></i>
                                </button>
                            </div>
                        </div>

                        {{-- Collapsible Content --}}
                        <div x-show="!block._collapsed" x-collapse>

                        {{-- WYSIWYG Block --}}
                        <x-admin.article.blocks.wysiwyg :locale="$locale" />

                        {{-- Verse Block --}}
                        <x-admin.article.blocks.verse />

                        {{-- Hadith Block --}}
                        <x-admin.article.blocks.hadith :locale="$locale" />

                        {{-- Sahabi Block --}}
                        <x-admin.article.blocks.sahabi />

                        {{-- Seerah Block --}}
                        <x-admin.article.blocks.seerah />

                        </div>{{-- end collapsible --}}
                    </div>
                </template>
            </div>

            {{-- Hidden textarea for serialized blocks --}}
            <textarea name="content_blocks[{{ $locale }}]"
                      id="content_blocks_{{ $locale }}"
                      class="hidden"></textarea>
        </div>

        {{-- TOC Manager --}}
        <x-admin.toc-manager
            name="toc"
            :locale="$locale"
            :content="'content_' . $locale"
            :values="old('toc.' . $locale, $article && $article->getArticleLocale($locale) ? ($article->getArticleLocale($locale)->content['toc'] ?? []) : [])"
            :label="__('articles.fields.toc')"
        />

        {{-- Summary (Quill) --}}
        <div>
            <label class="block text-sm font-semibold mb-1.5"
                   :class="darkMode ? 'text-white' : 'text-gray-900'">
                {{ __('articles.fields.summary') }}
            </label>
            <div id="summary_{{ $locale }}_editor" class="quill-editor" style="height: 120px;"></div>
            <textarea name="summary[{{ $locale }}]"
                      id="summary_{{ $locale }}"
                      class="hidden">{{ old("summary.{$locale}", $article && $article->getArticleLocale($locale) ? ($article->getArticleLocale($locale)->content['summary'] ?? '') : '') }}</textarea>
        </div>
    </div>
    @endforeach
</div>
