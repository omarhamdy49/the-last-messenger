@extends('layouts.admin')

@section('content')
<div x-data="{ submitting: false }">
    <div class="mb-8 text-center">
        <h1 class="text-3xl font-bold gradient-text mb-2">{{ __('admin.content_home_sections') }}</h1>
    </div>

    @include('admin.content._flash')

    <form id="section-order-form"
          method="POST"
          action="{{ route('admin.content.home-sections.reorder') }}"
          @submit="submitting = true">
        @csrf
    </form>

    <div class="rounded-2xl overflow-hidden shadow-xl border"
         :class="darkMode ? 'bg-gray-900 border-gray-800' : 'bg-white border-gray-200'">
        @if (count($sections) === 0)
            <div class="p-12 text-center">
                <i data-lucide="panels-top-left" class="w-12 h-12 mx-auto mb-4 text-gray-400"></i>
                <p :class="darkMode ? 'text-gray-400' : 'text-gray-600'">{{ __('admin.content_sections_empty') }}</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead :class="darkMode ? 'bg-gray-800 text-gray-300' : 'bg-gray-50 text-gray-600'">
                        <tr>
                            <th class="px-6 py-4 text-start">{{ __('admin.content_key') }}</th>
                            <th class="px-6 py-4 text-start">{{ __('admin.title') }}</th>
                            <th class="px-6 py-4 text-center">{{ __('admin.display_order') }}</th>
                            <th class="px-6 py-4 text-center">{{ __('admin.active_status') }}</th>
                            <th class="px-6 py-4 text-center">{{ __('admin.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y" :class="darkMode ? 'divide-gray-800' : 'divide-gray-200'">
                        @foreach ($sections as $index => $section)
                            <tr>
                                <td class="px-6 py-4 font-mono text-sm" dir="ltr">{{ $section->key }}</td>
                                <td class="px-6 py-4">{{ $section->heading ?: '—' }}</td>
                                <td class="px-6 py-4 text-center">
                                    <input type="hidden"
                                           form="section-order-form"
                                           name="sections[{{ $index }}][id]"
                                           value="{{ $section->id }}">
                                    <input type="number"
                                           form="section-order-form"
                                           name="sections[{{ $index }}][display_order]"
                                           value="{{ old("sections.{$index}.display_order", $section->displayOrder) }}"
                                           min="1"
                                           max="10000"
                                           required
                                           class="w-24 px-3 py-2 rounded-lg border text-center"
                                           :class="darkMode ? 'bg-gray-950 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                                    @error("sections.{$index}.display_order")
                                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                    @enderror
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <form method="POST"
                                          action="{{ route('admin.content.home-sections.toggle', $section->id) }}"
                                          x-data="{ submitting: false }"
                                          @submit="submitting = true">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="is_active" value="{{ $section->isActive ? 0 : 1 }}">
                                        <button type="submit"
                                                :disabled="submitting"
                                                class="px-3 py-1.5 rounded-full text-xs font-bold disabled:opacity-60 {{ $section->isActive ? 'bg-green-500/15 text-green-500' : 'bg-gray-500/15 text-gray-500' }}">
                                            <span x-show="!submitting">{{ $section->isActive ? __('admin.active') : __('admin.inactive') }}</span>
                                            <i x-show="submitting" x-cloak data-lucide="loader-2" class="w-4 h-4 animate-spin"></i>
                                        </button>
                                    </form>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <a href="{{ route('admin.content.home-sections.edit', $section->id) }}"
                                       class="inline-flex p-2 rounded-lg text-accent hover:bg-accent/10"
                                       title="{{ __('admin.edit') }}">
                                        <i data-lucide="pencil" class="w-4 h-4"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="p-6 flex justify-end border-t" :class="darkMode ? 'border-gray-800' : 'border-gray-200'">
                <button type="submit"
                        form="section-order-form"
                        :disabled="submitting"
                        class="px-6 py-3 rounded-lg font-semibold bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-lg disabled:opacity-60 flex items-center gap-2">
                    <i data-lucide="save" class="w-5 h-5" x-show="!submitting"></i>
                    <i data-lucide="loader-2" class="w-5 h-5 animate-spin" x-show="submitting" x-cloak></i>
                    <span>{{ __('admin.save_changes') }}</span>
                </button>
            </div>
        @endif
    </div>
</div>
@endsection
