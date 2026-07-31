@extends('public.layouts.main')
@section('page_vars')
    @include('public.layouts.microdata.open_graph', [
     'title' => $seo->meta_title,
     'description' => $seo->meta_description,
     'image' => '/images/logo.png'
     ])
@endsection

@section('content')
    <div class="page-strains">
        <div class="page-top">
            <div class="container">
                {!! Breadcrumbs::render('page', $page) !!}
                <h1 class="page-title">{{ $seo->name }}</h1>
                <div class="page-description">
                    {!! $seo->description !!}
                </div>
            </div>
        </div>
        <div class="strains-main">
            <div class="strains-main__wrapper">
                @foreach($fields['strains'] as $strains)
                    <a href="{{ $strains->link }}" class="strains-item">
                        <div class="strains-item__top">
                            <i>
                                {!! !empty($strains->image['image']) ? $strains->image['image']->image([70, 106], ['alt' => $strains->name, 'loading' => 'lazy'], 'contain') : '<img src="\images\larchik\no_image.jpg" alt="No image" loading="lazy">' !!}
                            </i>
                            <span>{{ $strains->name }}
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M12 5L19 12L12 19" stroke="#0B0B0B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M4.99805 11.9995L18.998 11.9995" stroke="#0B0B0B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </span>
                        </div>
                        <div class="strains-item__description">{{ $strains->description }}</div>
                    </a>
                @endforeach
                <a href="{{ base_url('/') }}" class="strains-item">
                    <div class="strains-item__logo">
                    <span>
                        <img src="/images/strains-logo.svg" class="lazy" alt="">
                    </span>
                    </div>
                </a>
            </div>
        </div>
        <div class="strains-text">
            <div class="section-text">
                <div class="container">
                    <div class="text-container">
                        <h2>{{ $fields['seo_title'] }}</h2>
                        <div class="text-main">
                            {!! $fields['seo_text'] !!}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
