{{--
  Desktop nav "Categories" dropdown — beauty grid.
  @var string $allUrl
--}}
@php
    $allCategoriesUrl = $allUrl ?? route('website.shop');
    $ws = app(\App\Services\WebsiteService::class);
@endphp
<a href="{{ $allCategoriesUrl }}" class="gaget-nav-dropdown-item gaget-nav-dropdown-item--all">
    <span>{{ __('website.mobile.all_categories') }}</span>
    <span class="gaget-nav-dropdown-cta">{{ __('website.nav.view_all') }} @include('website.partials.nav-dropdown-arrow', ['class' => ''])</span>
</a>
@if(($allCategories ?? collect())->isNotEmpty())
    <div class="gaget-nav-cats-grid">
        @foreach($allCategories as $cat)
            @php
                $catMeta = $cat->iconMeta();
                $catImage = filled($cat->image_path) ? $ws->categoryImageUrl($cat) : null;
            @endphp
            <a href="{{ route('website.category', $cat->slug) }}"
               class="gaget-nav-cat-card"
               title="{{ $cat->name }}">
                <span class="gaget-nav-cat-visual" style="--dd-bg: {{ $catMeta['bg'] }}; --dd-fg: {{ $catMeta['color'] }};">
                    @if($catImage)
                        <img src="{{ $catImage }}" alt="" class="gaget-nav-cat-photo" loading="lazy" decoding="async">
                    @else
                        <span class="gaget-nav-cat-icon">
                            @include('website.partials.category-icon-svg', ['icon' => $catMeta['key'], 'class' => ''])
                        </span>
                    @endif
                </span>
                <span class="gaget-nav-cat-name">{{ $cat->name }}</span>
            </a>
        @endforeach
    </div>
@else
    <span class="gaget-nav-dropdown-empty">{{ __('website.nav.no_categories') }}</span>
@endif
