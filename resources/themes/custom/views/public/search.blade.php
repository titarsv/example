@extends('public.layouts.main')
@section('meta')
    <title>Поиск: {{ $search_text }}</title>
    <meta name="description" content="Поиск: {{ $search_text }}">
    <meta name="robots" content="noindex, nofollow"/>
@endsection
@section('page_vars')
    @include('public.layouts.microdata.open_graph', [
     'title' => 'Поиск: '.$search_text,
     'description' => 'Поиск: '.$search_text,
     'image' => theme_asset('images/favicon.png')
     ])
@endsection

@section('content')
    <div class="container py-4">
        <div class="mb-3">{!! Breadcrumbs::render('search') !!}</div>
        <h1 class="h3 mb-4">Результаты поиска «{{ $search_text }}»</h1>

        @if($products->total())
            <p class="text-muted">{{ trans_choice('app.products_found', $products->total(), [':count' => $products->total()], app()->getLocale()) }}</p>

            <div class="row row-cols-2 row-cols-md-3 row-cols-xl-4 g-3">
                @foreach($products as $product)
                    <div class="col">
                        @include('public.layouts.product', ['product' => $product])
                    </div>
                @endforeach
            </div>

            <div class="mt-4">
                @include('public.layouts.pagination', ['paginator' => $products])
            </div>
        @else
            <div class="text-center py-4">
                <p>По запросу <strong>{{ $search_text }}</strong> ничего не найдено. Попробуйте один из популярных запросов:</p>
                <div class="d-flex flex-wrap justify-content-center gap-2">
                    @foreach($categories ?? [] as $popular_category)
                        <a href="{{ base_url('/search') }}?text={{ urlencode($popular_category->name) }}" class="btn btn-outline-secondary btn-sm">{{ $popular_category->name }}</a>
                    @endforeach
                </div>
            </div>

            @if(!empty($recommended) && $recommended->count())
                <h2 class="h4 mt-5 mb-3">Рекомендуем</h2>
                <div class="position-relative px-4">
                    <div class="js-products-slider">
                        @foreach($recommended as $recommended_product)
                            <div>
                                @include('public.layouts.product', ['product' => $recommended_product])
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        @endif
    </div>
@endsection
