{{--
    Unified Image Input Component

    One image, three ways to provide it:
    1. Browse the media library (opens the media picker modal)
    2. Upload a file
    3. External URL — previewed in the browser before it is accepted (opt-in via `urlName`)

    Server contract (what the form submits):
    - `name`       uploaded file (wins over everything else)
    - `idName`     media-library id
    - `urlName`    accepted external URL; pre-filled with the current image when that is
                   already an external URL, cleared when a file/library image is chosen
    - `removeName` "1" when the user removed the image and chose nothing else — without
                   it, removing only clears the preview and the saved image is kept

    @props
    - name: string (required) - File input name, e.g. 'profile_image'
    - idName: string|null - Hidden input name for the media library id
    - urlName: string|null - Enables the external-URL option; field name for the URL
    - removeName: string|null - Hidden "remove current image" flag field name
    - currentImage: string|null - Current image URL (absolute) for edit pages
    - aspectClass: string - Tailwind aspect class for preview (default: 'aspect-video')
    - placeholderIcon: string - Lucide icon for the empty state (default: 'image-plus')
    - accept: string - Accepted file types (default: 'image/jpeg,image/png,image/webp')
    - maxSize: string - Max file size label (default: '5MB')
    - formats: string - Supported formats label (default: 'JPG, PNG, WebP')
    - mediaLibrary: bool - Show the media library option (default: true)
    - label: string|null - Section label

    Events: listens to `media-selected` (picker) and `image-preview-set` ({ name, url }).
--}}

@props([
    'name' => 'thumbnail',
    'idName' => null,
    'urlName' => null,
    'removeName' => null,
    'currentImage' => null,
    'aspectClass' => 'aspect-video',
    'placeholderIcon' => 'image-plus',
    'accept' => 'image/jpeg,image/png,image/webp',
    'maxSize' => '5MB',
    'formats' => 'JPG, PNG, WebP',
    'mediaLibrary' => true,
    'label' => null,
])

@php
    $uniqueId = 'img_upload_'.Str::random(6);
    $isExternal = filled($currentImage)
        && preg_match('#^https?://#i', $currentImage)
        && ! str_starts_with($currentImage, url('/'));
    // After a failed submit, keep what the user typed instead of reverting.
    $initialUrl = $urlName ? (string) old($urlName, $isExternal ? $currentImage : '') : '';
    $initialPreview = $initialUrl !== '' ? $initialUrl : ($currentImage ?: null);
    $optionCount = 1 + ($mediaLibrary ? 1 : 0) + ($urlName ? 1 : 0);
    $gridCols = [1 => 'grid-cols-1', 2 => 'grid-cols-2', 3 => 'grid-cols-3'][$optionCount];
@endphp

<div x-data="{
        preview: @js($initialPreview),
        source: @js($initialPreview ? ($initialUrl !== '' ? 'url' : 'current') : null),
        mediaId: null,
        urlOpen: @js($urlName && $initialUrl !== '' && ! $currentImage),
        url: @js($initialUrl),
        committedUrl: @js($initialUrl),
        urlState: 'idle',
        removed: false,
        probe: 0,

        clearFile() {
            if (this.$refs.fileInput) this.$refs.fileInput.value = '';
        },

        use(url, source) {
            this.preview = url;
            this.source = source;
            this.removed = false;
            // Library picks and accepted URLs fire no trusted input event; tell a host wizard.
            this.$dispatch('wizard:dirty');
            if (source !== 'url') {
                this.committedUrl = '';
                this.urlState = 'idle';
            }
        },

        handleFileChange(event) {
            const file = event.target.files[0];
            if (!file) return;
            this.mediaId = null;
            this.use(URL.createObjectURL(file), 'file');
        },

        handleMediaSelect(detail) {
            if (detail.target !== '{{ $uniqueId }}' || !detail.media) return;
            const media = Array.isArray(detail.media) ? detail.media[0] : detail.media;
            this.mediaId = media.id;
            this.clearFile();
            this.use(media.url || ('/storage/' + media.path), 'library');
        },

        previewUrl() {
            const candidate = (this.url || '').trim();
            let parsed = null;
            try { parsed = new URL(candidate); } catch (e) {}
            if (!parsed || !['http:', 'https:'].includes(parsed.protocol)) {
                this.urlState = 'invalid';
                return;
            }
            // Only the latest probe may commit: typing fast must not let a slow,
            // stale image overwrite a newer one.
            const token = ++this.probe;
            this.urlState = 'loading';
            const img = new Image();
            const timer = setTimeout(() => { if (token === this.probe) this.urlState = 'error'; }, 15000);
            img.onload = () => {
                clearTimeout(timer);
                if (token !== this.probe) return;
                this.mediaId = null;
                this.clearFile();
                this.committedUrl = candidate;
                this.urlState = 'ok';
                this.use(candidate, 'url');
            };
            img.onerror = () => {
                clearTimeout(timer);
                if (token === this.probe) this.urlState = 'error';
            };
            img.src = candidate;
        },

        removeImage() {
            this.probe++;
            this.preview = null;
            this.source = null;
            this.mediaId = null;
            this.committedUrl = '';
            this.url = '';
            this.urlState = 'idle';
            this.removed = true;
            this.clearFile();
            this.$dispatch('wizard:dirty');
        }
     }"
     x-init="$watch('urlOpen', open => open && $nextTick(() => $refs.urlInput?.focus()))"
     x-effect="preview; source; urlState; urlOpen; $nextTick(() => window.lucide && lucide.createIcons())"
     @media-selected.window="handleMediaSelect($event.detail)"
     {{-- After a wizard quick save the chosen image is stored: keep the preview, drop the one-shot inputs. --}}
     @wizard-saved.window="clearFile(); mediaId = null; if (removed) { preview = null; source = null; } removed = false; if (preview && source !== 'url') source = 'current'"
     {{-- Preview-only fill (e.g. a fetched YouTube thumbnail): shows the image
          without sending a file or library id — the server-side fallback is
          expected to resolve the same URL. --}}
     @image-preview-set.window="if ($event.detail.name === '{{ $name }}') { mediaId = null; clearFile(); use($event.detail.url, 'current'); }"
     class="@container space-y-3">

    @if($label)
        <h3 class="text-sm font-semibold flex items-center gap-2"
            :class="darkMode ? 'text-white' : 'text-gray-900'">
            <i data-lucide="image" class="w-4 h-4 text-gray-400"></i>
            {{ $label }}
        </h3>
    @endif

    {{-- Preview --}}
    <div class="relative {{ $aspectClass }} rounded-xl overflow-hidden border"
         :class="darkMode ? 'bg-gray-800 border-gray-700' : 'bg-gray-50 border-gray-200'">
        <template x-if="preview">
            <img :src="preview" class="absolute inset-0 w-full h-full object-cover" alt="" loading="lazy">
        </template>

        <template x-if="!preview">
            <div class="absolute inset-0 flex flex-col items-center justify-center gap-2 p-4 text-center"
                 :class="darkMode ? 'text-gray-500' : 'text-gray-400'">
                <i data-lucide="{{ $placeholderIcon }}" class="w-10 h-10" aria-hidden="true"></i>
                <span class="text-xs font-medium">{{ __('common.image_input.empty') }}</span>
                <span class="text-xs">{{ $maxSize }} · {{ $formats }}</span>
            </div>
        </template>

        {{-- Source badge + actions --}}
        <template x-if="preview">
            <div class="absolute inset-x-0 top-0 p-2 flex items-start justify-between gap-2 bg-gradient-to-b from-black/50 to-transparent">
                <span class="inline-flex items-center gap-1 px-2 py-1 rounded-lg text-[11px] font-semibold text-white bg-black/40 backdrop-blur-sm">
                    {{-- Static icons behind x-show: lucide swaps <i> for <svg>, which would drop a bound :data-lucide. --}}
                    @foreach(['url' => 'link', 'file' => 'upload', 'library' => 'folder-open', 'current' => 'image'] as $src => $icon)
                        <span x-show="source === '{{ $src }}'" class="inline-flex"><i data-lucide="{{ $icon }}" class="w-3.5 h-3.5" aria-hidden="true"></i></span>
                    @endforeach
                    <span x-text="{
                        url: @js(__('common.image_input.source_url')),
                        file: @js(__('common.image_input.source_file')),
                        library: @js(__('common.image_input.source_library')),
                        current: @js(__('common.image_input.source_current')),
                    }[source] || ''"></span>
                </span>
                <span class="flex items-center gap-1.5">
                    <template x-if="source === 'url'">
                        <a :href="preview" target="_blank" rel="noopener noreferrer"
                           class="w-9 h-9 rounded-lg flex items-center justify-center text-white bg-black/40 backdrop-blur-sm hover:bg-black/60 focus:outline-none focus-visible:ring-2 focus-visible:ring-white transition-colors"
                           title="{{ __('common.image_input.open') }}" aria-label="{{ __('common.image_input.open') }}">
                            <i data-lucide="external-link" class="w-4 h-4" aria-hidden="true"></i>
                        </a>
                    </template>
                    <button type="button" @click="removeImage()"
                            class="w-9 h-9 rounded-lg flex items-center justify-center text-white bg-red-500/90 hover:bg-red-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-white transition-colors"
                            title="{{ __('common.image_input.remove') }}" aria-label="{{ __('common.image_input.remove') }}">
                        <i data-lucide="trash-2" class="w-4 h-4" aria-hidden="true"></i>
                    </button>
                </span>
            </div>
        </template>

        {{-- URL probe in flight --}}
        <div x-show="urlState === 'loading'" x-cloak x-transition.opacity
             class="absolute inset-0 flex flex-col items-center justify-center gap-2 bg-black/40 text-white text-xs font-semibold">
            <i data-lucide="loader-2" class="w-7 h-7 animate-spin motion-reduce:animate-none" aria-hidden="true"></i>
            <span>{{ __('common.image_input.loading') }}</span>
        </div>
    </div>

    {{-- Options --}}
    <div class="grid {{ $gridCols }} gap-2">
        @if($mediaLibrary)
            <button type="button"
                    @click="$dispatch('open-media-library', { target: '{{ $uniqueId }}', multiple: false })"
                    class="min-h-[40px] px-2 py-2 text-xs whitespace-nowrap @max-[300px]:flex-col @max-[300px]:gap-1 @max-[300px]:px-1 @max-[300px]:text-[11px] rounded-lg border transition-colors font-semibold flex items-center justify-center gap-1.5 focus:outline-none focus-visible:ring-2 focus-visible:ring-accent"
                    :class="source === 'library'
                        ? 'border-accent text-accent ' + (darkMode ? 'bg-gray-800' : 'bg-accent/5')
                        : (darkMode ? 'border-gray-600 hover:bg-gray-700 text-gray-300' : 'border-gray-300 hover:bg-gray-50 text-gray-700')">
                <i data-lucide="folder-open" class="w-4 h-4" aria-hidden="true"></i>
                {{ __('common.image_input.browse') }}
            </button>
        @endif

        <label class="min-h-[40px] px-2 py-2 text-xs whitespace-nowrap @max-[300px]:flex-col @max-[300px]:gap-1 @max-[300px]:px-1 @max-[300px]:text-[11px] rounded-lg border transition-colors cursor-pointer font-semibold flex items-center justify-center gap-1.5 focus-within:ring-2 focus-within:ring-accent"
               :class="source === 'file'
                   ? 'border-accent text-accent ' + (darkMode ? 'bg-gray-800' : 'bg-accent/5')
                   : (darkMode ? 'border-gray-600 hover:bg-gray-700 text-gray-300' : 'border-gray-300 hover:bg-gray-50 text-gray-700')">
            <i data-lucide="upload" class="w-4 h-4" aria-hidden="true"></i>
            {{ __('common.image_input.upload') }}
            <input type="file"
                   name="{{ $name }}"
                   accept="{{ $accept }}"
                   @change="handleFileChange($event)"
                   x-ref="fileInput"
                   class="sr-only">
        </label>

        @if($urlName)
            <button type="button"
                    @click="urlOpen = !urlOpen"
                    :aria-expanded="urlOpen.toString()"
                    aria-controls="{{ $uniqueId }}_url_panel"
                    class="min-h-[40px] px-2 py-2 text-xs whitespace-nowrap @max-[300px]:flex-col @max-[300px]:gap-1 @max-[300px]:px-1 @max-[300px]:text-[11px] rounded-lg border transition-colors font-semibold flex items-center justify-center gap-1.5 focus:outline-none focus-visible:ring-2 focus-visible:ring-accent"
                    :class="urlOpen || source === 'url'
                        ? 'border-accent text-accent ' + (darkMode ? 'bg-gray-800' : 'bg-accent/5')
                        : (darkMode ? 'border-gray-600 hover:bg-gray-700 text-gray-300' : 'border-gray-300 hover:bg-gray-50 text-gray-700')">
                <i data-lucide="link" class="w-4 h-4" aria-hidden="true"></i>
                {{ __('common.image_input.url') }}
            </button>
        @endif
    </div>

    @if($urlName)
        <div id="{{ $uniqueId }}_url_panel" x-show="urlOpen" x-collapse x-cloak class="space-y-2">
            <label for="{{ $uniqueId }}_url" class="block text-xs font-semibold"
                   :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                {{ __('common.image_input.url_label') }}
            </label>
            <div class="flex gap-2 @max-[300px]:flex-col">
                <input type="url" id="{{ $uniqueId }}_url" inputmode="url" dir="ltr" autocomplete="off"
                       x-ref="urlInput"
                       x-model="url"
                       @input="if (urlState !== 'loading') urlState = 'idle'"
                       @keydown.enter.prevent="previewUrl()"
                       placeholder="{{ __('common.image_input.url_placeholder') }}"
                       aria-describedby="{{ $uniqueId }}_url_msg"
                       :aria-invalid="(['invalid', 'error'].includes(urlState)).toString()"
                       class="flex-1 min-w-0 @max-[300px]:flex-none px-3 py-2.5 rounded-xl border text-sm focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                       :class="['invalid', 'error'].includes(urlState)
                           ? 'border-red-400 ' + (darkMode ? 'bg-gray-800 text-white' : 'bg-white text-gray-900')
                           : (darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900')">
                <button type="button" @click="previewUrl()"
                        :disabled="!url.trim() || urlState === 'loading'"
                        class="shrink-0 min-h-[40px] px-4 rounded-xl text-sm font-semibold text-white justify-center bg-gradient-to-r from-[#0056b3] to-[#218838] shadow flex items-center gap-1.5 transition-all hover:scale-105 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:scale-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-accent">
                    <i data-lucide="eye" class="w-4 h-4" aria-hidden="true"></i>
                    {{ __('common.image_input.preview') }}
                </button>
            </div>
            <p id="{{ $uniqueId }}_url_msg" class="text-xs flex items-start gap-1.5 min-h-[1rem]" aria-live="polite">
                <template x-if="urlState === 'invalid'">
                    <span class="text-red-500 flex items-start gap-1.5"><i data-lucide="alert-circle" class="w-3.5 h-3.5 shrink-0 mt-px" aria-hidden="true"></i>{{ __('common.image_input.invalid') }}</span>
                </template>
                <template x-if="urlState === 'error'">
                    <span class="text-red-500 flex items-start gap-1.5"><i data-lucide="image-off" class="w-3.5 h-3.5 shrink-0 mt-px" aria-hidden="true"></i>{{ __('common.image_input.error') }}</span>
                </template>
                <template x-if="urlState === 'ok'">
                    <span class="text-green-600 flex items-start gap-1.5"><i data-lucide="check-circle" class="w-3.5 h-3.5 shrink-0 mt-px" aria-hidden="true"></i>{{ __('common.image_input.source_url') }}</span>
                </template>
            </p>
        </div>

        <input type="hidden" name="{{ $urlName }}" :value="committedUrl">
    @endif

    @if($idName)
        <input type="hidden" name="{{ $idName }}" :value="mediaId ?? ''">
    @endif

    @if($removeName)
        <input type="hidden" name="{{ $removeName }}" :value="removed ? 1 : 0">
    @endif

    @foreach(array_filter([$name, $idName, $urlName]) as $field)
        @error($field)
            <p class="text-sm text-red-500" role="alert">{{ $message }}</p>
        @enderror
    @endforeach
</div>
