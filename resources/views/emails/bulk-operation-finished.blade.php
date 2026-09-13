<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $locale === 'ar' ? 'rtl' : 'ltr' }}">
<head><meta charset="UTF-8"><title>{{ __('bulk_operations.subject', ['id' => $summary['id']], $locale) }}</title></head>
<body style="margin:0;background:#f5f7fa;color:#333;font-family:Arial,sans-serif">
    <div style="max-width:600px;margin:24px auto;padding:24px;background:#fff;border-top:4px solid #0056b3">
        <h1 style="font-size:22px;color:#0056b3">{{ __('bulk_operations.subject', ['id' => $summary['id']], $locale) }}</h1>
        <p>{{ __('bulk_operations.statuses.'.$summary['status'], [], $locale) }}</p>
        <p>{{ __('bulk_operations.total', [], $locale) }}: {{ $summary['total'] }}</p>
        <p>{{ __('bulk_operations.successful', [], $locale) }}: {{ $summary['successful'] }}</p>
        <p>{{ __('bulk_operations.failed', [], $locale) }}: {{ $summary['failed'] }}</p>
        @if ($summary['status'] !== 'completed')
            <p>{{ __('bulk_operations.failure_details', [], $locale) }}</p>
        @endif
        <a href="{{ $statusUrl }}" style="display:inline-block;padding:12px 20px;background:#218838;color:#fff;text-decoration:none">{{ __('bulk_operations.details', [], $locale) }}</a>
    </div>
</body>
</html>
