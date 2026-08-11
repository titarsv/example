<ul class="list-group list-group-flush mb-3">
    <li class="list-group-item d-flex justify-content-between px-0">
        <span>Товары</span>
        <span class="js_cart_price">₽{{ $cart->total_price }}</span>
    </li>
    @if(!empty($cart->coupon_sale))
        <li class="list-group-item d-flex justify-content-between px-0 text-success">
            <span>Скидка по промокоду</span>
            <span class="js_coupon_sale">- ₽{{ $cart->coupon_sale }}</span>
        </li>
    @endif
    @if(!empty($cart->amount_sale))
        <li class="list-group-item d-flex justify-content-between px-0 text-success">
            <span>Скидка на сумму заказа</span>
            <span>- ₽{{ $cart->amount_sale }}</span>
        </li>
    @endif
    <li class="list-group-item d-flex justify-content-between px-0">
        <span>Доставка</span>
        <span>₽{{ $cart->shipping }}</span>
    </li>
    <li class="list-group-item d-flex justify-content-between px-0 fw-bold fs-5">
        <span>Итого</span>
        <span>₽{{ $cart->total_price - $cart->total_sale + $cart->shipping }}</span>
    </li>
</ul>
