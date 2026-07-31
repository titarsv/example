@extends('public.layouts.main')
@section('page_vars')
    @include('public.layouts.microdata.open_graph', [
     'title' => $seo->meta_title,
     'description' => $seo->meta_description,
     'image' => '/images/logo.png'
     ])
@endsection

@section('content')
    <div class="section page-city">
        <div class="page-top">
            <div class="container">
                {!! Breadcrumbs::render('page', $page) !!}
                <h1 class="page-title">{{ $seo->name }}</h1>
            </div>
        </div>
    </div>
    <div class="section section-info">
        <div class="container">
            {!! $page->body !!}
        </div>
    </div>
@endsection
