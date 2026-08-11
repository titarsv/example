@extends('public.layouts.main')
@section('page_vars')
    @include('public.layouts.microdata.open_graph', [
     'title' => $seo->meta_title,
     'description' => $seo->meta_description
     ])
@endsection

@section('content')
    <div class="container py-4">
        <div class="mb-3">{!! Breadcrumbs::render('cart') !!}</div>
        <h1 class="h3 mb-4">{{ $seo->name }}</h1>

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="js-cart-body">
                    @include('public.layouts.cart')
                </div>
            </div>
        </div>

        @if($recommendations->count())
            <div class="mt-5">
                <h2 class="h4 mb-3">Рекомендуем</h2>
                <div class="position-relative px-4">
                    <div class="js-products-slider">
                        @foreach($recommendations as $recommendation)
                            <div>
                                @include('public.layouts.product', ['product' => $recommendation])
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection
