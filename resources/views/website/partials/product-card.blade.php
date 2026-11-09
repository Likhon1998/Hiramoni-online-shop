@php
    $ws = app(\App\Services\WebsiteService::class);
    $discount = $product->discountPercent();
    $currentPrice = $product->currentPrice();
    $compareAt = $product->compareAtPrice();
    $img = $ws->productImageUrl($product);
    $displayName = $product->storefrontDisplayName();
    $catLabel = $product->category?->name ?? $product->brand_name ?? '';
    $isNew = $product->showsAsNew();
    $shadeDots = [];
    if ($product->variant_group) {
        $shadeDots = \App\Models\Product::query()
            ->where('shop_id', $product->shop_id)
            ->where('variant_group', $product->variant_group)
            ->where(fn ($q) => $q->where('is_published', true)->orWhereNull('is_published'))
            ->whereNotNull('color_hex')
            ->orderBy('id')
            ->limit(6)
            ->pluck('color_hex')
            ->filter()
            ->values()
            ->all();
    } elseif (filled($product->color_hex)) {
        $shadeDots = [$product->color_hex];
    }
    $cartItem = [
        'id' => $product->id,
        'name' => $displayName,
        'price' => $currentPrice,
        'image' => $img,
        'stock' => max(0, (int) $product->availableStock()),
    ];
    $listItem = [
        'id' => $product->id,
        'name' => $displayName,
        'price' => $currentPrice,
        'image' => $img,
        'url' => route('website.product', $product),
        'category' => $catLabel,
        'rating' => (float) ($product->rating ?? 0),
        'stock' => max(0, (int) $product->availableStock()),
    ];
@endphp
<article class="gaget-product-card gs-card">
    @if($discount > 0)
        <span class="gaget-discount-badge">-{{ $discount }}%</span>
    @elseif($isNew)
        <span class="gs-badge-new">{{ __('website.common.new') }}</span>
    @elseif($product->is_best_seller)
        <span class="gs-badge-new gs-badge-best">{{ __('website.common.best_seller') }}</span>
    @endif

    <a href="{{ route('website.product', $product) }}" class="gaget-product-img">
        <img src="{{ $img }}" alt="{{ $displayName }}" class="bb-fill" loading="lazy">
    </a>

    <div class="gaget-product-body">
        <p class="gaget-product-cat">{{ $catLabel }}</p>
        <a href="{{ route('website.product', $product) }}" class="gaget-product-name">{{ $displayName }}</a>

        @if(count($shadeDots) > 0)
            <div class="gs-shade-dots" aria-hidden="true">
                @foreach($shadeDots as $hex)
                    <span class="gs-shade-dot" style="background: {{ $hex }}"></span>
                @endforeach
            </div>
        @endif

        @if(($product->review_count ?? 0) > 0)
        <div class="gaget-stars">
            @for($i = 1; $i <= 5; $i++)
                <span class="gaget-star {{ $i > round($product->rating ?? 0) ? 'gaget-star-empty' : '' }}">★</span>
            @endfor
            <span class="gaget-review-count">({{ number_format($product->review_count) }})</span>
        </div>
        @endif

        <div class="gs-card-footer">
            <div class="gaget-price-row">
                <span class="gaget-price-current">{{ $ws->formatPrice($currentPrice, $settings) }}</span>
                @if($compareAt)
                    <span class="gaget-price-old">{{ $ws->formatPrice($compareAt, $settings) }}</span>
                @endif
            </div>

            <div class="gs-card-actions">
                <button type="button"
                        class="gs-icon-btn gs-icon-btn--cart"
                        title="{{ __('website.common.add_to_cart') }}"
                        data-add-to-cart='@json($cartItem)'
                        data-qty="1"
                        data-open-cart="1">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.3 2.3c-.6.6-.2 1.7.7 1.7H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"/></svg>
                </button>
                <button type="button"
                        class="gs-icon-btn"
                        title="{{ __('website.common.wishlist') }}"
                        :class="inWishlist({{ $product->id }}) && 'is-wish'"
                        @click.prevent="toggleWishlist(@js($listItem))">
                    <svg :fill="inWishlist({{ $product->id }}) ? 'currentColor' : 'none'" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                </button>
            </div>
        </div>
    </div>
</article>
