@extends('public.layouts.main')
@section('page_vars')
    @include('public.layouts.microdata.open_graph', [
     'title' => $seo->meta_title,
     'description' => $seo->meta_description,
     'image' => '/images/logo.png'
     ])
@endsection

@section('content')
    <div class="page-text-info">
        <div class="page-top">
            <div class="container">
                {!! Breadcrumbs::render('page', $page) !!}
                <h1 class="page-title">{{ $seo->name }}</h1>
            </div>
        </div>
        <div class="text-info-main">
            <div class="container">
                <div class="row">
                    <div class="col-lg-8">
                        {!! $fields['description'] !!}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
