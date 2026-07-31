@extends('public.layouts.main')
@section('meta')
    <title>{{ trans('app.news') }}</title>
    <meta name="description" content="{!! $settings->meta_description !!}">
    <meta name="keywords" content="{!! $settings->meta_keywords !!}">
@endsection
@section('page_vars')
    @include('public.layouts.microdata.open_graph', [
     'title' => trans('app.news'),
     'description' => $settings->meta_description,
     'image' => '/images/logo.png'
     ])
@endsection

@section('content')
    <div class="section page-blog">
        <div class="page-top">
            <div class="container">
                @if(empty($current_category))
                    {!! Breadcrumbs::render('blog') !!}
                @else
                    {!! Breadcrumbs::render('content_category', $current_category) !!}
                @endif
                <h1 class="page-title">{{ $seo->name }}</h1>
            </div>
        </div>
        <div class="blog-main">
            <div class="blog-categories">
                <div class="container">
                        <span class="blog-categories__title">
                            Blog categories
                            <i>
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16" fill="none">
                                  <path d="M3.33325 8H12.6666" stroke="#0B0B0B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                  <path d="M8 3.33334V12.6667" stroke="#0B0B0B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16" fill="none">
                                  <path d="M3.33325 8H12.6666" stroke="#0B0B0B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </i>
                        </span>
                    <div class="blog-categories__dropdown">
                        <a href="{{ base_url('/blog') }}">All categories</a>
                        @foreach($categories as $category)
                            <a href="{{ $category->link }}">{{ $category->name }}</a>
                        @endforeach
                    </div>
                    <span class="blog-categories__title-mob">
                            <i>
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                                    <path d="M14 4H20V10H14V4Z" stroke="#F5B700" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M4 14H10V20H4V14Z" stroke="#F5B700" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M14 17C14 17.7956 14.3161 18.5587 14.8787 19.1213C15.4413 19.6839 16.2044 20 17 20C17.7956 20 18.5587 19.6839 19.1213 19.1213C19.6839 18.5587 20 17.7956 20 17C20 16.2044 19.6839 15.4413 19.1213 14.8787C18.5587 14.3161 17.7956 14 17 14C16.2044 14 15.4413 14.3161 14.8787 14.8787C14.3161 15.4413 14 16.2044 14 17Z" stroke="#F5B700" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M4 7C4 7.39397 4.0776 7.78407 4.22836 8.14805C4.37913 8.51203 4.6001 8.84274 4.87868 9.12132C5.15726 9.3999 5.48797 9.62087 5.85195 9.77164C6.21593 9.9224 6.60603 10 7 10C7.39397 10 7.78407 9.9224 8.14805 9.77164C8.51203 9.62087 8.84274 9.3999 9.12132 9.12132C9.3999 8.84274 9.62087 8.51203 9.77164 8.14805C9.9224 7.78407 10 7.39397 10 7C10 6.60603 9.9224 6.21593 9.77164 5.85195C9.62087 5.48797 9.3999 5.15726 9.12132 4.87868C8.84274 4.6001 8.51203 4.37913 8.14805 4.22836C7.78407 4.0776 7.39397 4 7 4C6.60603 4 6.21593 4.0776 5.85195 4.22836C5.48797 4.37913 5.15726 4.6001 4.87868 4.87868C4.6001 5.15726 4.37913 5.48797 4.22836 5.85195C4.0776 6.21593 4 6.60603 4 7Z" stroke="#F5B700" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </i>
                            Blog Categories
                        </span>
                    <div class="blog-categories__mob">
                        <div class="blog-categories__mob-head">
                            <i>
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                                    <path d="M14 4H20V10H14V4Z" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M4 14H10V20H4V14Z" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M14 17C14 17.7956 14.3161 18.5587 14.8787 19.1213C15.4413 19.6839 16.2044 20 17 20C17.7956 20 18.5587 19.6839 19.1213 19.1213C19.6839 18.5587 20 17.7956 20 17C20 16.2044 19.6839 15.4413 19.1213 14.8787C18.5587 14.3161 17.7956 14 17 14C16.2044 14 15.4413 14.3161 14.8787 14.8787C14.3161 15.4413 14 16.2044 14 17Z" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M4 7C4 7.39397 4.0776 7.78407 4.22836 8.14805C4.37913 8.51203 4.6001 8.84274 4.87868 9.12132C5.15726 9.3999 5.48797 9.62087 5.85195 9.77164C6.21593 9.9224 6.60603 10 7 10C7.39397 10 7.78407 9.9224 8.14805 9.77164C8.51203 9.62087 8.84274 9.3999 9.12132 9.12132C9.3999 8.84274 9.62087 8.51203 9.77164 8.14805C9.9224 7.78407 10 7.39397 10 7C10 6.60603 9.9224 6.21593 9.77164 5.85195C9.62087 5.48797 9.3999 5.15726 9.12132 4.87868C8.84274 4.6001 8.51203 4.37913 8.14805 4.22836C7.78407 4.0776 7.39397 4 7 4C6.60603 4 6.21593 4.0776 5.85195 4.22836C5.48797 4.37913 5.15726 4.6001 4.87868 4.87868C4.6001 5.15726 4.37913 5.48797 4.22836 5.85195C4.0776 6.21593 4 6.60603 4 7Z" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </i>
                            Blog Categories
                            <span class="blog-categories__close">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                                      <path d="M18 6L6 18" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                      <path d="M6 6L18 18" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </span>
                        </div>
                        <div class="blog-categories__mob-main">
                            <a href="{{ base_url('/blog') }}">All categories</a>
                            @foreach($categories as $category)
                                <a href="{{ $category->link }}">{{ $category->name }}</a>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
            <div class="blog-wrapper">
                @foreach($articles as $i => $article)
                    <a href="{{ $article->link() }}" class="blog-item">
                        <div class="blog-item__picture">
                            <div>
                                {!! !empty($article->image) ? $article->image->image([1414, 600], ['alt' => $article->name, 'loading' => 'lazy'], 'cover', ['<575' => '100vw', '50vw']) : '<img src="\images\larchik\no_image.jpg" alt="No image" loading="lazy">' !!}
                            </div>
                            @foreach($article->categories as $category)
                                <span class="blog-item__label">
                                        @if(!empty($category->color))
                                        <i style="background:{{ $category->color }};"></i>
                                    @endif
                                    {{ $category->name }}
                                    </span>
                            @endforeach
                        </div>
                        <div class="blog-item__info">
                            <span class="blog-item__title">{{ $article->name }}</span>
                            <div class="blog-item__specs">
                                <span>{{ date('F d, Y', strtotime($article->created_at)) }}</span>
                                @if(!empty($article->reading_time))
                                    <span>{{ $article->reading_time }} {{ __('minutes read') }}</span>
                                @endif
                            </div>
                            <div class="blog-item__description">
                                {!! $article->excerpt !!}
                            </div>
                            <div class="blog-item__btn">
                                Read More
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                                    <path d="M12 5L19 12L12 19" stroke="#0B0B0B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M5 12L19 12" stroke="#0B0B0B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
            @include('public.layouts.pagination', ['paginator' => $articles, 'without_js' => true])
        </div>
    </div>
    {{--        <div class="section section-news">--}}
    {{--            <div class="container">--}}
    {{--                <div class="section-head">--}}
    {{--                    <h2 class="section-title">Latest news</h2>--}}
    {{--                    <a class="link-all" href="{{ base_url('/blog') }}">View All</a>--}}
    {{--                </div>--}}
    {{--            </div>--}}
    {{--            <div class="news-slider slick-slider" data-slick='{"slidesToShow": 4, "slidesToScroll": 1, "infinite": true, "arrows": true, "dots": true, "prevArrow": "#news-slider-prev", "nextArrow": "#news-slider-next", "appendDots": "#news-slider-dots", "responsive":[{"breakpoint":1199,"settings":{"slidesToShow": 3}}, {"breakpoint":991,"settings":{"slidesToShow": 2}}, {"breakpoint":575,"settings":{"slidesToShow": 1}}]}'>--}}
    {{--                @foreach($latest_articles as $latest_article)--}}
    {{--                    <div class="slide">--}}
    {{--                        <a href="{{ $latest_article->link() }}" class="news-item">--}}
    {{--                            <div class="news-item__photo">--}}
    {{--                                @foreach($latest_article->categories as $category)--}}
    {{--                                    <span class="news-item__label">--}}
    {{--                                        <i style="background:#00CFA8;"></i>--}}
    {{--                                        {{ $category->name }}--}}
    {{--                                    </span>--}}
    {{--                                @endforeach--}}
    {{--                                {!! !empty($latest_article->image) ? $latest_article->image->image([480, 383], ['alt' => $latest_article->name, 'loading' => 'lazy'], 'cover', ['<629' => '50vw', '<837' => '33.3333vw', '25vw']) : '<img src="\images\larchik\no_image.jpg" alt="No image" loading="lazy">' !!}--}}
    {{--                            </div>--}}
    {{--                            <div class="news-item__info">--}}
    {{--                                <span class="news-item__title">{{ $latest_article->name }}</span>--}}
    {{--                                <div class="news-item__bot">--}}
    {{--                                    <span>{{ date('F d, Y', strtotime($latest_article->created_at)) }}</span>--}}
    {{--                                    @if(!empty($latest_article->reading_time))--}}
    {{--                                        <span>{{ $latest_article->reading_time }} minutes read</span>--}}
    {{--                                    @endif--}}
    {{--                                </div>--}}
    {{--                            </div>--}}
    {{--                        </a>--}}
    {{--                    </div>--}}
    {{--                @endforeach--}}
    {{--            </div>--}}
    {{--            <div class="custom-slider__controls">--}}
    {{--                <div class="container">--}}
    {{--                    <div class="dots" id="news-slider-dots"></div>--}}
    {{--                    <div class="arrows">--}}
    {{--                        <div id="news-slider-prev">--}}
    {{--                            <svg xmlns="http://www.w3.org/2000/svg" width="56" height="56" viewBox="0 0 56 56" fill="none">--}}
    {{--                                <path d="M28.0001 44.3333L11.6667 28L28.0001 11.6666" stroke="#0B0B0B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>--}}
    {{--                                <path d="M44.3334 28H11.6667" stroke="#0B0B0B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>--}}
    {{--                            </svg>--}}
    {{--                        </div>--}}
    {{--                        <div id="news-slider-next">--}}
    {{--                            <svg xmlns="http://www.w3.org/2000/svg" width="56" height="56" viewBox="0 0 56 56" fill="none">--}}
    {{--                                <path d="M27.9999 11.6667L44.3333 28L27.9999 44.3334" stroke="#0B0B0B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>--}}
    {{--                                <path d="M11.6666 28L44.3333 28" stroke="#0B0B0B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>--}}
    {{--                            </svg>--}}
    {{--                        </div>--}}
    {{--                    </div>--}}
    {{--                </div>--}}
    {{--            </div>--}}
    {{--        </div>--}}
@endsection
