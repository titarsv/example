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

        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
            <div>
                <h1 class="h3 mb-1">{{ $seo->name }}</h1>
                @include('public.layouts.rating-stars', ['rating' => $reviews_grade])
                <strong>({{ number_format($reviews_grade, 1, '.') }})</strong>
                <span class="text-muted">на основе {{ $reviews_count }} отзывов</span>
            </div>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#siteReviewModal">Оставить отзыв</button>
        </div>

        @include('public.layouts.site_reviews')
    </div>
    @include('public.layouts.site_reviews_form')
@endsection
