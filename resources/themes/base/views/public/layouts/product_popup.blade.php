<div class="row g-4 js-product-card">
    <div class="col-md-6">
        {!! !empty($product->image) ? $product->image->image(
            [500, 500],
            ['alt' => $product->name, 'loading' => 'lazy', 'class' => 'img-fluid rounded w-100'],
            'cover',
            ['<767' => '90vw', '450px']
        ) : '<img src="/images/larchik/no_image.jpg" alt="Нет фото" class="img-fluid rounded w-100" loading="lazy">' !!}
    </div>
    <div class="col-md-6">
        @include('public.layouts.rating-stars', ['rating' => $product->rating])
        <span class="text-muted small">({{ number_format($product->rating, 1, '.') }})</span>

        <h2 class="h4 mt-2">{{ $product->name }}</h2>

        <div class="fs-4 fw-bold mb-3">
            @if($product->actual_price > 0)
                <span class="js_current_price" data-price="{{ $product->actual_price }}" data-original_price="{{ $product->original_price }}">₽{{ $product->actual_price }}</span>
            @endif
            @if($product->actual_price < $product->original_price && $product->original_price > 0)
                <span class="text-muted text-decoration-line-through js_old_price fs-6 ms-2">₽{{ $product->original_price }}</span>
            @endif
        </div>

        @if(!empty($variations))
            @foreach($variations as $variation)
                <div class="mb-3">
                    <div class="small text-muted mb-1">{{ $variation['name'] }}:</div>
                    <div class="d-flex flex-wrap gap-2">
                        @foreach($variation['values'] as $val_id => $value)
                            <span class="btn btn-sm {{ in_array($val_id, $selected_variation_attributes) ? 'btn-primary current js_active' : 'btn-outline-secondary' }} {{ $value['stock'] ? 'js_variation' : 'disabled' }}" data-id="{{ $val_id }}">
                                {{ $value['name'] }}{{ isset($variations_prices[$val_id]) ? ' — ₽'.$variations_prices[$val_id]['price'] : '' }}
                            </span>
                        @endforeach
                    </div>
                </div>
            @endforeach
            @foreach($variations_prices as $variation => $val)
                <input class="js_var_{{ $variation }} d-none" data-id="{{ $variation }}" type="radio" name="variation" value="{{ $val['id'] }}" data-price="{{ $val['price'] }}" data-original_price="{{ $val['original_price'] }}"{{ $variation == array_key_first($variations_prices) ? ' checked' : '' }}>
            @endforeach
        @endif

        @if(module_active('cart_checkout'))
            <div class="d-flex flex-wrap gap-2 mb-3">
                <button type="button" class="btn btn-primary js-add-to-cart" data-id="{{ $product->id }}">В корзину</button>
                @if(module_active('wishlist') && \Cartalyst\Sentinel\Native\Facades\Sentinel::check())
                    <button type="button" class="btn btn-outline-secondary js-wishlist-toggle{{ $product->in_wish() ? ' text-danger' : '' }}" data-id="{{ $product->id }}">
                        <i class="bi {{ $product->in_wish() ? 'bi-heart-fill' : 'bi-heart' }}"></i>
                    </button>
                @endif
            </div>
        @endif

        <a href="{{ $product->link() }}" class="small">Подробнее о товаре &rarr;</a>
    </div>
</div>
