@if($cart->total_price - $cart->total_sale < 3000)
    <div class="alert alert-light border small mb-3">
        До бесплатной доставки осталось <strong>₽{{ 3000 - $cart->total_price + $cart->total_sale }}</strong>
    </div>
@endif
