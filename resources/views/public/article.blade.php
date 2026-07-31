@extends('public.layouts.main')
@section('page_vars')
    @include('public.layouts.microdata.open_graph', [
     'title' => $seo->meta_title,
     'description' => $seo->meta_description,
     'image' => '/images/logo.png'
     ])
@endsection

@section('content')
    <div class="section page-article">
        {!! !empty($article->image) ? $article->image->image([2880, 1080], ['alt' => $article->name, 'loading' => 'lazy'], 'cover', ['100vw']) : '<img src="\images\larchik\no_image.jpg" alt="No image" loading="lazy">' !!}
        <div class="article-wrapper">
            <div class="article-main">
                {!! Breadcrumbs::render('blog_item', $article) !!}
                @foreach($article->categories as $category)
                    <span class="article-label">
                            <i style="background:#00CFA8;"></i>
                            {{ $category->name }}
                        </span>
                @endforeach
                <h1>{{ $seo->name }}</h1>
                <div class="article-specs">
                    <span>{{ date('F d, Y', strtotime($article->created_at)) }}</span>
                    @if(!empty($article->reading_time))
                        <span>{{ $article->reading_time }} {{ __('minutes read') }}</span>
                    @endif
                </div>
                <div class="article-text">{!! html_entity_decode($article->body) !!}</div>
            </div>
            <div class="article-navigation">
                @if(empty($prev))
                    <span></span>
                @else
                    <a href="{{ $prev->link() }}" class="article-navigation__btn">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                            <path d="M12 19L5 12L12 5" stroke="#0B0B0B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M19 12H5" stroke="#0B0B0B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        Previous Article
                    </a>
                @endif
                @if(!empty($next))
                    <a href="{{ $next->link() }}" class="article-navigation__btn">
                        Next Article
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                            <path d="M12 5L19 12L12 19" stroke="#0B0B0B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M5 12L19 12" stroke="#0B0B0B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </a>
                @endif
            </div>
        </div>
    </div>
    <div class="section section-news">
        <div class="container">
            <div class="section-head">
                <h2 class="section-title">Latest news</h2>
                <a class="link-all" href="{{ base_url('/blog') }}">View All</a>
            </div>
        </div>
        <div class="news-slider slick-slider" data-slick='{"slidesToShow": 4, "slidesToScroll": 1, "infinite": true, "arrows": true, "dots": true, "prevArrow": "#news-slider-prev", "nextArrow": "#news-slider-next", "appendDots": "#news-slider-dots", "responsive":[{"breakpoint":1199,"settings":{"slidesToShow": 3}}, {"breakpoint":991,"settings":{"slidesToShow": 2}}, {"breakpoint":575,"settings":{"slidesToShow": 1}}]}'>
            @foreach($latest_articles as $latest_article)
                <div class="slide">
                    <a href="{{ $latest_article->link() }}" class="news-item">
                        <div class="news-item__photo">
                            @foreach($latest_article->categories as $category)
                                <span class="news-item__label">
                                        <i style="background:#00CFA8;"></i>
                                        {{ $category->name }}
                                    </span>
                            @endforeach
                            {!! !empty($latest_article->image) ? $latest_article->image->image([480, 383], ['alt' => $latest_article->name, 'loading' => 'lazy'], 'cover', ['<629' => '50vw', '<837' => '33.3333vw', '25vw']) : '<img src="\images\larchik\no_image.jpg" alt="No image" loading="lazy">' !!}
                        </div>
                        <div class="news-item__info">
                            <span class="news-item__title">{{ $latest_article->name }}</span>
                            <div class="news-item__bot">
                                <span>{{ date('F d, Y', strtotime($latest_article->created_at)) }}</span>
                                @if(!empty($latest_article->reading_time))
                                    <span>{{ $latest_article->reading_time }} minutes read</span>
                                @endif
                            </div>
                        </div>
                    </a>
                </div>
            @endforeach
        </div>
        <div class="custom-slider__controls">
            <div class="container">
                <div class="dots" id="news-slider-dots"></div>
                <div class="arrows">
                    <div id="news-slider-prev">
                        <svg xmlns="http://www.w3.org/2000/svg" width="56" height="56" viewBox="0 0 56 56" fill="none">
                            <path d="M28.0001 44.3333L11.6667 28L28.0001 11.6666" stroke="#0B0B0B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M44.3334 28H11.6667" stroke="#0B0B0B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <div id="news-slider-next">
                        <svg xmlns="http://www.w3.org/2000/svg" width="56" height="56" viewBox="0 0 56 56" fill="none">
                            <path d="M27.9999 11.6667L44.3333 28L27.9999 44.3334" stroke="#0B0B0B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M11.6666 28L44.3333 28" stroke="#0B0B0B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
