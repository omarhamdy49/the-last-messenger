@extends('errors.layout')

@section('code', '404')
@section('heading', __('errors.404_title'))
@section('message', __('errors.404_message'))

@section('extras')
    <div class="panel">
        <form class="search" action="{{ url('/search') }}" method="GET" role="search">
            <label class="sr-only" for="error-search">{{ __('errors.search_label') }}</label>
            <input id="error-search" type="search" name="q" autocomplete="off"
                   placeholder="{{ __('site.search_placeholder') }}">
            <button type="submit" class="btn btn-primary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>
                </svg>
                {{ __('errors.search_action') }}
            </button>
        </form>
    </div>

    <p class="label" style="margin-top:1.5rem;margin-bottom:0;">{{ __('errors.explore') }}</p>
    <nav class="links" aria-label="{{ __('errors.explore') }}">
        <a href="{{ url('/seerah') }}">{{ __('site.seerah') }}</a>
        <a href="{{ url('/hadith') }}">{{ __('site.hadith') }}</a>
        <a href="{{ url('/quran') }}">{{ __('site.quran') }}</a>
        <a href="{{ url('/sahaba') }}">{{ __('site.sahaba') }}</a>
        <a href="{{ url('/media') }}">{{ __('site.media') }}</a>
        <a href="{{ url('/articles') }}">{{ __('site.articles') }}</a>
    </nav>
@endsection
