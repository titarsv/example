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
        <h1 class="h3 mb-4">{{ $seo->name }}</h1>
        <div class="row">
            <div class="col-lg-8">
                {!! $fields['description'] !!}
            </div>
        </div>
    </div>
@endsection
