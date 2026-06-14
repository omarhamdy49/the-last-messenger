@extends('layouts.admin')

@section('page-header')
<div class="flex items-center gap-4">
    <a href="{{ route('admin.live-channels.show', $channel->id) }}"
       class="p-2 rounded-xl transition-colors"
       :class="darkMode ? 'hover:bg-gray-800 text-gray-400' : 'hover:bg-gray-100 text-gray-600'">
        <i data-lucide="{{ in_array(app()->getLocale(), ['ar','ur']) ? 'chevron-right' : 'chevron-left' }}" class="w-5 h-5"></i>
    </a>
    <div>
        <h1 class="text-3xl font-bold gradient-text flex items-center gap-3">
            <div class="p-3 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] shadow-lg">
                <i data-lucide="radio" class="w-7 h-7 text-white"></i>
            </div>
            {{ __('admin.edit_live_channel') }}
        </h1>
    </div>
</div>
@endsection

@section('content')
@include('admin.live-channels._form', ['channel' => $channel, 'statuses' => $statuses])
@endsection
