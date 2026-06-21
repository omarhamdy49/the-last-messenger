<?php echo '<?xml version="1.0" encoding="UTF-8"?>'."\n"; ?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:xhtml="http://www.w3.org/1999/xhtml">
@foreach ($entries as $entry)
    <url>
        <loc>{{ $entry['loc'] }}</loc>
@if (! empty($entry['lastmod']))
        <lastmod>{{ $entry['lastmod'] }}</lastmod>
@endif
@foreach ($entry['alternates'] as $hl => $hlUrl)
        <xhtml:link rel="alternate" hreflang="{{ $hl }}" href="{{ $hlUrl }}"/>
@endforeach
    </url>
@endforeach
</urlset>
