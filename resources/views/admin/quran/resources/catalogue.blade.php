@extends('layouts.admin')

@section('page-header')
    <x-admin.quran.page-header :title="__('quran.manage.qf_catalogue').' · '.$kind->label()" icon="cloud-download" :subtitle="__('quran.manage.qf_catalogue_help')"
        :crumbs="[$kind->label() => route('admin.quran.resources.index', $kind->routeSegment())]" />
@endsection

@section('content')
    <x-admin.quran.flash />

    @if ($error)
        <div class="mb-4 rounded-xl border border-red-300 bg-red-50 p-4 text-sm text-red-800" role="alert">{{ $error }}</div>
    @endif

    <x-admin.quran.table :empty="$rows === []" :empty-text="__('quran.manage.catalogue_up_to_date')">
        <x-slot:head>
            <th>QF #</th>
            <th>{{ __('quran.manage.name') }}</th>
            <th>{{ __('quran.manage.author') }}</th>
            <th>{{ __('quran.manage.language') }}</th>
            <th class="text-end">{{ __('quran.manage.actions') }}</th>
        </x-slot:head>
        @foreach ($rows as $row)
            <tr>
                <td>{{ $row['qf_id'] }}</td>
                <td class="font-semibold">{{ $row['name'] }}</td>
                <td>{{ $row['author_name'] ?? '—' }}</td>
                <td>{{ $row['language'] ?? '—' }}</td>
                <td class="text-end">
                    <form method="POST" action="{{ route('admin.quran.resources.import-from-qf', $kind->routeSegment()) }}">
                        @csrf
                        <input type="hidden" name="qf_id" value="{{ $row['qf_id'] }}">
                        <x-admin.quran.button variant="secondary" icon="download">{{ __('quran.manage.import') }}</x-admin.quran.button>
                    </form>
                </td>
            </tr>
        @endforeach
    </x-admin.quran.table>
@endsection
