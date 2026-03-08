@extends('layouts.admin')

@section('title', __('media.settings_page.title'))

@section('page-header')
    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <p class="text-sm uppercase tracking-[0.25em]"
               :class="darkMode ? 'text-gray-500' : 'text-gray-500'">
                {{ __('media.settings_page.kicker') }}
            </p>
            <h1 class="text-3xl font-black mt-2"
                :class="darkMode ? 'text-white' : 'text-gray-900'">
                {{ __('media.settings_page.title') }}
            </h1>
            <p class="mt-2 text-sm max-w-2xl"
               :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                {{ __('media.settings_page.subtitle') }}
            </p>
        </div>

        <a href="{{ route('admin.media-library.index') }}"
           class="px-5 py-3 rounded-xl font-semibold transition-all"
            :class="darkMode ? 'bg-gray-900 border border-gray-800 text-white hover:bg-gray-800' : 'bg-white border border-gray-200 text-gray-900 hover:bg-gray-50'">
            <i data-lucide="arrow-left" class="w-4 h-4 inline me-2"></i>
            {{ __('media.settings_page.back_to_library') }}
        </a>
    </div>
@endsection

@section('content')
    <div class="space-y-6">
        @if(session('success'))
            <div class="rounded-2xl px-5 py-4 border"
                 :class="darkMode ? 'bg-emerald-900/20 border-emerald-800 text-emerald-300' : 'bg-emerald-50 border-emerald-200 text-emerald-700'">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="rounded-2xl px-5 py-4 border"
                 :class="darkMode ? 'bg-red-900/20 border-red-800 text-red-300' : 'bg-red-50 border-red-200 text-red-700'">
                <ul class="space-y-1 text-sm">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid gap-6 xl:grid-cols-[360px_1fr]">
            <section class="rounded-2xl shadow-lg p-6 h-fit"
                     :class="darkMode ? 'bg-gray-900 border border-gray-800' : 'bg-white border border-gray-200'">
                <div class="flex items-center gap-3 mb-5">
                    <div class="w-11 h-11 rounded-2xl bg-gradient-to-r from-[#0056b3] to-[#218838] flex items-center justify-center">
                        <i data-lucide="plus" class="w-5 h-5 text-white"></i>
                    </div>
                    <div>
                        <h2 class="font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ __('media.settings_page.add_preset') }}</h2>
                        <p class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">{{ __('media.settings_page.add_preset_help') }}</p>
                    </div>
                </div>

                <form action="{{ route('admin.media-library.settings.presets.store') }}" method="POST" class="space-y-4">
                    @csrf

                    <div>
                        <label class="block text-sm font-semibold mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ __('media.fields.name') }}</label>
                        <input type="text" name="name" placeholder="{{ __('media.settings_page.name_placeholder') }}"
                               class="w-full px-4 py-3 rounded-xl border focus:ring-2 focus:ring-[#64CCC5] focus:border-transparent"
                               :class="darkMode ? 'bg-gray-950 border-gray-800 text-white' : 'bg-white border-gray-300 text-gray-900'">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ __('media.fields.width') }}</label>
                            <input type="number" name="width" min="1"
                                   class="w-full px-4 py-3 rounded-xl border focus:ring-2 focus:ring-[#64CCC5] focus:border-transparent"
                                   :class="darkMode ? 'bg-gray-950 border-gray-800 text-white' : 'bg-white border-gray-300 text-gray-900'">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ __('media.fields.height') }}</label>
                            <input type="number" name="height" min="1"
                                   class="w-full px-4 py-3 rounded-xl border focus:ring-2 focus:ring-[#64CCC5] focus:border-transparent"
                                   :class="darkMode ? 'bg-gray-950 border-gray-800 text-white' : 'bg-white border-gray-300 text-gray-900'">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ __('media.fields.method') }}</label>
                            <select name="method"
                                    class="w-full px-4 py-3 rounded-xl border focus:ring-2 focus:ring-[#64CCC5] focus:border-transparent"
                                    :class="darkMode ? 'bg-gray-950 border-gray-800 text-white' : 'bg-white border-gray-300 text-gray-900'">
                                <option value="crop">{{ __('media.settings_page.methods.crop') }}</option>
                                <option value="fit">{{ __('media.settings_page.methods.fit') }}</option>
                                <option value="resize">{{ __('media.settings_page.methods.resize') }}</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ __('media.fields.quality') }}</label>
                            <input type="number" name="quality" min="1" max="100" value="85"
                                   class="w-full px-4 py-3 rounded-xl border focus:ring-2 focus:ring-[#64CCC5] focus:border-transparent"
                                   :class="darkMode ? 'bg-gray-950 border-gray-800 text-white' : 'bg-white border-gray-300 text-gray-900'">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ __('media.fields.sort_order') }}</label>
                            <input type="number" name="sort_order" min="0" value="{{ $presets->count() + 1 }}"
                                   class="w-full px-4 py-3 rounded-xl border focus:ring-2 focus:ring-[#64CCC5] focus:border-transparent"
                                   :class="darkMode ? 'bg-gray-950 border-gray-800 text-white' : 'bg-white border-gray-300 text-gray-900'">
                        </div>

                        <div class="flex items-end">
                            <label class="flex items-center gap-3 px-4 py-3 rounded-xl border w-full"
                                   :class="darkMode ? 'border-gray-800 bg-gray-950 text-white' : 'border-gray-300 bg-white text-gray-900'">
                                <input type="hidden" name="enabled" value="0">
                                <input type="checkbox" name="enabled" value="1" checked
                                       class="rounded border-gray-300 text-[#0056b3] focus:ring-[#64CCC5]">
                                {{ __('media.settings_page.enabled') }}
                            </label>
                        </div>
                    </div>

                    <button type="submit"
                            class="w-full px-4 py-3 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] text-white font-semibold shadow-lg">
                        {{ __('media.settings_page.create_preset') }}
                    </button>
                </form>
            </section>

            <section class="rounded-2xl shadow-lg p-6"
                     :class="darkMode ? 'bg-gray-900 border border-gray-800' : 'bg-white border border-gray-200'">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-11 h-11 rounded-2xl bg-gradient-to-r from-[#0056b3] to-[#218838] flex items-center justify-center">
                        <i data-lucide="sliders-horizontal" class="w-5 h-5 text-white"></i>
                    </div>
                    <div>
                        <h2 class="font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ __('media.settings_page.configured_presets') }}</h2>
                        <p class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">{{ __('media.settings_page.configured_presets_help') }}</p>
                    </div>
                </div>

                <div class="space-y-5">
                    @forelse($presets as $preset)
                        <div class="rounded-2xl border p-5"
                             :class="darkMode ? 'border-gray-800 bg-gray-950' : 'border-gray-200 bg-gray-50'">
                            <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                                <form action="{{ route('admin.media-library.settings.presets.update', $preset) }}" method="POST" class="grid gap-4 flex-1 md:grid-cols-5">
                                    @csrf
                                    @method('PUT')

                                    <div>
                                        <label class="block text-xs font-semibold mb-2 uppercase tracking-[0.16em]"
                                               :class="darkMode ? 'text-gray-500' : 'text-gray-500'">{{ __('media.fields.name') }}</label>
                                        <input type="text" name="name" value="{{ $preset->name }}"
                                               class="w-full px-4 py-3 rounded-xl border focus:ring-2 focus:ring-[#64CCC5] focus:border-transparent"
                                               :class="darkMode ? 'bg-gray-900 border-gray-800 text-white' : 'bg-white border-gray-300 text-gray-900'">
                                    </div>

                                    <div>
                                        <label class="block text-xs font-semibold mb-2 uppercase tracking-[0.16em]"
                                               :class="darkMode ? 'text-gray-500' : 'text-gray-500'">{{ __('media.fields.width') }}</label>
                                        <input type="number" name="width" min="1" value="{{ $preset->width }}"
                                               class="w-full px-4 py-3 rounded-xl border focus:ring-2 focus:ring-[#64CCC5] focus:border-transparent"
                                               :class="darkMode ? 'bg-gray-900 border-gray-800 text-white' : 'bg-white border-gray-300 text-gray-900'">
                                    </div>

                                    <div>
                                        <label class="block text-xs font-semibold mb-2 uppercase tracking-[0.16em]"
                                               :class="darkMode ? 'text-gray-500' : 'text-gray-500'">{{ __('media.fields.height') }}</label>
                                        <input type="number" name="height" min="1" value="{{ $preset->height }}"
                                               class="w-full px-4 py-3 rounded-xl border focus:ring-2 focus:ring-[#64CCC5] focus:border-transparent"
                                               :class="darkMode ? 'bg-gray-900 border-gray-800 text-white' : 'bg-white border-gray-300 text-gray-900'">
                                    </div>

                                    <div>
                                        <label class="block text-xs font-semibold mb-2 uppercase tracking-[0.16em]"
                                               :class="darkMode ? 'text-gray-500' : 'text-gray-500'">{{ __('media.fields.method') }}</label>
                                        <select name="method"
                                                class="w-full px-4 py-3 rounded-xl border focus:ring-2 focus:ring-[#64CCC5] focus:border-transparent"
                                                :class="darkMode ? 'bg-gray-900 border-gray-800 text-white' : 'bg-white border-gray-300 text-gray-900'">
                                            <option value="crop" @selected($preset->method === 'crop')>{{ __('media.settings_page.methods.crop') }}</option>
                                            <option value="fit" @selected($preset->method === 'fit')>{{ __('media.settings_page.methods.fit') }}</option>
                                            <option value="resize" @selected($preset->method === 'resize')>{{ __('media.settings_page.methods.resize') }}</option>
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-semibold mb-2 uppercase tracking-[0.16em]"
                                               :class="darkMode ? 'text-gray-500' : 'text-gray-500'">{{ __('media.fields.quality_order') }}</label>
                                        <div class="grid grid-cols-2 gap-2">
                                            <input type="number" name="quality" min="1" max="100" value="{{ $preset->quality }}"
                                                   class="w-full px-4 py-3 rounded-xl border focus:ring-2 focus:ring-[#64CCC5] focus:border-transparent"
                                                   :class="darkMode ? 'bg-gray-900 border-gray-800 text-white' : 'bg-white border-gray-300 text-gray-900'">
                                            <input type="number" name="sort_order" min="0" value="{{ $preset->sort_order }}"
                                                   class="w-full px-4 py-3 rounded-xl border focus:ring-2 focus:ring-[#64CCC5] focus:border-transparent"
                                                   :class="darkMode ? 'bg-gray-900 border-gray-800 text-white' : 'bg-white border-gray-300 text-gray-900'">
                                        </div>
                                    </div>

                                    <div class="md:col-span-5 flex flex-wrap items-center gap-3">
                                        <label class="flex items-center gap-3 px-4 py-3 rounded-xl border"
                                               :class="darkMode ? 'border-gray-800 bg-gray-900 text-white' : 'border-gray-300 bg-white text-gray-900'">
                                            <input type="hidden" name="enabled" value="0">
                                            <input type="checkbox" name="enabled" value="1" @checked($preset->enabled)
                                                   class="rounded border-gray-300 text-[#0056b3] focus:ring-[#64CCC5]">
                                            {{ __('media.settings_page.enabled') }}
                                        </label>

                                        <button type="submit"
                                                class="px-4 py-3 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] text-white font-semibold">
                                            {{ __('admin.save') }}
                                        </button>
                                    </div>
                                </form>

                                <form action="{{ route('admin.media-library.settings.presets.destroy', $preset) }}" method="POST" onsubmit="return confirm(@js(__('media.settings_page.confirm_delete_preset', ['name' => $preset->name])));">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="px-4 py-3 rounded-xl bg-red-500 text-white font-semibold hover:bg-red-600 transition-all">
                                        {{ __('admin.delete') }}
                                    </button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-2xl border p-8 text-center"
                             :class="darkMode ? 'border-gray-800 bg-gray-950 text-gray-400' : 'border-gray-200 bg-gray-50 text-gray-600'">
                            {{ __('media.settings_page.no_presets') }}
                        </div>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
@endsection
