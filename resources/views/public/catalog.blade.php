@extends('public.layouts.main', ['pagination' => $products, 'root_category' => !empty($category) ? $category->get_root_category() : null, 'css' => 'css/critical/catalog.min.css', 'critical_css' => 'css/critical/catalog_header.min.css'])
@section('page_vars')
    @if(!empty($category))
        <script>
            dataLayer = [{
                "page": {
                    "type": "category",
                    "categoryId": "{{ $category->id }}"
                }
            }];
        </script>
        @include('public.layouts.microdata.category', ['category' => $category])
    @endif
    <script>
        var fbqProductsData = [];
    </script>
    <!-- Код тега ремаркетинга Google -->
    <script>
        var google_tag_params = {
            ecomm_prodid: [{{ implode(', ', $products->pluck('id')->toArray()) }}],
            ecomm_pagetype: 'category',
            ecomm_totalvalue: [{{ implode(', ', $products->pluck('price')->toArray()) }}],
        };
    </script>
    @include('public.layouts.microdata.open_graph', [
     'title' => $seo->meta_title,
     'description' => $seo->meta_description
     ])
@endsection

@section('content')
    <div class="section page-catalog">
        <div class="page-top">
            <div class="container">
                @if(!empty($additional_crumb))
                    {!! Breadcrumbs::render('filter', $category, $additional_crumb) !!}
                @elseif(!empty($category))
                    {!! Breadcrumbs::render('categories', $category) !!}
                @else
                    {!! Breadcrumbs::render('catalog') !!}
                @endif
                <h1 class="page-title">{{ !empty($seo->getAttributes()['name']) ? $seo->getAttributes()['name'] : $seo->name }}</h1>
            </div>
        </div>
        <div class="catalog-main">
            <div class="catalog-categories">
                <div class="catalog-categories__arrow arrow-left">
                    <svg xmlns="http://www.w3.org/2000/svg" width="56" height="56" viewBox="0 0 56 56" fill="none">
                        <path d="M28.0001 44.3333L11.6667 28L28.0001 11.6666" stroke="#0B0B0B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M44.3334 28H11.6667" stroke="#0B0B0B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <div class="catalog-categories__arrow arrow-right">
                    <svg xmlns="http://www.w3.org/2000/svg" width="56" height="56" viewBox="0 0 56 56" fill="none">
                        <path d="M27.9999 11.6667L44.3333 28L27.9999 44.3334" stroke="#0B0B0B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M11.6666 28L44.3333 28" stroke="#0B0B0B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <div class="catalog-categories__list">
                    <ul>
                        @if(empty($category))
                            <li class="current">
                                <span>Shop All</span>
                            </li>
                        @else
                            <li>
                                <a href="{{ base_url('/catalog') }}">Shop All</a>
                            </li>
                        @endif

                        @foreach($categories as $cat)
                            @if(!empty($category) && $category->id == $cat->id)
                                <li class="current">
                                    <span>{{ $cat->name }}</span>
                                </li>
                            @else
                                <li>
                                    <a href="{{ $cat->link() }}">{{ $cat->name }}</a>
                                </li>
                            @endif
                        @endforeach
                    </ul>
                </div>
            </div>
            @if(!empty($effects))
                <div class="container">
                    <div class="effects-wrapper row">
                        <svg class="effect-svg" xmlns='http://www.w3.org/2000/svg' style="width: 0;height: 0;">
                            <filter id='noiseFilter'>
                                <feTurbulence type='fractalNoise' baseFrequency='0.65' numOctaves='3' stitchTiles='stitch' />
                            </filter>
                        </svg>
                        @foreach($effects as $effect)
                            <div class="col{!! request()->is('*/effects-' . $effect->value) ? ' active' : '' !!}">
                                <a href="{{ base_url('/catalog/effects-'. $effect->value) }}">
                                    <div>
{{--                                        <img src="{{ $effect->image->url() }}" loading="lazy" alt="{{ $effect->filter_name }}">--}}
                                        {!! $effect->image->image(
                                            [306, 306],
                                            ['alt' => $effect->filter_name, 'loading' => 'lazy', 'width' => 306, 'height' => 306],
                                            'cover',
                                            [
                                                '<575' => '90px',
                                                '<767' => '25vw - 30px',
                                                '<991' => '20vw - 30px',
                                                '12.5vw - 30px'
                                            ]
                                        ) !!}
                                        <svg viewBox="0 0 200 200" xmlns='http://www.w3.org/2000/svg'>
                                            <rect width='100%' height='100%' filter='url(#noiseFilter)' />
                                        </svg>
                                    </div>
                                    <span>{{ $effect->name }}</span>
                                </a>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
            <div class="catalog-filters">
                <div class="container">
                    <span class="catalog-filters__counter js_total_products_count">{{ trans_choice('app.products_found', $products->total(), [':count' => $products->total()], app()->getLocale()) }}</span>
                    <span class="catalog-filters__btn">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                              <path d="M10.5 6H20.25M10.5 6C10.5 6.39782 10.342 6.77936 10.0607 7.06066C9.77936 7.34196 9.39782 7.5 9 7.5C8.60218 7.5 8.22064 7.34196 7.93934 7.06066C7.65804 6.77936 7.5 6.39782 7.5 6M10.5 6C10.5 5.60218 10.342 5.22064 10.0607 4.93934C9.77936 4.65804 9.39782 4.5 9 4.5C8.60218 4.5 8.22064 4.65804 7.93934 4.93934C7.65804 5.22064 7.5 5.60218 7.5 6M7.5 6H3.75M10.5 18H20.25M10.5 18C10.5 18.3978 10.342 18.7794 10.0607 19.0607C9.77936 19.342 9.39782 19.5 9 19.5C8.60218 19.5 8.22064 19.342 7.93934 19.0607C7.65804 18.7794 7.5 18.3978 7.5 18M10.5 18C10.5 17.6022 10.342 17.2206 10.0607 16.9393C9.77936 16.658 9.39782 16.5 9 16.5C8.60218 16.5 8.22064 16.658 7.93934 16.9393C7.65804 17.2206 7.5 17.6022 7.5 18M7.5 18H3.75M16.5 12H20.25M16.5 12C16.5 12.3978 16.342 12.7794 16.0607 13.0607C15.7794 13.342 15.3978 13.5 15 13.5C14.6022 13.5 14.2206 13.342 13.9393 13.0607C13.658 12.7794 13.5 12.3978 13.5 12M16.5 12C16.5 11.6022 16.342 11.2206 16.0607 10.9393C15.7794 10.658 15.3978 10.5 15 10.5C14.6022 10.5 14.2206 10.658 13.9393 10.9393C13.658 11.2206 13.5 11.6022 13.5 12M13.5 12H3.75" stroke="#0B0B0B" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            Filter & Sort
                        </span>
                    @if(!empty($category))
                        <input type="hidden" value="{{ $category->id }}" id="js_category">
                    @endif
                    <nav class="catalog-filters__bar" id="js_filters">
                        @include('public.layouts.filters')
                    </nav>
                    @include('public.layouts.selected_filters')
                </div>
            </div>
            <div class="catalog-filters__overlay"></div>
            <div class="catalog-filters__mob">
                <div class="catalog-filters__mob-btn">
                    <i>
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                            <path d="M10.5 6H20.25M10.5 6C10.5 6.39782 10.342 6.77936 10.0607 7.06066C9.77936 7.34196 9.39782 7.5 9 7.5C8.60218 7.5 8.22064 7.34196 7.93934 7.06066C7.65804 6.77936 7.5 6.39782 7.5 6M10.5 6C10.5 5.60218 10.342 5.22064 10.0607 4.93934C9.77936 4.65804 9.39782 4.5 9 4.5C8.60218 4.5 8.22064 4.65804 7.93934 4.93934C7.65804 5.22064 7.5 5.60218 7.5 6M7.5 6H3.75M10.5 18H20.25M10.5 18C10.5 18.3978 10.342 18.7794 10.0607 19.0607C9.77936 19.342 9.39782 19.5 9 19.5C8.60218 19.5 8.22064 19.342 7.93934 19.0607C7.65804 18.7794 7.5 18.3978 7.5 18M10.5 18C10.5 17.6022 10.342 17.2206 10.0607 16.9393C9.77936 16.658 9.39782 16.5 9 16.5C8.60218 16.5 8.22064 16.658 7.93934 16.9393C7.65804 17.2206 7.5 17.6022 7.5 18M7.5 18H3.75M16.5 12H20.25M16.5 12C16.5 12.3978 16.342 12.7794 16.0607 13.0607C15.7794 13.342 15.3978 13.5 15 13.5C14.6022 13.5 14.2206 13.342 13.9393 13.0607C13.658 12.7794 13.5 12.3978 13.5 12M16.5 12C16.5 11.6022 16.342 11.2206 16.0607 10.9393C15.7794 10.658 15.3978 10.5 15 10.5C14.6022 10.5 14.2206 10.658 13.9393 10.9393C13.658 11.2206 13.5 11.6022 13.5 12M13.5 12H3.75" stroke="#F5B700" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </i>
                    Filter & Sort
                </div>
                <span class="catalog-filters__mob-counter js_total_products_count">{{ trans_choice('app.products_found', $products->total(), [':count' => $products->total()], app()->getLocale()) }}</span>
            </div>
            <div class="catalog-filters__mob-main">
                <div class="catalog-filters__mob-head">
                    <i>
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="17" viewBox="0 0 18 17" fill="none">
                            <path d="M7.5 2.25H17.25M7.5 2.25C7.5 2.64782 7.34196 3.02936 7.06066 3.31066C6.77936 3.59196 6.39782 3.75 6 3.75C5.60218 3.75 5.22064 3.59196 4.93934 3.31066C4.65804 3.02936 4.5 2.64782 4.5 2.25M7.5 2.25C7.5 1.85218 7.34196 1.47064 7.06066 1.18934C6.77936 0.908035 6.39782 0.75 6 0.75C5.60218 0.75 5.22064 0.908035 4.93934 1.18934C4.65804 1.47064 4.5 1.85218 4.5 2.25M4.5 2.25H0.75M7.5 14.25H17.25M7.5 14.25C7.5 14.6478 7.34196 15.0294 7.06066 15.3107C6.77936 15.592 6.39782 15.75 6 15.75C5.60218 15.75 5.22064 15.592 4.93934 15.3107C4.65804 15.0294 4.5 14.6478 4.5 14.25M7.5 14.25C7.5 13.8522 7.34196 13.4706 7.06066 13.1893C6.77936 12.908 6.39782 12.75 6 12.75C5.60218 12.75 5.22064 12.908 4.93934 13.1893C4.65804 13.4706 4.5 13.8522 4.5 14.25M4.5 14.25H0.75M13.5 8.25H17.25M13.5 8.25C13.5 8.64782 13.342 9.02936 13.0607 9.31066C12.7794 9.59196 12.3978 9.75 12 9.75C11.6022 9.75 11.2206 9.59196 10.9393 9.31066C10.658 9.02936 10.5 8.64782 10.5 8.25M13.5 8.25C13.5 7.85218 13.342 7.47064 13.0607 7.18934C12.7794 6.90804 12.3978 6.75 12 6.75C11.6022 6.75 11.2206 6.90804 10.9393 7.18934C10.658 7.47064 10.5 7.85218 10.5 8.25M10.5 8.25H0.75" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </i>
                    Filter & Sort
                    <span class="catalog-filters__mob-close">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                              <path d="M18 6L6 18" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                              <path d="M6 6L18 18" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </span>
                </div>
                <div class="catalog-filters__mob-body catalog-filters">
                    @include('public.layouts.selected_filters')
                    @include('public.layouts.mob_filters')
                </div>
            </div>
            <div class="catalog-main__wrapper" id="js_products_wrapper">
                @include('public.layouts.products_list')
            </div>
            @include('public.layouts.pagination', ['paginator' => $products, 'js' => true])
        </div>
        {!! $seo->description !!}
    </div>
@endsection
