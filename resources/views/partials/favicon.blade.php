{{-- Dynamic browser tab icon: favicon upload → store logo → circular brand mark --}}
@php
    $faviconUrl = site_favicon_url($settings ?? null);
    $faviconPath = data_get($settings ?? null, 'favicon_path') ?: data_get($settings ?? null, 'logo_path');
    $faviconFile = public_path(ltrim(parse_url($faviconUrl, PHP_URL_PATH) ?? '', '/'));
    $faviconVer = $faviconPath && is_file(public_storage_path($faviconPath))
        ? (@filemtime(public_storage_path($faviconPath)) ?: time())
        : (@filemtime($faviconFile) ?: time());
    if ($faviconUrl && ! str_contains($faviconUrl, '?')) {
        $faviconUrl .= '?v='.$faviconVer;
    }
    $appleUrl = $faviconUrl;
    $pathLower = strtolower(parse_url($faviconUrl, PHP_URL_PATH) ?? '');
    $faviconType = str_ends_with($pathLower, '.svg')
        ? 'image/svg+xml'
        : (str_ends_with($pathLower, '.ico') ? 'image/x-icon' : 'image/png');
@endphp
<link rel="icon" href="{{ $faviconUrl }}" type="{{ $faviconType }}" sizes="32x32">
<link rel="icon" href="{{ $faviconUrl }}" type="{{ $faviconType }}" sizes="48x48">
<link rel="icon" href="{{ $faviconUrl }}" type="{{ $faviconType }}" sizes="192x192">
<link rel="shortcut icon" href="{{ $faviconUrl }}">
<link rel="apple-touch-icon" href="{{ $appleUrl }}" sizes="180x180">
{{-- Root favicon.ico for crawlers / old browsers (copied on deploy) --}}
<link rel="icon" href="{{ asset('favicon.png') }}?v={{ $faviconVer }}" type="image/png" sizes="any">
