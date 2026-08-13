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
     'image' => theme_asset('images/favicon.png')
     ])
@endsection

@section('content')
    <div class="container py-4">
        <div class="mb-3">
            @if(empty($current_category))
                {!! Breadcrumbs::render('blog') !!}
            @else
                {!! Breadcrumbs::render('content_category', $current_category) !!}
            @endif
        </div>

        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
            <h1 class="h3 mb-0">{{ $seo->name }}</h1>
            <div class="dropdown">
                <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    Категории блога
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="{{ base_url('/blog') }}">Все категории</a></li>
                    @foreach($categories as $category)
                        <li><a class="dropdown-item" href="{{ $category->link }}">{{ $category->name }}</a></li>
                    @endforeach
                </ul>
            </div>
        </div>

        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
            @foreach($articles as $article)
                <div class="col">
                    <a href="{{ $article->link() }}" class="card h-100 text-decoration-none text-body">
                        {!! !empty($article->image) ? $article->image->image([1414, 600], ['alt' => $article->name, 'loading' => 'lazy', 'class' => 'card-img-top', 'style' => 'aspect-ratio: 16/9; object-fit: cover;'], 'cover', ['<575' => '100vw', '50vw']) : '<img src="/images/larchik/no_image.jpg" alt="Нет фото" class="card-img-top" loading="lazy">' !!}
                        <div class="card-body">
                            @foreach($article->categories as $category)
                                <span class="badge text-bg-secondary mb-2">{{ $category->name }}</span>
                            @endforeach
                            <div class="card-title">{{ $article->name }}</div>
                            <div class="text-muted small mb-2">
                                {{ date('d.m.Y', strtotime($article->created_at)) }}
                                @if(!empty($article->reading_time))
                                    &middot; {{ $article->reading_time }} {{ __('minutes read') }}
                                @endif
                            </div>
                            <div class="card-text text-muted small">{!! $article->excerpt !!}</div>
                        </div>
                    </a>
                </div>
            @endforeach
        </div>

        <div class="mt-4">
            @include('public.layouts.pagination', ['paginator' => $articles])
        </div>
    </div>
@endsection
