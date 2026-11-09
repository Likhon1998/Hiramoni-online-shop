@php
    $shopLinks = [
        [
            'label' => __('website.nav.deals'),
            'hint' => __('website.nav.deals_hint'),
            'filter' => 'deals',
            'tone' => 'offers',
            'bg' => '#FFF1E8',
            'fg' => '#E85D2A',
            'path' => 'M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3zM6 6h.008v.008H6V6z',
        ],
        [
            'label' => __('website.nav.new_arrivals'),
            'hint' => __('website.nav.new_arrivals_hint'),
            'filter' => 'new',
            'tone' => 'new',
            'bg' => '#F3EEFF',
            'fg' => '#7C3AED',
            'path' => 'M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 00-2.456 2.456z',
        ],
        [
            'label' => __('website.nav.best_sellers'),
            'hint' => __('website.nav.best_sellers_hint'),
            'filter' => 'bestsellers',
            'tone' => 'best',
            'bg' => '#FFF8E1',
            'fg' => '#C9A227',
            'path' => 'M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z',
        ],
        [
            'label' => __('website.nav.combo_deals'),
            'hint' => __('website.nav.combo_deals_hint'),
            'filter' => 'combo',
            'tone' => 'combo',
            'bg' => '#FFE4F1',
            'fg' => '#E91E63',
            'path' => 'M21 11.25v8.25a1.5 1.5 0 01-1.5 1.5H5.25a1.5 1.5 0 01-1.5-1.5v-8.25M12 4.875A2.625 2.625 0 109.375 7.5H12m0-2.625V7.5m0-2.625A2.625 2.625 0 1114.625 7.5H12m0 0V21m-8.625-9.75h18c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125h-18c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z',
        ],
    ];
@endphp
<a href="{{ route('website.shop') }}" class="gaget-nav-dropdown-item gaget-nav-dropdown-item--all">
    <span>{{ __('website.nav.all_products') }}</span>
    <span class="gaget-nav-dropdown-cta">{{ __('website.nav.shop_all') }} @include('website.partials.nav-dropdown-arrow', ['class' => ''])</span>
</a>
<div class="gaget-nav-shop-grid">
    @foreach($shopLinks as $shopLink)
        <a href="{{ route('website.shop', ['filter' => $shopLink['filter']]) }}"
           class="gaget-nav-shop-card gaget-nav-shop-card--{{ $shopLink['tone'] }}"
           style="--dd-bg: {{ $shopLink['bg'] }}; --dd-fg: {{ $shopLink['fg'] }};">
            <span class="gaget-nav-shop-visual" aria-hidden="true">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="{{ $shopLink['path'] }}"/>
                </svg>
            </span>
            <span class="gaget-nav-shop-copy">
                <span class="gaget-nav-shop-label">{{ $shopLink['label'] }}</span>
                <span class="gaget-nav-shop-hint">{{ $shopLink['hint'] }}</span>
            </span>
            <span class="gaget-nav-shop-go" aria-hidden="true">
                @include('website.partials.nav-dropdown-arrow', ['class' => ''])
            </span>
        </a>
    @endforeach
</div>
