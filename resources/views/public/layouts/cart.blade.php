@if(isset($cart) && $cart->total_quantity)
    <div class="cart-close cart-close__top">
        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 32 32" fill="none">
            <path d="M24 8L8 24" stroke="#0B0B0B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M8 8L24 24" stroke="#0B0B0B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
    </div>
    <div class="cart-head">
        <span class="amount js_cart_counter">{{ isset($cart) && $cart->total_quantity ? $cart->total_quantity : '0' }}</span> item in the cart
    </div>
    <div class="cart-title">
        Your cart
    </div>
    <div class="cart-body">
        <div class="cart-items">
            @foreach($cart->get_products() as $code => $product)
                @if(is_object($product['product']))
                    <div class="cart-item">
                        <div class="cart-item__del js_remove_product_from_cart" data-id="{{ $code }}">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                                <path d="M14.74 9.00003L14.394 18M9.606 18L9.26 9.00003M19.228 5.79003C19.57 5.84203 19.91 5.89703 20.25 5.95603M19.228 5.79003L18.16 19.673C18.1164 20.2383 17.8611 20.7662 17.445 21.1513C17.029 21.5364 16.4829 21.7502 15.916 21.75H8.084C7.5171 21.7502 6.97102 21.5364 6.55498 21.1513C6.13894 20.7662 5.88359 20.2383 5.84 19.673L4.772 5.79003M19.228 5.79003C18.0739 5.61555 16.9138 5.48313 15.75 5.39303M4.772 5.79003C4.43 5.84103 4.09 5.89603 3.75 5.95503M4.772 5.79003C5.92613 5.61555 7.08623 5.48313 8.25 5.39303M15.75 5.39303V4.47703C15.75 3.29703 14.84 2.31303 13.66 2.27603C12.5536 2.24067 11.4464 2.24067 10.34 2.27603C9.16 2.31303 8.25 3.29803 8.25 4.47703V5.39303M15.75 5.39303C13.2537 5.20011 10.7463 5.20011 8.25 5.39303" stroke="black" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <div class="cart-item__pic">
                            {!! $product['product']->image == null ? '<picture class="lazy-hidden">
                            <source data-src="/images/larchik/no_image.webp" srcset="/images/pixel.webp" type="image/webp">
                            <source data-src="/images/larchik/no_image.jpg" srcset="/images/pixel.jpg" type="image/jpeg">
                            <img src="/images/pixel.jpg" alt="'.$product['product']->name.' ">
                            </picture>' : $product['product']->image->webp([150, 150], ['alt' => $product['product']->name]) !!}
                        </div>
                        <div class="cart-item__info">
                            <a href="{{ $product['product']->link() }}" class="cart-item__title">
                                {{ $product['product']->name }}
                                @if(!empty($product['variations']))
                                    @foreach($product['variations'] as $name => $val)
                                        <p class="characteristic">
                                            <span>{{ $name }}:</span>
                                            <span>{{ $val }}</span>
                                        </p>
                                    @endforeach
                                @endif
                            </a>
                            <span class="cart-item__tag">{{ $product['product']->category->name }}</span>
                            <div class="cart-item__footer">
                                <div class="cart-item__counter js_counter">
                                    <span class="minus">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                                          <path d="M5 12H19" stroke="black" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                    </span>
                                    <input class="value js_cart_qty" value="{{ $product['quantity'] }}" type="text" data-prod-id="{{ $code }}" oninput="this.value=this.value.replace(/[^0-9\.]/g,'');">
                                    <span class="plus">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                                          <path d="M5 12H19" stroke="black" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                          <path d="M12 5V19" stroke="black" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                    </span>
                                </div>
                                <div class="cart-item__footer">£{{ $product['price'] }}</div>
                            </div>
                        </div>
                        <div class="cart-item__footer cart-item__footer-mob">
                            <div class="cart-item__price">£{{ $product['price'] }}</div>
                            <div class="cart-item__counter js_counter">
                                <span class="minus">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                                      <path d="M5 12H19" stroke="black" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </span>
                                <input class="value js_cart_qty" value="{{ $product['quantity'] }}" type="text" data-prod-id="{{ $code }}" oninput="this.value=this.value.replace(/[^0-9\.]/g,'');">
                                <span class="plus">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                                      <path d="M5 12H19" stroke="black" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                      <path d="M12 5V19" stroke="black" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </span>
                            </div>
                            <div class="cart-item__del js_remove_product_from_cart" data-id="{{ $code }}">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                                    <path d="M14.74 9.00003L14.394 18M9.606 18L9.26 9.00003M19.228 5.79003C19.57 5.84203 19.91 5.89703 20.25 5.95603M19.228 5.79003L18.16 19.673C18.1164 20.2383 17.8611 20.7662 17.445 21.1513C17.029 21.5364 16.4829 21.7502 15.916 21.75H8.084C7.5171 21.7502 6.97102 21.5364 6.55498 21.1513C6.13894 20.7662 5.88359 20.2383 5.84 19.673L4.772 5.79003M19.228 5.79003C18.0739 5.61555 16.9138 5.48313 15.75 5.39303M4.772 5.79003C4.43 5.84103 4.09 5.89603 3.75 5.95503M4.772 5.79003C5.92613 5.61555 7.08623 5.48313 8.25 5.39303M15.75 5.39303V4.47703C15.75 3.29703 14.84 2.31303 13.66 2.27603C12.5536 2.24067 11.4464 2.24067 10.34 2.27603C9.16 2.31303 8.25 3.29803 8.25 4.47703V5.39303M15.75 5.39303C13.2537 5.20011 10.7463 5.20011 8.25 5.39303" stroke="black" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                @endif
            @endforeach
        </div>
        <div class="cart-promo">
            <div class="cart-promo__head">
                <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 36 36" fill="none">
                    <path d="M13.5 22.5L22.5 13.5" stroke="black" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M14.25 15C14.6642 15 15 14.6642 15 14.25C15 13.8358 14.6642 13.5 14.25 13.5C13.8358 13.5 13.5 13.8358 13.5 14.25C13.5 14.6642 13.8358 15 14.25 15Z" fill="black" stroke="black" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M21.75 22.5C22.1642 22.5 22.5 22.1642 22.5 21.75C22.5 21.3358 22.1642 21 21.75 21C21.3358 21 21 21.3358 21 21.75C21 22.1642 21.3358 22.5 21.75 22.5Z" fill="black" stroke="black" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M4.5 18C4.5 19.7728 4.84919 21.5283 5.52763 23.1662C6.20606 24.8041 7.20047 26.2923 8.45406 27.5459C9.70765 28.7995 11.1959 29.7939 12.8338 30.4724C14.4717 31.1508 16.2272 31.5 18 31.5C19.7728 31.5 21.5283 31.1508 23.1662 30.4724C24.8041 29.7939 26.2923 28.7995 27.5459 27.5459C28.7995 26.2923 29.7939 24.8041 30.4724 23.1662C31.1508 21.5283 31.5 19.7728 31.5 18C31.5 16.2272 31.1508 14.4717 30.4724 12.8338C29.7939 11.1959 28.7995 9.70765 27.5459 8.45406C26.2923 7.20047 24.8041 6.20607 23.1662 5.52763C21.5283 4.84919 19.7728 4.5 18 4.5C16.2272 4.5 14.4717 4.84919 12.8338 5.52763C11.1959 6.20607 9.70765 7.20047 8.45406 8.45406C7.20047 9.70765 6.20606 11.1959 5.52763 12.8338C4.84919 14.4717 4.5 16.2272 4.5 18Z" stroke="black" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <span>Apply Promo Code</span>
                <div class="cart-promo__plus">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                        <path d="M5 12H19" stroke="black" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M12 5V19" stroke="black" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
            </div>
            <div class="cart-promo__body promo-wrapper">
                <div class="cart-promo__wrapper">
                    <input type="text" class="input" name="promo" value="{{ empty($cart->coupon) ? '' : $cart->coupon->code }}" >
                    @if(empty($cart->coupon))
                        <button type="button" class="btn js_apply_promocode" data-default="Apply coupon" data-success="Applied" disabled>Apply coupon</button>
                    @else
                        <button type="button" class="btn" data-default="Apply coupon" data-success="Applied" disabled>Applied</button>
                    @endif
                </div>
                <div class="promo-error" style="display: none">Promo code "<span></span>" does not exist!</div>
            </div>
        </div>
        <div class="cart-subtotal">
            <span>Subtotal</span>
            <span class="js_cart_price">£{{ number_format($cart->total_price, 0, '.', ' ') }}</span>
        </div>
        <div class="cart-subtotal promo-subtotal{{ !empty($cart->coupon) ? ' active' : '' }} checkout-price__item-coupon">
            <span>Coupon sale</span>
            <span class="js_coupon_sale">- £{{ number_format($cart->coupon_sale, 0, '.', ' ') }}</span>
            <i class="js_remove_coupon">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                    <path d="M14.74 9.00003L14.394 18M9.606 18L9.26 9.00003M19.228 5.79003C19.57 5.84203 19.91 5.89703 20.25 5.95603M19.228 5.79003L18.16 19.673C18.1164 20.2383 17.8611 20.7662 17.445 21.1513C17.029 21.5364 16.4829 21.7502 15.916 21.75H8.084C7.5171 21.7502 6.97102 21.5364 6.55498 21.1513C6.13894 20.7662 5.88359 20.2383 5.84 19.673L4.772 5.79003M19.228 5.79003C18.0739 5.61555 16.9138 5.48313 15.75 5.39303M4.772 5.79003C4.43 5.84103 4.09 5.89603 3.75 5.95503M4.772 5.79003C5.92613 5.61555 7.08623 5.48313 8.25 5.39303M15.75 5.39303V4.47703C15.75 3.29703 14.84 2.31303 13.66 2.27603C12.5536 2.24067 11.4464 2.24067 10.34 2.27603C9.16 2.31303 8.25 3.29803 8.25 4.47703V5.39303M15.75 5.39303C13.2537 5.20011 10.7463 5.20011 8.25 5.39303" stroke="black" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                </svg>
            </i>
        </div>
        <div class="cart-slider">
            <div class="section-head">
                <span class="section-title">Add to your order</span>
                <div class="custom-slider__controls">
                    <div class="arrows">
                        <div id="cart-slider-prev">
                            <svg xmlns="http://www.w3.org/2000/svg" width="56" height="56" viewBox="0 0 56 56" fill="none">
                                <path d="M28.0001 44.3333L11.6667 28L28.0001 11.6666" stroke="#0B0B0B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M44.3334 28H11.6667" stroke="#0B0B0B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <div id="cart-slider-next">
                            <svg xmlns="http://www.w3.org/2000/svg" width="56" height="56" viewBox="0 0 56 56" fill="none">
                                <path d="M27.9999 11.6667L44.3333 28L27.9999 44.3334" stroke="#0B0B0B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M11.6666 28L44.3333 28" stroke="#0B0B0B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>
            <div class="products-slider slick-slider"
                 data-slick='{"slidesToShow": 2, "slidesToScroll": 1, "infinite": true, "arrows": true, "dots": false, "prevArrow": "#cart-slider-prev", "nextArrow": "#cart-slider-next", "responsive":[{"breakpoint":767,"settings":{"arrows": false, "dots": true, "appendDots": "#cart-slider-dots"}}]}'>
                @foreach($popular_products as $i => $product)
                    <div class="slide">
                        @include('public.layouts.product', ['product' => $product, 'counter' => $i])
                    </div>
                @endforeach
            </div>
            <div class="custom-slider__controls">
                <div class="container">
                    <div class="dots" id="cart-slider-dots"></div>
                </div>
            </div>
        </div>
        <div class="cart-footer">
            <div class="cart-total">
                <span>Estimated Total</span>
                <span class="js_total_without_shipping">£{{ number_format($cart->total_price - $cart->coupon_sale, 0, '.', ' ') }}</span>
            </div>
            <a href="{{ base_url('/checkout') }}" class="btn">Check Out</a>
            <div class="cart-close cart-close__bot">Continue shopping</div>
        </div>
    </div>
@else
    <div class="cart-close cart-close__top">
        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 32 32" fill="none">
            <path d="M24 8L8 24" stroke="#0B0B0B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M8 8L24 24" stroke="#0B0B0B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
    </div>
    <div class="cart-head">
        <span class="amount js_cart_counter">{{ isset($cart) && $cart->total_quantity ? $cart->total_quantity : '0' }}</span> item in the cart
    </div>
    <div class="cart-title">
        Your cart
    </div>
    <div class="cart-empty">
        <img src="/images/empty-cart.png" loading="lazy" alt="">
        <span class="cart-empty__text">Your cart is empty. Tap to explore all cannabis products!</span>
        <a href="{{ base_url('/catalog') }}" class="btn">Shop All Products</a>
    </div>
@endif
