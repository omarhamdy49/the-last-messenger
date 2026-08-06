@extends('layouts.app')

@section('title', __('people.tree').' — '.$muslim->name())

@section('content')
<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold mb-2" data-testid="tree-title">{{ __('people.tree') }}</h1>
    <p class="text-lg text-gray-600 mb-6" data-testid="tree-root">{{ $muslim->name() }}</p>

    @if ($depthCapped)
        {{-- Reported, never silently truncated: a capped tree that looked
             complete would be indistinguishable from a real one. --}}
        <p class="mb-6 px-4 py-2 rounded-lg bg-amber-100 text-amber-900" data-testid="tree-depth-capped">
            {{ __('people.depth_capped') }}
        </p>
    @endif

    <section class="mb-8">
        <h2 class="text-xl font-semibold mb-3">{{ __('people.ancestors') }}</h2>
        <ol class="grid gap-2" data-testid="tree-ancestors">
            @forelse ($ancestors as $node)
                <li data-testid="tree-ancestor" data-depth="{{ $node['depth'] }}"
                    style="padding-inline-start: {{ ($node['depth'] - 1) * 1.5 }}rem">
                    <a href="{{ route('public.muslims.show', $node['slug']) }}">
                        {{ app()->getLocale() === 'ar'
                            ? ($node['name_ar'] ?? $node['name_en'])
                            : ($node['name_en'] ?? $node['name_ar']) }}
                    </a>
                    <span class="text-xs text-gray-500">({{ __('people.'.$node['relation']) }})</span>
                </li>
            @empty
                <li data-testid="tree-no-ancestors">{{ __('people.no_ancestors') }}</li>
            @endforelse
        </ol>
    </section>

    <section class="mb-8">
        <h2 class="text-xl font-semibold mb-3">{{ __('people.descendants') }}</h2>
        <ol class="grid gap-2" data-testid="tree-descendants">
            @forelse ($descendants as $node)
                <li data-testid="tree-descendant" data-depth="{{ $node['depth'] }}"
                    style="padding-inline-start: {{ ($node['depth'] - 1) * 1.5 }}rem">
                    <a href="{{ route('public.muslims.show', $node['slug']) }}">
                        {{ app()->getLocale() === 'ar'
                            ? ($node['name_ar'] ?? $node['name_en'])
                            : ($node['name_en'] ?? $node['name_ar']) }}
                    </a>
                </li>
            @empty
                <li data-testid="tree-no-descendants">{{ __('people.no_descendants') }}</li>
            @endforelse
        </ol>
    </section>

    @if ($relations !== [])
        <section>
            <h2 class="text-xl font-semibold mb-3">{{ __('people.relations') }}</h2>
            <ul class="grid gap-2" data-testid="tree-relations">
                @foreach ($relations as $relation)
                    <li data-testid="tree-relation">
                        <a href="{{ route('public.muslims.show', $relation['slug']) }}">{{ $relation['name'] }}</a>
                        <span class="text-sm text-gray-500">— {{ $relation['label'] }}</span>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif
</div>
@endsection
