@extends('public.layouts.main')
@section('page_vars')
    @include('public.layouts.microdata.open_graph', [
     'title' => $seo->meta_title,
     'description' => $seo->meta_description,
     'image' => theme_asset('images/favicon.png')
     ])
@endsection

@section('content')
    <div class="container py-4">
        <div class="mb-3">{!! Breadcrumbs::render('page', $page) !!}</div>
        <h1 class="h3">{{ $seo->name }}</h1>
        <div class="text-muted mb-4">{!! $fields['subtitle'] !!}</div>

        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-4 mb-5">
            @foreach($fields['blocks'] as $blocks)
                <div class="col text-center">
                    <div class="mb-3" style="width: 80px; margin: 0 auto;">
                        {!! !empty($blocks->image['image']) ? $blocks->image['image']->image([100, 100], ['alt' => $blocks->title, 'loading' => 'lazy', 'class' => 'img-fluid'], 'cover', ['80px']) : '<img src="/images/larchik/no_image.jpg" alt="Нет фото" class="img-fluid" loading="lazy">' !!}
                    </div>
                    <div class="fw-semibold mb-1">{!! $blocks->title !!}</div>
                    <div class="text-muted small">{!! $blocks->description !!}</div>
                </div>
            @endforeach
        </div>

        <div class="card bg-light border-0 mb-4">
            <div class="card-body text-center py-5">
                <h2 class="h4">{!! $fields['thanks_title'] !!}</h2>
                <div class="text-muted">{!! $fields['thanks_description'] !!}</div>
            </div>
        </div>

        <div class="text-center">
            <span class="text-muted">{!! $fields['shop_link_title'] !!}</span>
            <a href="{!! $fields['shop_link_url'] !!}" class="btn btn-primary ms-2">{!! $fields['shop_link_name'] !!}</a>
        </div>
    </div>
@endsection