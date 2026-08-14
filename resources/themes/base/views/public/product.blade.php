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
                    <h2 class="h4 mb-0">Отзывы покупателей ({{ $reviews->count() }})</h2>
                    <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#productReviewModal">Написать отзыв</button>
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

            <div class="modal fade" id="productReviewModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Написать отзыв</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
                        </div>
                        <div class="modal-body">
                            <form class="js-review-form" action="{{ base_url('/review/add') }}" method="POST">
                                @csrf
                                <input type="hidden" name="product_id" value="{{ $product->id }}">
                                <input type="hidden" name="type" value="review">
                                <input type="hidden" name="grade" value="0">
                                <div class="mb-3 js-rating-input">
                                    @for($i = 1; $i <= 5; $i++)
                                        <i class="bi bi-star fs-4 text-warning" role="button" data-value="{{ $i }}"></i>
                                    @endfor
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Имя</label>
                                    <input type="text" class="form-control" name="name" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="email" class="form-control" name="email" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Отзыв</label>
                                    <textarea class="form-control" name="review" rows="4" required></textarea>
                                </div>
                                <div class="alert alert-danger d-none js-review-error"></div>
                                <button type="submit" class="btn btn-primary w-100">Отправить отзыв</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        @if($similar->count())
            <div class="container py-4">
                <h2 class="h4 mb-3">Похожие товары</h2>
                <div class="position-relative px-4">
                    <div class="js-products-slider">
                        @foreach($similar as $similar_product)
                            <div>
                                @include('public.layouts.product', ['product' => $similar_product])
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        @if($bought_together->count())
            <div class="container py-4">
                <h2 class="h4 mb-3">Часто покупают вместе</h2>
                <div class="position-relative px-4">
                    <div class="js-products-slider">
                        @foreach($bought_together as $bought_together_product)
                            <div>
                                @include('public.layouts.product', ['product' => $bought_together_product])
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        @if(!empty($seo->description))
            <div class="container py-4 border-top">
                <div class="text-muted">{!! $seo->description !!}</div>
            </div>
        @endif
    @endif
@endsection
