{{--
    Shared Live Channel form (create + edit).

    @param \App\Domain\Media\Models\LiveChannel|null $channel  Null on create
    @param array                                     $statuses LiveStatus cases
--}}
@php
    $isEdit = $channel !== null;
    $locales = enabled_content_languages();
    $existingTranslations = $isEdit ? $channel->translations->keyBy('locale') : collect();
    $inputClasses = 'w-full text-base sm:text-sm border rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-[#0056b3] transition-shadow';
@endphp

<form method="POST"
      action="{{ $isEdit ? route('admin.live-channels.update', $channel->id) : route('admin.live-channels.store') }}"
      enctype="multipart/form-data"
      x-data="{ submitting: false }"
      @submit="submitting = true">
    @csrf
    @if($isEdit)
        @method('PUT')
    @endif

    @if($errors->any())
    <div class="mb-6 p-4 rounded-xl border-s-4 border-red-500 animate-fadeInUp"
         :class="darkMode ? 'bg-red-900/20' : 'bg-red-50'">
        <div class="flex items-start gap-3">
            <i data-lucide="alert-circle" class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5"></i>
            <div class="flex-1">
                <h3 class="font-bold text-red-500 mb-2">{{ __('common.please_fix_errors') }}</h3>
                <ul class="list-disc list-inside space-y-1 text-sm" :class="darkMode ? 'text-red-400' : 'text-red-600'">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- ==================== MAIN COLUMN ==================== --}}
        <div class="lg:col-span-2 space-y-6">

            {{-- Translations --}}
            <section class="rounded-2xl border p-6 animate-fadeInUp" :class="darkMode ? 'bg-gray-900 border-gray-800' : 'bg-white border-gray-200'">
                <h3 class="text-lg font-semibold mb-4 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                    <i data-lucide="languages" class="w-5 h-5 text-[#64CCC5]"></i>
                    {{ __('admin.translations') }}
                </h3>

                <div x-data="{ activeLocale: 'ar' }">
                    <div class="flex gap-2 mb-4 overflow-x-auto whitespace-nowrap pb-1">
                        @foreach($locales as $code => $label)
                        @php $hasTitle = filled(old("title.{$code}", $existingTranslations->get($code)?->title)); @endphp
                        <button type="button" @click="activeLocale = '{{ $code }}'"
                                class="flex-shrink-0 px-4 py-2 rounded-lg text-sm font-medium transition-colors flex items-center gap-1.5"
                                :class="activeLocale === '{{ $code }}'
                                    ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white'
                                    : (darkMode ? 'text-gray-400 hover:bg-gray-800' : 'text-gray-600 hover:bg-gray-100')">
                            {{ $label }}
                            <span class="w-1.5 h-1.5 rounded-full {{ $hasTitle ? 'bg-green-400' : 'bg-gray-400/60' }}"></span>
                        </button>
                        @endforeach
                    </div>

                    @foreach($locales as $code => $label)
                    @php $t = $existingTranslations->get($code); @endphp
                    <div x-show="activeLocale === '{{ $code }}'" class="space-y-4" dir="{{ in_array($code, ['ar','ur']) ? 'rtl' : 'ltr' }}">
                        <div>
                            <label class="block text-sm font-medium mb-1" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                                {{ __('admin.title') }} ({{ $label }}) {{ $code === 'ar' ? '*' : '' }}
                            </label>
                            <input type="text" name="title[{{ $code }}]" value="{{ old('title.'.$code, $t?->title) }}"
                                   class="{{ $inputClasses }}"
                                   :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                                {{ __('admin.description') }} ({{ $label }})
                            </label>
                            <textarea name="description[{{ $code }}]" rows="4"
                                      class="{{ $inputClasses }}"
                                      :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">{{ old('description.'.$code, $t?->description) }}</textarea>
                        </div>
                    </div>
                    @endforeach
                </div>
            </section>

            {{-- Streaming --}}
            <section class="rounded-2xl border p-6 animate-fadeInUp" :class="darkMode ? 'bg-gray-900 border-gray-800' : 'bg-white border-gray-200'">
                <h3 class="text-lg font-semibold mb-4 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                    <i data-lucide="radio" class="w-5 h-5 text-[#64CCC5]"></i>
                    {{ __('admin.live_channel_streaming') }}
                </h3>
                <label class="block text-sm font-medium mb-1" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                    {{ __('admin.live_channel_streaming_url') }}
                </label>
                <input type="url" name="streaming_url" dir="ltr"
                       value="{{ old('streaming_url', $isEdit ? $channel->streaming_url : '') }}"
                       placeholder="https://www.youtube.com/embed/VIDEO_ID"
                       class="{{ $inputClasses }}"
                       :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                <p class="mt-1 text-xs" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">{{ __('admin.live_channel_streaming_hint') }}</p>

                {{-- Stream type. Blank = auto, which reads the type off the URL
                     extension. Radio mounts (radiojar/Icecast) carry no extension,
                     so those must be set to "audio" by hand. --}}
                <div class="mt-4">
                    <label class="block text-sm font-medium mb-1" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                        {{ __('admin.live_channel_stream_type') }}
                    </label>
                    <select name="stream_type" class="{{ $inputClasses }}"
                            :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                        <option value="" @selected(old('stream_type', $isEdit ? $channel->stream_type?->value : null) === null)>
                            {{ __('admin.stream_type_auto') }}@if($isEdit && $channel->stream_type === null && $channel->streaming_url) — {{ __($channel->resolved_stream_type->labelKey()) }}@endif
                        </option>
                        @foreach($streamTypes as $type)
                            <option value="{{ $type->value }}"
                                @selected(old('stream_type', $isEdit ? $channel->stream_type?->value : null) === $type->value)>
                                {{ __($type->labelKey()) }}
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">{{ __('admin.live_channel_stream_type_hint') }}</p>
                </div>
            </section>
        </div>

        {{-- ==================== SIDEBAR ==================== --}}
        <div class="space-y-6">
            {{-- Settings --}}
            <section class="rounded-2xl border p-6 animate-fadeInUp" :class="darkMode ? 'bg-gray-900 border-gray-800' : 'bg-white border-gray-200'">
                <h3 class="text-lg font-semibold mb-4 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                    <i data-lucide="settings-2" class="w-5 h-5 text-[#64CCC5]"></i>
                    {{ __('admin.settings') }}
                </h3>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium mb-1" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ __('admin.status') }}</label>
                        <select name="status" class="{{ $inputClasses }}"
                                :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                            @foreach($statuses as $status)
                                <option value="{{ $status->value }}"
                                    @selected(old('status', $isEdit ? $channel->status->value : 'offline') === $status->value)>
                                    {{ __('media.public.live_status_'.$status->value) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ __('admin.slug') }}</label>
                        <input type="text" name="slug" dir="ltr" value="{{ old('slug', $isEdit ? $channel->slug : '') }}"
                               class="{{ $inputClasses }}"
                               :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                        <p class="mt-1 text-xs" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">
                            {{ $isEdit ? __('admin.url_friendly_hint') : __('admin.live_channel_slug_auto_hint') }}
                        </p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ __('admin.display_order') }}</label>
                        <input type="number" name="display_order" min="0" value="{{ old('display_order', $isEdit ? $channel->display_order : 0) }}"
                               class="{{ $inputClasses }}"
                               :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                    </div>

                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="hidden" name="is_published" value="0">
                        <input type="checkbox" name="is_published" value="1"
                               @checked(old('is_published', $isEdit ? $channel->is_published : true))
                               class="w-5 h-5 rounded text-[#0056b3] focus:ring-[#0056b3]">
                        <span class="text-sm font-medium" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ __('admin.published') }}</span>
                    </label>
                </div>
            </section>

            {{-- Thumbnail --}}
            <section class="rounded-2xl border p-6 animate-fadeInUp" :class="darkMode ? 'bg-gray-900 border-gray-800' : 'bg-white border-gray-200'">
                <h3 class="text-lg font-semibold mb-4 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                    <i data-lucide="image" class="w-5 h-5 text-[#64CCC5]"></i>
                    {{ __('admin.thumbnail') }}
                </h3>
                <x-admin.image-upload
                    name="thumbnail"
                    idName="thumbnail_id"
                    :currentImage="$isEdit ? $channel->thumbnail_url : null"
                    maxSize="10MB"
                    formats="JPG, PNG, WebP"
                    accept="image/jpeg,image/jpg,image/png,image/webp" />
                <p class="mt-2 text-xs" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">{{ __('admin.thumbnail_hint') }}</p>
            </section>

            <div class="flex flex-wrap gap-3">
                <button type="submit" :disabled="submitting"
                        class="flex-1 px-6 py-3 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] text-white font-bold shadow-lg hover:shadow-xl transition-all disabled:opacity-60 flex items-center justify-center gap-2">
                    <i data-lucide="loader-2" class="w-5 h-5 animate-spin" x-show="submitting" x-cloak></i>
                    <span x-show="!submitting">{{ $isEdit ? __('admin.update') : __('admin.create') }}</span>
                    <span x-show="submitting" x-cloak>{{ __('admin.saving') }}</span>
                </button>
                <a href="{{ route('admin.live-channels.index') }}"
                   class="px-6 py-3 rounded-xl font-medium transition-colors"
                   :class="darkMode ? 'bg-gray-800 text-gray-300 hover:bg-gray-700' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'">
                    {{ __('admin.cancel') }}
                </a>
            </div>
        </div>
    </div>
</form>

{{-- Media library picker (used by the image-upload component) --}}
<x-admin.media-picker-modal />
