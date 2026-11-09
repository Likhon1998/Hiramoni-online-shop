@php
    $ws = app(\App\Services\WebsiteService::class);
    $images = $ws->productImageUrls($product);
    $comboPreviews = $product->is_combo ? $ws->comboItemPreviews($product) : [];
    $comboGallery = collect($comboPreviews)->pluck('image')->filter()->unique()->values()->all();
    if ($comboGallery !== []) {
        $images = $comboGallery;
    }
    $img = $images[0] ?? $ws->placeholderImageUrl();
    $reviews = $reviews ?? collect();
    $variantOptions = $variantOptions ?? ['groups' => [], 'specs' => []];
    $variantGroups = $variantOptions['groups'] ?? [];
    $variantSpecs = $variantOptions['specs'] ?? [];
    $discountPct = $product->discountPercent();
    $currentPrice = $product->currentPrice();
    $compareAt = $product->compareAtPrice();
    $displayName = $product->storefrontDisplayName();
    $longDescription = $product->description ?: $product->short_description;
@endphp

{{-- Breadcrumbs --}}
<nav class="pd-crumbs text-xs text-slate-400 mb-4 flex flex-wrap items-center gap-1.5">
    <a href="{{ route('home') }}">{{ __('website.common.home') }}</a>
    <span class="pd-crumb-sep" aria-hidden="true">/</span>
    @if($product->category)
        <a href="{{ route('website.shop', ['category' => $product->category_id]) }}">{{ $product->category->name }}</a>
        <span class="pd-crumb-sep" aria-hidden="true">/</span>
    @endif
    @if($product->brand_name || $product->brand)
        <span>{{ $product->brand_name ?? $product->brand?->name }}</span>
        <span class="pd-crumb-sep" aria-hidden="true">/</span>
    @endif
    <span class="text-slate-600 truncate max-w-[16rem]">{{ $displayName }}</span>
</nav>

<div class="pd-layout"
     x-data="{
        active: 0,
        images: @js($images),
        tab: 'description',
        qty: 1,
        zooming: false,
        zoomX: 50,
        zoomY: 50,
        onZoomEnter() {
            if (window.matchMedia('(hover: hover) and (pointer: fine)').matches) {
                this.zooming = true;
            }
        },
        onZoomLeave() {
            this.zooming = false;
            this.zoomX = 50;
            this.zoomY = 50;
        },
        onZoomMove(e) {
            if (!this.zooming) return;
            const rect = e.currentTarget.getBoundingClientRect();
            const x = ((e.clientX - rect.left) / Math.max(rect.width, 1)) * 100;
            const y = ((e.clientY - rect.top) / Math.max(rect.height, 1)) * 100;
            this.zoomX = Math.min(100, Math.max(0, x));
            this.zoomY = Math.min(100, Math.max(0, y));
        },
     }">

    {{-- Gallery --}}
    <div class="pd-gallery {{ $product->is_combo ? 'pd-gallery--combo' : '' }}">
        <div class="pd-zoom-stage"
             @mouseenter="onZoomEnter()"
             @mouseleave="onZoomLeave()"
             @mousemove="onZoomMove($event)"
             :class="zooming && 'pd-zoom-stage--active'">
            @if($discountPct > 0)
                <span class="pd-sale-badge">-{{ $discountPct }}%</span>
            @endif
            <img :src="images[active]"
                 alt="{{ $product->name }}"
                 class="pd-zoom-img"
                 :style="zooming
                    ? `transform: scale(2.35); transform-origin: ${zoomX}% ${zoomY}%;`
                    : 'transform: scale(1); transform-origin: 50% 50%;'"
                 draggable="false">
            <span class="pd-zoom-hint" x-show="!zooming" x-cloak>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v6m3-3H7"/></svg>
                {{ __('website.product.hover_to_zoom') }}
            </span>
        </div>

        @if(count($images) > 1)
            <div class="pd-thumbs">
                @foreach($images as $i => $url)
                    <button type="button"
                            @click="active = {{ $i }}; onZoomLeave()"
                            :class="active === {{ $i }} ? 'is-active' : ''"
                            class="pd-thumb"
                            aria-label="{{ __('website.product.view_image', ['n' => $i + 1]) }}">
                        <img src="{{ $url }}" alt="">
                    </button>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Buy box --}}
    <div class="pd-buy">
        <div class="pd-brand-row">
            @if($product->showsAsNew())
                <span class="pd-pill pd-pill--new">{{ __('website.common.new') }}</span>
            @endif
            @if($product->is_combo)
                <span class="pd-pill pd-pill--combo">{{ __('website.product.combo_set') }}</span>
            @endif
            @if($product->brand?->logo_path)
                <img src="{{ public_storage_url($product->brand->logo_path) }}"
                     alt="{{ $product->brand->name }}"
                     class="pd-brand-logo">
            @endif
            @if($product->brand_name || $product->brand)
                <p class="pd-brand-kicker">{{ $product->brand_name ?? $product->brand?->name }}</p>
            @endif
        </div>

        <h1 class="pd-title">{{ $displayName }}</h1>

        @if($product->review_count > 0)
            <div class="pd-rating">
                @php $stars = round((float) $product->rating); @endphp
                <span class="pd-stars" aria-hidden="true">
                    @for($i=1;$i<=5;$i++)<span class="{{ $i<=$stars?'is-on':'' }}">★</span>@endfor
                </span>
                <span>({{ trans_choice('website.product.reviews_count', $product->review_count, ['count' => $product->review_count]) }})</span>
            </div>
        @endif

        <div class="pd-price-block mb-4 pb-4 border-b border-rose-100/80 space-y-1.5">
            <div class="flex flex-wrap items-baseline gap-2">
                <span class="pd-price">{{ $ws->formatPrice($currentPrice, $settings) }}</span>
                @if($compareAt)
                    <span class="pd-price-was">{{ $ws->formatPrice($compareAt, $settings) }}</span>
                    @if($discountPct > 0)
                        <span class="pd-pill pd-pill--sale">{{ $discountPct }}% OFF</span>
                    @endif
                @endif
            </div>
            <div class="pd-stock {{ $product->availableStock() > 0 ? 'is-in' : 'is-out' }}">
                @if($product->availableStock() > 0)
                    <span class="pd-stock-label">{{ __('website.product.in_stock') }}</span>
                @else
                    <span class="pd-stock-label">{{ __('website.product.out_of_stock') }}</span>
                @endif
            </div>
        </div>

        @if(count($variantGroups) > 0)
            <div class="pd-variants pd-variants--beauty">
                @foreach($variantGroups as $group)
                    @php
                        $isColorGroup = $group['type'] === 'color';
                        $activeOpt = collect($group['options'])->firstWhere('active');
                    @endphp
                    <div class="pd-variant-block pd-variant-block--full">
                        <p class="pd-variant-label">
                            {{ $group['attribute'] }}
                            @if($activeOpt)
                                <span class="pd-variant-selected">{{ $activeOpt['label'] }}</span>
                            @endif
                        </p>
                        <div class="pd-option-row {{ $isColorGroup ? 'pd-option-row--swatches' : '' }}">
                            @foreach($group['options'] as $opt)
                                <a href="{{ $opt['url'] }}"
                                   data-product-variant
                                   data-no-loader
                                   title="{{ $opt['label'] }}{{ empty($opt['available']) ? ' (out of stock)' : '' }}"
                                   aria-label="{{ $group['attribute'] }}: {{ $opt['label'] }}"
                                   class="pd-option {{ $isColorGroup ? 'pd-option--shade' : 'pd-option--chip' }} {{ !empty($opt['active']) ? 'is-active' : '' }} {{ empty($opt['available']) ? 'is-disabled' : '' }}">
                                    @if($isColorGroup)
                                        <span class="pd-swatch pd-swatch--lg" style="background-color: {{ $opt['hex'] ?? '#E91E63' }}"></span>
                                        <span class="pd-shade-name">{{ $opt['label'] }}</span>
                                    @else
                                        <span class="pd-option-main">{{ $opt['label'] }}</span>
                                    @endif
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        @if(count($variantSpecs) > 0)
            <div class="pd-meta-lines">
                @foreach($variantSpecs as $spec)
                    <p class="pd-meta-line">{{ $spec['label'] }}: <strong>{{ $spec['value'] }}</strong></p>
                @endforeach
            </div>
        @endif

        @if($product->is_combo)
            @php
                $comboItems = $product->comboItemList();
                $comboCount = count($comboItems);
                $comboBlurb = trim((string) ($product->short_description ?? ''));
            @endphp
            <div class="pd-combo">
                <div class="pd-combo-head">
                    <p class="pd-combo-title">{{ __('website.product.combo_set') }}</p>
                    <span class="pd-combo-badge">
                        {{ $comboCount > 0 ? $comboCount.' '.\Illuminate\Support\Str::plural('product', $comboCount) : 'Multi-item pack' }}
                    </span>
                </div>
                @if($comboBlurb !== '')
                    <p class="pd-combo-blurb">{{ $comboBlurb }}</p>
                @endif
                @if($comboCount > 0)
                    <p class="pd-combo-subtitle">{{ __('website.product.whats_included') }}</p>
                    <ol class="pd-combo-list">
                        @foreach($comboItems as $item)
                            @php
                                $qty = 1;
                                $text = $item;
                                if (preg_match('/^\s*(\d+)\s*[x×]\s*(.+)$/u', $item, $m)) {
                                    $qty = (int) $m[1];
                                    $text = trim($m[2]);
                                }
                            @endphp
                            <li>
                                <span class="pd-combo-qty">{{ $qty }}x</span>
                                <span class="pd-combo-text">{{ $text }}</span>
                            </li>
                        @endforeach
                    </ol>
                @endif
            </div>
        @endif

        @php
            $availableQty = max(0, (int) $product->availableStock());
            $cartItem = [
                'id' => $product->id,
                'name' => $displayName,
                'price' => $currentPrice,
                'image' => $img,
                'stock' => $availableQty,
            ];
            $listItem = [
                'id' => $product->id,
                'name' => $displayName,
                'price' => $currentPrice,
                'image' => $img,
                'url' => route('website.product', $product),
                'category' => $product->category?->name ?? $product->brand_name ?? 'Products',
                'rating' => (float) ($product->rating ?? 0),
                'stock' => $availableQty,
            ];
        @endphp

        <div class="pd-cta">
            @if($availableQty > 0)
                <div class="pd-cta-top">
                    <div class="pd-qty">
                        <button type="button" @click="qty = Math.max(1, qty - 1)" aria-label="{{ __('website.common.decrease_qty') }}">−</button>
                        <span x-text="qty"></span>
                        <button type="button"
                                @click="qty = Math.min({{ max(1, $availableQty) }}, qty + 1)"
                                :disabled="qty >= {{ max(1, $availableQty) }}"
                                :class="qty >= {{ max(1, $availableQty) }} && 'opacity-40 cursor-not-allowed'"
                                aria-label="{{ __('website.common.increase_qty') }}">+</button>
                    </div>
                    <button type="button"
                            data-add-to-cart='@json($cartItem)'
                            :data-qty="qty"
                            data-open-cart="0"
                            data-checkout="1"
                            class="pd-btn-primary">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        {{ __('website.product.buy_now') }}
                    </button>
                </div>
                <div class="pd-cta-bottom">
                    <button type="button"
                            data-add-to-cart='@json($cartItem)'
                            :data-qty="qty"
                            data-open-cart="1"
                            class="pd-btn-secondary">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        {{ __('website.product.add_to_cart') }}
                    </button>
                    <button type="button"
                            class="pd-btn-wish"
                            :class="inWishlist({{ $product->id }}) && 'is-active'"
                            @click="toggleWishlist(@js($listItem))"
                            :aria-label="inWishlist({{ $product->id }}) ? @js(__('website.common.remove_wishlist')) : @js(__('website.common.add_wishlist'))">
                        <svg viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
                             :fill="inWishlist({{ $product->id }}) ? 'currentColor' : 'none'">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                        </svg>
                    </button>
                </div>
            @else
                <p class="pd-oos">{{ __('website.product.oos_hint') }}</p>
                <button type="button"
                        class="pd-btn-wish pd-btn-wish--wide"
                        :class="inWishlist({{ $product->id }}) && 'is-active'"
                        @click="toggleWishlist(@js($listItem))">
                    <svg viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
                         :fill="inWishlist({{ $product->id }}) ? 'currentColor' : 'none'" class="w-4 h-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                    </svg>
                    <span x-text="inWishlist({{ $product->id }}) ? @js(__('website.product.wishlisted')) : @js(__('website.product.save_later'))"></span>
                </button>
            @endif
        </div>

        @php
            $trustItems = collect($ws->productTrustItems($features ?? collect(), $deliveryConfig ?? null, $settings));
        @endphp
        @if($trustItems->isNotEmpty())
        <div class="pd-trust">
            @foreach($trustItems->take(4) as [$title, $sub])
                <div class="pd-trust-item">
                    <svg class="pd-trust-check" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    <div class="min-w-0">
                        <p class="pd-trust-title">{{ $title }}</p>
                        <p class="pd-trust-sub">{{ $sub }}</p>
                    </div>
                </div>
            @endforeach
        </div>
        @endif
    </div>
</div>

{{-- Tabs + related --}}
@if($longDescription || $reviews->isNotEmpty() || $related->count())
<section class="pd-lower mt-8 pt-6 border-t border-rose-100/70">
    @if($longDescription || $reviews->isNotEmpty())
        <div class="pd-tabs" x-data="{ tab: 'description' }">
            <div class="pd-tablist flex gap-1 mb-5">
                @if($longDescription)
                    <button type="button" @click="tab='description'"
                            :class="tab==='description' ? 'is-active' : ''"
                            class="pd-tab">{{ __('website.product.description') }}</button>
                @endif
                @if($reviews->isNotEmpty())
                    <button type="button" @click="tab='reviews'"
                            :class="tab==='reviews' ? 'is-active' : ''"
                            class="pd-tab">{{ __('website.product.reviews') }} ({{ $reviews->count() }})</button>
                @endif
            </div>

            @if($longDescription)
                @php
                    $descLines = preg_split("/\r\n|\n|\r/", trim((string) $longDescription)) ?: [];
                    $descIntro = [];
                    $descBullets = [];
                    foreach ($descLines as $line) {
                        $trimmed = trim($line);
                        if ($trimmed === '') {
                            continue;
                        }
                        if (preg_match('/^(?:[•\-–*]|\d+[.)])\s*(.+)$/u', $trimmed, $m)) {
                            $descBullets[] = $m[1];
                        } else {
                            $descIntro[] = $trimmed;
                        }
                    }
                @endphp
                <div class="pd-about" x-show="tab==='description'">
                    <h3 class="pd-about-title">{{ __('website.product.about') }}</h3>
                    @foreach($descIntro as $para)
                        <p class="pd-about-copy">{{ $para }}</p>
                    @endforeach
                    @if($descBullets)
                        <ul class="pd-about-list">
                            @foreach($descBullets as $bullet)
                                <li>{{ $bullet }}</li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            @endif

            @if($reviews->isNotEmpty())
                <div x-show="tab==='reviews'" x-cloak class="pd-reviews">
                    @foreach($reviews as $review)
                        <article class="pd-review">
                            <div class="pd-stars" aria-hidden="true">{{ str_repeat('★', (int) $review->rating) }}{{ str_repeat('☆', max(0, 5 - (int) $review->rating)) }}</div>
                            <p class="pd-review-body">“{{ $review->body }}”</p>
                            <p class="pd-review-name">{{ $review->customer_name }}</p>
                        </article>
                    @endforeach
                </div>
            @endif
        </div>
    @endif

    @if($related->count())
        <div class="pd-related {{ ($longDescription || $reviews->isNotEmpty()) ? 'pd-related--spaced' : '' }}">
            <h2 class="pd-related-title">{{ __('website.product.related') }}</h2>
            <div class="pd-related-grid">
                @foreach($related->take(4) as $rel)
                    @php $relImg = $ws->productImageUrl($rel); @endphp
                    <a href="{{ route('website.product', $rel) }}" class="pd-related-card">
                        <img src="{{ $relImg }}" alt="" class="pd-related-img">
                        <div class="min-w-0">
                            <p class="pd-related-name">{{ $rel->storefrontDisplayName() }}</p>
                            <p class="pd-related-price">{{ $ws->formatPrice($rel->currentPrice(), $settings) }}</p>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    @endif
</section>
@endif
