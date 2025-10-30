{{--
    Filter Pills Component

    Props:
    - $categories (array): Array of categories
    - $active (string): Currently active category
    - $sticky (bool): Make pills sticky

    Usage:
    <x-filter-pills :categories="$categories" :active="$activeCategory" :sticky="true" />
--}}

@props([
    'categories',
    'active' => 'all',
    'sticky' => false
])

<section {{ $attributes->merge(['class' => ($sticky ? 'sticky top-16 z-40 ' : '') . 'py-4']) }}
         :class="darkMode ? 'bg-gray-950/95 backdrop-blur-lg' : 'bg-gray-50/95 backdrop-blur-lg'">
    <div class="flex gap-3 overflow-x-auto hide-scrollbar pb-2">
        @foreach($categories as $key => $category)
        <button @click="activeFilter = '{{ $key }}'"
                class="category-pill whitespace-nowrap px-6 py-2.5 rounded-full font-semibold transition-all duration-300 flex items-center gap-2"
                :class="activeFilter === '{{ $key }}' ?
                    'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-lg scale-105' :
                    (darkMode ? 'bg-gray-800 text-gray-300 hover:bg-gray-700' : 'bg-white text-gray-700 hover:bg-gray-100 shadow-md')">
            @if(isset($category['icon']))
            <i class="w-4 h-4" data-lucide="{{ $category['icon'] }}"></i>
            @endif
            <span>{{ $category['name'] }}</span>
            @if(isset($category['count']))
            <span class="px-2 py-0.5 rounded-full text-xs font-bold"
                  :class="activeFilter === '{{ $key }}' ? 'bg-white/20' : (darkMode ? 'bg-gray-700' : 'bg-gray-200')">
                {{ $category['count'] }}
            </span>
            @endif
        </button>
        @endforeach
    </div>
</section>
