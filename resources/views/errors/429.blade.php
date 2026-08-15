@extends('errors.layout')

@section('code', '429')
@section('heading', __('errors.429_title'))
@section('message', __('errors.429_message'))

@section('actions_extra')
    <a class="btn btn-ghost" href="{{ url()->full() }}">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><path d="M3 3v5h5"/>
        </svg>
        {{ __('errors.retry') }}
    </a>
@endsection
