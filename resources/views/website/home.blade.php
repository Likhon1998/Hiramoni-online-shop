@extends('website.layout')
@php
    $ws = app(\App\Services\WebsiteService::class);
    $storeName = $settings->store_name ?? config('app.name', 'Hira Moni');
    $homeCopy = collect(data_get($settings, 'home_copy') ?: [])
        ->map(fn ($v) => is_string($v) ? str_replace('{store}', $storeName, $v) : $v)
        ->all();
    $currency = $settings->currency_symbol ?? '৳';

    $heroEyebrowDefault = __('website.home.hero_eyebrow');
    $heroTitleDefault = __('website.home.hero_title');
    $heroSubtitleDefault = __('website.home.hero_subtitle');
    $heroCtaDefault = __('website.home.hero_cta');
    $heroTitleLine1 = __('website.home.hero_title_line1');
    $heroTitleLine2 = __('website.home.hero_title_line2');

    // Known English CMS defaults — localize these when the active locale is not English
    $englishHero = [
        'eyebrow' => ['THE ART OF EVERYDAY GLOW'],
        'title' => ['Beauty, Made Beautifully Simple.', 'Beauty, Made Beautifully Simple'],
        'subtitle' => ['Discover skincare and beauty essentials chosen for your everyday ritual.'],
        'cta' => ['Explore the Collection', 'Shop Now', 'Shop the Collection'],
    ];

    $heroEyebrow = trim((string) ($homeCopy['hero_eyebrow'] ?? $homeCopy['hero_badge'] ?? '')) ?: $heroEyebrowDefault;
    $heroTitle = trim((string) ($homeCopy['hero_title'] ?? '')) ?: $heroTitleDefault;
    // Replace legacy CMS headline with the editorial lockup from the brand mockup
    if (preg_match('/glow\s+naturally|live\s+beautifully/ui', $heroTitle)) {
        $heroTitle = $heroTitleDefault;
    }
    $heroSubtitle = trim((string) ($homeCopy['hero_subtitle'] ?? '')) ?: $heroSubtitleDefault;
    if (preg_match('/premium\s+skincare|unique\s+glow/ui', $heroSubtitle)) {
        $heroSubtitle = $heroSubtitleDefault;
    }
    $heroCta = trim((string) ($homeCopy['hero_cta'] ?? '')) ?: $heroCtaDefault;

    if (app()->getLocale() !== 'en') {
        if (in_array($heroEyebrow, $englishHero['eyebrow'], true)) {
            $heroEyebrow = $heroEyebrowDefault;
        }
        if (in_array($heroTitle, $englishHero['title'], true) || preg_match('/^Beauty,\s*Made\s+Beautifully\s+Simple\.?$/ui', $heroTitle)) {
            $heroTitle = $heroTitleDefault;
        }
        if (in_array($heroSubtitle, $englishHero['subtitle'], true)) {
            $heroSubtitle = $heroSubtitleDefault;
        }
        if (in_array($heroCta, $englishHero['cta'], true)) {
            $heroCta = $heroCtaDefault;
        }
    }

    $heroImages = ($heroSlides ?? collect())->filter(fn ($s) => filled($s->image_path))->map(function ($slide) {
        $src = public_storage_url($slide->image_path);
        $full = public_storage_path($slide->image_path);

        return (object) [
            'src' => $src.'?v='.(is_file($full) ? filemtime($full) : time()),
            'title' => $slide->title,
            'url' => $slide->button_url ?: route('website.shop'),
        ];
    })->values();

    // Editorial split: first clause dark, second line italic magenta
    $heroTitleHtml = e($heroTitleLine1).'<br><em class="gw-hero-accent">'.e(rtrim($heroTitleLine2, '.')).'.</em>';
    if (app()->getLocale() === 'en' && $heroTitle !== $heroTitleDefault) {
        $heroTitleHtml = e($heroTitle);
        if (preg_match('/^(Beauty,\s*Made)\s+(Beautifully\s+Simple\.?)$/ui', $heroTitle, $m)) {
            $heroTitleHtml = e($m[1]).'<br><em class="gw-hero-accent">'.e(rtrim($m[2], '.')).'.</em>';
        } elseif (preg_match('/^(.*?Made)\s+(Beautifully.*)$/ui', $heroTitle, $m)) {
            $heroTitleHtml = e(rtrim($m[1])).'<br><em class="gw-hero-accent">'.e($m[2]).'</em>';
        } elseif (str_contains($heroTitle, ',')) {
            [$a, $b] = array_map('trim', explode(',', $heroTitle, 2));
            $heroTitleHtml = e($a).',<br><em class="gw-hero-accent">'.e($b).'</em>';
        }
    } elseif (app()->getLocale() !== 'en' && $heroTitle !== $heroTitleDefault && str_contains($heroTitle, ',')) {
        [$a, $b] = array_map('trim', explode(',', $heroTitle, 2));
        $heroTitleHtml = e($a).',<br><em class="gw-hero-accent">'.e($b).'</em>';
    }

    $bestProducts = collect($featuredProducts ?? [])
        ->sortBy('id')
        ->take(6)
        ->values();
    if ($bestProducts->count() < 6) {
        $bestProducts = $bestProducts
            ->concat(collect($bestSellers ?? []))
            ->concat(collect($flashSaleProducts ?? []))
            ->concat(collect($newArrivals ?? []))
            ->unique('id')
            ->take(6)
            ->values();
    }

    $whyFeatures = ($features ?? collect())->take(4);
    $whyFallback = [
        ['title' => 'Premium Quality', 'subtitle' => 'Only the finest beauty formulas, carefully curated for you.'],
        ['title' => 'Safe & Gentle', 'subtitle' => 'Dermatologist-friendly ingredients for every skin type.'],
        ['title' => 'Fast Delivery', 'subtitle' => 'Quick, careful shipping across Bangladesh.'],
        ['title' => 'Dedicated Support', 'subtitle' => 'Beauty experts ready to help anytime you need.'],
    ];

    $catFallback = $ws->placeholderImageUrl();
    $blogPosts = ($latestBlogs ?? collect())->take(3);
    $reviews = ($featuredReviews ?? collect())->take(3);

    // Dual banner art: cosmetics pair supplied for this section
    $dualBannerArt = [
        [
            'image' => asset('images/dual-makeup.jpg').'?v='.(@filemtime(public_path('images/dual-makeup.jpg')) ?: time()),
            'theme' => 'dark',
            'badge_text' => 'Makeup',
            'title' => 'Colour, Softly Done',
            'subtitle' => 'Palettes, lipsticks & everyday beauty essentials',
            'button_text' => 'Shop Makeup',
            'button_url' => route('website.shop'),
        ],
        [
            'image' => asset('images/dual-skincare.jpg').'?v='.(@filemtime(public_path('images/dual-skincare.jpg')) ?: time()),
            'theme' => 'light',
            'badge_text' => 'Skincare',
            'title' => 'Glow Ritual Essentials',
            'subtitle' => 'Serums, creams & soft-finish skincare',
            'button_text' => 'Shop Skincare',
            'button_url' => route('website.shop'),
        ],
    ];

    $dualBannerScore = static function ($b): int {
        $hay = mb_strtolower(trim(implode(' ', array_filter([
            $b->title ?? null,
            $b->subtitle ?? null,
            $b->badge_text ?? null,
            $b->button_text ?? null,
        ]))));

        return preg_match('/beauty|glow|skin|makeup|cosmetic|lip|care|fragrance|serum|ritual/u', $hay) ? 2 : 0;
    };

    $dualCopyPool = collect($midPromoBanners ?? [])
        ->filter(fn ($b) => filled($b->title ?? null) && $dualBannerScore($b) > 0)
        ->sortByDesc($dualBannerScore)
        ->values();

    $dualBanners = collect($dualBannerArt)->map(function (array $art, int $i) use ($dualCopyPool) {
        $copy = $dualCopyPool->get($i);

        return (object) [
            'image_url' => $art['image'],
            'theme' => $art['theme'],
            'badge_text' => trim((string) (($copy->badge_text ?? null) ?: $art['badge_text'])),
            'title' => trim((string) (($copy->title ?? null) ?: $art['title'])),
            'subtitle' => trim((string) (($copy->subtitle ?? null) ?: $art['subtitle'])),
            'button_text' => trim((string) (($copy->button_text ?? null) ?: $art['button_text'])),
            'button_url' => filled($copy->button_url ?? null) ? $copy->button_url : $art['button_url'],
        ];
    });
@endphp

@section('content')

{{-- 1. HERO — edge-to-edge background photo + live text overlays --}}
<section class="gw-hero" aria-label="{{ $storeName }} home banner">
    <div class="gw-hero-stage">
        <img
            class="gw-hero-bleed"
            src="{{ asset('images/hiramoni-hero-bleed.jpg') }}?v={{ @filemtime(public_path('images/hiramoni-hero-bleed.jpg')) ?: time() }}"
            width="1024"
            height="446"
            alt=""
            decoding="async"
            fetchpriority="high"
            aria-hidden="true"
        >

        <div class="tn-container gw-hero-overlay">
            <div class="gw-hero-copy">
                <p class="gw-hero-eyebrow">
                    <span class="gw-hero-eyebrow-line" aria-hidden="true"></span>
                    <span class="gw-hero-eyebrow-text">{{ $heroEyebrow }}</span>
                    <span class="gw-hero-eyebrow-line" aria-hidden="true"></span>
                </p>
                <h1 class="gw-hero-title">{!! $heroTitleHtml !!}</h1>
                <p class="gw-hero-sub">{{ $heroSubtitle }}</p>

                <div class="gw-hero-actions">
                    <a href="{{ route('website.shop') }}" class="gw-btn-primary">
                        {{ $heroCta }}
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M5 12h14m-6-6 6 6-6 6"/></svg>
                    </a>
                </div>
            </div>
        </div>

        <a href="{{ route('website.shop') }}" class="gw-hero-spot gw-hero-spot--serum" aria-label="{{ __('website.home.shop_serum') }}"></a>
        <a href="{{ route('website.shop') }}" class="gw-hero-spot gw-hero-spot--lipstick" aria-label="{{ __('website.home.shop_lipstick') }}"></a>
        <a href="{{ route('website.shop') }}" class="gw-hero-spot gw-hero-spot--cream" aria-label="{{ __('website.home.shop_cream') }}"></a>
    </div>
</section>

{{-- 3. CATEGORIES --}}
@if(($categories ?? collect())->isNotEmpty())
<section class="gw-section gw-cats" aria-labelledby="gw-cats-heading">
    <div class="tn-container">
        <header class="gw-cats-head">
            <div class="gw-cats-head-copy">
                <p class="gw-cats-eyebrow">
                    <span class="gw-cats-eyebrow-rule" aria-hidden="true"></span>
                    {{ __('website.home.shop_by_category') }}
                </p>
                <h2 id="gw-cats-heading" class="gw-section-title">
                    {{ $homeCopy['categories_title'] ?? __('website.home.explore_our') }}
                    <span>{{ $homeCopy['categories_title_accent'] ?? __('website.home.categories') }}</span>
                </h2>
            </div>
            <a href="{{ route('website.shop') }}" class="gw-cats-view-all">
                {{ __('website.home.view_all_categories') }}
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M5 12h14m-6-6 6 6-6 6"/></svg>
            </a>
        </header>

        @php
            $beautyOrder = ['skincare', 'makeup', 'hair-care', 'body-care', 'nail-care', 'mens-grooming', 'fragrance', 'beauty-tools'];
            $homeCategories = collect($categories ?? [])
                ->sortBy(function ($category) use ($beautyOrder) {
                    $slug = (string) ($category->slug ?? '');
                    $idx = array_search($slug, $beautyOrder, true);

                    return $idx === false ? 100 + (int) ($category->id ?? 0) : $idx;
                })
                ->take(6)
                ->values();
        @endphp

        <div class="gw-cat-edge">
            <div class="gw-cat-viewport" data-cats-viewport>
                <div class="gw-cat-track" data-cats-track>
                    @foreach([1, 2] as $loopCopy)
                        <div class="gw-cat-group" @if($loopCopy === 2) aria-hidden="true" @endif>
                            @foreach($homeCategories as $category)
                                @php
                                    $img = $ws->categoryImageUrl($category) ?: $catFallback;
                                    if (filled($category->image_path ?? null)) {
                                        $full = public_storage_path($category->image_path);
                                        $img .= '?v='.(is_file($full) ? filemtime($full) : time());
                                    }
                                @endphp
                                <a href="{{ route('website.category', $category->slug ?? $category->id) }}" class="gw-cat-card" @if($loopCopy === 2) tabindex="-1" @endif>
                                    <span class="gw-cat-media">
                                        <span class="gw-cat-glow" aria-hidden="true"></span>
                                        <span class="gw-cat-ring">
                                            <img src="{{ $img }}" alt="{{ $category->name }}" loading="lazy" decoding="async">
                                        </span>
                                    </span>
                                    <span class="gw-cat-name">{{ $category->name }}</span>
                                </a>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <script>
        (() => {
            const track = document.querySelector('[data-cats-track]');
            const viewport = document.querySelector('[data-cats-viewport]');
            if (!track || !viewport) return;

            const mq = window.matchMedia('(max-width: 900px)');
            let paused = false;
            let offset = 0;
            let half = 0;
            let raf = 0;
            const speed = 0.42;

            const measure = () => {
                const group = track.querySelector('.gw-cat-group');
                half = group ? group.getBoundingClientRect().width : track.scrollWidth / 2;
            };

            const resetDesktop = () => {
                offset = 0;
                track.style.transform = '';
            };

            const tick = () => {
                if (mq.matches && !paused && half > 0) {
                    offset -= speed;
                    if (Math.abs(offset) >= half) offset = 0;
                    track.style.transform = `translate3d(${offset}px,0,0)`;
                }
                raf = requestAnimationFrame(tick);
            };

            const onChange = () => {
                measure();
                if (!mq.matches) resetDesktop();
            };

            viewport.addEventListener('pointerenter', () => { paused = true; });
            viewport.addEventListener('pointerleave', () => { paused = false; });
            viewport.addEventListener('touchstart', () => { paused = true; }, { passive: true });
            viewport.addEventListener('touchend', () => { setTimeout(() => { paused = false; }, 900); }, { passive: true });
            window.addEventListener('resize', onChange);
            mq.addEventListener?.('change', onChange);
            measure();
            raf = requestAnimationFrame(tick);
        })();
    </script>
</section>
@endif

{{-- 3b. DUAL BANNERS --}}
<section class="gw-section gw-dual-banners" aria-label="{{ __('website.home.featured_promos') }}">
    <div class="gw-dual-grid">
        @foreach($dualBanners->take(2) as $index => $banner)
            @php
                $bannerImg = $banner->image_url ?? $catFallback;
                $bannerUrl = filled($banner->button_url ?? null)
                    ? $banner->button_url
                    : route('website.shop');
                $bannerTone = (($banner->theme ?? null) === 'light') ? 'is-light' : 'is-dark';
                $bannerBadge = trim((string) ($banner->badge_text ?? ''));
                $bannerTitle = trim((string) ($banner->title ?? ''));
                $bannerSub = trim((string) ($banner->subtitle ?? ''));
                $bannerCta = trim((string) ($banner->button_text ?? '')) ?: 'Shop the Edit';
            @endphp
            <a href="{{ $bannerUrl }}" class="gw-dual-card {{ $bannerTone }}">
                <img
                    class="gw-dual-media"
                    src="{{ $bannerImg }}"
                    alt="{{ $bannerTitle }}"
                    loading="lazy"
                    decoding="async"
                >
                <span class="gw-dual-shade" aria-hidden="true"></span>
                <span class="gw-dual-body">
                    @if($bannerBadge !== '')
                        <span class="gw-dual-badge">{{ $bannerBadge }}</span>
                    @endif
                    @if($bannerTitle !== '')
                        <span class="gw-dual-title">{{ $bannerTitle }}</span>
                    @endif
                    @if($bannerSub !== '')
                        <span class="gw-dual-sub">{{ $bannerSub }}</span>
                    @endif
                    <span class="gw-dual-cta">{{ $bannerCta }}</span>
                </span>
            </a>
        @endforeach
    </div>
</section>

{{-- 4. FLASH SALE --}}
@php
    $homeFlash = collect($flashSaleProducts ?? [])->filter(fn ($p) => $p->isOnSale())->values();
    $flashEndsAt = $flashSaleEndsAt ?? $homeFlash->map(fn ($p) => $p->sale_ends_at)->filter()->sort()->first();
    $flashEyebrow = $settings->deals_kicker;
    $flashTitle = $settings->deals_title;
    $flashAccent = $settings->deals_title_accent;
    $flashSub = $settings->deals_subtitle;
@endphp
@if($homeFlash->isNotEmpty())
<section class="gw-section gw-flash" aria-labelledby="gw-flash-heading"
         @if($flashEndsAt)
         x-data="{
            left: 0, d: '00', h: '00', m: '00', s: '00',
            init() {
                const end = Date.parse(@js(optional($flashEndsAt)->toIso8601String()));
                const pad = (n) => String(Math.max(0, n)).padStart(2, '0');
                const tick = () => {
                    this.left = Math.max(0, end - Date.now());
                    this.d = pad(Math.floor(this.left / 864e5));
                    this.h = pad(Math.floor(this.left / 36e5) % 24);
                    this.m = pad(Math.floor(this.left / 6e4) % 60);
                    this.s = pad(Math.floor(this.left / 1e3) % 60);
                };
                tick();
                setInterval(tick, 1000);
            }
         }"
         @endif>
    <div class="tn-container">
        <div class="gw-flash-stage">
            <header class="gw-flash-banner">
                <div class="gw-flash-banner-copy">
                    <p class="gw-flash-kicker">
                        <span class="gw-flash-pulse" aria-hidden="true"></span>
                        {{ __('website.home.live_now') }} · {{ $flashEyebrow }}
                    </p>
                    <h2 id="gw-flash-heading" class="gw-flash-heading">
                        {{ $flashTitle }} <em>{{ $flashAccent }}</em>
                    </h2>
                    <p class="gw-flash-lede">{{ $flashSub }}</p>
                </div>
                <div class="gw-flash-banner-aside">
                    @if($flashEndsAt)
                        <div class="gw-flash-timer" x-show="left > 0" x-cloak>
                            <span class="gw-flash-ends">{{ __('website.home.ends_in') }}</span>
                            <div class="gw-flash-digits">
                                <span class="gw-flash-box"><strong x-text="d"></strong><small>{{ __('website.home.flash_days') }}</small></span>
                                <span class="gw-flash-colon" aria-hidden="true">:</span>
                                <span class="gw-flash-box"><strong x-text="h"></strong><small>{{ __('website.home.flash_hours') }}</small></span>
                                <span class="gw-flash-colon" aria-hidden="true">:</span>
                                <span class="gw-flash-box"><strong x-text="m"></strong><small>{{ __('website.home.flash_mins') }}</small></span>
                                <span class="gw-flash-colon" aria-hidden="true">:</span>
                                <span class="gw-flash-box"><strong x-text="s"></strong><small>{{ __('website.home.flash_secs') }}</small></span>
                            </div>
                        </div>
                    @endif
                    <a href="{{ route('website.shop', ['filter' => 'deals']) }}" class="gw-flash-shop">
                        {{ __('website.home.view_all_deals') }}
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M5 12h14m-6-6 6 6-6 6"/></svg>
                    </a>
                </div>
            </header>

            <div class="gw-flash-grid">
                @foreach($homeFlash->take(6) as $product)
                    @include('website.partials.tn-product-card', ['product' => $product, 'flash' => true])
                @endforeach
            </div>
        </div>
    </div>
</section>
@endif

{{-- 5. BEST SELLERS --}}
@if($bestProducts->isNotEmpty())
<section class="gw-section gw-bestsellers" aria-labelledby="gw-best-heading">
    <div class="tn-container gw-best-inner">
        <header class="gw-best-head">
            <div class="gw-best-head-copy">
                <p class="gw-best-eyebrow">{{ __('website.home.our_best_sellers') }}</p>
                <h2 id="gw-best-heading" class="gw-best-title">
                    {{ __('website.home.loved_by') }} <em>{{ __('website.home.thousands') }}</em>
                </h2>
                <p class="gw-best-sub">{{ __('website.home.best_sub') }}</p>
            </div>
            <a href="{{ route('website.shop') }}" class="gw-best-view-all">
                {{ __('website.home.view_all_products') }}
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M5 12h14m-6-6 6 6-6 6"/></svg>
            </a>
        </header>

        <div class="gw-best-stage">
            <div class="gw-best-rail" data-best-rail>
                @foreach($bestProducts->take(6) as $product)
                    @include('website.partials.tn-product-card', ['product' => $product])
                @endforeach
            </div>
        </div>
    </div>

</section>
@endif

{{-- 6. COMBO PACKS --}}
@php $homeCombos = collect($comboProducts ?? [])->take(6); @endphp
@if($homeCombos->isNotEmpty())
<section class="gw-section gw-combos" aria-labelledby="gw-combo-heading">
    <div class="tn-container">
        <header class="gw-best-head gw-combo-head">
            <div class="gw-best-head-copy">
                <p class="gw-best-eyebrow">{{ __('website.home.combo_eyebrow') }}</p>
                <h2 id="gw-combo-heading" class="gw-best-title">
                    {{ __('website.home.combo_title') }} <em>{{ __('website.home.combo_title_accent') }}</em>
                </h2>
                <p class="gw-best-sub">{{ $homeCopy['combo_subtitle'] ?? __('website.home.combo_sub') }}</p>
            </div>
            <a href="{{ route('website.shop', ['filter' => 'combo']) }}" class="gw-best-view-all">
                {{ __('website.home.view_all_combos') }}
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M5 12h14m-6-6 6 6-6 6"/></svg>
            </a>
        </header>

        <div class="gw-combo-grid" style="--bb-combo-count: {{ min(6, max(1, $homeCombos->count())) }}">
            @foreach($homeCombos as $product)
                @include('website.partials.tn-combo-card', ['product' => $product])
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- 6. BRANDS --}}
@php
    $brandMarquee = collect($trustedBrands ?? []);
@endphp
@if($brandMarquee->isNotEmpty())
<section class="gw-section gw-brands" id="brands" aria-label="{{ __('website.home.trusted_brands') }}">
    <div class="tn-container">
        <header class="gw-brands-head">
            <h2 class="gw-brands-title">
                <span class="gw-brands-rule" aria-hidden="true"></span>
                {{ __('website.home.trusted_brands') }}
            </h2>
            <p class="gw-brands-sub">{{ $homeCopy['brands_subtitle'] ?? __('website.home.brands_subtitle') }}</p>
        </header>
    </div>

    <div class="gw-brands-edge">
        <button type="button" class="gw-brands-nav gw-brands-nav--prev" data-brands-prev aria-label="{{ __('website.home.previous_brands') }}">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M15 19l-7-7 7-7"/></svg>
        </button>

        <div class="gw-brands-viewport" data-brands-viewport>
            <div class="gw-brands-track" data-brands-track>
                @foreach([1, 2] as $loopCopy)
                    <div class="gw-brands-group" @if($loopCopy === 2) aria-hidden="true" @endif>
                        @foreach($brandMarquee as $brand)
                            <a href="{{ route('website.brand', $brand->slug) }}" class="gw-brand-item" title="{{ $brand->name }}">
                                @if(!empty($brand->logo_url))
                                    <img src="{{ $brand->logo_url }}" alt="{{ $brand->name }}" class="gw-brand-logo" loading="lazy" decoding="async">
                                @else
                                    <span>{{ $brand->name }}</span>
                                @endif
                            </a>
                        @endforeach
                    </div>
                @endforeach
            </div>
        </div>

        <button type="button" class="gw-brands-nav gw-brands-nav--next" data-brands-next aria-label="{{ __('website.home.next_brands') }}">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M9 5l7 7-7 7"/></svg>
        </button>
    </div>

    <script>
        (() => {
            const track = document.querySelector('[data-brands-track]');
            const viewport = document.querySelector('[data-brands-viewport]');
            const prev = document.querySelector('[data-brands-prev]');
            const next = document.querySelector('[data-brands-next]');
            if (!track || !viewport) return;

            let paused = false;
            let offset = 0;
            let half = 0;
            const speed = 0.45;

            const measure = () => {
                const group = track.querySelector('.gw-brands-group');
                half = group ? group.getBoundingClientRect().width : track.scrollWidth / 2;
            };

            const tick = () => {
                if (!paused && half > 0) {
                    offset -= speed;
                    if (Math.abs(offset) >= half) offset = 0;
                    track.style.transform = `translate3d(${offset}px,0,0)`;
                }
                requestAnimationFrame(tick);
            };

            const nudge = (dir) => {
                measure();
                offset += dir * Math.min(280, viewport.clientWidth * 0.45);
                if (offset > 0) offset = -half + (offset % half);
                if (Math.abs(offset) >= half) offset = offset % half;
                track.style.transform = `translate3d(${offset}px,0,0)`;
            };

            viewport.addEventListener('mouseenter', () => { paused = true; });
            viewport.addEventListener('mouseleave', () => { paused = false; });
            prev?.addEventListener('click', (e) => { e.preventDefault(); paused = true; nudge(1); setTimeout(() => { paused = false; }, 1200); });
            next?.addEventListener('click', (e) => { e.preventDefault(); paused = true; nudge(-1); setTimeout(() => { paused = false; }, 1200); });
            window.addEventListener('resize', measure);
            measure();
            requestAnimationFrame(tick);
        })();
    </script>
</section>
@endif

@endsection
