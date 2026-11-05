@php
    $storeName = $settings->store_name ?? config('app.name', 'Hira Moni');
    $tagline = trim((string) ($settings->footer_tagline ?? ''));
    if ($tagline === '') {
        $tagline = 'Premium cosmetics & skincare for your natural glow.';
    }
    $footerCopy = data_get($settings, 'home_copy') ?: [];
    $footerLogoTagline = 'glow naturally, Define you';

    $socialLinks = collect(\App\Models\SiteSetting::socialProfiles(data_get($settings, 'social_links') ?: []));
    if ($socialLinks->isEmpty()) {
        // Default showcase order matches brand mock: FB · IG · TikTok · WhatsApp · YouTube
        $socialLinks = collect(['facebook', 'instagram', 'tiktok', 'whatsapp', 'youtube'])->map(function ($key) {
            $meta = \App\Models\SiteSetting::SOCIAL_NETWORKS[$key];

            return [
                'key' => $key,
                'label' => $meta['label'],
                'url' => '#',
                'path' => $meta['path'],
            ];
        });
    }

    $pages = collect($footerPages ?? []);
    $publishedPages = \App\Models\CmsPage::where('shop_id', app(\App\Services\WebsiteService::class)->shopId())
        ->where('is_published', true)
        ->orderBy('sort_order')
        ->get(['id', 'slug', 'title']);
    $findPage = function (array $needles) use ($publishedPages) {
        return $publishedPages->first(function ($p) use ($needles) {
            $hay = strtolower(($p->slug ?? '').' '.($p->title ?? ''));
            foreach ($needles as $n) {
                if (str_contains($hay, $n)) {
                    return true;
                }
            }

            return false;
        });
    };
    $aboutPage = $findPage(['about']);
    $privacyPage = $findPage(['privacy']);
    $termsPage = $findPage(['terms', 'condition']);
    $shippingPage = $findPage(['shipping', 'delivery']);
    $returnsPage = $findPage(['return', 'refund']);

    $privacyUrl = $privacyPage ? route('website.page', $privacyPage->slug) : null;
    $termsUrl = $termsPage ? route('website.page', $termsPage->slug) : null;
    $returnsUrl = $returnsPage ? route('website.page', $returnsPage->slug) : null;

    $footerIconPath = $settings->logo_path ?: $settings->favicon_path;
    $footerIcon = $footerIconPath ? public_storage_url($footerIconPath) : null;
    $footerIconVer = $footerIconPath
        ? (@filemtime(public_storage_path($footerIconPath)) ?: time())
        : time();

    $lotusLeft = asset('images/footer/blend-lotus-left.jpg');
    $lotusRight = asset('images/footer/blend-lotus-right.jpg');
    $lotusBed = asset('images/footer/blend-bed.jpg');
@endphp
<footer class="tn-footer bb-footer gw-footer" aria-label="Site footer" style="--gw-footer-bed:url('{{ $lotusBed }}')">
    <div class="gw-footer-shell">
        <img class="gw-footer-bloom gw-footer-bloom--left" src="{{ $lotusLeft }}" alt="" aria-hidden="true" loading="lazy" decoding="async">
        <img class="gw-footer-bloom gw-footer-bloom--right" src="{{ $lotusRight }}" alt="" aria-hidden="true" loading="lazy" decoding="async">

        <div class="tn-container gw-footer-inner">
                <div class="gw-footer-main">
                    <div class="gw-footer-brand">
                        <a href="{{ route('home') }}" class="gw-footer-logo" aria-label="{{ $storeName }}">
                            @if($footerIcon)
                                <img src="{{ $footerIcon }}?v={{ $footerIconVer }}" alt="" class="gw-footer-logo-img" width="72" height="72">
                            @else
                                <span class="gw-footer-logo-mark" aria-hidden="true">
                                    <svg viewBox="0 0 48 48" fill="none">
                                        <path fill="#E91E63" d="M24 6c5 9 6 17 0 26C18 23 19 15 24 6Z"/>
                                        <path fill="#F48FB1" d="M8 20c10 1 17 6 17 14C15 31 9 25 8 20Z"/>
                                        <path fill="#EC407A" d="M40 20c-10 1-17 6-17 14 10-3 16-9 17-14Z"/>
                                        <path fill="#F8BBD0" d="M14 38c8-8 14-10 20 0-8 2-14 2-20 0Z"/>
                                        <circle cx="24" cy="26" r="5" fill="#FFD54F"/>
                                    </svg>
                                </span>
                            @endif
                            <span class="gw-footer-logo-stack">
                                <span class="gw-footer-logo-text">HIRAMONI</span>
                                <span class="gw-footer-logo-tagline">
                                    glow naturally, <em>Define you</em>
                                </span>
                            </span>
                        </a>
                        <p class="gw-footer-tagline">{{ $tagline }}</p>
                        <div class="gw-footer-social" role="list">
                            @foreach($socialLinks->take(5) as $social)
                                <a href="{{ $social['url'] }}"
                                   class="gw-footer-social-link"
                                   role="listitem"
                                   @if(($social['url'] ?? '') !== '#') target="_blank" rel="noopener noreferrer" @endif
                                   aria-label="{{ $social['label'] }}"
                                   title="{{ $social['label'] }}">
                                    @include('website.partials.social-icon', [
                                        'key' => $social['key'] ?? '',
                                        'path' => $social['path'] ?? '',
                                        'label' => $social['label'] ?? '',
                                    ])
                                </a>
                            @endforeach
                        </div>
                    </div>

                    <div class="gw-footer-col">
                        <h4 class="gw-footer-heading">Quick Links</h4>
                        <ul class="gw-footer-links">
                            <li><a href="{{ route('home') }}">Home</a></li>
                            <li><a href="{{ route('website.shop') }}">Shop</a></li>
                            @if($aboutPage)
                                <li><a href="{{ route('website.page', $aboutPage->slug) }}">About Us</a></li>
                            @else
                                <li><a href="{{ route('website.contact') }}">About Us</a></li>
                            @endif
                            <li><a href="{{ route('website.blogs') }}">Blog</a></li>
                            <li><a href="{{ route('website.contact') }}">Contact</a></li>
                        </ul>
                    </div>

                    <div class="gw-footer-col">
                        <h4 class="gw-footer-heading">Customer Care</h4>
                        <ul class="gw-footer-links">
                            <li><a href="{{ route('website.faqs') }}">FAQs</a></li>
                            @if($shippingPage)
                                <li><a href="{{ route('website.page', $shippingPage->slug) }}">Shipping Policy</a></li>
                            @else
                                <li><a href="{{ route('website.contact') }}">Shipping Policy</a></li>
                            @endif
                            @if($returnsUrl)
                                <li><a href="{{ $returnsUrl }}">Return Policy</a></li>
                            @else
                                <li><a href="{{ route('website.contact') }}">Return Policy</a></li>
                            @endif
                            <li><a href="{{ route('website.track') }}">Track Order</a></li>
                            <li><a href="{{ route('website.contact') }}">Help Center</a></li>
                        </ul>
                    </div>

                    <div class="gw-footer-col">
                        <h4 class="gw-footer-heading">My Account</h4>
                        <ul class="gw-footer-links">
                            <li><a href="{{ route('website.account') }}">Login / Register</a></li>
                            <li><a href="{{ route('website.account') }}">My Orders</a></li>
                            <li><a href="{{ route('website.wishlist') }}">Wishlist</a></li>
                            <li><a href="{{ route('website.shop', ['filter' => 'combo']) }}">Compare</a></li>
                            <li><a href="#" @click.prevent="openCart()">Cart</a></li>
                        </ul>
                    </div>

                    <div class="gw-footer-col gw-footer-connect">
                        <h4 class="gw-footer-heading">Stay Connected</h4>
                        <p class="gw-footer-connect-copy">Get updates on new products and special offers.</p>
                        <form class="gw-footer-news" action="{{ route('website.contact') }}" method="get" onsubmit="return false;">
                            <label class="sr-only" for="gw-footer-email">Email address</label>
                            <input id="gw-footer-email" type="email" name="email" placeholder="Your email address" autocomplete="email" required>
                            <button type="button" class="gw-footer-news-btn" aria-label="Subscribe" onclick="window.location='{{ route('website.contact') }}'">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z"/></svg>
                            </button>
                        </form>
                    </div>
                </div>

            <div class="gw-footer-credit">
                <p class="gw-footer-copy">&copy; {{ date('Y') }} {{ $storeName }}. All rights reserved.</p>
                <nav class="gw-footer-legal" aria-label="Legal">
                    @if($privacyUrl)<a href="{{ $privacyUrl }}">Privacy Policy</a>@endif
                    @if($termsUrl)<a href="{{ $termsUrl }}">Terms &amp; Conditions</a>@endif
                    <a href="{{ route('website.contact') }}">Contact</a>
                </nav>
            </div>
        </div>
    </div>
</footer>
