@extends('website.layout')
@php
    $settings = $settings ?? (object) [];
    $storeName = data_get($settings, 'store_name', config('app.name', 'Hira Moni'));
    $heroKicker = data_get($settings, 'blog_hero_kicker') ?: 'Beauty Journal';
    $heroTitle = data_get($settings, 'blog_hero_title') ?: 'Glow Tips & Beauty Stories';
    $heroSub = data_get($settings, 'blog_hero_subtitle')
        ?: 'Skincare routines, makeup guides, and honest product notes from '.$storeName.'.';
    $heroImage = data_get($settings, 'blog_hero_image')
        ? public_storage_url(data_get($settings, 'blog_hero_image'))
        : asset('images/hiramoni-hero-bleed.jpg');
    $articlesTitle = data_get($settings, 'blog_articles_title') ?: 'Latest Stories';
    $newsletterTitle = data_get($settings, 'blog_newsletter_title') ?: 'Stay in the glow';
    $newsletterText = data_get($settings, 'blog_newsletter_text') ?: 'Beauty tips and gentle offers — straight to your inbox.';
    $features = array_values(array_filter([
        [
            'title' => data_get($settings, 'blog_feature_1_title') ?: 'Expert Tips',
            'text' => data_get($settings, 'blog_feature_1_text') ?: 'Real routines that work',
            'icon' => 'tip',
        ],
        [
            'title' => data_get($settings, 'blog_feature_2_title') ?: 'Product Guides',
            'text' => data_get($settings, 'blog_feature_2_text') ?: 'Find your perfect match',
            'icon' => 'guide',
        ],
        [
            'title' => data_get($settings, 'blog_feature_3_title') ?: 'Fresh Ideas',
            'text' => data_get($settings, 'blog_feature_3_text') ?: 'Trends made simple',
            'icon' => 'spark',
        ],
    ], fn ($f) => filled($f['title'])));
    $blogCategories = $blogCategories ?? collect();
    $popularPosts = $popularPosts ?? collect();
    $blogSearch = $blogSearch ?? null;
    $activeBlogCategory = $activeBlogCategory ?? null;
    $hasFilters = filled($blogSearch) || filled($activeBlogCategory);
    $activeCategoryName = null;
    if ($activeBlogCategory) {
        $activeCategoryName = optional($blogCategories->firstWhere('slug', $activeBlogCategory))->name
            ?: $activeBlogCategory;
    }
@endphp
@section('title', 'Blog — '.$storeName)
@section('meta_description', $heroSub)
@section('content')

<section class="gw-blog-hero" aria-labelledby="gw-blog-hero-title">
    <div class="gw-blog-hero-media" aria-hidden="true">
        <img src="{{ $heroImage }}" alt="">
        <span class="gw-blog-hero-veil"></span>
    </div>
    <div class="tn-container gw-blog-hero-inner">
        <p class="gw-blog-kicker">{{ $heroKicker }}</p>
        <h1 id="gw-blog-hero-title" class="gw-blog-hero-title">{{ $heroTitle }}</h1>
        <p class="gw-blog-hero-sub">{{ $heroSub }}</p>

        @if(count($features))
            <ul class="gw-blog-promises">
                @foreach($features as $feature)
                    <li>
                        <span class="gw-blog-promise-icon" aria-hidden="true">
                            @if($feature['icon'] === 'guide')
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                            @elseif($feature['icon'] === 'spark')
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>
                            @else
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                            @endif
                        </span>
                        <span>
                            <strong>{{ $feature['title'] }}</strong>
                            <small>{{ $feature['text'] }}</small>
                        </span>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</section>

<section class="gw-blog-page">
    <div class="tn-container gw-blog-layout">
        <div class="gw-blog-main">
            <header class="gw-blog-toolbar">
                @if($hasFilters)
                    <div class="gw-blog-filter-note">
                        @if($blogSearch)
                            <p>Results for <strong>“{{ $blogSearch }}”</strong></p>
                        @endif
                        @if($activeCategoryName)
                            <p>Category: <strong>{{ $activeCategoryName }}</strong></p>
                        @endif
                    </div>
                    <a href="{{ route('website.blogs') }}" class="gw-blog-clear">Clear filters</a>
                @else
                    <h2 class="gw-blog-section-title">{{ $articlesTitle }}</h2>
                    <p class="gw-blog-section-count">{{ method_exists($blogs, 'total') ? $blogs->total() : $blogs->count() }} stories</p>
                @endif
            </header>

            <div class="gw-blog-listing">
                @forelse($blogs as $post)
                    <article class="gw-blog-card">
                        <a href="{{ route('website.blog', $post->slug) }}" class="gw-blog-media">
                            <img src="{{ $post->coverUrl() }}" alt="{{ $post->title }}" loading="lazy" decoding="async">
                            @if($post->category)
                                <span class="gw-blog-badge">{{ $post->category->name }}</span>
                            @endif
                        </a>
                        <div class="gw-blog-body">
                            <div class="gw-blog-meta">
                                <time datetime="{{ optional($post->published_at)->toDateString() }}">
                                    {{ optional($post->published_at)->format('M d, Y') }}
                                </time>
                                <span>{{ $post->readingTimeLabel() }}</span>
                            </div>
                            <h3>
                                <a href="{{ route('website.blog', $post->slug) }}">{{ $post->title }}</a>
                            </h3>
                            @if($post->excerpt)
                                <p class="gw-blog-excerpt">{{ $post->excerpt }}</p>
                            @endif
                            <a href="{{ route('website.blog', $post->slug) }}" class="gw-blog-more">
                                Read story
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                            </a>
                        </div>
                    </article>
                @empty
                    <div class="gw-blog-empty">
                        <p>No stories match your filters yet.</p>
                        <a href="{{ route('website.blogs') }}" class="gw-blog-clear">Browse all stories</a>
                    </div>
                @endforelse
            </div>

            @if(method_exists($blogs, 'hasPages') && $blogs->hasPages())
                <div class="gw-blog-pagination">{{ $blogs->withQueryString()->links() }}</div>
            @endif
        </div>

        <aside class="gw-blog-aside" aria-label="Blog sidebar">
            <div class="gw-blog-panel">
                <h3 class="gw-blog-panel-title">Search</h3>
                <form action="{{ route('website.blogs') }}" method="GET" class="gw-blog-search">
                    @if($activeBlogCategory)
                        <input type="hidden" name="category" value="{{ $activeBlogCategory }}">
                    @endif
                    <label class="sr-only" for="blog-search">Search articles</label>
                    <input id="blog-search" type="search" name="q" value="{{ $blogSearch }}" placeholder="Search beauty tips...">
                    <button type="submit" aria-label="Search">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </button>
                </form>
            </div>

            @if($blogCategories->count())
                <div class="gw-blog-panel">
                    <h3 class="gw-blog-panel-title">Categories</h3>
                    <ul class="gw-blog-cats">
                        <li>
                            <a href="{{ route('website.blogs', array_filter(['q' => $blogSearch])) }}"
                               class="{{ ! $activeBlogCategory ? 'is-active' : '' }}">
                                <span>All stories</span>
                                <span>{{ method_exists($blogs, 'total') && ! $hasFilters ? $blogs->total() : $blogCategories->sum('blogs_count') }}</span>
                            </a>
                        </li>
                        @foreach($blogCategories as $cat)
                            <li>
                                <a href="{{ route('website.blogs', array_filter(['category' => $cat->slug, 'q' => $blogSearch])) }}"
                                   class="{{ ($activeBlogCategory ?? '') === $cat->slug ? 'is-active' : '' }}">
                                    <span>{{ $cat->name }}</span>
                                    <span>{{ $cat->blogs_count }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if($popularPosts->count())
                <div class="gw-blog-panel">
                    <h3 class="gw-blog-panel-title">Popular reads</h3>
                    <ul class="gw-blog-popular">
                        @foreach($popularPosts as $pop)
                            <li>
                                <a href="{{ route('website.blog', $pop->slug) }}">
                                    <img src="{{ $pop->coverUrl() }}" alt="" loading="lazy" decoding="async">
                                    <span>
                                        <strong>{{ $pop->title }}</strong>
                                        <small>{{ optional($pop->published_at)->format('M d, Y') }}</small>
                                    </span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="gw-blog-panel gw-blog-panel--newsletter">
                <h3 class="gw-blog-panel-title">{{ $newsletterTitle }}</h3>
                <p>{{ $newsletterText }}</p>
                @if(session('newsletter_success'))
                    <p class="gw-blog-newsletter-ok">{{ session('newsletter_success') }}</p>
                @endif
                <form action="{{ route('website.newsletter') }}" method="POST" class="gw-blog-newsletter">
                    @csrf
                    <label class="sr-only" for="blog-newsletter-email">Email</label>
                    <input id="blog-newsletter-email" type="email" name="email" required placeholder="Your email address">
                    <button type="submit">Subscribe</button>
                </form>
            </div>
        </aside>
    </div>
</section>
@endsection
