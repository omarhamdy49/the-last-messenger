@extends('layouts.admin')

@section('page-header')
<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
    <div>
        <h1 class="text-3xl font-bold gradient-text flex items-center gap-3">
            <div class="p-3 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] shadow-lg">
                <i data-lucide="languages" class="w-7 h-7 text-white"></i>
            </div>
            {{ __('quran.admin.translation_sources') }}
        </h1>
        <p class="mt-2 text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
            {{ __('quran.admin.translation_sources_subtitle') }}
        </p>
    </div>
    <a href="{{ route('admin.translation-resources.create') }}"
       class="inline-flex items-center gap-2 px-5 py-3 rounded-xl font-semibold text-white bg-gradient-to-r from-[#0056b3] to-[#218838] shadow-lg hover:shadow-xl transition-all">
        <i data-lucide="plus" class="w-5 h-5"></i>
        {{ __('quran.admin.add_source') }}
    </a>
</div>
@endsection

@section('content')
@if(session('success'))
<div class="mb-4 p-4 rounded-xl bg-green-100 text-green-800 border border-green-200">{{ session('success') }}</div>
@endif

<div class="rounded-2xl border overflow-hidden" :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
    <div class="overflow-x-auto">
        <table class="w-full text-start">
            <thead :class="darkMode ? 'bg-gray-800/50 text-gray-300' : 'bg-gray-50 text-gray-600'">
                <tr class="text-sm">
                    <th class="px-4 py-3 text-start font-semibold">{{ __('quran.admin.name_ar') }}</th>
                    <th class="px-4 py-3 text-start font-semibold">{{ __('quran.admin.language') }}</th>
                    <th class="px-4 py-3 text-start font-semibold">{{ __('quran.admin.author') }}</th>
                    <th class="px-4 py-3 text-start font-semibold">{{ __('quran.admin.entries') }}</th>
                    <th class="px-4 py-3 text-start font-semibold">{{ __('quran.admin.is_default') }}</th>
                    <th class="px-4 py-3 text-start font-semibold">{{ __('quran.admin.is_active') }}</th>
                    <th class="px-4 py-3 text-end font-semibold">{{ __('quran.admin.actions') }}</th>
                </tr>
            </thead>
            <tbody :class="darkMode ? 'text-gray-200' : 'text-gray-800'">
                @forelse($resources as $resource)
                <tr class="border-t" :class="darkMode ? 'border-gray-800' : 'border-gray-100'">
                    <td class="px-4 py-3 font-semibold">{{ $resource->getName('ar') }}</td>
                    <td class="px-4 py-3"><span class="px-2 py-0.5 rounded-full text-xs bg-blue-100 text-blue-700">{{ strtoupper($resource->language) }}</span></td>
                    <td class="px-4 py-3 text-sm">{{ $resource->author ?: '—' }}</td>
                    <td class="px-4 py-3 text-sm">{{ number_format($resource->translations_count) }}</td>
                    <td class="px-4 py-3">
                        @if($resource->is_default)
                        <span class="px-2 py-0.5 rounded-full text-xs bg-amber-100 text-amber-700">{{ __('quran.admin.default') }}</span>
                        @else
                        <span class="text-gray-400">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        @if($resource->is_active)
                        <span class="px-2 py-0.5 rounded-full text-xs bg-green-100 text-green-700">{{ __('quran.admin.active') }}</span>
                        @else
                        <span class="px-2 py-0.5 rounded-full text-xs bg-gray-200 text-gray-600">{{ __('quran.admin.inactive') }}</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center justify-end gap-2 flex-wrap">
                            <a href="{{ route('admin.translation-resources.edit', $resource->id) }}"
                               class="p-2 rounded-lg hover:bg-blue-100 text-blue-600" title="{{ __('quran.admin.edit_source') }}">
                                <i data-lucide="pencil" class="w-4 h-4"></i>
                            </a>
                            <form method="POST" action="{{ route('admin.translation-resources.destroy', $resource->id) }}"
                                  onsubmit="return confirm('{{ __('quran.admin.confirm_delete') }}')">
                                @csrf @method('DELETE')
                                <button type="submit" class="p-2 rounded-lg hover:bg-red-100 text-red-600" title="{{ __('quran.admin.delete') }}">
                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="px-4 py-10 text-center" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">{{ __('quran.admin.no_sources') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-4">{{ $resources->links() }}</div>
@endsection
