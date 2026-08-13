@extends('public.layouts.main')
@section('page_vars')
    @include('public.layouts.microdata.open_graph', [
     'title' => $seo->meta_title,
     'description' => $seo->meta_description,
     'image' => theme_asset('images/favicon.png')
     ])
@endsection

@section('content')
    {!! !empty($article->image) ? $article->image->image([2880, 1080], ['alt' => $article->name, 'loading' => 'lazy', 'class' => 'w-100', 'style' => 'max-height: 420px; object-fit: cover;'], 'cover', ['100vw']) : '' !!}

    <div class="container py-4">
        <div class="mb-3">{!! Breadcrumbs::render('blog_item', $article) !!}</div>

        @foreach($article->categories as $category)
            <span class="badge text-bg-secondary">{{ $category->name }}</span>
        @endforeach

        <h1 class="h3 mt-2">{{ $seo->name }}</h1>
        <div class="text-muted mb-4">
            {{ date('d.m.Y', strtotime($article->created_at)) }}
            @if(!empty($article->reading_time))
                &middot; {{ $article->reading_time }} {{ __('minutes read') }}
            @endif
        </div>

        <div class="mb-4">{!! html_entity_decode($article->body) !!}</div>

        <div class="d-flex justify-content-between border-top pt-3">
            @if(!empty($prev))
                <a href="{{ $prev->link() }}" class="text-decoration-none">&larr; Предыдущая статья</a>
            @else
                <span></span>
            @endif
            @if(!empty($next))
                <a href="{{ $next->link() }}" class="text-decoration-none">Следующая статья &rarr;</a>
            @endif
        </div>
    </div>

    @if($latest_articles->count())
        <div class="container py-4 border-top">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h4 mb-0">Последние новости</h2>
                <a href="{{ base_url('/blog') }}" class="text-decoration-none">Смотреть все &rarr;</a>
            </div>
            <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-3">
                @foreach($latest_articles as $latest_article)
                    <div class="col">
                        <a href="{{ $latest_article->link() }}" class="card h-100 text-decoration-none text-body">
                            {!! !empty($latest_article->image) ? $latest_article->image->image([480, 383], ['alt' => $latest_article->name, 'loading' => 'lazy', 'class' => 'card-img-top', 'style' => 'aspect-ratio: 16/9; object-fit: cover;'], 'cover', ['<629' => '50vw', '<837' => '33.3333vw', '25vw']) : '<img src="/images/larchik/no_image.jpg" alt="Нет фото" class="card-img-top" loading="lazy">' !!}
                            <div class="card-body">
                                @foreach($latest_article->categories as $category)
                                    <span class="badge text-bg-secondary mb-2">{{ $category->name }}</span>
                                @endforeach
                                <div class="card-title">{{ $latest_article->name }}</div>
                                <div class="text-muted small">
                                    {{ date('d.m.Y', strtotime($latest_article->created_at)) }}
                                    @if(!empty($latest_article->reading_time))
                                        &middot; {{ $latest_article->reading_time }} {{ __('minutes read') }}
                                    @endif
                                </div>
                            </div>
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
@endsection
