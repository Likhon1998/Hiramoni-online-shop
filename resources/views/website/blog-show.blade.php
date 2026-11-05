@extends('website.layout')
@php
    $storeName = $settings->store_name ?? config('app.name', 'Hira Moni');
@endphp
@section('title', $blog->title.' — '.$storeName)
@section('meta_description', $blog->excerpt ?: \Illuminate\Support\Str::limit(strip_tags((string) $blog->body), 160))
@section('og_type', 'article')
@if($blog->cover_image)
    @section('og_image', $blog->coverUrl())
@endif
@section('content')

<article class="gw-article">
    <div class="tn-container">
        <nav class="gw-article-crumbs" aria-label="Breadcrumb">
            <a href="{{ route('home') }}">Home</a>
            <span aria-hidden="true">/</span>
            <a href="{{ route('website.blogs') }}">Blog</a>
            @if($blog->category)
                <span aria-hidden="true">/</span>
                <a href="{{ route('website.blogs', ['category' => $blog->category->slug]) }}">{{ $blog->category->name }}</a>
            @endif
            <span aria-hidden="true">/</span>
            <span>{{ \Illuminate\Support\Str::limit($blog->title, 42) }}</span>
        </nav>

        <div class="gw-article-layout">
            <div class="gw-article-main">
                <header class="gw-article-head">
                    @if($blog->category)
                        <a href="{{ route('website.blogs', ['category' => $blog->category->slug]) }}" class="gw-blog-badge">{{ $blog->category->name }}</a>
                    @endif
                    <h1>{{ $blog->title }}</h1>
                    <div class="gw-article-meta">
                        @if($blog->author_name)
                            <span>By {{ $blog->author_name }}</span>
                        @endif
                        <time datetime="{{ optional($blog->published_at)->toDateString() }}">
                            {{ optional($blog->published_at)->format('M d, Y') }}
                        </time>
                        <span>{{ $blog->readingTimeLabel() }}</span>
                    </div>
                </header>

                <figure class="gw-article-cover">
                    <img src="{{ $blog->coverUrl() }}" alt="{{ $blog->title }}" decoding="async">
                </figure>

                @if($blog->excerpt)
                    <p class="gw-article-lead">{{ $blog->excerpt }}</p>
                @endif

                <div class="gw-article-body">
                    {!! nl2br(e($blog->body)) !!}
                </div>

                <footer class="gw-article-foot">
                    <a href="{{ route('website.blogs') }}" class="gw-article-back">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                        Back to all stories
                    </a>
                    <a href="{{ route('website.shop') }}" class="gw-article-shop">Shop the look</a>
                </footer>

                @if(($relatedPosts ?? collect())->count())
                    <section class="gw-article-related" aria-labelledby="gw-related-heading">
                        <h2 id="gw-related-heading">Related stories</h2>
                        <div class="gw-article-related-grid">
                            @foreach($relatedPosts as $rel)
                                <a href="{{ route('website.blog', $rel->slug) }}" class="gw-article-related-card">
                                    <img src="{{ $rel->coverUrl() }}" alt="" loading="lazy" decoding="async">
                                    <span>
                                        @if($rel->category)
                                            <small>{{ $rel->category->name }}</small>
                                        @endif
                                        <strong>{{ $rel->title }}</strong>
                                    </span>
                                </a>
                            @endforeach
                        </div>
                    </section>
                @endif
            </div>

            <aside class="gw-blog-aside" aria-label="Article sidebar">
                @if(($blogCategories ?? collect())->count())
                    <div class="gw-blog-panel">
                        <h3 class="gw-blog-panel-title">Categories</h3>
                        <ul class="gw-blog-cats">
                            @foreach($blogCategories as $cat)
                                <li>
                                    <a href="{{ route('website.blogs', ['category' => $cat->slug]) }}"
                                       class="{{ optional($blog->category)->slug === $cat->slug ? 'is-active' : '' }}">
                                        <span>{{ $cat->name }}</span>
                                        <span>{{ $cat->blogs_count }}</span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if(($popularPosts ?? collect())->count())
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

                <a href="{{ route('website.blogs') }}" class="gw-article-aside-cta">Browse the journal</a>
            </aside>
        </div>
    </div>
</article>
@endsection
