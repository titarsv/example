<div class="checkout-spend"{!! $cart->total_price - $cart->total_sale >= 100 ? 'style="display:none"' : '' !!}>
    Spend <span>£{{ $cart->total_price - $cart->total_sale >= 100 ? 0 : 100 - $cart->total_price + $cart->total_sale }}</span> more for free shipping!
</div>
