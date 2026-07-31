@extends('public.layouts.main', ['css' => 'css/critical/catalog.min.css', 'critical_css' => 'css/critical/catalog_header.min.css'])
@section('meta')
    <title>Search Results for {{ $search_text }}</title>
    <meta name="description" content="Search: {{ $search_text }}">
    <meta name="keywords" content="{{ $search_text }}">
    <meta name="robots" content="noindex, nofollow"/>
@endsection
@section('page_vars')
    <script>
        var fbqProductsData = [];
        window.ecommerce_products_data = [];
    </script>
    @include('public.layouts.microdata.open_graph', [
     'title' => 'Search: '.$search_text,
     'description' => 'Search: '.$search_text,
     'image' => '/images/logo_og.svg'
     ])
@endsection

@section('content')
    <div class="section page-search-results">
        <div class="page-top">
            <div class="container">
                {!! Breadcrumbs::render('search') !!}
                <h1 class="page-title">Search Results for “{{ $search_text }}”</h1>
            </div>
        </div>
        @if($products->total())
            <div class="search-results__main">
                <div class="catalog-filters active">
                    <div class="container">
                        <input type="hidden" id="js_search_text" value="{{ $search_text }}">
                        <nav class="catalog-filters__bar">
                            <span class="catalog-filters__counter js_total_products_count">{{ trans_choice('app.products_found', $products->total(), [':count' => $products->total()], app()->getLocale()) }}</span>
                            <div class="catalog-filter__wrapper">
                                    <span class="catalog-filter__btn">
                                        Sort by:
                                        <i>
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16" fill="none">
                                              <path d="M3.33325 8H12.6666" stroke="#0B0B0B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                              <path d="M8 3.33334V12.6667" stroke="#0B0B0B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16" fill="none">
                                              <path d="M3.33325 8H12.6666" stroke="#0B0B0B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                        </i>
                                    </span>
                                <div class="catalog-filter__dropdown one-col js_sort">
                                        <span class="catalog-sort{{ request('order') == 'priority-asc' || empty(request('order')) ? ' current' : '' }}" data-value="priority-asc" data-sort="Default sorting">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                                              <path d="M19 3H5C3.89543 3 3 3.89543 3 5V19C3 20.1046 3.89543 21 5 21H19C20.1046 21 21 20.1046 21 19V5C21 3.89543 20.1046 3 19 3Z" stroke="#6C6C6C" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                              <path d="M9 12L11 14L15 10" stroke="#FFBB44" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                            Default sorting
                                        </span>
                                    <span class="catalog-sort{{ request('order') == 'popularity-desc' ? ' current' : '' }}" data-value="popularity-desc" data-sort="by Popularity">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                                              <path d="M19 3H5C3.89543 3 3 3.89543 3 5V19C3 20.1046 3.89543 21 5 21H19C20.1046 21 21 20.1046 21 19V5C21 3.89543 20.1046 3 19 3Z" stroke="#6C6C6C" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                              <path d="M9 12L11 14L15 10" stroke="#FFBB44" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                            by Popularity
                                        </span>
                                    <span class="catalog-sort{{ request('order') == 'rating-desc' ? ' current' : '' }}" data-value="rating-desc" data-sort="by Average rating">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                                              <path d="M19 3H5C3.89543 3 3 3.89543 3 5V19C3 20.1046 3.89543 21 5 21H19C20.1046 21 21 20.1046 21 19V5C21 3.89543 20.1046 3 19 3Z" stroke="#6C6C6C" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                              <path d="M9 12L11 14L15 10" stroke="#FFBB44" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                            by Average rating
                                        </span>
                                    <span class="catalog-sort{{ request('order') == 'created-desc' ? ' current' : '' }}" data-value="created-desc" data-sort="by Latest">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                                              <path d="M19 3H5C3.89543 3 3 3.89543 3 5V19C3 20.1046 3.89543 21 5 21H19C20.1046 21 21 20.1046 21 19V5C21 3.89543 20.1046 3 19 3Z" stroke="#6C6C6C" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                              <path d="M9 12L11 14L15 10" stroke="#FFBB44" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                            by Latest
                                        </span>
                                    <span class="catalog-sort{{ request('order') == 'price-asc' ? ' current' : '' }}" data-value="price-asc" data-sort="by Price: low to high">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                                              <path d="M19 3H5C3.89543 3 3 3.89543 3 5V19C3 20.1046 3.89543 21 5 21H19C20.1046 21 21 20.1046 21 19V5C21 3.89543 20.1046 3 19 3Z" stroke="#6C6C6C" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                              <path d="M9 12L11 14L15 10" stroke="#FFBB44" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                            by Price: low to high
                                        </span>
                                    <span class="catalog-sort{{ request('order') == 'price-desc' ? ' current' : '' }}" data-value="price-desc" data-sort="by Price: high to low">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                                              <path d="M19 3H5C3.89543 3 3 3.89543 3 5V19C3 20.1046 3.89543 21 5 21H19C20.1046 21 21 20.1046 21 19V5C21 3.89543 20.1046 3 19 3Z" stroke="#6C6C6C" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                              <path d="M9 12L11 14L15 10" stroke="#FFBB44" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                            by Price: high to low
                                        </span>
                                </div>
                            </div>
                        </nav>
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
                        Sort
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
                        Sort
                        <span class="catalog-filters__mob-close">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                              <path d="M18 6L6 18" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                              <path d="M6 6L18 18" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </span>
                    </div>
                    <div class="catalog-filters__mob-body catalog-filters">
                        <div class="js_mob_filters">
                            <div class="catalog-filter__block active">
                                <div class="catalog-filter__block-head">
                                    Sort by
                                    <i>
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16" fill="none">
                                            <path d="M3.33325 8H12.6666" stroke="#0B0B0B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                            <path d="M8 3.33334V12.6667" stroke="#0B0B0B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16" fill="none">
                                            <path d="M3.33325 8H12.6666" stroke="#0B0B0B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                    </i>
                                </div>
                                <div class="catalog-filter__block-body js_mob_sort">
                                    <span class="catalog-sort{{ request('order') == 'priority-asc' || empty(request('order')) ? ' current' : '' }}" data-value="priority-asc" data-sort="Default sorting">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                                          <path d="M19 3H5C3.89543 3 3 3.89543 3 5V19C3 20.1046 3.89543 21 5 21H19C20.1046 21 21 20.1046 21 19V5C21 3.89543 20.1046 3 19 3Z" stroke="#6C6C6C" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                          <path d="M9 12L11 14L15 10" stroke="#FFBB44" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                        Default sorting
                                    </span>
                                    <span class="catalog-sort{{ request('order') == 'popularity-desc' ? ' current' : '' }}" data-value="popularity-desc" data-sort="by Popularity">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                                          <path d="M19 3H5C3.89543 3 3 3.89543 3 5V19C3 20.1046 3.89543 21 5 21H19C20.1046 21 21 20.1046 21 19V5C21 3.89543 20.1046 3 19 3Z" stroke="#6C6C6C" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                          <path d="M9 12L11 14L15 10" stroke="#FFBB44" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                        by Popularity
                                    </span>
                                    <span class="catalog-sort{{ request('order') == '   ' ? ' current' : '' }}" data-value="rating-desc" data-sort="by Average rating">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                                          <path d="M19 3H5C3.89543 3 3 3.89543 3 5V19C3 20.1046 3.89543 21 5 21H19C20.1046 21 21 20.1046 21 19V5C21 3.89543 20.1046 3 19 3Z" stroke="#6C6C6C" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                          <path d="M9 12L11 14L15 10" stroke="#FFBB44" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                        by Average rating
                                    </span>
                                    <span class="catalog-sort{{ request('order') == 'created-desc' ? ' current' : '' }}" data-value="created-desc" data-sort="by Latest">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                                          <path d="M19 3H5C3.89543 3 3 3.89543 3 5V19C3 20.1046 3.89543 21 5 21H19C20.1046 21 21 20.1046 21 19V5C21 3.89543 20.1046 3 19 3Z" stroke="#6C6C6C" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                          <path d="M9 12L11 14L15 10" stroke="#FFBB44" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                        by Latest
                                    </span>
                                    <span class="catalog-sort{{ request('order') == 'price-asc' ? ' current' : '' }}" data-value="price-asc" data-sort="by Price: low to high">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                                          <path d="M19 3H5C3.89543 3 3 3.89543 3 5V19C3 20.1046 3.89543 21 5 21H19C20.1046 21 21 20.1046 21 19V5C21 3.89543 20.1046 3 19 3Z" stroke="#6C6C6C" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                          <path d="M9 12L11 14L15 10" stroke="#FFBB44" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                        by Price: low to high
                                    </span>
                                    <span class="catalog-sort{{ request('order') == 'price-desc' ? ' current' : '' }}" data-value="price-desc" data-sort="by Price: high to low">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                                          <path d="M19 3H5C3.89543 3 3 3.89543 3 5V19C3 20.1046 3.89543 21 5 21H19C20.1046 21 21 20.1046 21 19V5C21 3.89543 20.1046 3 19 3Z" stroke="#6C6C6C" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                          <path d="M9 12L11 14L15 10" stroke="#FFBB44" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                        by Price: high to low
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="catalog-main__wrapper" id="js_products_wrapper">
                    @include('public.layouts.products_list')
                </div>
                @include('public.layouts.pagination', ['paginator' => $products, 'js' => true])
            </div>
        @else
            <div class="search-results__empty">
                <div class="container">
                    <p>Sorry, nothing found for <strong>{{ $search_text }}</strong>. Check out some of these popular searches:</p>
                    <ul class="search-tags">
                        <li><a href="{{ base_url('/search') }}?text=Vape" class="search-tag">Vape</a></li>
                        <li><a href="{{ base_url('/search') }}?text=Pod Battery" class="search-tag">Pod Battery</a></li>
                        <li><a href="{{ base_url('/search') }}?text=Cartridges" class="search-tag">Cartridges</a></li>
                        <li><a href="{{ base_url('/search') }}?text=Edibles" class="search-tag">Edibles</a></li>
                        {{--                            <li><a href="{{ base_url('/search') }}?text=Accessories" class="search-tag">Accessories</a></li>--}}
                    </ul>
                </div>
            </div>
        @endif
    </div>
    @if(!$products->total())
        <div class="section section-products">
            <div class="container">
                <div class="section-head">
                    <h2 class="section-title">Recommended for You</h2>
                </div>
            </div>
            <div class="products-slider slick-slider"
                 data-slick='{"slidesToShow": 5, "slidesToScroll": 1, "infinite": true, "arrows": true, "dots": true, "prevArrow": "#products-slider-prev", "nextArrow": "#products-slider-next", "appendDots": "#products-slider-dots", "responsive":[{"breakpoint":1199,"settings":{"slidesToShow": 4}}, {"breakpoint":991,"settings":{"slidesToShow": 3}}, {"breakpoint":575,"settings":{"slidesToShow": 2}}]}'>
                @foreach($recommended as $recommended_product)
                    <div class="slide">
                        @include('public.layouts.product', ['product' => $recommended_product])
                    </div>
                @endforeach
            </div>
            <div class="custom-slider__controls">
                <div class="container">
                    <div class="dots" id="products-slider-dots"></div>
                    <div class="arrows">
                        <div id="products-slider-prev">
                            <svg xmlns="http://www.w3.org/2000/svg" width="56" height="56" viewBox="0 0 56 56" fill="none">
                                <path d="M28.0001 44.3333L11.6667 28L28.0001 11.6666" stroke="#0B0B0B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M44.3334 28H11.6667" stroke="#0B0B0B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <div id="products-slider-next">
                            <svg xmlns="http://www.w3.org/2000/svg" width="56" height="56" viewBox="0 0 56 56" fill="none">
                                <path d="M27.9999 11.6667L44.3333 28L27.9999 44.3334" stroke="#0B0B0B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M11.6666 28L44.3333 28" stroke="#0B0B0B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection
