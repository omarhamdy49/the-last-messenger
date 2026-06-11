@extends('layouts.admin')

@section('page-header')
<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
    <div class="flex items-center gap-4">
        <a href="{{ route('admin.media-episodes.index', ['program_id' => $episode->program_id]) }}"
           class="p-2 rounded-xl transition-colors"
           :class="darkMode ? 'hover:bg-gray-800 text-gray-400' : 'hover:bg-gray-100 text-gray-600'">
            <i data-lucide="{{ in_array(app()->getLocale(), ['ar','ur']) ? 'chevron-right' : 'chevron-left' }}" class="w-5 h-5"></i>
        </a>
        <div>
            <h1 class="text-3xl font-bold gradient-text">
                {{ $episode->getTranslation('title', 'ar') ?? $episode->slug }}
            </h1>
            <p class="mt-1 text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                S{{ $episode->season_number }}E{{ str_pad($episode->episode_number, 2, '0', STR_PAD_LEFT) }}
                @if($episode->program)
                — {{ $episode->program->getTranslation('title', 'ar') ?? $episode->program->slug }}
                @endif
            </p>
        </div>
    </div>
    <div class="flex flex-wrap items-center gap-3">
        <a href="{{ route('admin.media-episodes.edit', $episode->id) }}"
           class="px-6 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 flex items-center gap-2 shadow-lg bg-gradient-to-r from-[#0056b3] to-[#218838] text-white">
            <i data-lucide="pencil" class="w-4 h-4"></i>
            {{ __('admin.edit') }}
        </a>
    </div>
</div>
@endsection

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Main Content -->
    <div class="lg:col-span-2 space-y-6">
        <!-- Video Preview -->
        @if($episode->video_url)
        <div class="rounded-2xl border overflow-hidden" :class="darkMode ? 'bg-gray-900 border-gray-800' : 'bg-white border-gray-200'">
            <div class="aspect-video">
                <iframe src="{{ $episode->video_url }}"
                        class="w-full h-full"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                        allowfullscreen></iframe>
            </div>
        </div>
        @endif

        <!-- Translations -->
        <div class="rounded-2xl border p-6" :class="darkMode ? 'bg-gray-900 border-gray-800' : 'bg-white border-gray-200'">
            <h3 class="text-lg font-semibold mb-4" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ __('admin.translations') }}</h3>

            @php $locales = ['ar' => 'العربية', 'en' => 'English']; @endphp
            <div x-data="{ activeLocale: 'ar' }">
                <div class="flex gap-2 mb-4 overflow-x-auto whitespace-nowrap pb-1">
                    @foreach($locales as $code => $label)
                    <button type="button" @click="activeLocale = '{{ $code }}'"
                            class="flex-shrink-0 px-4 py-2 rounded-lg text-sm font-medium transition-colors"
                            :class="activeLocale === '{{ $code }}'
                                ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white'
                                : (darkMode ? 'text-gray-400 hover:bg-gray-800' : 'text-gray-600 hover:bg-gray-100')">
                        {{ $label }}
                    </button>
                    @endforeach
                </div>

                @foreach($locales as $code => $label)
                @php $t = $episode->translations->where('locale', $code)->first(); @endphp
                <div x-show="activeLocale === '{{ $code }}'" dir="{{ in_array($code, ['ar','ur']) ? 'rtl' : 'ltr' }}">
                    @if($t)
                    <div class="space-y-4">
                        <div>
                            <p class="text-xs font-medium mb-1 uppercase tracking-wide" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">{{ __('admin.title') }}</p>
                            <p class="font-semibold text-lg" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $t->title }}</p>
                        </div>
                        @if($t->description)
                        <div>
                            <p class="text-xs font-medium mb-1 uppercase tracking-wide" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">{{ __('admin.description') }}</p>
                            <p class="text-sm leading-relaxed" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ $t->description }}</p>
                        </div>
                        @endif
                    </div>
                    @else
                    <p class="text-sm" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">{{ __('admin.no_translation') }}</p>
                    @endif
                </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="space-y-6">
        <!-- Thumbnail -->
        @if($episode->getFirstMedia('thumbnail'))
        <div class="rounded-2xl border overflow-hidden" :class="darkMode ? 'bg-gray-900 border-gray-800' : 'bg-white border-gray-200'">
            <img src="{{ $episode->getFirstMediaUrl('thumbnail') }}" class="w-full object-cover" alt="{{ $episode->slug }}">
        </div>
        @endif

        <!-- Episode Info -->
        <div class="rounded-2xl border p-6" :class="darkMode ? 'bg-gray-900 border-gray-800' : 'bg-white border-gray-200'">
            <h3 class="text-lg font-semibold mb-4" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ __('admin.episode_information') }}</h3>
            <dl class="space-y-3">
                @if($episode->program)
                <div class="flex justify-between items-start">
                    <dt class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">{{ __('admin.program') }}</dt>
                    <dd class="text-sm font-medium text-end" :class="darkMode ? 'text-white' : 'text-gray-900'">
                        <a href="{{ route('admin.media-programs.show', $episode->program_id) }}"
                           class="text-[#0056b3] hover:underline">
                            {{ $episode->program->getTranslation('title', 'ar') ?? $episode->program->slug }}
                        </a>
                    </dd>
                </div>
                @endif
                <div class="flex justify-between items-start">
                    <dt class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">{{ __('admin.season_number') }}</dt>
                    <dd class="text-sm font-medium" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $episode->season_number }}</dd>
                </div>
                <div class="flex justify-between items-start">
                    <dt class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">{{ __('admin.episode_number') }}</dt>
                    <dd class="text-sm font-medium" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $episode->episode_number }}</dd>
                </div>
                @if($episode->duration_seconds)
                <div class="flex justify-between items-start">
                    <dt class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">{{ __('admin.duration_seconds') }}</dt>
                    <dd class="text-sm font-medium" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ gmdate('G:i', $episode->duration_seconds) }}</dd>
                </div>
                @endif
                <div class="flex justify-between items-start">
                    <dt class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">{{ __('admin.views') }}</dt>
                    <dd class="text-sm font-medium" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ number_format($episode->views_count) }}</dd>
                </div>
                <div class="flex justify-between items-start">
                    <dt class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">{{ __('admin.created_at') }}</dt>
                    <dd class="text-sm font-medium" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $episode->created_at->format('Y-m-d') }}</dd>
                </div>
            </dl>
            @if($episode->is_featured)
            <div class="mt-4">
                <span class="px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">{{ __('admin.featured_only') }}</span>
            </div>
            @endif
        </div>

        <!-- Delete -->
        <form method="POST" action="{{ route('admin.media-episodes.destroy', $episode->id) }}"
              onsubmit="return confirm('{{ __('admin.confirm_delete') }}')">
            @csrf
            @method('DELETE')
            <button type="submit"
                    class="w-full py-2.5 rounded-xl font-semibold text-red-600 border border-red-300 hover:bg-red-50 transition-colors flex items-center justify-center gap-2">
                <i data-lucide="trash-2" class="w-4 h-4"></i>
                {{ __('admin.delete') }}
            </button>
        </form>
    </div>
</div>
@endsection
