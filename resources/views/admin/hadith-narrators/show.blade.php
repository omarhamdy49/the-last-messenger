@extends('admin.layouts.admin')

@section('title', $narrator->name['en'] ?? $narrator->name['ar'])

@section('content')
<div x-data="narratorShow()" class="container mx-auto max-w-5xl px-4 py-8">
    <!-- Page Header -->
    <div class="flex justify-between items-start mb-8">
        <div>
            <h1 class="text-4xl font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">
                {{ $narrator->name['en'] ?? $narrator->name['ar'] }}
            </h1>
            <p class="text-gray-500 mt-2">{{ __('admin.narrator_profile') }}</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.hadith-narrators.edit', $narrator) }}"
               class="flex items-center gap-2 px-6 py-3 rounded-lg font-semibold transition text-white bg-gradient-to-r from-[#0056b3] to-[#218838]">
                <i data-lucide="edit" class="w-5 h-5"></i>
                {{ __('admin.edit') }}
            </a>
            <form action="{{ route('admin.hadith-narrators.destroy', $narrator) }}" method="POST" style="display:inline" onsubmit="return confirm('{{ __('admin.confirm_delete') }}');">
                @csrf
                @method('DELETE')
                <button type="submit" class="flex items-center gap-2 px-6 py-3 rounded-lg font-semibold transition text-white bg-red-500 hover:bg-red-600">
                    <i data-lucide="trash-2" class="w-5 h-5"></i>
                    {{ __('admin.delete') }}
                </button>
            </form>
        </div>
    </div>

    <!-- Status & Grade Badges -->
    <div class="flex gap-4 mb-8">
        <!-- Status Badge -->
        <span class="px-4 py-2 rounded-full font-semibold transition"
              :class="'{{ $narrator->status }}' === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'">
            {{ __("admin.{$narrator->status}") }}
        </span>

        <!-- Grade Badge -->
        <span class="px-4 py-2 rounded-full font-semibold"
              :class="getGradeBadgeClass('{{ $narrator->reliability_grade }}')">
            {{ $narrator->reliability_grade }}
        </span>

        <!-- Era Badge -->
        <span class="px-4 py-2 rounded-full font-semibold transition"
              :class="darkMode ? 'bg-blue-900 text-blue-200' : 'bg-blue-100 text-blue-800'">
            {{ __("admin.{$narrator->era}") }}
        </span>
    </div>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
        <!-- Left Column: Basic Info -->
        <div class="lg:col-span-1 space-y-6">
            <!-- Basic Information Card -->
            <div class="glass rounded-2xl p-6 backdrop-blur-lg"
                 :class="darkMode ? 'bg-gray-900/30 border-gray-700' : 'bg-white/70 border-white'">
                <h2 class="text-lg font-bold mb-4 flex items-center gap-2"
                    :class="darkMode ? 'text-white' : 'text-gray-900'">
                    <i data-lucide="info" class="w-5 h-5 text-[#0056b3]"></i>
                    {{ __('admin.basic_information') }}
                </h2>

                <div class="space-y-4">
                    <!-- Country -->
                    <div>
                        <p class="text-sm text-gray-500">{{ __('admin.country') }}</p>
                        <p class="font-semibold" :class="darkMode ? 'text-white' : 'text-gray-900'">
                            {{ $narrator->country ?? __('admin.not_specified') }}
                        </p>
                    </div>

                    <!-- Birth Date -->
                    <div>
                        <p class="text-sm text-gray-500">{{ __('admin.birth_hijri') }}</p>
                        <p class="font-semibold" :class="darkMode ? 'text-white' : 'text-gray-900'">
                            {{ $narrator->birth_hijri ?? __('admin.not_specified') }}
                        </p>
                    </div>

                    <!-- Death Date -->
                    <div>
                        <p class="text-sm text-gray-500">{{ __('admin.death_hijri') }}</p>
                        <p class="font-semibold" :class="darkMode ? 'text-white' : 'text-gray-900'">
                            {{ $narrator->death_hijri ?? __('admin.not_specified') }}
                        </p>
                    </div>

                    <!-- Hadith Count -->
                    <div class="pt-4 border-t" :class="darkMode ? 'border-gray-700' : 'border-gray-200'">
                        <p class="text-sm text-gray-500">{{ __('admin.hadith_count') }}</p>
                        <p class="text-2xl font-bold text-[#0056b3]">{{ $hadithCount }}</p>
                    </div>
                </div>
            </div>

            <!-- Metadata Card -->
            <div class="glass rounded-2xl p-6 backdrop-blur-lg"
                 :class="darkMode ? 'bg-gray-900/30 border-gray-700' : 'bg-white/70 border-white'">
                <h3 class="font-bold mb-4" :class="darkMode ? 'text-white' : 'text-gray-900'">
                    {{ __('admin.metadata') }}
                </h3>

                <div class="space-y-3 text-sm">
                    <div>
                        <p class="text-gray-500">{{ __('admin.created_at') }}</p>
                        <p :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            {{ $narrator->created_at->format('M d, Y') }}
                        </p>
                    </div>
                    <div>
                        <p class="text-gray-500">{{ __('admin.updated_at') }}</p>
                        <p :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            {{ $narrator->updated_at->format('M d, Y') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Detailed Info -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Biography Section -->
            <div class="glass rounded-2xl p-6 backdrop-blur-lg"
                 :class="darkMode ? 'bg-gray-900/30 border-gray-700' : 'bg-white/70 border-white'">
                <h2 class="text-lg font-bold mb-6 flex items-center gap-2"
                    :class="darkMode ? 'text-white' : 'text-gray-900'">
                    <i data-lucide="book" class="w-5 h-5 text-[#218838]"></i>
                    {{ __('admin.biography') }}
                </h2>

                <!-- Language Tabs -->
                <div class="flex gap-2 mb-6 border-b border-gray-300" :class="darkMode ? 'border-gray-700' : ''">
                    @foreach(['ar' => 'العربية', 'en' => 'English', 'fr' => 'Français', 'tr' => 'Türkçe', 'ur' => 'اردو'] as $lang => $label)
                        <button type="button"
                                @click="activeTab = '{{ $lang }}'"
                                :class="activeTab === '{{ $lang }}' ? 'border-b-2 border-[#0056b3] text-[#0056b3]' : 'text-gray-500'"
                                class="px-4 py-2 font-semibold transition whitespace-nowrap">
                            {{ $label }}
                        </button>
                    @endforeach
                </div>

                <!-- Biography Content -->
                <div class="prose dark:prose-invert max-w-none"
                     :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                    @foreach(['ar', 'en', 'fr', 'tr', 'ur'] as $lang)
                        <div x-show="activeTab === '{{ $lang }}'" x-cloak>
                            @if($narrator->biography_json[$lang] ?? null)
                                <p>{{ $narrator->biography_json[$lang] }}</p>
                            @else
                                <p class="text-gray-500 italic">{{ __('admin.no_biography') }}</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Narrator Chain Section -->
            <div class="glass rounded-2xl p-6 backdrop-blur-lg"
                 :class="darkMode ? 'bg-gray-900/30 border-gray-700' : 'bg-white/70 border-white'">
                <h2 class="text-lg font-bold mb-6 flex items-center gap-2"
                    :class="darkMode ? 'text-white' : 'text-gray-900'">
                    <i data-lucide="link" class="w-5 h-5 text-[#64CCC5]"></i>
                    {{ __('admin.narrator_chain') }}
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <!-- Teachers -->
                    <div>
                        <h3 class="font-bold mb-4" :class="darkMode ? 'text-white' : 'text-gray-900'">
                            {{ __('admin.teachers') }} ({{ count($teachers) }})
                        </h3>
                        @if(count($teachers) > 0)
                            <ul class="space-y-2">
                                @foreach($teachers as $teacher)
                                    <li>
                                        <a href="{{ route('admin.hadith-narrators.show', $teacher) }}"
                                           class="flex items-center gap-2 p-3 rounded-lg transition"
                                           :class="darkMode ? 'bg-gray-800 hover:bg-gray-700' : 'bg-gray-100 hover:bg-gray-200'">
                                            <i data-lucide="arrow-right" class="w-4 h-4"></i>
                                            <span>{{ $teacher->name['en'] ?? $teacher->name['ar'] }}</span>
                                            <span class="text-xs px-2 py-1 rounded" :class="getGradeBadgeClass($teacher->reliability_grade)">
                                                {{ $teacher->reliability_grade }}
                                            </span>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <p class="text-gray-500 italic">{{ __('admin.no_teachers') }}</p>
                        @endif
                    </div>

                    <!-- Students -->
                    <div>
                        <h3 class="font-bold mb-4" :class="darkMode ? 'text-white' : 'text-gray-900'">
                            {{ __('admin.students') }} ({{ count($students) }})
                        </h3>
                        @if(count($students) > 0)
                            <ul class="space-y-2">
                                @foreach($students as $student)
                                    <li>
                                        <a href="{{ route('admin.hadith-narrators.show', $student) }}"
                                           class="flex items-center gap-2 p-3 rounded-lg transition"
                                           :class="darkMode ? 'bg-gray-800 hover:bg-gray-700' : 'bg-gray-100 hover:bg-gray-200'">
                                            <i data-lucide="arrow-left" class="w-4 h-4"></i>
                                            <span>{{ $student->name['en'] ?? $student->name['ar'] }}</span>
                                            <span class="text-xs px-2 py-1 rounded" :class="getGradeBadgeClass($student->reliability_grade)">
                                                {{ $student->reliability_grade }}
                                            </span>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <p class="text-gray-500 italic">{{ __('admin.no_students') }}</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Back Button -->
    <div class="flex gap-4">
        <a href="{{ route('admin.hadith-narrators.index') }}"
           class="flex items-center gap-2 px-6 py-3 rounded-lg font-semibold transition"
           :class="darkMode ? 'bg-gray-800 text-white hover:bg-gray-700' : 'bg-gray-200 text-gray-900 hover:bg-gray-300'">
            <i data-lucide="arrow-left" class="w-5 h-5"></i>
            {{ __('admin.back_to_list') }}
        </a>
    </div>
</div>

<script>
function narratorShow() {
    return {
        activeTab: 'ar',
        darkMode: localStorage.getItem('darkMode') !== 'false',

        getGradeBadgeClass(grade) {
            const classes = {
                'Sahih': 'bg-green-100 text-green-800',
                'Hasan': 'bg-blue-100 text-blue-800',
                'Daif': 'bg-orange-100 text-orange-800',
                'Weak': 'bg-red-100 text-red-800',
            };
            return classes[grade] || 'bg-gray-100 text-gray-800';
        },

        init() {
            this.$watch('darkMode', () => {
                localStorage.setItem('darkMode', this.darkMode);
            });
            lucide.createIcons();
        }
    };
}
</script>
@endsection
