{{--
    Premium Article Wizard with Block-Based Content Builder
    Features: Multi-step form, drag-drop blocks (WYSIWYG, Quran, Hadith, Sahabah), advanced controls
--}}

@props([
    'article' => null,
    'formArticle' => null,
    'categories' => [],
    'writers' => [],
    'tags' => [],
    'locales' => array_keys(config('app.supported_locales', ['ar' => [], 'en' => []]))
])

@php
    $isEdit = $article && $article->exists;
    $actionUrl = $isEdit ? route('admin.articles.update', $article) : route('admin.articles.store');
@endphp

<style>
    /* Wizard Styles */
    .wizard-step {
        display: none;
    }

    .wizard-step.active {
        display: block;
        animation: fadeIn 0.3s ease-in;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* Block Styles */
    .content-block {
        position: relative;
        transition: all 0.3s ease;
        border: 2px solid transparent;
    }

    .content-block:hover {
        border-color: #64CCC5;
        box-shadow: 0 8px 24px rgba(100, 204, 197, 0.15);
    }

    .content-block.dragging {
        opacity: 0.5;
        transform: scale(0.95);
    }

    .block-handle {
        cursor: grab;
        transition: all 0.2s;
    }

    .block-handle:active {
        cursor: grabbing;
    }

    /* Gradient borders for different block types */
    .block-wysiwyg { border-color: rgba(0, 86, 179, 0.2); }
    .block-quran { border-color: rgba(0, 150, 136, 0.2); }
    .block-hadith { border-color: rgba(33, 136, 56, 0.2); }
    .block-sahabah { border-color: rgba(245, 158, 11, 0.2); }

    /* Smooth transitions */
    .step-progress {
        transition: width 0.3s ease;
    }
</style>

<form action="{{ $actionUrl }}"
      method="POST"
      enctype="multipart/form-data"
      x-data="articleWizard(@js($formArticle ?? $article ?? null), @js($locales))"
      @submit.prevent="handleSubmit"
      class="min-h-screen pb-12">

    @csrf
    @if($isEdit)
        @method('PUT')
    @endif

    <!-- Header with Wizard Progress -->
    <div class="sticky top-0 z-40 bg-gradient-to-r from-[#0056b3]/95 to-[#218838]/95 backdrop-blur-xl border-b border-white/10 shadow-lg">
        <div class="max-w-7xl mx-auto px-6 py-6">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-3xl font-bold text-white mb-1">
                        <i data-lucide="wand-2" class="w-8 h-8 inline me-3"></i>
                        {{ $isEdit ? __('articles.edit_title') : __('articles.create_title') }}
                    </h1>
                    <p class="text-white/80 text-sm">{{ __('articles.wizard_subtitle') }}</p>
                </div>
                <button type="button"
                        @click="currentStep = Math.max(1, currentStep - 1)"
                        :disabled="currentStep === 1"
                        class="px-4 py-2 rounded-lg bg-white/20 hover:bg-white/30 disabled:opacity-50 disabled:cursor-not-allowed text-white transition-all">
                    <i data-lucide="arrow-right" class="w-5 h-5 inline"></i>
                </button>
            </div>

            <!-- Progress Bar -->
            <div class="w-full bg-white/20 rounded-full h-1 overflow-hidden">
                <div class="step-progress h-full bg-gradient-to-r from-[#64CCC5] to-[#218838]"
                     :style="`width: ${(currentStep / totalSteps) * 100}%`"></div>
            </div>

            <!-- Step Indicators -->
            <div class="flex items-center justify-between mt-4 text-xs text-white/80">
                <template x-for="step in totalSteps" :key="step">
                    <div class="flex flex-col items-center cursor-pointer"
                         @click="currentStep = step"
                         :class="currentStep >= step ? 'opacity-100' : 'opacity-50'">
                        <div class="w-8 h-8 rounded-full mb-2 flex items-center justify-center font-bold"
                             :class="currentStep === step ?
                                'bg-[#64CCC5] text-gray-900' :
                                (currentStep > step ? 'bg-white/30' : 'bg-white/10')">
                            <span x-text="step"></span>
                        </div>
                        <span x-text="stepTitles[step - 1]" class="text-center text-xs"></span>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-6 py-8">

        <!-- STEP 1: Basic Information -->
        <div class="wizard-step" :class="{ active: currentStep === 1 }">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-8 space-y-6">

                <div class="grid md:grid-cols-2 gap-6">
                    <!-- Title -->
                    <div class="md:col-span-2">
                        <label class="block text-sm font-semibold mb-3" :class="darkMode ? 'text-white' : 'text-gray-900'">
                            {{ __('articles.fields.title') }}
                        </label>
                        <input type="text"
                               name="title"
                               x-model="formData.title"
                               placeholder="{{ __('articles.placeholders.title') }}"
                               class="w-full px-4 py-3 text-lg rounded-xl border-2 border-transparent focus:border-[#64CCC5] transition-all"
                               :class="darkMode ? 'bg-gray-700 text-white' : 'bg-gray-50 text-gray-900'">
                    </div>

                    <!-- Slug -->
                    <div>
                        <label class="block text-sm font-semibold mb-3" :class="darkMode ? 'text-white' : 'text-gray-900'">
                            {{ __('articles.fields.slug') }}
                        </label>
                        <input type="text"
                               name="slug"
                               x-model="formData.slug"
                               placeholder="article-slug"
                               class="w-full px-4 py-3 rounded-xl border-2 border-transparent focus:border-[#64CCC5] transition-all"
                               :class="darkMode ? 'bg-gray-700 text-white' : 'bg-gray-50 text-gray-900'">
                    </div>

                    <!-- Category -->
                    <div>
                        <label class="block text-sm font-semibold mb-3" :class="darkMode ? 'text-white' : 'text-gray-900'">
                            {{ __('articles.fields.primary_category') }}
                        </label>
                        <select name="category_id"
                                x-model="formData.category_id"
                                class="w-full px-4 py-3 rounded-xl border-2 border-transparent focus:border-[#64CCC5] transition-all"
                                :class="darkMode ? 'bg-gray-700 text-white' : 'bg-gray-50 text-gray-900'">
                            <option value="">{{ __('articles.placeholders.select_category') }}</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Writer -->
                    <div>
                        <label class="block text-sm font-semibold mb-3" :class="darkMode ? 'text-white' : 'text-gray-900'">
                            {{ __('articles.fields.writer') }}
                        </label>
                        <select name="writer_id"
                                x-model="formData.writer_id"
                                class="w-full px-4 py-3 rounded-xl border-2 border-transparent focus:border-[#64CCC5] transition-all"
                                :class="darkMode ? 'bg-gray-700 text-white' : 'bg-gray-50 text-gray-900'">
                            <option value="">{{ __('articles.placeholders.select_writer') }}</option>
                            @foreach($writers as $writer)
                                <option value="{{ $writer->id }}">{{ $writer->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Thumbnail -->
                <div>
                    <label class="block text-sm font-semibold mb-3" :class="darkMode ? 'text-white' : 'text-gray-900'">
                        {{ __('articles.fields.thumb') }}
                    </label>
                    <div class="relative aspect-video bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-800 rounded-xl overflow-hidden border-2 border-dashed border-gray-300 dark:border-gray-600">
                        <template x-if="thumbnailPreview">
                            <img :src="thumbnailPreview" class="w-full h-full object-cover" alt="Thumbnail">
                        </template>
                        <template x-if="!thumbnailPreview">
                            <div class="flex flex-col items-center justify-center h-full gap-3 cursor-pointer hover:bg-white/5 transition-all">
                                <i data-lucide="image-plus" class="w-12 h-12 text-gray-400"></i>
                                <p class="text-sm text-gray-500">{{ __('articles.placeholders.upload_thumbnail') }}</p>
                            </div>
                        </template>
                    </div>
                    <div class="grid grid-cols-2 gap-3 mt-3">
                        <button type="button"
                                @click="$dispatch('open-media-library', { target: 'thumbnail', multiple: false })"
                                class="px-4 py-2 rounded-lg bg-[#64CCC5]/20 hover:bg-[#64CCC5]/30 text-[#64CCC5] font-medium transition-all">
                            <i data-lucide="folder-open" class="w-4 h-4 inline me-2"></i>{{ __('common.select_from_library') }}
                        </button>
                        <label class="px-4 py-2 rounded-lg bg-[#0056b3]/20 hover:bg-[#0056b3]/30 text-[#0056b3] font-medium transition-all cursor-pointer text-center">
                            <i data-lucide="upload-cloud" class="w-4 h-4 inline me-2"></i>{{ __('common.upload') }}
                            <input type="file" name="thumb" accept="image/*" @change="handleThumbnailUpload" class="hidden">
                        </label>
                    </div>
                </div>

                <!-- Read Time -->
                <div class="grid md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-semibold mb-3" :class="darkMode ? 'text-white' : 'text-gray-900'">
                            {{ __('articles.fields.read_time') }} ({{ __('common.minutes') }})
                        </label>
                        <input type="number"
                               name="read_time"
                               x-model="formData.read_time"
                               min="1"
                               class="w-full px-4 py-3 rounded-xl border-2 border-transparent focus:border-[#64CCC5] transition-all"
                               :class="darkMode ? 'bg-gray-700 text-white' : 'bg-gray-50 text-gray-900'">
                    </div>

                    <!-- Status -->
                    <div>
                        <label class="block text-sm font-semibold mb-3" :class="darkMode ? 'text-white' : 'text-gray-900'">
                            {{ __('common.status') }}
                        </label>
                        <select name="status"
                                x-model="formData.status"
                                class="w-full px-4 py-3 rounded-xl border-2 border-transparent focus:border-[#64CCC5] transition-all"
                                :class="darkMode ? 'bg-gray-700 text-white' : 'bg-gray-50 text-gray-900'">
                            <option value="draft">{{ __('common.draft') }}</option>
                            <option value="published">{{ __('common.published') }}</option>
                            <option value="scheduled">{{ __('common.scheduled') }}</option>
                        </select>
                    </div>
                </div>

                <!-- Featured & Pinned -->
                <div class="grid md:grid-cols-2 gap-6 pt-4 border-t" :class="darkMode ? 'border-gray-700' : 'border-gray-200'">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" name="is_featured" x-model="formData.is_featured" class="w-5 h-5 rounded">
                        <span :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ __('articles.fields.is_featured') }}</span>
                    </label>
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" name="is_pinned" x-model="formData.is_pinned" class="w-5 h-5 rounded">
                        <span :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ __('articles.fields.is_pinned') }}</span>
                    </label>
                </div>
            </div>
        </div>

        <!-- STEP 2: Content Builder with Blocks -->
        <div class="wizard-step space-y-6" :class="{ active: currentStep === 2 }">

            <!-- Block Library -->
            <div class="grid md:grid-cols-4 gap-4">
                <!-- WYSIWYG Block -->
                <button type="button"
                        @click="addBlock('wysiwyg')"
                        class="group p-6 rounded-2xl border-2 border-dashed border-blue-200 dark:border-blue-800 hover:border-blue-500 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-all text-center">
                    <div class="w-12 h-12 rounded-xl bg-blue-500/20 flex items-center justify-center mx-auto mb-3 group-hover:bg-blue-500/30 transition-all">
                        <i data-lucide="type" class="w-6 h-6 text-blue-600"></i>
                    </div>
                    <h3 class="font-semibold mb-1" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ __('articles.blocks.wysiwyg') }}</h3>
                    <p class="text-xs opacity-70">{{ __('articles.blocks.wysiwyg_desc') }}</p>
                </button>

                <!-- Quran Block -->
                <button type="button"
                        @click="addBlock('quran')"
                        class="group p-6 rounded-2xl border-2 border-dashed border-green-200 dark:border-green-800 hover:border-green-500 hover:bg-green-50 dark:hover:bg-green-900/20 transition-all text-center">
                    <div class="w-12 h-12 rounded-xl bg-green-500/20 flex items-center justify-center mx-auto mb-3 group-hover:bg-green-500/30 transition-all">
                        <i data-lucide="book-open" class="w-6 h-6 text-green-600"></i>
                    </div>
                    <h3 class="font-semibold mb-1" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ __('articles.blocks.quran') }}</h3>
                    <p class="text-xs opacity-70">{{ __('articles.blocks.quran_desc') }}</p>
                </button>

                <!-- Hadith Block -->
                <button type="button"
                        @click="addBlock('hadith')"
                        class="group p-6 rounded-2xl border-2 border-dashed border-emerald-200 dark:border-emerald-800 hover:border-emerald-500 hover:bg-emerald-50 dark:hover:bg-emerald-900/20 transition-all text-center">
                    <div class="w-12 h-12 rounded-xl bg-emerald-500/20 flex items-center justify-center mx-auto mb-3 group-hover:bg-emerald-500/30 transition-all">
                        <i data-lucide="quote" class="w-6 h-6 text-emerald-600"></i>
                    </div>
                    <h3 class="font-semibold mb-1" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ __('articles.blocks.hadith') }}</h3>
                    <p class="text-xs opacity-70">{{ __('articles.blocks.hadith_desc') }}</p>
                </button>

                <!-- Sahabah Block -->
                <button type="button"
                        @click="addBlock('sahabah')"
                        class="group p-6 rounded-2xl border-2 border-dashed border-amber-200 dark:border-amber-800 hover:border-amber-500 hover:bg-amber-50 dark:hover:bg-amber-900/20 transition-all text-center">
                    <div class="w-12 h-12 rounded-xl bg-amber-500/20 flex items-center justify-center mx-auto mb-3 group-hover:bg-amber-500/30 transition-all">
                        <i data-lucide="users" class="w-6 h-6 text-amber-600"></i>
                    </div>
                    <h3 class="font-semibold mb-1" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ __('articles.blocks.sahabah') }}</h3>
                    <p class="text-xs opacity-70">{{ __('articles.blocks.sahabah_desc') }}</p>
                </button>
            </div>

            <!-- Content Blocks Container -->
            <div class="space-y-4 min-h-[400px] p-6 rounded-2xl border-2 border-dashed"
                 :class="darkMode ? 'bg-gray-800/50 border-gray-700' : 'bg-gray-50 border-gray-300'"
                 @dragover.prevent="dragOverZone = true"
                 @dragleave.prevent="dragOverZone = false">

                <template x-if="contentBlocks.length === 0">
                    <div class="text-center py-12 opacity-50">
                        <i data-lucide="inbox" class="w-16 h-16 mx-auto mb-3"></i>
                        <p class="text-lg">{{ __('articles.blocks.empty_state') }}</p>
                    </div>
                </template>

                <template x-for="(block, index) in contentBlocks" :key="block.id">
                    <div class="content-block group rounded-xl p-6 bg-white dark:bg-gray-700 shadow-md"
                         :class="`block-${block.type}`"
                         draggable="true"
                         @dragstart="draggedBlock = index"
                         @dragend="draggedBlock = null"
                         @dragover.prevent="dragOverIndex = index"
                         @dragleave.prevent="dragOverIndex = null"
                         @drop.prevent="reorderBlocks(index)">

                        <!-- Block Header -->
                        <div class="flex items-center justify-between mb-4 pb-4 border-b" :class="darkMode ? 'border-gray-600' : 'border-gray-200'">
                            <div class="flex items-center gap-3">
                                <div class="block-handle p-1 rounded hover:bg-gray-200 dark:hover:bg-gray-600">
                                    <i data-lucide="grip" class="w-5 h-5 text-gray-400"></i>
                                </div>
                                <span class="font-semibold text-sm uppercase" :class="darkMode ? 'text-gray-300' : 'text-gray-600'">
                                    <i :data-lucide="blockIcons[block.type]" class="w-4 h-4 inline me-2"></i>
                                    <span x-text="blockTitles[block.type]"></span>
                                </span>
                            </div>
                            <button type="button"
                                    @click="removeBlock(index)"
                                    class="p-2 rounded-lg hover:bg-red-100 dark:hover:bg-red-900/30 text-red-500 transition-all">
                                <i data-lucide="trash-2" class="w-5 h-5"></i>
                            </button>
                        </div>

                        <!-- Block Content (Dynamic based on type) -->
                        <template x-if="block.type === 'wysiwyg'">
                            <textarea :name="`blocks[${index}][content]`"
                                      x-model="block.content"
                                      placeholder="{{ __('articles.blocks.wysiwyg_placeholder') }}"
                                      rows="6"
                                      class="w-full px-4 py-3 rounded-lg border-2 border-transparent focus:border-[#0056b3] font-mono text-sm"
                                      :class="darkMode ? 'bg-gray-800 text-white' : 'bg-gray-50 text-gray-900'"></textarea>
                        </template>

                        <template x-if="block.type === 'quran'">
                            <div class="space-y-4">
                                <div class="grid md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-semibold mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                                            {{ __('articles.blocks.surah') }}
                                        </label>
                                        <select :name="`blocks[${index}][surah_id]`"
                                                x-model="block.surah_id"
                                                class="w-full px-3 py-2 rounded-lg border-2 border-transparent focus:border-[#218838]"
                                                :class="darkMode ? 'bg-gray-800 text-white' : 'bg-gray-50'">
                                            <option value="">{{ __('articles.blocks.select_surah') }}</option>
                                            <!-- Populate from backend -->
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                                            {{ __('articles.blocks.verse_range') }}
                                        </label>
                                        <input type="text"
                                               :name="`blocks[${index}][verse_range]`"
                                               x-model="block.verse_range"
                                               placeholder="1-10"
                                               class="w-full px-3 py-2 rounded-lg border-2 border-transparent focus:border-[#218838]"
                                               :class="darkMode ? 'bg-gray-800 text-white' : 'bg-gray-50'">
                                    </div>
                                </div>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" :name="`blocks[${index}][show_translation]`" x-model="block.show_translation">
                                    <span class="text-sm" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ __('articles.blocks.show_translation') }}</span>
                                </label>
                            </div>
                        </template>

                        <template x-if="block.type === 'hadith'">
                            <div class="space-y-4">
                                <div class="grid md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-semibold mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                                            {{ __('articles.blocks.hadith_book') }}
                                        </label>
                                        <select :name="`blocks[${index}][hadith_book_id]`"
                                                x-model="block.hadith_book_id"
                                                class="w-full px-3 py-2 rounded-lg border-2 border-transparent focus:border-[#218838]"
                                                :class="darkMode ? 'bg-gray-800 text-white' : 'bg-gray-50'">
                                            <option value="">{{ __('articles.blocks.select_book') }}</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                                            {{ __('articles.blocks.hadith_number') }}
                                        </label>
                                        <input type="number"
                                               :name="`blocks[${index}][hadith_number]`"
                                               x-model="block.hadith_number"
                                               class="w-full px-3 py-2 rounded-lg border-2 border-transparent focus:border-[#218838]"
                                               :class="darkMode ? 'bg-gray-800 text-white' : 'bg-gray-50'">
                                    </div>
                                </div>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" :name="`blocks[${index}][show_chain]`" x-model="block.show_chain">
                                    <span class="text-sm" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ __('articles.blocks.show_narrator_chain') }}</span>
                                </label>
                            </div>
                        </template>

                        <template x-if="block.type === 'sahabah'">
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-semibold mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                                        {{ __('articles.blocks.companion') }}
                                    </label>
                                    <select :name="`blocks[${index}][sahabah_id]`"
                                            x-model="block.sahabah_id"
                                            class="w-full px-3 py-2 rounded-lg border-2 border-transparent focus:border-[#218838]"
                                            :class="darkMode ? 'bg-gray-800 text-white' : 'bg-gray-50'">
                                        <option value="">{{ __('articles.blocks.select_companion') }}</option>
                                    </select>
                                </div>
                                <div class="grid md:grid-cols-2 gap-4">
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="checkbox" :name="`blocks[${index}][show_biography]`" x-model="block.show_biography">
                                        <span class="text-sm" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ __('articles.blocks.show_biography') }}</span>
                                    </label>
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="checkbox" :name="`blocks[${index}][show_contributions]`" x-model="block.show_contributions">
                                        <span class="text-sm" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ __('articles.blocks.show_contributions') }}</span>
                                    </label>
                                </div>
                            </div>
                        </template>
                    </div>
                </template>
            </div>
        </div>

        <!-- STEP 3: Meta & Settings -->
        <div class="wizard-step" :class="{ active: currentStep === 3 }">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-8 space-y-6">

                <!-- SEO Section -->
                <div class="border-b pb-6" :class="darkMode ? 'border-gray-700' : 'border-gray-200'">
                    <h2 class="text-xl font-bold mb-4" :class="darkMode ? 'text-white' : 'text-gray-900'">
                        <i data-lucide="search" class="w-5 h-5 inline me-2"></i>{{ __('articles.sections.seo') }}
                    </h2>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-semibold mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                                {{ __('articles.fields.meta_description') }}
                            </label>
                            <textarea name="meta_description"
                                      x-model="formData.meta_description"
                                      rows="3"
                                      class="w-full px-4 py-3 rounded-xl border-2 border-transparent focus:border-[#64CCC5]"
                                      :class="darkMode ? 'bg-gray-700 text-white' : 'bg-gray-50'"></textarea>
                            <p class="text-xs mt-2 opacity-60">{{ __('articles.hints.meta_length') }}</p>
                        </div>
                    </div>
                </div>

                <!-- Tags & Categories -->
                <div class="border-b pb-6" :class="darkMode ? 'border-gray-700' : 'border-gray-200'">
                    <h2 class="text-xl font-bold mb-4" :class="darkMode ? 'text-white' : 'text-gray-900'">
                        <i data-lucide="tag" class="w-5 h-5 inline me-2"></i>{{ __('articles.sections.tags') }}
                    </h2>
                    <select name="tags[]"
                            multiple
                            x-model="formData.tags"
                            class="w-full px-4 py-3 rounded-xl border-2 border-transparent focus:border-[#64CCC5]"
                            :class="darkMode ? 'bg-gray-700 text-white' : 'bg-gray-50'">
                        @foreach($tags as $tag)
                            <option value="{{ $tag->id }}">{{ $tag->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Publishing Options -->
                <div>
                    <h2 class="text-xl font-bold mb-4" :class="darkMode ? 'text-white' : 'text-gray-900'">
                        <i data-lucide="calendar" class="w-5 h-5 inline me-2"></i>{{ __('articles.sections.publishing') }}
                    </h2>
                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                                {{ __('articles.fields.scheduled_at') }}
                            </label>
                            <input type="datetime-local"
                                   name="scheduled_at"
                                   x-model="formData.scheduled_at"
                                   class="w-full px-4 py-3 rounded-xl border-2 border-transparent focus:border-[#64CCC5]"
                                   :class="darkMode ? 'bg-gray-700 text-white' : 'bg-gray-50'">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- STEP 4: Review & Publish -->
        <div class="wizard-step" :class="{ active: currentStep === 4 }">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-8 space-y-6">

                <div class="border-l-4 border-[#64CCC5] bg-[#64CCC5]/10 rounded-r-xl p-4 mb-6">
                    <h3 class="font-bold mb-2">{{ __('articles.review.header') }}</h3>
                    <p class="text-sm opacity-80">{{ __('articles.review.subtitle') }}</p>
                </div>

                <!-- Content Summary -->
                <div>
                    <h3 class="text-lg font-bold mb-4" :class="darkMode ? 'text-white' : 'text-gray-900'">
                        <i data-lucide="file-text" class="w-5 h-5 inline me-2"></i>{{ __('articles.review.content') }}
                    </h3>
                    <div class="space-y-3 p-4 rounded-xl" :class="darkMode ? 'bg-gray-700' : 'bg-gray-50'">
                        <div class="flex justify-between">
                            <span class="opacity-70">{{ __('articles.fields.title') }}:</span>
                            <span class="font-semibold" x-text="formData.title || '—'"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="opacity-70">{{ __('articles.fields.blocks') }}:</span>
                            <span class="font-semibold" x-text="`${contentBlocks.length} {{ __('articles.blocks_count') }}`"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="opacity-70">{{ __('articles.fields.status') }}:</span>
                            <span class="font-semibold capitalize" x-text="formData.status"></span>
                        </div>
                    </div>
                </div>

                <!-- Publish Button -->
                <div class="pt-6 border-t" :class="darkMode ? 'border-gray-700' : 'border-gray-200'">
                    <button type="submit"
                            class="w-full px-8 py-4 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] hover:from-[#003a7a] hover:to-[#186b2e] text-white font-bold text-lg transition-all transform hover:scale-105 shadow-lg">
                        <i data-lucide="check-circle" class="w-6 h-6 inline me-3"></i>
                        {{ $isEdit ? __('common.update') : __('common.publish') }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Navigation Footer -->
    <div class="fixed bottom-0 left-0 right-0 bg-white dark:bg-gray-800 border-t shadow-lg z-30"
         :class="darkMode ? 'border-gray-700' : 'border-gray-200'">
        <div class="max-w-7xl mx-auto px-6 py-4 flex items-center justify-between">
            <button type="button"
                    @click="currentStep = Math.max(1, currentStep - 1)"
                    :disabled="currentStep === 1"
                    class="px-6 py-3 rounded-lg bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 disabled:opacity-50 disabled:cursor-not-allowed transition-all">
                <i data-lucide="arrow-right" class="w-5 h-5 inline me-2"></i>{{ __('common.previous') }}
            </button>

            <button type="button"
                    @click="currentStep = Math.min(totalSteps, currentStep + 1)"
                    :disabled="currentStep === totalSteps"
                    class="px-6 py-3 rounded-lg bg-[#64CCC5] hover:bg-[#54b5b5] text-gray-900 font-semibold disabled:opacity-50 disabled:cursor-not-allowed transition-all">
                {{ __('common.next') }}
                <i data-lucide="arrow-left" class="w-5 h-5 inline ms-2"></i>
            </button>
        </div>
    </div>
</form>

@push('scripts')
<script>
    function articleWizard(initialData, locales) {
        return {
            currentStep: 1,
            totalSteps: 4,
            darkMode: localStorage.getItem('darkMode') !== 'false',
            formData: initialData || {
                title: '',
                slug: '',
                category_id: '',
                writer_id: '',
                status: 'draft',
                is_featured: false,
                is_pinned: false,
                read_time: 5,
                meta_description: '',
                tags: [],
                scheduled_at: ''
            },
            contentBlocks: [],
            thumbnailPreview: null,
            draggedBlock: null,
            dragOverIndex: null,
            dragOverZone: false,

            stepTitles: [
                '{{ __("articles.wizard_step1") }}',
                '{{ __("articles.wizard_step2") }}',
                '{{ __("articles.wizard_step3") }}',
                '{{ __("articles.wizard_step4") }}'
            ],

            blockTitles: {
                wysiwyg: '{{ __("articles.blocks.wysiwyg") }}',
                quran: '{{ __("articles.blocks.quran") }}',
                hadith: '{{ __("articles.blocks.hadith") }}',
                sahabah: '{{ __("articles.blocks.sahabah") }}'
            },

            blockIcons: {
                wysiwyg: 'type',
                quran: 'book-open',
                hadith: 'quote',
                sahabah: 'users'
            },

            addBlock(type) {
                this.contentBlocks.push({
                    id: Date.now(),
                    type: type,
                    content: '',
                    surah_id: '',
                    verse_range: '',
                    show_translation: false,
                    hadith_book_id: '',
                    hadith_number: '',
                    show_chain: false,
                    sahabah_id: '',
                    show_biography: false,
                    show_contributions: false
                });
            },

            removeBlock(index) {
                if (confirm('{{ __("common.confirm_delete") }}')) {
                    this.contentBlocks.splice(index, 1);
                }
            },

            reorderBlocks(dropIndex) {
                if (this.draggedBlock !== null && this.draggedBlock !== dropIndex) {
                    const [draggedItem] = this.contentBlocks.splice(this.draggedBlock, 1);
                    this.contentBlocks.splice(dropIndex, 0, draggedItem);
                }
                this.draggedBlock = null;
                this.dragOverIndex = null;
            },

            handleThumbnailUpload(e) {
                const file = e.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = (event) => {
                        this.thumbnailPreview = event.target.result;
                    };
                    reader.readAsDataURL(file);
                }
            },

            handleSubmit() {
                // Serialize blocks to JSON
                const blocksJson = JSON.stringify(this.contentBlocks);
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'blocks_json';
                input.value = blocksJson;
                document.querySelector('.article-form').appendChild(input);

                // Submit form
                document.querySelector('form').submit();
            },

            init() {
                this.$nextTick(() => lucide.createIcons());
            }
        };
    }
</script>
@endpush
