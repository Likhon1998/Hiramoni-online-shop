@php
    $navBrands = ($trustedBrands ?? null);
    if (! $navBrands || (method_exists($navBrands, 'isEmpty') && $navBrands->isEmpty())) {
        $navBrands = collect($brands ?? [])->map(function ($brand) {
            return (object) [
                'name' => $brand->name,
                'slug' => \Illuminate\Support\Str::slug($brand->name),
                'logo_url' => $brand->logo_url ?? null,
            ];
        });
    }
    $allBrandsUrl = $allUrl ?? route('website.brands');
@endphp
<a href="{{ $allBrandsUrl }}" class="gaget-nav-dropdown-item gaget-nav-dropdown-item--all">
    <span>All brands</span>
    <span class="gaget-nav-dropdown-cta">View all @include('website.partials.nav-dropdown-arrow', ['class' => ''])</span>
</a>
@if($navBrands->isNotEmpty())
    <div class="gaget-nav-brands-grid">
        @foreach($navBrands as $brand)
            <a href="{{ route('website.brand', $brand->slug) }}"
               class="gaget-nav-brand-card"
               style="--i: {{ min($loop->index + 1, 14) }}"
               title="{{ $brand->name }}">
                <span class="gaget-nav-brand-card-logo">
                    @if(!empty($brand->logo_url))
                        <img src="{{ $brand->logo_url }}" alt="{{ $brand->name }}" class="gaget-nav-brand-logo" loading="lazy" decoding="async">
                    @else
                        <span class="gaget-nav-brand-card-fallback">{{ mb_strtoupper(mb_substr($brand->name, 0, 1)) }}</span>
                    @endif
                </span>
                <span class="gaget-nav-brand-card-name">{{ $brand->name }}</span>
            </a>
        @endforeach
    </div>
@else
    <span class="gaget-nav-dropdown-empty">No brands yet</span>
@endif
