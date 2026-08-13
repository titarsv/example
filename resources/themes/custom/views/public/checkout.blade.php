@extends('public.layouts.main')
@section('page_vars')
    @include('public.layouts.microdata.open_graph', [
     'title' => $seo->meta_title,
     'description' => $seo->meta_description
     ])
@endsection

@section('content')
    <div class="container py-4">
        <div class="mb-3">{!! Breadcrumbs::render('checkout') !!}</div>
        <h1 class="h3 mb-4">{{ $seo->name }}</h1>

        <form id="order-checkout">
            <div class="row g-4">
                <div class="col-lg-7">
                    <div class="card mb-4">
                        <div class="card-body">
                            <h2 class="h5 mb-3">Данные для доставки</h2>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Имя *</label>
                                    <input class="form-control" type="text" name="first_name" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Фамилия *</label>
                                    <input class="form-control" type="text" name="last_name" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Страна *</label>
                                    <input class="form-control" type="text" name="county" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Город *</label>
                                    <input class="form-control" type="text" name="city" required>
                                </div>
                                <div class="col-md-8">
                                    <label class="form-label">Адрес *</label>
                                    <input class="form-control" type="text" name="address" placeholder="Улица, дом, квартира" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Индекс *</label>
                                    <input class="form-control" type="text" name="index" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Email *</label>
                                    <input class="form-control" type="email" name="email" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Телефон *</label>
                                    <input class="form-control" type="tel" name="phone" required>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-4">
                        <div class="card-body">
                            <h2 class="h5 mb-3">Оплата</h2>
                            @foreach($payments as $payment_key => $payment)
                                <div class="form-check mb-2">
                                    <input type="radio" class="form-check-input" name="payment" id="payment-{{ $payment_key }}" value="{{ $payment_key }}"{{ $payment_key == $payment_method ? ' checked' : '' }}>
                                    <label class="form-check-label" for="payment-{{ $payment_key }}">{{ $payment['name'] }}</label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="col-lg-5">
                    <div class="card">
                        <div class="card-body">
                            <h2 class="h5 mb-3">Ваш заказ</h2>

                            <div class="list-group list-group-flush mb-3">
                                @foreach ($cart->get_products() as $code => $product)
                                    @if(is_object($product['product']))
                                        <div class="list-group-item d-flex gap-3 px-0">
                                            <div style="width: 64px;">
                                                {!! $product['product']->image == null
                                                    ? '<img src="/images/larchik/no_image.jpg" alt="Нет фото" class="img-fluid rounded">'
                                                    : $product['product']->image->webp([64, 64], ['alt' => $product['product']->name, 'class' => 'img-fluid rounded']) !!}
                                            </div>
                                            <div class="flex-grow-1">
                                                <a href="{{ $product['product']->link() }}" class="text-body text-decoration-none d-block small fw-semibold" target="_blank">{{ $product['product']->name }}</a>
                                                <div class="d-flex justify-content-between align-items-center mt-1">
                                                    <input type="number" min="1" class="form-control form-control-sm js_checkout_qty" style="width: 65px;" value="{{ $product['quantity'] }}" data-prod-id="{{ $code }}">
                                                    <span class="small fw-semibold">₽{{ $product['price'] }}</span>
                                                </div>
                                            </div>
                                            <button type="button" class="btn-close align-self-start js_remove_product_from_checkout" data-id="{{ $code }}" aria-label="Удалить"></button>
                                        </div>
                                    @endif
                                @endforeach
                            </div>

                            @include('public.layouts.checkout_spend')

                            @if(module_active('coupons'))
                                <form class="js-coupon-form input-group input-group-sm mb-3" action="{{ base_url('/apply_coupon') }}">
                                    <input type="text" class="form-control" name="code" placeholder="Промокод" value="{{ empty($cart->coupon) ? '' : $cart->coupon->code }}">
                                    <button type="submit" class="btn btn-outline-secondary">Применить</button>
                                </form>
                                <div class="alert alert-danger d-none py-1 px-2 small js-coupon-error"></div>
                            @endif

                            <div id="checkout_prices">
                                @include('public.layouts.checkout_prices')
                            </div>

                            <p class="small text-muted mt-3">
                                Ваши данные используются только для обработки заказа. <a href="javascript:void(0)">Политика конфиденциальности</a>
                            </p>

                            <div class="form-check mb-2">
                                <input type="checkbox" class="form-check-input" name="terms" id="terms" required>
                                <label class="form-check-label small" for="terms">
                                    Я прочитал(а) и согласен(на) с <a href="/terms-of-use/" target="_blank">условиями использования</a>
                                </label>
                            </div>
                            <div class="form-check mb-3">
                                <input type="checkbox" class="form-check-input" id="updates">
                                <label class="form-check-label small" for="updates">
                                    Хочу получать уведомления о статусе доставки на email (необязательно)
                                </label>
                            </div>

                            <div class="alert alert-danger d-none js-checkout-error"></div>
                            <button type="submit" class="btn btn-primary w-100 js_checkout_submit">Оформить заказ</button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection
