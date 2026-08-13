@extends('public.layouts.main')
@section('page_vars')
    @include('public.layouts.microdata.open_graph', [
     'title' => $seo->meta_title,
     'description' => $seo->meta_description,
     'image' => theme_asset('images/favicon.png')
     ])
@endsection

@section('content')
    <div class="hero py-5 mb-5">
        <div class="container py-4 text-center">
            <h1 class="display-5 fw-bold mb-3">{{ $seo->name }}</h1>
            @if(!empty($fields['slider_text']))
                <p class="lead text-muted mb-4">{{ $fields['slider_text'] }}</p>
            @endif
            <a href="{{ base_url('/catalog') }}" class="btn btn-primary btn-lg">Смотреть каталог</a>
        </div>
    </div>

    <div class="container mb-5">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h4 mb-0">Категории</h2>
            <a href="{{ base_url('/catalog') }}" class="text-decoration-none">Смотреть всё &rarr;</a>
        </div>
        <div class="row row-cols-2 row-cols-md-3 row-cols-lg-4 g-3">
            @foreach($categories as $category)
                <div class="col">
                    <a href="{{ $category->link() }}" class="card text-decoration-none h-100 product-card">
                        @if(!empty($category->image))
                            {!! $category->image->image([456, 456], ['alt' => $category->name, 'loading' => 'lazy', 'class' => 'card-img-top product-card__image'], 'cover', ['456px']) !!}
                        @else
                            <img src="/images/larchik/no_image.jpg" alt="Нет фото" class="card-img-top product-card__image" loading="lazy">
                        @endif
                        <div class="card-body text-center">
                            <div class="card-title text-body mb-0">{{ $category->name }} <span class="text-muted">({{ $category->products_count }})</span></div>
                        </div>
                    </a>
                </div>
            @endforeach
        </div>
    </div>

    @if($favorites->count())
        <div class="container mb-5">
            <h2 class="h4 mb-3">Хиты продаж</h2>
            <div class="position-relative px-4">
                <div class="js-products-slider">
                    @foreach($favorites as $favorite)
                        <div>
                            @include('public.layouts.product', ['product' => $favorite])
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    @if(count($articles) > 0)
        <div class="container mb-5">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h4 mb-0">Последние новости</h2>
                <a href="{{ base_url('/blog') }}" class="text-decoration-none">Смотреть все &rarr;</a>
            </div>
            <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-3">
                @foreach($articles as $article)
                    <div class="col">
                        <a href="{{ $article->link() }}" class="card text-decoration-none h-100">
                            {!! !empty($article->image) ? $article->image->image([1414, 600], ['alt' => $article->name, 'loading' => 'lazy', 'class' => 'card-img-top', 'style' => 'aspect-ratio: 16/9; object-fit: cover;'], 'cover', ['<575' => '100vw', '50vw']) : '<img src="/images/larchik/no_image.jpg" alt="Нет фото" class="card-img-top" loading="lazy">' !!}
                            <div class="card-body">
                                @foreach($article->categories as $category)
                                    <span class="badge text-bg-secondary mb-2">{{ $category->name }}</span>
                                @endforeach
                                <div class="card-title text-body">{{ $article->name }}</div>
                                <div class="text-muted small">
                                    {{ date('d.m.Y', strtotime($article->created_at)) }}
                                    @if(!empty($article->reading_time))
                                        &middot; {{ $article->reading_time }} {{ __('minutes read') }}
                                    @endif
                                </div>
                            </div>
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    @if(count($reviews) > 0)
        <div class="container mb-5">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <div>
                    <h2 class="h4 mb-1">Отзывы покупателей</h2>
                    @include('public.layouts.rating-stars', ['rating' => $reviews_grade])
                    <strong>({{ number_format($reviews_grade, 1, '.') }})</strong>
                    <span class="text-muted">на основе {{ $reviews_count }} отзывов</span>
                </div>
                <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#siteReviewModal">Оставить отзыв</button>
            </div>
            <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-3">
                @foreach($reviews as $review)
                    <div class="col">
                        <div class="card h-100">
                            <div class="card-body">
                                <p class="card-text">{{ $review->review }}</p>
                                <div class="fw-semibold small">{{ $review->author }}</div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    @if(!empty($seo->name) && !empty($seo->description))
        <div class="container mb-5">
            <div class="text-muted">
                {!! $seo->description !!}
            </div>
        </div>
    @endif

    @include('public.layouts.site_reviews_form')
@endsection
