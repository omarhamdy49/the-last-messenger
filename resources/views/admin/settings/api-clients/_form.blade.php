{{-- Shared create/edit fields for an API client. Expects $scopes and optional $client. --}}
@php
    $selectedScopes = old('scopes', isset($client) ? ($client->scopes ?? []) : ['*']);
    $origins = old('allowed_origins', isset($client) ? implode("\n", $client->allowed_origins ?? []) : '');
    if (is_array($origins)) {
        $origins = implode("\n", $origins);
    }
@endphp

@if ($errors->any())
    <div class="mb-4 rounded-xl border border-red-300 bg-red-50 p-4 text-sm text-red-700 dark:border-red-800 dark:bg-red-900/20 dark:text-red-300" role="alert">
        <ul class="list-disc ps-5 space-y-1">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <label class="block">
        <span class="block text-sm font-medium mb-1" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ __('admin.api_clients.name') }}</span>
        <input type="text" name="name" required maxlength="100" value="{{ old('name', $client->name ?? '') }}"
               class="w-full rounded-xl border px-4 py-2.5"
               :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
    </label>

    <label class="block">
        <span class="block text-sm font-medium mb-1" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ __('admin.api_clients.rate_limit') }}</span>
        <input type="number" name="rate_limit_per_minute" required min="1" max="6000"
               value="{{ old('rate_limit_per_minute', $client->rate_limit_per_minute ?? 60) }}"
               class="w-full rounded-xl border px-4 py-2.5" dir="ltr"
               :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
    </label>

    <fieldset class="md:col-span-2">
        <legend class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ __('admin.api_clients.scopes') }}</legend>
        <div class="flex flex-wrap gap-3">
            @foreach ($scopes as $scope)
                <label class="inline-flex items-center gap-2 rounded-lg border px-3 py-2 text-sm"
                       :class="darkMode ? 'border-gray-700 text-gray-300' : 'border-gray-200 text-gray-700'">
                    <input type="checkbox" name="scopes[]" value="{{ $scope }}" @checked(in_array($scope, $selectedScopes, true))>
                    <span dir="ltr">{{ $scope === '*' ? __('admin.api_clients.scope_all') : $scope }}</span>
                </label>
            @endforeach
        </div>
    </fieldset>

    <label class="block md:col-span-2">
        <span class="block text-sm font-medium mb-1" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ __('admin.api_clients.allowed_origins') }}</span>
        <textarea name="allowed_origins" rows="3" dir="ltr"
                  class="w-full rounded-xl border px-4 py-2.5 font-mono text-sm"
                  :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">{{ $origins }}</textarea>
        <span class="mt-1 block text-xs" :class="darkMode ? 'text-gray-500' : 'text-gray-500'">{{ __('admin.api_clients.allowed_origins_help') }}</span>
    </label>

    <label class="block">
        <span class="block text-sm font-medium mb-1" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ __('admin.api_clients.expires_at') }}</span>
        <input type="date" name="expires_at" value="{{ old('expires_at', isset($client) ? $client->expires_at?->format('Y-m-d') : '') }}"
               class="w-full rounded-xl border px-4 py-2.5" dir="ltr"
               :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
    </label>

    <label class="inline-flex items-center gap-2 self-end pb-3">
        <input type="hidden" name="is_active" value="0">
        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $client->is_active ?? true))>
        <span class="text-sm" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ __('admin.api_clients.active') }}</span>
    </label>
</div>
