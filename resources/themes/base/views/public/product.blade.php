@extends(!empty($for_cache) ? 'public.layouts.empty' : 'public.layouts.main')
@section('page_vars')
    @include('public.layouts.microdata.product', ['product' => $product, 'reviews' => $reviews])
    @if(!empty($product->image))
        @include('public.layouts.microdata.image', ['image' => $product->image])
    @endif
    @include('public.layouts.microdata.open_graph', [
     'title' => $seo->meta_title,
     'description' => $seo->meta_description,
     'image' => !empty($product->image) ? $product->image->url() : theme_asset('images/favicon.png')
     ])
@endsection

@section('content')
    @if(!empty($from_cache))
        {!! $html !!}
    @else
        <div class="container py-4">
            <div class="mb-3">
                {!! Breadcrumbs::render('product', $product, $product->category) !!}
            </div>

            <div class="row g-4 js-product-card">
                <div class="col-lg-6">
                    {!! !empty($product->image) ? $product->image->image(
                        [600, 600],
                        ['alt' => $product->name, 'loading' => 'lazy', 'class' => 'img-fluid rounded product-card__image w-100'],
                        'cover',
                        ['<991' => '100vw', 'calc(50vw - 48px)']
                    ) : '<img src="/images/larchik/no_image.jpg" alt="Нет фото" class="img-fluid rounded w-100" loading="lazy">' !!}
                </div>

                <div class="col-lg-6">
                    @include('public.layouts.rating-stars', ['rating' => $product->rating])
                    <span class="text-muted small">({{ number_format($product->rating, 1, '.') }})</span>

                    <h1 class="h3 mt-2">{{ $product->name }}</h1>

                    @include('public.layouts.product_price')

                    @include('public.layouts.product_variations')

                    @include('public.layouts.product_actions')

                    @include('public.layouts.product_attributes')

                    <div class="accordion mt-3" id="productInfoAccordion">
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#productDescription">
                                    Описание
                                </button>
                            </h2>
                            <div id="productDescription" class="accordion-collapse collapse show" data-bs-parent="#productInfoAccordion">
                                <div class="accordion-body">
                                    {!! $product->description !!}
                                </div>
                            </div>
                        </div>

                        @include('public.layouts.product_video_reviews')
                    </div>
                </div>
            </div>
        </div>

        @if(module_active('reviews'))
            <div class="container py-4 border-top">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                    @include('public.layouts.product_review_header')
                </div>

                @if($reviews->count() > 0)
                    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-3">
                        @foreach($reviews as $review)
                            <div class="col">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <p class="card-text">{{ $review->review }}</p>
                                        <div class="fw-semibold small">{{ $review->author }}</div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-muted">Пока нет отзывов — станьте первым!</p>
                @endif
            </div>

            @include('public.layouts.product_review_modal')
        @endif

        @include('public.layouts.product_related_slider', ['items' => $similar, 'heading' => 'Похожие товары'])

        @include('public.layouts.product_related_slider', ['items' => $bought_together, 'heading' => 'Часто покупают вместе'])

        @if(!empty($seo->description))
            <div class="container py-4 border-top">
                <div class="text-muted">{!! $seo->description !!}</div>
            </div>
        @endif
    @endif
@endsection
