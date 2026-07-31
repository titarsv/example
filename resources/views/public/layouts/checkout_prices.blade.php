<div class="checkout-price">
    <div class="checkout-price__item">
        <span>Subtotal</span>
        <span class="js_cart_price">£{{ $cart->total_price }}</span>
    </div>
    @if(!empty($cart->coupon_sale))
        <div class="checkout-price__item checkout-price__item-coupon">
            <span>Coupon sale</span>
            <span class="js_coupon_sale">- £{{ $cart->coupon_sale }}</span>
            <i class="js_remove_coupon">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                    <path d="M14.74 9.00003L14.394 18M9.606 18L9.26 9.00003M19.228 5.79003C19.57 5.84203 19.91 5.89703 20.25 5.95603M19.228 5.79003L18.16 19.673C18.1164 20.2383 17.8611 20.7662 17.445 21.1513C17.029 21.5364 16.4829 21.7502 15.916 21.75H8.084C7.5171 21.7502 6.97102 21.5364 6.55498 21.1513C6.13894 20.7662 5.88359 20.2383 5.84 19.673L4.772 5.79003M19.228 5.79003C18.0739 5.61555 16.9138 5.48313 15.75 5.39303M4.772 5.79003C4.43 5.84103 4.09 5.89603 3.75 5.95503M4.772 5.79003C5.92613 5.61555 7.08623 5.48313 8.25 5.39303M15.75 5.39303V4.47703C15.75 3.29703 14.84 2.31303 13.66 2.27603C12.5536 2.24067 11.4464 2.24067 10.34 2.27603C9.16 2.31303 8.25 3.29803 8.25 4.47703V5.39303M15.75 5.39303C13.2537 5.20011 10.7463 5.20011 8.25 5.39303" stroke="black" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                </svg>
            </i>
        </div>
    @endif
    @if(!empty($cart->amount_sale))
        <div class="checkout-price__item">
            <span>Discount on the amount</span>
            <span>- £{{ $cart->amount_sale }}</span>
        </div>
    @endif
    @if(!empty($cart->crypto_sale))
        <div class="checkout-price__item">
            <span>Crypto Discount</span>
            <span>- £{{ $cart->crypto_sale }}</span>
        </div>
    @endif
    <div class="checkout-price__item">
        <span>Shipping</span>
        <span>£{{ $cart->shipping }}</span>
    </div>
</div>
<div class="checkout-total">
    <span>Total</span>
    <span>£{{ $cart->total_price - $cart->total_sale + $cart->shipping }}</span>
</div>
