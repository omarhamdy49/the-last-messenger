@extends('layouts.app')

@section('title', $muslim->name())

@section('content')
<div class="container mx-auto px-4 py-8">
    <article data-testid="muslim-profile">
        <header class="mb-6">
            <h1 class="text-3xl font-bold" data-testid="muslim-name">{{ $muslim->name() }}</h1>

            @if ($kunya = $muslim->getTranslation('kunya', app()->getLocale(), false))
                <p class="text-lg text-gray-600 mt-1" data-testid="muslim-kunya">{{ $kunya }}</p>
            @endif

            {{-- One profile, every role. This is the whole point of the rebuild:
                 the same person used to be up to four unrelated records. --}}
            <div class="mt-3 flex flex-wrap gap-2" data-testid="muslim-roles">
                @foreach ($muslim->roles as $role)
                    <span class="text-sm px-3 py-1 rounded-full bg-[#64CCC5]/20"
                          data-testid="muslim-role" data-role="{{ $role->slug }}">
                        {{ $role->name() }}
                    </span>
                @endforeach
            </div>
        </header>

        <dl class="grid gap-3 sm:grid-cols-2 mb-8">
            @if ($muslim->birth_year_hijri !== null)
                <div>
                    <dt class="text-sm text-gray-500">{{ __('people.birth_year') }}</dt>
                    <dd data-testid="muslim-birth-year">
                        {{-- Negative years are pre-Hijra, rendered as "قبل الهجرة". --}}
                        @if ($muslim->birth_year_hijri < 0)
                            {{ abs($muslim->birth_year_hijri) }} {{ __('people.before_hijra') }}
                        @else
                            {{ $muslim->birth_year_hijri }} {{ __('people.hijri') }}
                        @endif
                    </dd>
                </div>
            @endif
            @if ($muslim->death_year_hijri !== null)
                <div>
                    <dt class="text-sm text-gray-500">{{ __('people.death_year') }}</dt>
                    <dd>{{ $muslim->death_year_hijri }} {{ __('people.hijri') }}</dd>
                </div>
            @endif
            @if ($muslim->father)
                <div>
                    <dt class="text-sm text-gray-500">{{ __('people.father') }}</dt>
                    <dd><a href="{{ route('public.muslims.show', $muslim->father->slug) }}"
                           data-testid="muslim-father">{{ $muslim->father->name() }}</a></dd>
                </div>
            @endif
            @if ($muslim->mother)
                <div>
                    <dt class="text-sm text-gray-500">{{ __('people.mother') }}</dt>
                    <dd><a href="{{ route('public.muslims.show', $muslim->mother->slug) }}"
                           data-testid="muslim-mother">{{ $muslim->mother->name() }}</a></dd>
                </div>
            @endif
        </dl>

        @if ($bio = $muslim->getTranslation('bio', app()->getLocale(), false))
            <section class="prose max-w-none mb-8" data-testid="muslim-bio">{!! nl2br(e($bio)) !!}</section>
        @endif

        @if ($relations !== [])
            <section class="mb-8">
                <h2 class="text-xl font-semibold mb-3">{{ __('people.relations') }}</h2>
                <ul class="grid gap-2" data-testid="muslim-relations">
                    @foreach ($relations as $relation)
                        <li data-testid="muslim-relation">
                            <a href="{{ route('public.muslims.show', $relation['slug']) }}">{{ $relation['name'] }}</a>
                            <span class="text-sm text-gray-500">— {{ $relation['label'] }}</span>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        <a href="{{ route('public.muslims.tree', $muslim->slug) }}"
           class="inline-block px-5 py-2 rounded-lg bg-[#0056b3] text-white"
           data-testid="muslim-tree-link">
            {{ __('people.tree') }}
        </a>
    </article>
</div>
@endsection
