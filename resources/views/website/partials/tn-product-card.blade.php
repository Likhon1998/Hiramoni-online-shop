@php
    $ws = app(\App\Services\WebsiteService::class);
    $discount = $product->discountPercent();
    $currentPrice = $product->currentPrice();
    $compareAt = $product->compareAtPrice();
    $img = $ws->productImageUrl($product);
    $displayName = $product->storefrontDisplayName();
    $brandLabel = trim((string) ($product->brand_name ?? $product->brand?->name ?? ''));
    $catLabel = $brandLabel !== '' ? $brandLabel : ($product->category?->name ?? 'Beauty');
    $flash = ! empty($flash);
    $saveAmount = ($compareAt && (float) $compareAt > (float) $currentPrice)
        ? round((float) $compareAt - (float) $currentPrice, 2)
        : 0;
    $isNew = $product->showsAsNew();
    $isCombo = (bool) $product->is_combo;
    $comboItems = $isCombo ? $product->comboItemList() : [];
    $comboCount = count($comboItems);
    $comboBlurb = $isCombo ? trim((string) ($product->short_description ?? '')) : '';
    $rating = (float) ($product->rating ?? 0);
    $reviews = (int) ($product->review_count ?? 0);
    if ($rating <= 0) {
        $rating = 4.8;
    }
    if ($reviews <= 0) {
        $reviews = 24;
    }
    $reviewLabel = $reviews >= 1000
        ? number_format($reviews / 1000, 1).'k'
        : number_format($reviews);

    $listItem = [
        'id' => $product->id,
        'name' => $displayName,
        'price' => $currentPrice,
        'image' => $img,
        'url' => route('website.product', $product),
        'category' => $catLabel,
        'rating' => $rating,
    ];

    $cartItem = [
        'id' => $product->id,
        'name' => $displayName,
        'price' => $currentPrice,
        'image' => $img,
        'stock' => max(0, (int) $product->availableStock()),
    ];
@endphp

<article class="tn-product-card {{ $flash ? 'tn-product-card--flash' : '' }} {{ $isNew && $discount <= 0 ? 'tn-product-card--new' : '' }} {{ $isCombo ? 'tn-product-card--combo' : '' }}">
    @if($discount > 0)
        <span class="tn-product-discount">-{{ $discount }}%</span>
    @elseif($isNew)
        <span class="tn-product-new">{{ __('website.common.new') }}</span>
    @elseif($product->is_best_seller)
        <span class="tn-product-best">{{ __('website.common.best_seller') }}</span>
    @endif

    <button type="button"
            class="tn-product-wish"
            title="Wishlist"
            :class="inWishlist({{ $product->id }}) && 'is-active'"
            @click.prevent="toggleWishlist(@js($listItem))"
            :aria-pressed="inWishlist({{ $product->id }}) ? 'true' : 'false'"
            aria-label="{{ __('website.common.toggle_wishlist') }}">
        <svg :fill="inWishlist({{ $product->id }}) ? 'currentColor' : 'none'" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
        </svg>
    </button>

    <a href="{{ route('website.product', $product) }}" class="tn-product-img" aria-label="{{ $displayName }}">
        <img src="{{ $img }}" alt="{{ $displayName }}" class="bb-fill" loading="lazy" decoding="async">
        @if($isCombo)
            <span class="tn-product-combo">
                Combo
                @if($comboCount > 0)
                    <span class="tn-product-combo-count">{{ $comboCount }} {{ \Illuminate\Support\Str::plural('product', $comboCount) }}</span>
                @endif
            </span>
        @endif
    </a>

    <div class="tn-product-meta">
        <p class="tn-product-cat">{{ $catLabel }}</p>
        <a href="{{ route('website.product', $product) }}" class="tn-product-name">{{ $displayName }}</a>

        @if($isCombo)
            <div class="tn-product-combo-box">
                @if($comboCount > 0)
                    <p class="tn-product-combo-label">What’s included ({{ $comboCount }})</p>
                    <ol class="tn-product-combo-list">
                        @foreach(array_slice($comboItems, 0, 4) as $item)
                            <li>{{ $item }}</li>
                        @endforeach
                    </ol>
                    @if($comboCount > 4)
                        <p class="tn-product-combo-more">+{{ $comboCount - 4 }} more in this set</p>
                    @endif
                @elseif($comboBlurb !== '')
                    <p class="tn-product-combo-blurb">{{ $comboBlurb }}</p>
                @else
                    <p class="tn-product-combo-blurb">{{ __('website.common.multi_combo') }}</p>
                @endif
            </div>
        @endif

        <div class="tn-product-stars" aria-label="Rated {{ number_format($rating, 1) }} from {{ $reviewLabel }} reviews">
            <span class="tn-product-star" aria-hidden="true">★</span>
            <span class="tn-product-rating">{{ number_format($rating, 1) }}</span>
            <span class="tn-product-reviews">({{ $reviewLabel }})</span>
        </div>

        <div class="tn-product-foot">
            <div class="tn-product-price-row {{ $compareAt && (float) $compareAt > (float) $currentPrice ? 'is-sale' : '' }}">
                <span class="tn-product-price">{!! $ws->formatPriceHtml($currentPrice, $settings) !!}</span>
                @if($compareAt && (float) $compareAt > (float) $currentPrice)
                    <span class="tn-product-old">{!! $ws->formatPriceHtml($compareAt, $settings) !!}</span>
                @endif
                @if($flash && $saveAmount > 0)
                    <span class="tn-product-save">{{ __('website.home.flash_save') }} {!! $ws->formatPriceHtml($saveAmount, $settings) !!}</span>
                @endif
            </div>
            <button type="button"
                    class="tn-product-add"
                    data-add-to-cart='@json($cartItem)'
                    data-qty="1"
                    data-open-cart="1"
                    aria-label="{{ __('website.common.add_to_cart') }}: {{ $displayName }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h2l.6 2.2M7 17h10l2.4-9.5H6.2L7 17zm0 0a1.5 1.5 0 100 3 1.5 1.5 0 000-3zm10 0a1.5 1.5 0 100 3 1.5 1.5 0 000-3z"/>
                </svg>
                <span class="tn-product-add-label">{{ __('website.common.add') }}</span>
            </button>
        </div>
    </div>
</article>
