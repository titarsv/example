@extends('public.layouts.main')
@section('page_vars')
    @include('public.layouts.microdata.open_graph', [
     'title' => $seo->meta_title,
     'description' => $seo->meta_description,
     'image' => '/images/logo.png'
     ])
@endsection

@section('content')
    <div class="section page-about">
        <div class="page-top">
            <div class="container">
                {!! Breadcrumbs::render('page', $page) !!}
                <h1 class="page-title">{{ $seo->name }}</h1>
                <div class="page-description">{!! $fields['subtitle'] !!}</div>
            </div>
        </div>
        <div class="about-main">
            <div class="about-main__wrapper">
                @foreach($fields['blocks'] as $blocks)
                    <div>
                        <i>
                            {!! !empty($blocks->image['image']) ? $blocks->image['image']->image([100, 100], ['alt' => $blocks->title, 'loading' => 'lazy'], 'cover', ['<767' => '50vw', '<991' => '33.3333vw',  '<1199' => '25vw', '20vw']) : '<img src="\images\larchik\no_image.jpg" alt="No image" loading="lazy">' !!}
                        </i>
                        <span>{!! $blocks->title !!}</span>
                        <div>{!! $blocks->description !!}</div>
                    </div>
                @endforeach
            </div>
            <div class="about-thx">
                <span class="section-title">{!! $fields['thanks_title'] !!}</span>
                <div class="about-thx__text">{!! $fields['thanks_description'] !!}</div>
            </div>
            <div class="about-footnote">
                <span>{!! $fields['shop_link_title'] !!}</span>
                <a href="{!! $fields['shop_link_url'] !!}">{!! $fields['shop_link_name'] !!}</a>
            </div>
        </div>
    </div>
@endsection
