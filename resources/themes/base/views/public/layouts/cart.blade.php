@if(isset($cart) && $cart->total_quantity)
    <div class="list-group list-group-flush flex-grow-1 overflow-auto">
        @foreach($cart->get_products() as $code => $product)
            @if(is_object($product['product']))
                <div class="list-group-item d-flex gap-3 py-3">
                    <a href="{{ $product['product']->link() }}" class="flex-shrink-0" style="width: 72px;">
                        {!! $product['product']->image == null
                            ? '<img src="/images/larchik/no_image.jpg" alt="Нет фото" class="img-fluid rounded">'
                            : $product['product']->image->webp([72, 72], ['alt' => $product['product']->name, 'class' => 'img-fluid rounded']) !!}
                    </a>
                    <div class="flex-grow-1">
                        <a href="{{ $product['product']->link() }}" class="d-block text-body text-decoration-none fw-semibold">{{ $product['product']->name }}</a>
                        @if(!empty($product['variations']))
                            <div class="text-muted small">
                                @foreach($product['variations'] as $name => $val)
                                    {{ $name }}: {{ $val }}
                                @endforeach
                            </div>
                        @endif
                        <div class="d-flex align-items-center justify-content-between mt-2">
                            <input type="number" min="1" class="form-control form-control-sm js-cart-qty" style="width: 70px;" value="{{ $product['quantity'] }}" data-id="{{ $code }}">
                            <span class="fw-semibold">₽{{ $product['price'] }}</span>
                        </div>
                    </div>
                    <button type="button" class="btn-close align-self-start js-cart-remove" data-id="{{ $code }}" aria-label="Удалить"></button>
                </div>
            @endif
        @endforeach
    </div>

    <div class="border-top p-3">
        <div class="d-flex justify-content-between fw-semibold fs-5 mb-3">
            <span>Итого</span>
            <span class="js_cart_price">₽{{ number_format($cart->total_price - $cart->coupon_sale, 0, '.', ' ') }}</span>
        </div>
        <a href="{{ base_url('/checkout') }}" class="btn btn-primary w-100 mb-2">Оформить заказ</a>
        <button type="button" class="btn btn-outline-secondary w-100" data-bs-dismiss="offcanvas">Продолжить покупки</button>
    </div>
@else
    <div class="d-flex flex-column align-items-center justify-content-center text-center flex-grow-1 p-4">
        <i class="bi bi-cart display-4 text-muted mb-3"></i>
        <p class="text-muted">Ваша корзина пуста. Перейдите в каталог, чтобы выбрать товары!</p>
        <a href="{{ base_url('/catalog') }}" class="btn btn-primary">Перейти в каталог</a>
    </div>
@endif
