@php
    $headerName = $settings->store_name ?? config('app.name', 'Hira Moni');
    $headerIconPath = $settings->logo_path ?: $settings->favicon_path;
    $headerIcon = $headerIconPath ? public_storage_url($headerIconPath) : null;
    // Fallback to shipped brand mark when CMS logo is not set / missing on disk.
    if (! $headerIcon || ($headerIconPath && ! is_file(public_storage_path($headerIconPath)))) {
        $fallbackLogo = public_path('images/hiramoni-logo-round.png');
        if (! is_file($fallbackLogo)) {
            $fallbackLogo = public_path('images/hiramoni-logo.png');
        }
        $headerIcon = is_file($fallbackLogo)
            ? asset('images/'.basename($fallbackLogo))
            : null;
        $headerIconPath = null;
        $headerIconVer = is_file($fallbackLogo) ? (filemtime($fallbackLogo) ?: time()) : time();
    } else {
        $headerIconVer = @filemtime(public_storage_path($headerIconPath)) ?: time();
    }
    $navLinks = $mainNav ?? collect();
    $headerCopy = data_get($settings, 'home_copy') ?: [];
    $headerTagline = trim((string) ($headerCopy['logo_tagline'] ?? ''));
    $headerTagline = $headerTagline === '-' ? '' : $headerTagline;
    // Brand lockup slogan (exact casing + highlight on "Define you")
    $headerSlogan = 'glow naturally, Define you';
    $headerWordmark = 'HIRAMONI';
    $hasAboutPage = $navLinks->isEmpty() && \App\Models\CmsPage::where('slug', 'about-us')->where('is_published', true)->exists();
@endphp

<div class="gaget-sticky-header gw-header-soft" x-data="{ searchOpen: false }">
    {{-- Magenta announcement strip (mockup) --}}
    <div class="gw-topbar" role="region" aria-label="Store announcements">
        <div class="gw-topbar-inner">
            <div class="gw-topbar-left">
                <span class="gw-topbar-item">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 7h11v8H3V7zm11 2h4l3 3v3h-7V9zM6 18.5a1.5 1.5 0 100 3 1.5 1.5 0 000-3zm10 0a1.5 1.5 0 100 3 1.5 1.5 0 000-3z"/></svg>
                    {{ __('website.topbar.free_shipping') }}
                </span>
                <span class="gw-topbar-sep" aria-hidden="true"></span>
                <span class="gw-topbar-item">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 7h.01M7 3h5.5a2 2 0 011.4.6l6.5 6.5a2 2 0 010 2.8l-6.1 6.1a2 2 0 01-2.8 0L4.6 12A2 2 0 014 10.6V5a2 2 0 012-2h1z"/></svg>
                    {{ __('website.topbar.first_order') }}
                </span>
                <span class="gw-topbar-sep" aria-hidden="true"></span>
                <span class="gw-topbar-item">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                    {{ __('website.topbar.beauty_begins') }}
                </span>
            </div>
            <div class="gw-topbar-right">
                <a href="{{ route('website.track') }}">{{ __('website.topbar.track_order') }}</a>
                <span class="gw-topbar-sep" aria-hidden="true"></span>
                <a href="{{ route('website.faqs') }}">{{ __('website.topbar.help') }}</a>
                <span class="gw-topbar-sep" aria-hidden="true"></span>
                @auth('web')
                    @if(auth('web')->user()->isStorefrontCustomer())
                        <a href="{{ route('website.account') }}">{{ __('website.topbar.my_account') }}</a>
                    @else
                        <button type="button" @click="openSignIn('login')">{{ __('website.topbar.login_register') }}</button>
                    @endif
                @else
                    <button type="button" @click="openSignIn('login')">{{ __('website.topbar.login_register') }}</button>
                @endauth
                <span class="gw-topbar-sep" aria-hidden="true"></span>
                @php
                    $localeBnUrl = \Illuminate\Support\Facades\Route::has('locale.switch')
                        ? route('locale.switch', 'bn')
                        : url('/locale/bn');
                    $localeEnUrl = \Illuminate\Support\Facades\Route::has('locale.switch')
                        ? route('locale.switch', 'en')
                        : url('/locale/en');
                @endphp
                <div class="gw-lang-switch" role="group" aria-label="{{ __('website.lang.aria') }}">
                    <a href="{{ $localeBnUrl }}"
                       class="gw-lang-link {{ app()->getLocale() === 'bn' ? 'is-active' : '' }}"
                       hreflang="bn"
                       lang="bn"
                       @if(app()->getLocale() === 'bn') aria-current="true" @endif>বাং</a>
                    <span class="gw-lang-divider" aria-hidden="true">|</span>
                    <a href="{{ $localeEnUrl }}"
                       class="gw-lang-link {{ app()->getLocale() === 'en' ? 'is-active' : '' }}"
                       hreflang="en"
                       lang="en"
                       @if(app()->getLocale() === 'en') aria-current="true" @endif>EN</a>
                </div>
            </div>
        </div>
    </div>

    <div class="gaget-floatbar-shell">
        <div class="gaget-floatbar">
            {{-- Brand --}}
            <div class="gaget-floatbar-brand">
                <button type="button"
                        class="gaget-mobile-menu-btn"
                        @click="mobileOpen = !mobileOpen; if (!mobileOpen) { mobileCatsOpen = false; mobileBrandsOpen = false; }"
                        :aria-expanded="mobileOpen"
                        aria-label="{{ __('website.nav.menu') }}">
                    <svg x-show="!mobileOpen" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    <svg x-show="mobileOpen" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>

                <a href="{{ route('home') }}" class="gaget-floatbar-logo {{ $headerIcon ? 'has-brand-mark' : '' }}" aria-label="{{ $headerName }}">
                    @if($headerIcon)
                        <img src="{{ $headerIcon }}?v={{ $headerIconVer }}"
                             alt=""
                             class="gaget-logo-mark gaget-logo-mark--round"
                             width="56"
                             height="56"
                             decoding="async">
                    @else
                        <span class="gaget-logo-icon" aria-hidden="true">
                            <svg viewBox="0 0 40 40" width="34" height="34" fill="none" aria-hidden="true">
                                <path d="M20 8c-1.5 4.5-6.5 7-6.5 12.5S20 32 20 32s6.5-6 6.5-11.5S21.5 12.5 20 8Z" fill="#E91E63"/>
                                <path d="M11 16c3.5 2 5.5 6.5 5.5 10.5S14 32 14 32s-7-4.5-7-10 1.5-7.5 4-6Z" fill="#F06292"/>
                                <path d="M29 16c-3.5 2-5.5 6.5-5.5 10.5S26 32 26 32s7-4.5 7-10-1.5-7.5-4-6Z" fill="#F06292"/>
                                <circle cx="20" cy="22" r="3.2" fill="#AD1457"/>
                            </svg>
                        </span>
                    @endif
                    <span class="gaget-logo-stack">
                        <span class="gaget-logo-text">{{ $headerWordmark }}</span>
                        <span class="gaget-logo-tagline">
                            glow naturally, <em>Define you</em>
                        </span>
                    </span>
                </a>
                <span class="gw-logo-divider" aria-hidden="true"></span>
            </div>

            {{-- Desktop nav --}}
            <nav class="gaget-floatbar-nav" aria-label="{{ __('website.nav.main') }}">
                <div class="gaget-nav-links">
                    @forelse($navLinks as $link)
                        @php $navLabel = trim((string) $link->label); @endphp
                        @if(strcasecmp($navLabel, 'Categories') === 0 || strcasecmp($navLabel, 'Category') === 0)
                            <div class="gaget-nav-dropdown"
                                 x-data="navDropdown()"
                                 :class="{ 'is-open': open }"
                                 @mouseenter="show()"
                                 @mouseleave="hide()"
                                 @focusin="show()"
                                 @focusout="onFocusOut($event)"
                                 @keydown.escape.window="close()"
                                 @click.outside="close()">
                                <button type="button"
                                        class="gaget-nav-link gaget-nav-link--dropdown"
                                        @click="toggle()"
                                        :aria-expanded="open"
                                        aria-haspopup="true">
                                    {{ website_nav_label($link->label) }}
                                    <svg class="gaget-nav-chevron" :class="{ 'is-open': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                </button>
                                <div class="gaget-nav-dropdown-menu gaget-nav-dropdown-menu--categories" @mouseover="track($event)" @focusin="track($event)" @mouseleave="untrack()">
                                    <span class="gaget-nav-dropdown-glow" aria-hidden="true" :class="{ 'is-on': hl.on, 'is-snap': hl.snap }" :style="`--hl-y:${hl.y}px;--hl-h:${hl.h}px`"></span>
                                    @include('website.partials.nav-dropdown-categories', ['allUrl' => $link->url ?: route('website.shop')])
                                </div>
                            </div>
                        @elseif(strcasecmp($navLabel, 'Brands') === 0 || strcasecmp($navLabel, 'Brand') === 0)
                            <div class="gaget-nav-dropdown"
                                 x-data="navDropdown()"
                                 :class="{ 'is-open': open }"
                                 @mouseenter="show()"
                                 @mouseleave="hide()"
                                 @focusin="show()"
                                 @focusout="onFocusOut($event)"
                                 @keydown.escape.window="close()"
                                 @click.outside="close()">
                                <button type="button"
                                        class="gaget-nav-link gaget-nav-link--dropdown"
                                        @click="toggle()"
                                        :aria-expanded="open"
                                        aria-haspopup="true">
                                    {{ website_nav_label($link->label) }}
                                    <svg class="gaget-nav-chevron" :class="{ 'is-open': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                </button>
                                <div class="gaget-nav-dropdown-menu gaget-nav-dropdown-menu--end gaget-nav-dropdown-menu--brands" @mouseover="track($event)" @focusin="track($event)" @mouseleave="untrack()">
                                    <span class="gaget-nav-dropdown-glow" aria-hidden="true" :class="{ 'is-on': hl.on, 'is-snap': hl.snap }" :style="`--hl-y:${hl.y}px;--hl-h:${hl.h}px`"></span>
                                    @include('website.partials.nav-dropdown-brands', ['allUrl' => route('website.brands')])
                                </div>
                            </div>
                        @elseif(strcasecmp($navLabel, 'Shop') === 0)
                            <div class="gaget-nav-dropdown"
                                 x-data="navDropdown()"
                                 :class="{ 'is-open': open }"
                                 @mouseenter="show()"
                                 @mouseleave="hide()"
                                 @focusin="show()"
                                 @focusout="onFocusOut($event)"
                                 @keydown.escape.window="close()"
                                 @click.outside="close()">
                                <button type="button"
                                        class="gaget-nav-link gaget-nav-link--dropdown {{ request()->routeIs('website.shop') ? 'is-active' : '' }}"
                                        @click="toggle()"
                                        :aria-expanded="open"
                                        aria-haspopup="true">
                                    {{ __('website.nav.shop') }}
                                    <svg class="gaget-nav-chevron" :class="{ 'is-open': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                </button>
                                <div class="gaget-nav-dropdown-menu gaget-nav-dropdown-menu--shop" @mouseover="track($event)" @focusin="track($event)" @mouseleave="untrack()">
                                    <span class="gaget-nav-dropdown-glow" aria-hidden="true" :class="{ 'is-on': hl.on, 'is-snap': hl.snap }" :style="`--hl-y:${hl.y}px;--hl-h:${hl.h}px`"></span>
                                    @include('website.partials.nav-dropdown-shop')
                                </div>
                            </div>
                        @else
                            @php
                                $isHome = strcasecmp($navLabel, 'Home') === 0 && request()->routeIs('home');
                                $isActive = $isHome || (url()->current() === url($link->url));
                            @endphp
                            @if(strcasecmp($navLabel, 'Home') === 0)
                                <a href="{{ $link->url }}" class="gaget-nav-link gaget-nav-link--home {{ $isActive ? 'is-active' : '' }}">
                                    <svg class="gw-nav-home-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10.5L12 3l9 7.5V20a1 1 0 01-1 1h-5v-6H9v6H4a1 1 0 01-1-1v-9.5z"/></svg>
                                    {{ website_nav_label($link->label) }}
                                </a>
                            @else
                                <a href="{{ $link->url }}" class="gaget-nav-link {{ $isActive ? 'is-active' : '' }}">{{ website_nav_label($link->label) }}</a>
                            @endif
                        @endif
                    @empty
                        <a href="{{ route('home') }}" class="gaget-nav-link gaget-nav-link--home {{ request()->routeIs('home') ? 'is-active' : '' }}">
                            <svg class="gw-nav-home-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10.5L12 3l9 7.5V20a1 1 0 01-1 1h-5v-6H9v6H4a1 1 0 01-1-1v-9.5z"/></svg>
                            {{ __('website.nav.home') }}
                        </a>

                        <div class="gaget-nav-dropdown"
                             x-data="navDropdown()"
                                 :class="{ 'is-open': open }"
                                 @mouseenter="show()"
                                 @mouseleave="hide()"
                                 @focusin="show()"
                                 @focusout="onFocusOut($event)"
                                 @keydown.escape.window="close()"
                                 @click.outside="close()">
                            <button type="button"
                                    class="gaget-nav-link gaget-nav-link--dropdown {{ request()->routeIs('website.shop') ? 'is-active' : '' }}"
                                    @click="toggle()"
                                    :aria-expanded="open"
                                    aria-haspopup="true">
                                {{ __('website.nav.shop') }}
                                <svg class="gaget-nav-chevron" :class="{ 'is-open': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div class="gaget-nav-dropdown-menu gaget-nav-dropdown-menu--shop" @mouseover="track($event)" @focusin="track($event)" @mouseleave="untrack()">
                                <span class="gaget-nav-dropdown-glow" aria-hidden="true" :class="{ 'is-on': hl.on, 'is-snap': hl.snap }" :style="`--hl-y:${hl.y}px;--hl-h:${hl.h}px`"></span>
                                @include('website.partials.nav-dropdown-shop')
                            </div>
                        </div>

                        <div class="gaget-nav-dropdown"
                             x-data="navDropdown()"
                                 :class="{ 'is-open': open }"
                                 @mouseenter="show()"
                                 @mouseleave="hide()"
                                 @focusin="show()"
                                 @focusout="onFocusOut($event)"
                                 @keydown.escape.window="close()"
                                 @click.outside="close()">
                            <button type="button"
                                    class="gaget-nav-link gaget-nav-link--dropdown"
                                    @click="toggle()"
                                    :aria-expanded="open"
                                    aria-haspopup="true">
                                {{ __('website.nav.categories') }}
                                <svg class="gaget-nav-chevron" :class="{ 'is-open': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div class="gaget-nav-dropdown-menu gaget-nav-dropdown-menu--categories" @mouseover="track($event)" @focusin="track($event)" @mouseleave="untrack()">
                                <span class="gaget-nav-dropdown-glow" aria-hidden="true" :class="{ 'is-on': hl.on, 'is-snap': hl.snap }" :style="`--hl-y:${hl.y}px;--hl-h:${hl.h}px`"></span>
                                @include('website.partials.nav-dropdown-categories', ['allUrl' => route('website.shop')])
                            </div>
                        </div>

                        @if(($trustedBrands ?? $brands ?? collect())->isNotEmpty())
                        <div class="gaget-nav-dropdown"
                             x-data="navDropdown()"
                                 :class="{ 'is-open': open }"
                                 @mouseenter="show()"
                                 @mouseleave="hide()"
                                 @focusin="show()"
                                 @focusout="onFocusOut($event)"
                                 @keydown.escape.window="close()"
                                 @click.outside="close()">
                            <button type="button"
                                    class="gaget-nav-link gaget-nav-link--dropdown"
                                    @click="toggle()"
                                    :aria-expanded="open"
                                    aria-haspopup="true">
                                {{ __('website.nav.brands') }}
                                <svg class="gaget-nav-chevron" :class="{ 'is-open': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div class="gaget-nav-dropdown-menu gaget-nav-dropdown-menu--end gaget-nav-dropdown-menu--brands" @mouseover="track($event)" @focusin="track($event)" @mouseleave="untrack()">
                                <span class="gaget-nav-dropdown-glow" aria-hidden="true" :class="{ 'is-on': hl.on, 'is-snap': hl.snap }" :style="`--hl-y:${hl.y}px;--hl-h:${hl.h}px`"></span>
                                @include('website.partials.nav-dropdown-brands', ['allUrl' => route('website.brands')])
                            </div>
                        </div>
                        @endif

                        @if($hasAboutPage)
                            <a href="{{ route('website.page', 'about-us') }}" class="gaget-nav-link {{ request()->is('page/about-us') ? 'is-active' : '' }}">{{ __('website.nav.about_us') }}</a>
                        @endif
                        <a href="{{ route('website.blogs') }}" class="gaget-nav-link {{ request()->routeIs('website.blogs*') ? 'is-active' : '' }}">{{ __('website.nav.blog') }}</a>
                        <a href="{{ route('website.contact') }}" class="gaget-nav-link {{ request()->routeIs('website.contact') ? 'is-active' : '' }}">{{ __('website.nav.contact') }}</a>
                    @endforelse
                </div>
            </nav>

            {{-- Search (in-bar on desktop) --}}
            <div class="gaget-floatbar-search"
                 x-data="headerSearch(@js(route('website.search.suggest')), @js(route('website.shop')), @js($settings->currency_symbol ?? '৳'))"
                 @click.outside="open = false">
                <form action="{{ route('website.shop') }}" method="GET" class="gaget-search-wrap" @submit="open = false">
                    <div class="gaget-search-bar">
                        <span class="gaget-search-lead" aria-hidden="true">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </span>
                        <input type="search"
                               name="search"
                               x-model="q"
                               x-ref="searchInput"
                               value="{{ request('search') }}"
                               placeholder="{{ __('website.search.placeholder') }}"
                               class="gaget-search-input"
                               autocomplete="off"
                               @focus="onFocus()"
                               @input.debounce.200ms="fetchSuggestions()"
                               @keydown.escape.prevent="open = false"
                               @keydown.arrow-down.prevent="move(1)"
                               @keydown.arrow-up.prevent="move(-1)"
                               @keydown.enter="onEnter($event)">
                        <button type="submit" class="gaget-search-btn" aria-label="{{ __('website.search.aria') }}" @click="if (!q) { $event.preventDefault(); $refs.searchInput.focus(); }">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </button>
                    </div>

                    <div class="gaget-search-suggest" x-show="open" x-cloak x-transition.opacity.duration.120ms>
                        <div class="gaget-search-suggest__head">
                            <span x-text="mode === 'best' ? @js(__('website.search.best_picks')) : @js(__('website.search.matching'))"></span>
                            <span class="gaget-search-suggest__hint" x-show="loading">{{ __('website.search.searching') }}</span>
                        </div>
                        <template x-if="!loading && products.length === 0">
                            <p class="gaget-search-suggest__empty" x-text="q ? @js(__('website.search.no_products')) : @js(__('website.search.no_products_yet'))"></p>
                        </template>
                        <div class="gaget-search-suggest__list" x-show="products.length > 0">
                            <template x-for="(item, index) in products" :key="item.id">
                                <a :href="item.url"
                                   class="gaget-search-suggest__item"
                                   :class="{ 'is-active': index === activeIndex }"
                                   @mouseenter="activeIndex = index">
                                    <img :src="item.image" :alt="item.name" loading="lazy">
                                    <div class="min-w-0 flex-1">
                                        <p class="gaget-search-suggest__name" x-text="item.name"></p>
                                        <p class="gaget-search-suggest__meta" x-text="item.brand || 'Product'"></p>
                                    </div>
                                    <strong class="gaget-search-suggest__price" x-text="currency + Math.round(Number(item.price) || 0).toLocaleString()"></strong>
                                </a>
                            </template>
                        </div>
                        <a :href="shopUrl" class="gaget-search-suggest__all" x-show="q.length > 0">
                            View all results for “<span x-text="q"></span>”
                        </a>
                    </div>
                </form>
            </div>

            {{-- Actions --}}
            <div class="gaget-floatbar-actions">
                <button type="button"
                        class="gaget-action-btn gaget-search-toggle"
                        :class="{ 'is-active': searchOpen }"
                        @click="searchOpen = !searchOpen; if (searchOpen) $nextTick(() => $el.closest('.gaget-sticky-header').querySelector('.gaget-floatbar-search-mobile input')?.focus())"
                        :aria-expanded="searchOpen"
                        aria-label="Search">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </button>
                <div class="gaget-action-wrap" x-data="{ open: false }" @mouseenter="open=true" @mouseleave="open=false">
                    <a href="{{ route('website.wishlist') }}" class="gaget-action-btn" aria-label="Wishlist">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                        <span class="gaget-action-label">Wishlist</span>
                        <span class="gaget-cart-badge"
                              :class="{ 'is-empty': wishlistCount < 1 }"
                              x-show="wishlistCount > 0"
                              x-text="wishlistCount"
                              x-cloak></span>
                    </a>
                    <div class="gaget-hover-panel" x-show="open" x-cloak x-transition.opacity.duration.150ms>
                        <div class="gaget-hover-panel__head">
                            <span>Wishlist</span>
                            <strong x-text="wishlistCount + (wishlistCount === 1 ? ' item' : ' items')"></strong>
                        </div>
                        <template x-if="wishlist.length === 0">
                            <p class="gaget-hover-panel__empty">No saved items yet.</p>
                        </template>
                        <div class="gaget-hover-panel__list" x-show="wishlist.length > 0">
                            <template x-for="item in wishlist.slice(0, 4)" :key="'w'+item.id">
                                <a :href="item.url" class="gaget-hover-panel__row">
                                    <img :src="item.image" :alt="item.name">
                                    <div>
                                        <p x-text="item.name"></p>
                                        <span x-text="currency + Math.round(Number(item.price) || 0).toLocaleString()"></span>
                                    </div>
                                </a>
                            </template>
                        </div>
                        <a href="{{ route('website.wishlist') }}" class="gaget-hover-panel__cta" x-show="wishlistCount > 0">View wishlist</a>
                    </div>
                </div>

                <div class="gaget-action-wrap" x-data="{ open: false }" @mouseenter="open=true" @mouseleave="open=false">
                    <button type="button"
                            id="gaget-cart-target"
                            data-cart-target
                            @click="openCart()"
                            class="gaget-action-btn gaget-action-btn--cart"
                            :class="{ 'is-cart-swing': cartBump }"
                            aria-label="Cart">
                        <svg class="gaget-cart-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        <span class="gaget-action-label">Cart</span>
                        <span class="gaget-cart-badge"
                              :class="{ 'is-empty': cartCount < 1 }"
                              x-show="cartCount > 0"
                              x-text="cartCount"
                              x-cloak></span>
                    </button>
                    <div class="gaget-hover-panel gaget-hover-panel--cart" x-show="open" x-cloak x-transition.opacity.duration.150ms>
                        <div class="gaget-hover-panel__head">
                            <span>Cart</span>
                            <strong x-text="cartCount + (cartCount === 1 ? ' item' : ' items')"></strong>
                        </div>
                        <template x-if="cart.length === 0">
                            <p class="gaget-hover-panel__empty">Your cart is empty.</p>
                        </template>
                        <div class="gaget-hover-panel__list" x-show="cart.length > 0">
                            <template x-for="item in cart.slice(0, 4)" :key="'c'+item.id">
                                <div class="gaget-hover-panel__row">
                                    <img :src="item.image" :alt="item.name">
                                    <div>
                                        <p x-text="item.name"></p>
                                        <span x-text="item.qty + ' × ' + currency + Math.round(Number(item.price) || 0).toLocaleString()"></span>
                                    </div>
                                </div>
                            </template>
                        </div>
                        <div class="gaget-hover-panel__foot" x-show="cart.length > 0">
                            <div class="gaget-hover-panel__total">
                                <span>Total</span>
                                <strong x-text="currency + Math.round(Number(cartTotal) || 0).toLocaleString()"></strong>
                            </div>
                            <button type="button" class="gaget-hover-panel__cta" @click="open=false; openCart()">View cart</button>
                        </div>
                    </div>
                </div>

                @auth('web')
                    @if(auth('web')->user()->isStorefrontCustomer())
                        <div class="gaget-action-wrap" x-data="{ open: false }" @mouseenter="open=true" @mouseleave="open=false">
                            <a href="{{ route('website.account') }}" class="gaget-action-btn" title="{{ auth('web')->user()->name }}">
                                @if(auth('web')->user()->avatarUrl())
                                    <img src="{{ auth('web')->user()->avatarUrl() }}" alt="" class="gaget-action-avatar">
                                @else
                                    <span class="gaget-action-avatar gaget-action-avatar--initials">{{ auth('web')->user()->avatarInitials() }}</span>
                                @endif
                                <span class="gaget-action-label">Account</span>
                            </a>
                            <div class="gaget-hover-panel gaget-hover-panel--account" x-show="open" x-cloak x-transition.opacity.duration.150ms>
                                <div class="gaget-account-menu__user">
                                    @if(auth('web')->user()->avatarUrl())
                                        <img src="{{ auth('web')->user()->avatarUrl() }}" alt="" class="gaget-account-menu__avatar">
                                    @else
                                        <span class="gaget-account-menu__avatar gaget-account-menu__avatar--initials">{{ auth('web')->user()->avatarInitials() }}</span>
                                    @endif
                                    <div class="min-w-0">
                                        <p class="gaget-account-menu__name">{{ auth('web')->user()->name }}</p>
                                        <p class="gaget-account-menu__email">{{ auth('web')->user()->email }}</p>
                                    </div>
                                </div>
                                <div class="gaget-account-menu__links">
                                    <a href="{{ route('website.account') }}" class="gaget-account-menu__item">
                                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                        Profile
                                    </a>
                                    @php $headerUnread = auth('web')->user()->unreadNotifications()->count(); @endphp
                                    <a href="{{ route('website.account') }}#notifications" class="gaget-account-menu__item">
                                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 10-12 0v3.2a2 2 0 01-.6 1.4L4 17h5m6 0a3 3 0 11-6 0"/></svg>
                                        Notifications
                                        @if($headerUnread > 0)
                                            <span style="margin-left:auto;background:#f43f5e;color:#fff;border-radius:9999px;padding:0 6px;font-size:10px;font-weight:700;">{{ $headerUnread }}</span>
                                        @endif
                                    </a>
                                    <button type="button"
                                            class="gaget-account-menu__item gaget-account-menu__item--danger"
                                            @mousedown.prevent="typeof storefrontLogout === 'function' && storefrontLogout()">
                                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                                        Sign out
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endif
                @else
                    <div class="gaget-action-wrap" x-data="{ open: false }" @mouseenter="open=true" @mouseleave="open=false">
                        <button type="button" @click="openSignIn('login')" class="gaget-action-btn" title="Sign in">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            <span class="gaget-action-label">Account</span>
                        </button>
                        <div class="gaget-hover-panel gaget-hover-panel--account" x-show="open" x-cloak x-transition.opacity.duration.150ms>
                            <div class="gaget-account-menu__links">
                                <button type="button" @click="open=false; openSignIn('login')" class="gaget-account-menu__item w-full text-left">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>
                                    Sign in
                                </button>
                                <button type="button" @click="open=false; openSignIn('register')" class="gaget-account-menu__item w-full text-left">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                                    Create account
                                </button>
                            </div>
                        </div>
                    </div>
                @endauth
            </div>
        </div>

        {{-- Compact search under pill on smaller screens --}}
        <div class="gaget-floatbar-search-mobile"
             :class="{ 'is-open': searchOpen }"
             @keydown.escape="searchOpen = false"
             x-data="headerSearch(@js(route('website.search.suggest')), @js(route('website.shop')), @js($settings->currency_symbol ?? '৳'))"
             @click.outside="open = false">
            <form action="{{ route('website.shop') }}" method="GET" class="gaget-search-wrap" @submit="open = false">
                <div class="gaget-search-bar">
                    <span class="gaget-search-lead" aria-hidden="true">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </span>
                    <input type="search"
                           name="search"
                           x-model="q"
                           value="{{ request('search') }}"
                           placeholder="{{ __('website.search.placeholder') }}"
                           class="gaget-search-input"
                           autocomplete="off"
                           @focus="onFocus()"
                           @input.debounce.200ms="fetchSuggestions()"
                           @keydown.escape.prevent="open = false"
                           @keydown.arrow-down.prevent="move(1)"
                           @keydown.arrow-up.prevent="move(-1)"
                           @keydown.enter="onEnter($event)">
                    <button type="submit" class="gaget-search-btn" aria-label="Search">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </button>
                </div>
                <div class="gaget-search-suggest" x-show="open" x-cloak x-transition.opacity.duration.120ms>
                    <div class="gaget-search-suggest__head">
                        <span x-text="mode === 'best' ? 'Best picks for you' : 'Matching products'"></span>
                        <span class="gaget-search-suggest__hint" x-show="loading">Searching…</span>
                    </div>
                    <template x-if="!loading && products.length === 0">
                        <p class="gaget-search-suggest__empty" x-text="q ? 'No products found' : 'No products available yet'"></p>
                    </template>
                    <div class="gaget-search-suggest__list" x-show="products.length > 0">
                        <template x-for="(item, index) in products" :key="'m'+item.id">
                            <a :href="item.url"
                               class="gaget-search-suggest__item"
                               :class="{ 'is-active': index === activeIndex }"
                               @mouseenter="activeIndex = index">
                                <img :src="item.image" :alt="item.name" loading="lazy">
                                <div class="min-w-0 flex-1">
                                    <p class="gaget-search-suggest__name" x-text="item.name"></p>
                                    <p class="gaget-search-suggest__meta" x-text="item.brand || 'Product'"></p>
                                </div>
                                <strong class="gaget-search-suggest__price" x-text="currency + Math.round(Number(item.price) || 0).toLocaleString()"></strong>
                            </a>
                        </template>
                    </div>
                    <a :href="shopUrl" class="gaget-search-suggest__all" x-show="q.length > 0">
                        View all results for “<span x-text="q"></span>”
                    </a>
                </div>
            </form>
        </div>
    </div>

    {{-- Mobile beauty menu --}}
    <div x-show="mobileOpen" x-cloak class="gaget-mobile-drawer gw-mobile-drawer" @click.self="mobileOpen = false; mobileCatsOpen = false; mobileBrandsOpen = false">
        <div class="gaget-mobile-drawer-panel gw-mobile-panel" x-show="mobileOpen" x-transition:enter="transition ease-out duration-220" x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0" x-transition:leave="transition ease-in duration-160" x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full">
            <div class="gaget-mobile-drawer-head gw-mobile-head">
                <a href="{{ route('home') }}" class="gaget-mobile-drawer-brand" @click="mobileOpen = false" aria-label="{{ $headerName }}">
                    @if($headerIcon)
                        <img src="{{ $headerIcon }}?v={{ $headerIconVer }}" alt="" class="gaget-mobile-drawer-brand-mark">
                    @else
                        <span class="gaget-mobile-drawer-brand-mark gaget-mobile-drawer-brand-mark--text">
                            <svg viewBox="0 0 36 36" fill="none" width="28" height="28" aria-hidden="true"><circle cx="18" cy="18" r="18" fill="rgba(233,30,99,.16)"/><path d="M18 8c0 0-6 3-6 8s6 8 6 8 6-3 6-8-6-8-6-8z" fill="#E91E63" opacity=".85"/><circle cx="18" cy="18" r="3" fill="#C2185B"/></svg>
                        </span>
                    @endif
                    <span class="gaget-mobile-drawer-brand-text">
                        <span class="gaget-mobile-drawer-brand-name">{{ $headerWordmark }}</span>
                        <span class="gaget-mobile-drawer-brand-sub">
                            glow naturally, <em>Define you</em>
                        </span>
                    </span>
                </a>
                <button type="button"
                        class="gaget-mobile-drawer-close"
                        aria-label="{{ __('website.nav.close_menu') }}"
                        @click="mobileOpen = false; mobileCatsOpen = false; mobileBrandsOpen = false">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="gw-mobile-scroll">
            <p class="gw-mobile-kicker">{{ __('website.mobile.discover') }}</p>
            <div class="gaget-mobile-drawer-quick gw-mobile-quick">
                <a href="{{ route('website.shop') }}" class="gaget-mobile-quick" @click="mobileOpen = false">
                    <span class="gw-mobile-quick-ico" aria-hidden="true">✦</span>
                    <span>{{ __('website.mobile.shop_all') }}</span>
                </a>
                <a href="{{ route('website.shop', ['filter' => 'new']) }}" class="gaget-mobile-quick" @click="mobileOpen = false">
                    <span class="gw-mobile-quick-ico" aria-hidden="true">♡</span>
                    <span>{{ __('website.mobile.new_in') }}</span>
                </a>
                <a href="{{ route('website.shop', ['filter' => 'bestseller']) }}" class="gaget-mobile-quick" @click="mobileOpen = false">
                    <span class="gw-mobile-quick-ico" aria-hidden="true">★</span>
                    <span>{{ __('website.mobile.best_sellers') }}</span>
                </a>
            </div>

            @if(($allCategories ?? collect())->isNotEmpty())
                <div class="gw-mobile-cats" aria-label="{{ __('website.mobile.shop_by_category') }}">
                    @foreach(($allCategories ?? collect())->take(8) as $cat)
                        @php $mobileCatMeta = $cat->iconMeta(); @endphp
                        <a href="{{ route('website.category', $cat->slug) }}" class="gw-mobile-cat" @click="mobileOpen = false">
                            @if(filled($cat->image_path ?? null))
                                <img src="{{ public_storage_url($cat->image_path) }}" alt="" loading="lazy" decoding="async">
                            @else
                                <span class="gw-mobile-cat-ico" style="--dd-bg: {{ $mobileCatMeta['bg'] }}; --dd-fg: {{ $mobileCatMeta['color'] }};" aria-hidden="true">
                                    @include('website.partials.category-icon-svg', ['icon' => $mobileCatMeta['key'], 'class' => ''])
                                </span>
                            @endif
                            <span>{{ $cat->name }}</span>
                        </a>
                    @endforeach
                </div>
            @endif

            <p class="gw-mobile-kicker">{{ __('website.nav.menu') }}</p>
            <nav class="gw-mobile-nav" aria-label="{{ __('website.nav.mobile') }}">
            @forelse($navLinks as $link)
                @php $navLabel = trim((string) $link->label); @endphp
                @if(strcasecmp($navLabel, 'Categories') === 0 || strcasecmp($navLabel, 'Category') === 0)
                    @if(($allCategories ?? collect())->isNotEmpty())
                        <div class="gaget-mobile-accordion">
                            <button type="button"
                                    class="gaget-mobile-accordion-btn"
                                    @click="mobileCatsOpen = !mobileCatsOpen; if (mobileCatsOpen) mobileBrandsOpen = false"
                                    :aria-expanded="mobileCatsOpen"
                                    :class="{ 'is-open': mobileCatsOpen }">
                                <span>{{ website_nav_label($link->label) }}</span>
                                <svg class="gaget-mobile-accordion-chevron" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div class="gaget-mobile-accordion-panel" x-show="mobileCatsOpen" x-cloak x-transition.opacity.duration.150ms>
                                <a href="{{ $link->url ?: route('website.shop') }}" class="gaget-mobile-drawer-link gaget-mobile-drawer-link--sub" @click="mobileOpen = false">{{ __('website.mobile.all_categories') }}</a>
                                @foreach(($allCategories ?? []) as $cat)
                                    @php $drawerCatMeta = $cat->iconMeta(); @endphp
                                    <a href="{{ route('website.category', $cat->slug) }}" class="gaget-mobile-drawer-link gaget-mobile-drawer-link--sub gaget-mobile-drawer-link--cat" @click="mobileOpen = false">
                                        <span class="gaget-mobile-drawer-cat-ico" style="--dd-bg: {{ $drawerCatMeta['bg'] }}; --dd-fg: {{ $drawerCatMeta['color'] }};" aria-hidden="true">
                                            @include('website.partials.category-icon-svg', ['icon' => $drawerCatMeta['key'], 'class' => ''])
                                        </span>
                                        <span>{{ $cat->name }}</span>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <a href="{{ $link->url ?: route('website.shop') }}" class="gaget-mobile-drawer-link" @click="mobileOpen = false">{{ website_nav_label($link->label) }}</a>
                    @endif
                @elseif(strcasecmp($navLabel, 'Brands') === 0 || strcasecmp($navLabel, 'Brand') === 0)
                    @php $mobileBrandList = ($trustedBrands ?? null); if (! $mobileBrandList || (method_exists($mobileBrandList, 'isEmpty') && $mobileBrandList->isEmpty())) { $mobileBrandList = collect($brands ?? [])->map(fn ($b) => (object) ['name' => $b->name, 'slug' => \Illuminate\Support\Str::slug($b->name)]); } @endphp
                    @if($mobileBrandList->isNotEmpty())
                        <div class="gaget-mobile-accordion">
                            <button type="button"
                                    class="gaget-mobile-accordion-btn"
                                    @click="mobileBrandsOpen = !mobileBrandsOpen; if (mobileBrandsOpen) mobileCatsOpen = false"
                                    :aria-expanded="mobileBrandsOpen"
                                    :class="{ 'is-open': mobileBrandsOpen }">
                                <span>{{ website_nav_label($link->label) }}</span>
                                <svg class="gaget-mobile-accordion-chevron" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div class="gaget-mobile-accordion-panel" x-show="mobileBrandsOpen" x-cloak x-transition.opacity.duration.150ms>
                                <a href="{{ route('website.brands') }}" class="gaget-mobile-drawer-link gaget-mobile-drawer-link--sub" @click="mobileOpen = false">{{ __('website.mobile.all_brands') }}</a>
                                @foreach($mobileBrandList as $brand)
                                    <a href="{{ route('website.brand', $brand->slug) }}" class="gaget-mobile-drawer-link gaget-mobile-drawer-link--sub" @click="mobileOpen = false">
                                        <span>{{ $brand->name }}</span>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <a href="{{ $link->url ?: route('home').'#brands' }}" class="gaget-mobile-drawer-link" @click="mobileOpen = false">{{ website_nav_label($link->label) }}</a>
                    @endif
                @else
                    <a href="{{ $link->url }}" class="gaget-mobile-drawer-link" @click="mobileOpen = false">{{ website_nav_label($link->label) }}</a>
                @endif
            @empty
            <a href="{{ route('home') }}" class="gaget-mobile-drawer-link" @click="mobileOpen = false">{{ __('website.nav.home') }}</a>
            <a href="{{ route('website.shop') }}" class="gaget-mobile-drawer-link" @click="mobileOpen = false">{{ __('website.nav.shop') }}</a>

            @if(($allCategories ?? collect())->isNotEmpty())
                <div class="gaget-mobile-accordion">
                    <button type="button"
                            class="gaget-mobile-accordion-btn"
                            @click="mobileCatsOpen = !mobileCatsOpen; if (mobileCatsOpen) mobileBrandsOpen = false"
                            :aria-expanded="mobileCatsOpen"
                            :class="{ 'is-open': mobileCatsOpen }">
                        <span>{{ __('website.nav.categories') }}</span>
                        <svg class="gaget-mobile-accordion-chevron" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div class="gaget-mobile-accordion-panel" x-show="mobileCatsOpen" x-cloak x-transition.opacity.duration.150ms>
                        <a href="{{ route('website.shop') }}" class="gaget-mobile-drawer-link gaget-mobile-drawer-link--sub" @click="mobileOpen = false">{{ __('website.mobile.all_categories') }}</a>
                        @foreach(($allCategories ?? []) as $cat)
                            @php $drawerCatMeta = $cat->iconMeta(); @endphp
                            <a href="{{ route('website.category', $cat->slug) }}" class="gaget-mobile-drawer-link gaget-mobile-drawer-link--sub gaget-mobile-drawer-link--cat" @click="mobileOpen = false">
                                <span class="gaget-mobile-drawer-cat-ico" style="--dd-bg: {{ $drawerCatMeta['bg'] }}; --dd-fg: {{ $drawerCatMeta['color'] }};" aria-hidden="true">
                                    @include('website.partials.category-icon-svg', ['icon' => $drawerCatMeta['key'], 'class' => ''])
                                </span>
                                <span>{{ $cat->name }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            @php $fallbackBrandList = ($trustedBrands ?? null); if (! $fallbackBrandList || (method_exists($fallbackBrandList, 'isEmpty') && $fallbackBrandList->isEmpty())) { $fallbackBrandList = collect($brands ?? [])->map(fn ($b) => (object) ['name' => $b->name, 'slug' => \Illuminate\Support\Str::slug($b->name)]); } @endphp
            @if($fallbackBrandList->isNotEmpty())
                <div class="gaget-mobile-accordion">
                    <button type="button"
                            class="gaget-mobile-accordion-btn"
                            @click="mobileBrandsOpen = !mobileBrandsOpen; if (mobileBrandsOpen) mobileCatsOpen = false"
                            :aria-expanded="mobileBrandsOpen"
                            :class="{ 'is-open': mobileBrandsOpen }">
                        <span>{{ __('website.nav.brands') }}</span>
                        <svg class="gaget-mobile-accordion-chevron" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div class="gaget-mobile-accordion-panel" x-show="mobileBrandsOpen" x-cloak x-transition.opacity.duration.150ms>
                        <a href="{{ route('website.brands') }}" class="gaget-mobile-drawer-link gaget-mobile-drawer-link--sub" @click="mobileOpen = false">{{ __('website.mobile.all_brands') }}</a>
                        @foreach($fallbackBrandList as $brand)
                            <a href="{{ route('website.brand', $brand->slug) }}" class="gaget-mobile-drawer-link gaget-mobile-drawer-link--sub" @click="mobileOpen = false">
                                <span>{{ $brand->name }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            @if($hasAboutPage)
                <a href="{{ route('website.page', 'about-us') }}" class="gaget-mobile-drawer-link" @click="mobileOpen = false">{{ __('website.nav.about_us') }}</a>
            @endif
            <a href="{{ route('website.blogs') }}" class="gaget-mobile-drawer-link" @click="mobileOpen = false">{{ __('website.nav.beauty_journal') }}</a>
            <a href="{{ route('website.contact') }}" class="gaget-mobile-drawer-link" @click="mobileOpen = false">{{ __('website.nav.contact') }}</a>
            @endforelse
            </nav>
            </div>

            <div class="gaget-mobile-drawer-foot gw-mobile-foot">
                @auth('web')
                    @if(auth('web')->user()->isStorefrontCustomer())
                        <a href="{{ route('website.account') }}" class="gaget-mobile-drawer-cta" @click="mobileOpen = false">{{ __('website.topbar.my_account') }}</a>
                        <button type="button"
                                class="gaget-mobile-drawer-signout"
                                @click="mobileOpen = false; typeof storefrontLogout === 'function' && storefrontLogout()">
                            {{ __('website.mobile.sign_out') }}
                        </button>
                    @endif
                @else
                    <button type="button" class="gaget-mobile-drawer-cta" @click="mobileOpen = false; openSignIn('login')">{{ __('website.mobile.sign_in_register') }}</button>
                    <a href="{{ route('website.track') }}" class="gw-mobile-track" @click="mobileOpen = false">{{ __('website.mobile.track_an_order') }}</a>
                    <p class="gaget-mobile-drawer-note">{{ __('website.mobile.guest_note') }}</p>
                @endauth
            </div>
        </div>
    </div>
</div>
<div class="gaget-header-spacer" id="gaget-header-spacer" aria-hidden="true"></div>
