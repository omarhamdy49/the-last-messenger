@extends('layouts.admin')

@section('page-header')
    <x-admin.quran.page-header :title="__('quran.manage.sync_run', ['id' => $run->id])" icon="activity"
        :crumbs="[__('quran.manage.sync') => route('admin.quran.sync.index')]" />
@endsection

@section('content')
    <x-admin.quran.panel>
        <dl class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
            <div><dt class="text-gray-500">{{ __('quran.manage.mode') }}</dt><dd class="font-semibold">{{ $run->mode->value }}</dd></div>
            <div><dt class="text-gray-500">{{ __('quran.manage.status') }}</dt><dd class="font-semibold">{{ $run->status }}</dd></div>
            <div><dt class="text-gray-500">{{ __('quran.manage.started') }}</dt><dd>{{ $run->started_at?->toDateTimeString() }}</dd></div>
            <div><dt class="text-gray-500">{{ __('quran.manage.finished') }}</dt><dd>{{ $run->finished_at?->toDateTimeString() ?? '—' }}</dd></div>
            <div><dt class="text-gray-500">{{ __('quran.manage.mutations') }}</dt><dd>{{ $run->mutations_applied }}</dd></div>
            <div><dt class="text-gray-500">{{ __('quran.manage.snapshots') }}</dt><dd>{{ $run->snapshots_fetched }}</dd></div>
            <div><dt class="text-gray-500">{{ __('quran.manage.upserted') }}</dt><dd>{{ $run->rows_upserted }}</dd></div>
            <div><dt class="text-gray-500">{{ __('quran.manage.deleted_rows') }}</dt><dd>{{ $run->rows_deleted }}</dd></div>
            <div><dt class="text-gray-500">{{ __('quran.manage.skipped_locked') }}</dt><dd>{{ $run->rows_skipped_locked }}</dd></div>
        </dl>
        <div class="mt-4">
            <div class="text-sm text-gray-500">{{ __('quran.manage.resources_filter') }}</div>
            <code class="block break-all text-xs" dir="ltr">{{ $run->resources_filter ?? '—' }}</code>
        </div>
    </x-admin.quran.panel>

    <h2 class="text-lg font-bold mb-3">{{ __('quran.manage.errors') }}</h2>
    <x-admin.quran.table :empty="empty($run->errors)" :empty-text="__('quran.manage.no_errors')">
        <x-slot:head>
            <th>{{ __('quran.manage.key') }}</th>
            <th class="w-full">{{ __('quran.manage.reason') }}</th>
        </x-slot:head>
        @foreach ($run->errors ?? [] as $error)
            <tr>
                <td dir="ltr" class="font-mono text-xs whitespace-nowrap">{{ $error['key'] ?? '—' }}</td>
                <td>{{ $error['reason'] ?? '' }}</td>
            </tr>
        @endforeach
    </x-admin.quran.table>
@endsection
