@php
    $key = $key ?? '';
    $networks = \App\Models\SiteSetting::SOCIAL_NETWORKS;
    $path = $path ?? ($networks[$key]['path'] ?? '');
    $label = $label ?? ($networks[$key]['label'] ?? 'Social');
@endphp
<svg class="gw-social-svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false">
    <path d="{{ $path }}"/>
</svg>
