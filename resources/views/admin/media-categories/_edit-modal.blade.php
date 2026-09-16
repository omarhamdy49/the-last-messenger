{{--
    Category edit modal — one instance per page.
    Open via: $dispatch('open-category-edit', { key, name: {}, description: {}, icon, color, display_order })
--}}
@php
    $locales = enabled_content_languages();
    $palette = ['#0056b3', '#218838', '#64CCC5', '#7c3aed', '#d97706', '#dc2626', '#0d9488', '#475569'];
    $inputClasses = 'w-full text-base sm:text-sm border rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-[#0056b3] transition-shadow';
@endphp

<div x-data="categoryEditModal()" x-on:open-category-edit.window="open($event.detail)" x-cloak>
    <div x-show="isOpen"
         x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-black/50 backdrop-blur-sm z-[9990]" @click="isOpen = false"></div>

    <div x-show="isOpen"
         x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
         class="fixed inset-0 z-[9991] flex items-center justify-center p-4"
         @keydown.escape.window="isOpen = false">
        <div class="w-full max-w-lg max-h-[85vh] overflow-y-auto rounded-2xl shadow-2xl border"
             :class="darkMode ? 'bg-gray-900 border-gray-700' : 'bg-white border-gray-200'" @click.stop>

            <div class="px-6 py-4 border-b flex items-center justify-between"
                 :class="darkMode ? 'border-gray-800' : 'border-gray-100'">
                <h3 class="text-lg font-bold flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                    <i data-lucide="pencil" class="w-5 h-5 text-[#64CCC5]"></i>
                    {{ __('admin.edit_category') }}
                    <code class="text-xs font-mono px-2 py-0.5 rounded" :class="darkMode ? 'bg-gray-800 text-gray-400' : 'bg-gray-100 text-gray-500'" x-text="form.key"></code>
                </h3>
                <button type="button" @click="isOpen = false" class="p-1.5 rounded-lg transition-colors"
                        :class="darkMode ? 'hover:bg-gray-800 text-gray-400' : 'hover:bg-gray-100 text-gray-500'">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <form method="POST" :action="action" class="p-6 space-y-5" @submit="submitting = true">
                @csrf
                @method('PATCH')
                <input type="hidden" name="redirect" :value="redirectTo">

                {{-- Localized names --}}
                <div>
                    <div class="flex gap-2 mb-3 overflow-x-auto whitespace-nowrap pb-1">
                        @foreach($locales as $code => $label)
                        <button type="button" @click="activeLocale = '{{ $code }}'"
                                class="flex-shrink-0 px-3 py-1.5 rounded-lg text-xs font-medium transition-colors"
                                :class="activeLocale === '{{ $code }}'
                                    ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white'
                                    : (darkMode ? 'text-gray-400 hover:bg-gray-800' : 'text-gray-600 hover:bg-gray-100')">
                            {{ $label }}
                        </button>
                        @endforeach
                    </div>

                    @foreach($locales as $code => $label)
                    <div x-show="activeLocale === '{{ $code }}'" class="space-y-3" dir="{{ in_array($code, ['ar','ur']) ? 'rtl' : 'ltr' }}">
                        <div>
                            <label class="block text-sm font-medium mb-1" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                                {{ __('admin.category_name') }} ({{ $label }})
                            </label>
                            <input type="text" name="name[{{ $code }}]" x-model="form.name.{{ $code }}" maxlength="100"
                                   placeholder="{{ __('admin.category_name_placeholder') }}"
                                   class="{{ $inputClasses }}"
                                   :class="darkMode ? 'bg-gray-800 border-gray-700 text-white placeholder-gray-500' : 'bg-white border-gray-300 text-gray-900 placeholder-gray-400'">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                                {{ __('admin.description') }} ({{ $label }})
                            </label>
                            <textarea name="description[{{ $code }}]" x-model="form.description.{{ $code }}" rows="2" maxlength="500"
                                      class="{{ $inputClasses }}"
                                      :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'"></textarea>
                        </div>
                    </div>
                    @endforeach
                    <p class="mt-2 text-xs" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">{{ __('admin.category_name_hint') }}</p>
                </div>

                {{-- Icon + order --}}
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-1" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            {{ __('admin.icon') }}
                        </label>
                        <input type="text" name="icon" x-model="form.icon" maxlength="50" dir="ltr"
                               placeholder="book-open"
                               class="{{ $inputClasses }}"
                               :class="darkMode ? 'bg-gray-800 border-gray-700 text-white placeholder-gray-500' : 'bg-white border-gray-300 text-gray-900 placeholder-gray-400'">
                        <p class="mt-1 text-xs" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">{{ __('admin.lucide_icon_hint') }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            {{ __('admin.display_order') }}
                        </label>
                        <input type="number" name="display_order" x-model="form.display_order" min="0" max="1000"
                               class="{{ $inputClasses }}"
                               :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                    </div>
                </div>

                {{-- Color swatches --}}
                <div>
                    <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                        {{ __('admin.color') }}
                    </label>
                    <input type="hidden" name="color" :value="form.color">
                    <div class="flex flex-wrap items-center gap-2">
                        @foreach($palette as $swatch)
                        <button type="button" @click="form.color = form.color === '{{ $swatch }}' ? null : '{{ $swatch }}'"
                                class="w-8 h-8 rounded-full transition-all hover:scale-110 ring-offset-2"
                                :class="form.color === '{{ $swatch }}' ? 'ring-2 ring-[#0056b3]' : ''"
                                style="background-color: {{ $swatch }}"
                                aria-label="{{ $swatch }}"></button>
                        @endforeach
                        <button type="button" @click="form.color = null"
                                class="px-2 py-1 rounded-lg text-xs font-medium border transition-colors"
                                :class="darkMode ? 'border-gray-700 text-gray-400 hover:bg-gray-800' : 'border-gray-300 text-gray-500 hover:bg-gray-50'">
                            {{ __('admin.clear') }}
                        </button>
                    </div>
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="submit" :disabled="submitting"
                            class="flex-1 py-2.5 rounded-xl font-semibold text-white bg-gradient-to-r from-[#0056b3] to-[#218838] hover:scale-105 transition-all disabled:opacity-50 flex items-center justify-center gap-2">
                        <i data-lucide="save" class="w-4 h-4"></i>
                        {{ __('admin.save') }}
                    </button>
                    <button type="button" @click="isOpen = false"
                            class="px-6 py-2.5 rounded-xl font-semibold border transition-colors"
                            :class="darkMode ? 'border-gray-700 text-gray-300 hover:bg-gray-800' : 'border-gray-300 text-gray-700 hover:bg-gray-50'">
                        {{ __('admin.cancel') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function categoryEditModal() {
    const emptyLocales = () => ({ ar: '', en: '', fr: '', tr: '', ur: '' });

    return {
        isOpen: false,
        submitting: false,
        activeLocale: 'ar',
        action: '',
        redirectTo: '',
        form: { key: '', name: emptyLocales(), description: emptyLocales(), icon: '', color: null, display_order: 0 },

        open(detail) {
            this.redirectTo = detail.redirect || '';
            this.form.key = detail.key;
            this.form.name = Object.assign(emptyLocales(), detail.name || {});
            this.form.description = Object.assign(emptyLocales(), detail.description || {});
            this.form.icon = detail.icon || '';
            this.form.color = detail.color || null;
            this.form.display_order = detail.display_order ?? 0;
            this.action = '{{ route('admin.media-categories.index') }}/' + detail.key;
            this.activeLocale = 'ar';
            this.submitting = false;
            this.isOpen = true;
        }
    };
}
</script>
