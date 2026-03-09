@extends('layouts.admin')

@section('title', __('admin.hadith') . ' #' . $hadith->id)

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <div class="flex items-center gap-2 mb-2">
                <a href="{{ route('admin.hadith.index') }}" class="text-[#0056b3] hover:underline">{{ __('admin.hadiths') }}</a>
                <span :class="darkMode ? 'text-gray-500' : 'text-gray-400'">/</span>
                <span :class="darkMode ? 'text-gray-300' : 'text-gray-600'">{{ __('admin.hadith') }} #{{ $hadith->id }}</span>
            </div>
            <h1 class="text-3xl font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ __('admin.hadith_details') }}</h1>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.hadith.edit', $hadith) }}" class="px-4 py-2 bg-[#0056b3] text-white rounded-lg hover:shadow-lg transition-all flex items-center gap-2">
                <i data-lucide="edit"></i>
                {{ __('admin.edit') }}
            </a>
            <a href="{{ route('admin.hadith.index') }}" class="px-4 py-2 border rounded-lg" :class="darkMode ? 'border-gray-600 text-gray-300' : 'border-gray-300 text-gray-700'">
                {{ __('admin.back') }}
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left Column -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Basic Information -->
            <div class="p-6 rounded-lg" :class="darkMode ? 'bg-gray-800' : 'bg-white'" style="box-shadow: 0 1px 3px rgba(0,0,0,0.1)">
                <h2 class="text-xl font-bold mb-4" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ __('admin.basic_information') }}</h2>
                <div class="space-y-4">
                    <div>
                        <p class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">{{ __('admin.hadith_number') }}</p>
                        <p class="text-lg font-semibold mt-1" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $hadith->hadith_number }}</p>
                    </div>
                    <div>
                        <p class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">{{ __('admin.collection') }}</p>
                        <p class="text-lg font-semibold mt-1" :class="darkMode ? 'text-white' : 'text-gray-900'">
                            {{ $hadith->book?->collection?->name_ar ?? '—' }}
                        </p>
                    </div>
                    <div>
                        <p class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">{{ __('admin.book') }}</p>
                        <p class="text-lg font-semibold mt-1" :class="darkMode ? 'text-white' : 'text-gray-900'">
                            {{ $hadith->book?->title_ar ?? '—' }}
                        </p>
                    </div>
                    <div>
                        <p class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">{{ __('admin.narrator') }}</p>
                        <p class="text-lg font-semibold mt-1" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $hadith->narrator?->name ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">{{ __('admin.grading') }}</p>
                        <p class="text-lg font-semibold mt-1">
                            @if($hadith->gradingLevel)
                                <span class="inline-block px-3 py-1 rounded-full text-sm" :class="darkMode ? 'bg-green-900 text-green-200' : 'bg-green-100 text-green-800'">
                                    {{ $hadith->gradingLevel->grade_ar }}
                                </span>
                            @else
                                <span :class="darkMode ? 'text-gray-400' : 'text-gray-500'">—</span>
                            @endif
                        </p>
                    </div>
                </div>
            </div>

            <!-- Translations -->
            @if($hadith->translations->isNotEmpty())
                <div class="p-6 rounded-lg" :class="darkMode ? 'bg-gray-800' : 'bg-white'" style="box-shadow: 0 1px 3px rgba(0,0,0,0.1)">
                    <h2 class="text-xl font-bold mb-4" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ __('admin.translations') }}</h2>
                    <div class="space-y-4">
                        @foreach($hadith->translations as $translation)
                            <div class="border-start-4" :class="darkMode ? 'border-gray-700 ps-4' : 'border-gray-300 ps-4'">
                                <p class="text-sm font-semibold mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-600'">{{ strtoupper($translation->language_code) }}</p>
                                <p :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ $translation->text }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <!-- Right Column -->
        <div class="space-y-6">
            <!-- Metadata -->
            <div class="p-6 rounded-lg" :class="darkMode ? 'bg-gray-800' : 'bg-white'" style="box-shadow: 0 1px 3px rgba(0,0,0,0.1)">
                <h2 class="text-lg font-bold mb-4" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ __('admin.metadata') }}</h2>
                <div class="space-y-3">
                    <div>
                        <p class="text-xs" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">{{ __('admin.id') }}</p>
                        <p class="text-sm font-semibold" :class="darkMode ? 'text-white' : 'text-gray-900'">#{{ $hadith->id }}</p>
                    </div>
                    <div>
                        <p class="text-xs" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">{{ __('admin.created_at') }}</p>
                        <p class="text-sm font-semibold" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $hadith->created_at->format('Y-m-d H:i') }}</p>
                    </div>
                    <div>
                        <p class="text-xs" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">{{ __('admin.updated_at') }}</p>
                        <p class="text-sm font-semibold" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $hadith->updated_at->format('Y-m-d H:i') }}</p>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="p-6 rounded-lg" :class="darkMode ? 'bg-gray-800' : 'bg-white'" style="box-shadow: 0 1px 3px rgba(0,0,0,0.1)">
                <h2 class="text-lg font-bold mb-4" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ __('admin.actions') }}</h2>
                <div class="space-y-2">
                    <a href="{{ route('admin.hadith.edit', $hadith) }}" class="block px-4 py-2 text-center rounded-lg bg-blue-600 text-white hover:bg-blue-700">
                        {{ __('admin.edit_hadith') }}
                    </a>
                    <form action="{{ route('admin.hadith.destroy', $hadith) }}" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="w-full px-4 py-2 rounded-lg bg-red-600 text-white hover:bg-red-700"
                            onclick="return confirm('{{ __('admin.confirm_delete_hadith') }}')">
                            {{ __('admin.delete_hadith') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    lucide.createIcons();
</script>
@endsection
