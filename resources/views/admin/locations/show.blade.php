@extends('layouts.admin')

@section('page-header')
<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
    <h1 class="text-3xl font-bold gradient-text flex items-center gap-3">
        <div class="p-3 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] shadow-lg">
            <i data-lucide="map-pin" class="w-7 h-7 text-white"></i>
        </div>
        {{ $location->name() ?? $location->slug }}
    </h1>
    <a href="{{ route('admin.locations.edit', $location->id) }}"
       class="px-6 py-2.5 rounded-xl font-semibold bg-gradient-to-r from-[#0056b3] to-[#218838] text-white flex items-center gap-2">
        <i data-lucide="pencil" class="w-5 h-5"></i>{{ __('admin.edit') }}
    </a>
</div>
@endsection

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="p-6 rounded-2xl border space-y-3" :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
        @foreach([
            'slug' => $location->slug,
            'type' => __('locations.types.'.$location->type->value),
            'parent' => $location->parent?->name() ?? __('locations.fields.no_parent'),
            'depth' => $location->depth,
            'coordinates' => $location->hasCoordinates() ? $location->latitude.', '.$location->longitude : '—',
        ] as $key => $value)
            <div class="flex items-start justify-between gap-4 text-sm">
                <span class="opacity-60">{{ __('locations.fields.'.$key) }}</span>
                <span class="font-semibold text-end" dir="auto">{{ $value }}</span>
            </div>
        @endforeach

        @if($location->aliasList())
            <div class="pt-3 border-t" :class="darkMode ? 'border-gray-800' : 'border-gray-100'">
                <p class="text-sm opacity-60 mb-2">{{ __('locations.fields.aliases') }}</p>
                <div class="flex flex-wrap gap-2">
                    @foreach($location->aliasList() as $alias)
                        <span class="text-xs px-2.5 py-1 rounded-lg" :class="darkMode ? 'bg-gray-800 text-gray-300' : 'bg-gray-100 text-gray-700'" dir="auto">{{ $alias }}</span>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    {{-- Reference counts come from LocationUsageRegistry, not from relations —
         Shared may not name a domain model. --}}
    <div class="p-6 rounded-2xl border" :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
        <h2 class="text-lg font-bold mb-4">{{ __('locations.fields.references') }}</h2>
        @forelse($references as $table => $count)
            <div class="flex items-center justify-between py-2 text-sm">
                <span class="opacity-70">{{ $table }}</span>
                <span class="font-semibold">{{ __('locations.usage.'.$table, ['count' => $count]) }}</span>
            </div>
        @empty
            <p class="text-sm opacity-60">—</p>
        @endforelse
    </div>
</div>
@endsection
