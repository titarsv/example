@extends('public.layouts.main')
@section('page_vars')
    @include('public.layouts.microdata.open_graph', [
     'title' => $seo->meta_title,
     'description' => $seo->meta_description,
     'image' => '/images/logo.png'
     ])
@endsection

@section('content')
    <div class="section page-telegram">
        <div class="page-top">
            <div class="container">
                {!! Breadcrumbs::render('page', $page) !!}
                <h1 class="page-title">{{ $seo->name }}</h1>
                <div class="page-description">{{ $fields['subtitle'] }}</div>
            </div>
        </div>
        <div class="telegram-main">
            <div class="telegram-main__wrapper">
                @foreach($fields['blocks'] as $blocks)
                    <div>
                        <i>
                            {!! !empty($blocks->icon['image']) ? $blocks->icon['image']->image([100, 100], ['alt' => $blocks->icon['image']->alt, 'loading' => 'lazy'], 'cover', ['100px']) : '<img src="\images\larchik\no_image.jpg" alt="No image" loading="lazy">' !!}
                        </i>
                        <div>{!! $blocks->description !!}</div>
                        <a href="{{ $blocks->button_link }}" rel="nofollow" target="_blank">{{ $blocks->button_name }}</a>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    @if(!empty($fields['content_title']) || !empty($fields['content_description']))
        <div class="section section-text text-center">
            <div class="container">
                <div class="text-container">
                    @if(!empty($fields['content_title']))
                        <h2>{{ $fields['content_title'] }}</h2>
                    @endif

                    @if(!empty($fields['content_description']))
                        <div class="text-main">
                            {!! $fields['content_description'] !!}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif
@endsection
