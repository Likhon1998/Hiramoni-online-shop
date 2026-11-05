{{-- Product grid + pagination for shop (also returned by AJAX) --}}
<div class="gs-results-meta" data-shop-count hidden>
    @php
        $from = $products->firstItem() ?? 0;
        $to = $products->lastItem() ?? 0;
        $total = $products->total();
    @endphp
    {{ __('website.shop.showing_range', ['from' => $from, 'to' => $to, 'total' => number_format($total)]) }}
</div>

<div class="gs-grid" data-shop-grid :class="view === 'list' && 'gs-grid--list'">
    @forelse($products as $product)
        @include('website.partials.product-card', ['product' => $product, 'listMode' => true])
    @empty
        <div class="gs-empty">
            <p>{{ __('website.common.no_products') }}</p>
            <a href="{{ route('website.shop') }}" data-shop-clear data-no-loader>{{ __('website.common.clear_filters') }}</a>
        </div>
    @endforelse
</div>

<div class="gs-pagination" data-shop-pagination @if(! $products->hasPages()) hidden @endif>
    @if($products->hasPages())
        {{ $products->onEachSide(2)->links('website.partials.shop-pagination') }}
    @endif
</div>
