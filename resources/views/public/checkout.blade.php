@extends('public.layouts.main', ['simple_header' => true, 'without_footer' => true])
@section('page_vars')
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <!-- Код тега ремаркетинга Google -->
    @php
        $ecomm_prodid = [];
        $ecomm_totalvalue = [];
        foreach ($cart->get_products() as $code => $product){
            $ecomm_prodid[] = $product['product']->id;
            $ecomm_totalvalue[] = $product['price'];
        }
    @endphp
    <script>
        var google_tag_params = {
            ecomm_prodid: [{{ implode(',', $ecomm_prodid) }}],
            ecomm_pagetype: 'cart',
            ecomm_totalvalue: [{{ implode(',', $ecomm_totalvalue) }}],
        };
    </script>
    @include('public.layouts.microdata.open_graph', [
     'title' => $seo->meta_title,
     'description' => $seo->meta_description
     ])
@endsection

@section('content')
    <div class="page-checkout">
        <div class="page-top">
            <div class="container">
                {!! Breadcrumbs::render('checkout') !!}
                <h1 class="page-title">{{ $seo->name }}</h1>
            </div>
        </div>
        <div class="container">
            <form class="checkout-form validate-form" id="order-checkout">
                <div class="checkout-delivery">
                    <div class="checkout-delivery__inputs">
                        <span class="checkout-subtitle">Delivery Information</span>
                        <div class="form-row">
                            <div class="input-wrapper">
                                <label>First name<span>*</span></label>
                                <input class="input" type="text" name="first_name" data-rule-required="true" data-msg-required="Required item">
                            </div>
                            <div class="input-wrapper">
                                <label>Last name<span>*</span></label>
                                <input class="input" type="text" name="last_name" data-rule-required="true" data-msg-required="Required item">
                            </div>
                        </div>
                        <div class="input-wrapper input-wrapper-uk">
                            <label>Country<span>*</span></label>
                            <div class="input">United Kingdom (UK)</div>
                            <input type="hidden" id="county" name="county" value="England">
                        </div>
                        <div class="form-row">
                            <div class="input-wrapper">
                                <label>Street address<span>*</span></label>
                                <input class="input" type="text" id="address" name="address" placeholder="House number and street name" data-rule-required="true" data-msg-required="Required item">
                            </div>
                            <div class="input-wrapper">
                                <label>City / town<span>*</span></label>
                                <input class="input" type="text" id="city" name="city" data-rule-required="true" data-msg-required="Required item">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="input-wrapper">
                                <label>Postcode<span>*</span></label>
                                <input class="input" type="text" id="index" name="index" data-rule-required="true" data-msg-required="Required item">
                            </div>
                            <div class="input-wrapper">
                                <label>Email<span>*</span></label>
                                <input class="input" type="email" name="email" data-rule-required="true" data-msg-required="Required item">
                            </div>
                        </div>
                    </div>
                    <div class="checkout-delivery__payment">
                        <div class="payment">
                            <div class="payment__top">
                                <span class="checkout-subtitle">Payment</span>
                                <div>Get <span>10%</span> Off when Paying by Crypto!</div>
                            </div>
                            <div class="payment__methods">
                                <div class="checkout-payment">
                                    @foreach($payments as $payment_key => $payment)
                                        <div class="checkout-payment__item{{ $payment_key == $payment_method ? ' checked' : '' }}">
                                                <span class="checkbox-wrapper">
                                                    <input type="radio" name="payment" class="js_payment_method" id="{{ $payment_key }}" value="{{ $payment_key }}"{{ $payment_key == $payment_method ? ' checked' : '' }}>
                                                    <label for="{{ $payment_key }}">
                                                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                            <path d="M17 1H3C1.89543 1 1 1.89543 1 3V17C1 18.1046 1.89543 19 3 19H17C18.1046 19 19 18.1046 19 17V3C19 1.89543 18.1046 1 17 1Z" stroke="#FFBB44" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                            <path d="M7 10L9 12L13 8" stroke="#FFBB44" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                        </svg>
                                                        <div class="label">
                                                            <span>{{ $payment['name'] }}</span>
                                                        </div>
                                                    </label>
                                                </span>
                                            <div class="checkout-payment__inner"{!! $payment_key == $payment_method ? '' : ' style="display: none;"' !!}>
                                                @if($payment_key == 'mycryptocheckout')
                                                    <span class="label-text">Please Select the Cryptocurrency You'd Like to Use:</span>
                                                    <div class="input-wrapper select-wrapper">
                                                        <select class="select select-cripto" name="cryptocurrency">
                                                            @php $c = 0 @endphp
                                                            @foreach($payment['currencies'] as $currency => $currency_name)
                                                                <option value="{{ $currency }}"{!! $c == 0 ? ' selected="selected"' : '' !!}>{{ $currency_name }}</option>
                                                                @php $c++ @endphp
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="payment-method__text">
                                                        * You might consider using a different cryptocurrency, such as ETH or LTC, instead of BTC to avoid high transaction fees.
                                                    </div>
                                                @elseif($payment_key == 'btcpay')
                                                    <span class="label-text">You will be redirected to BTCPay to complete your purchase.</span>
                                                @else
                                                    <span class="label-text">Our support team will provide you with a payment link to complete your order</span>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="checkout-order">
                    <span class="checkout-subtitle">Your order</span>
                    <div class="cart-items">
                        @foreach ($cart->get_products() as $code => $product)
                            @if(is_object($product['product']))
                                <div class="cart-item">
                                    <div class="cart-item__del js_remove_product_from_checkout" data-id="{{ $code }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                                            <path
                                                d="M14.74 9.00003L14.394 18M9.606 18L9.26 9.00003M19.228 5.79003C19.57 5.84203 19.91 5.89703 20.25 5.95603M19.228 5.79003L18.16 19.673C18.1164 20.2383 17.8611 20.7662 17.445 21.1513C17.029 21.5364 16.4829 21.7502 15.916 21.75H8.084C7.5171 21.7502 6.97102 21.5364 6.55498 21.1513C6.13894 20.7662 5.88359 20.2383 5.84 19.673L4.772 5.79003M19.228 5.79003C18.0739 5.61555 16.9138 5.48313 15.75 5.39303M4.772 5.79003C4.43 5.84103 4.09 5.89603 3.75 5.95503M4.772 5.79003C5.92613 5.61555 7.08623 5.48313 8.25 5.39303M15.75 5.39303V4.47703C15.75 3.29703 14.84 2.31303 13.66 2.27603C12.5536 2.24067 11.4464 2.24067 10.34 2.27603C9.16 2.31303 8.25 3.29803 8.25 4.47703V5.39303M15.75 5.39303C13.2537 5.20011 10.7463 5.20011 8.25 5.39303"
                                                stroke="black" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
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
                                        <a href="{{ $product['product']->link() }}" class="cart-item__title" target="_blank">{{ $product['product']->name }}</a>
                                        <span class="cart-item__tag">{{ $product['product']->category->name }}</span>
                                        <div class="cart-item__footer">
                                            <div class="cart-item__counter js_counter">
                                                    <span class="minus">
														<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
														  <path d="M5 12H19" stroke="black" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
														</svg>
									                </span>
                                                <input class="value js_checkout_qty" value="{{ $product['quantity'] }}" type="text" data-prod-id="{{ $code }}" oninput="this.value=this.value.replace(/[^0-9\.]/g,'');">
                                                <span class="plus">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                                                          <path d="M5 12H19" stroke="black" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                          <path d="M12 5V19" stroke="black" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                        </svg>
                                                    </span>
                                            </div>
                                            <div class="cart-item__price">£{{ $product['price'] }}</div>
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
                                            <input class="value js_checkout_qty" value="{{ $product['quantity'] }}" type="text" data-prod-id="{{ $code }}" oninput="this.value=this.value.replace(/[^0-9\.]/g,'');">
                                            <span class="plus">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                                                      <path d="M5 12H19" stroke="black" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                      <path d="M12 5V19" stroke="black" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                    </svg>
                                                </span>
                                        </div>
                                        <div class="cart-item__del">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                                                <path
                                                    d="M14.74 9.00003L14.394 18M9.606 18L9.26 9.00003M19.228 5.79003C19.57 5.84203 19.91 5.89703 20.25 5.95603M19.228 5.79003L18.16 19.673C18.1164 20.2383 17.8611 20.7662 17.445 21.1513C17.029 21.5364 16.4829 21.7502 15.916 21.75H8.084C7.5171 21.7502 6.97102 21.5364 6.55498 21.1513C6.13894 20.7662 5.88359 20.2383 5.84 19.673L4.772 5.79003M19.228 5.79003C18.0739 5.61555 16.9138 5.48313 15.75 5.39303M4.772 5.79003C4.43 5.84103 4.09 5.89603 3.75 5.95503M4.772 5.79003C5.92613 5.61555 7.08623 5.48313 8.25 5.39303M15.75 5.39303V4.47703C15.75 3.29703 14.84 2.31303 13.66 2.27603C12.5536 2.24067 11.4464 2.24067 10.34 2.27603C9.16 2.31303 8.25 3.29803 8.25 4.47703V5.39303M15.75 5.39303C13.2537 5.20011 10.7463 5.20011 8.25 5.39303"
                                                    stroke="black" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                    @include('public.layouts.checkout_spend')
                    <div class="checkout-promo">
                        <div class="checkout-promo__head">
                            <svg width="36" height="36" viewBox="0 0 36 36" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <g clip-path="url(#clip0_491_3668)">
                                    <path d="M22.5 7.5V10.5" stroke="#411CC1" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M22.5 16.5V19.5" stroke="#411CC1" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M22.5 25.5V28.5" stroke="#411CC1" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path
                                        d="M7.5 7.5H28.5C29.2956 7.5 30.0587 7.81607 30.6213 8.37868C31.1839 8.94129 31.5 9.70435 31.5 10.5V15C30.7044 15 29.9413 15.3161 29.3787 15.8787C28.8161 16.4413 28.5 17.2044 28.5 18C28.5 18.7956 28.8161 19.5587 29.3787 20.1213C29.9413 20.6839 30.7044 21 31.5 21V25.5C31.5 26.2956 31.1839 27.0587 30.6213 27.6213C30.0587 28.1839 29.2956 28.5 28.5 28.5H7.5C6.70435 28.5 5.94129 28.1839 5.37868 27.6213C4.81607 27.0587 4.5 26.2956 4.5 25.5V21C5.29565 21 6.05871 20.6839 6.62132 20.1213C7.18393 19.5587 7.5 18.7956 7.5 18C7.5 17.2044 7.18393 16.4413 6.62132 15.8787C6.05871 15.3161 5.29565 15 4.5 15V10.5C4.5 9.70435 4.81607 8.94129 5.37868 8.37868C5.94129 7.81607 6.70435 7.5 7.5 7.5Z"
                                        stroke="#411CC1" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </g>
                            </svg>
                            <div>Have a coupon? <span>Click here to enter your code</span></div>
                        </div>
                        <div class="checkout-promo__body promo-wrapper" style="display: none;">
                                <span>
                                    <span>If you have a coupon code, please apply it below.</span>
                                    <span class="checkout-promo__cancel">Cancel</span>
                                </span>
                            <div class="input-wrapper">
                                <input type="text" class="input">
                                <button type="button" class="btn js_apply_promocode" data-default="Apply coupon" data-success="Applied">Apply coupon</button>
                            </div>
                            <div class="promo-error" style="display: none">Promo code "<span></span>" does not exist!</div>
                        </div>
                    </div>
                    <div id="checkout_prices">
                        @include('public.layouts.checkout_prices')
                    </div>
                    <div class="checkout-footer">
                        <div class="checkout-footer__text">
                            Your personal data will be used only to process your order, and support your experience throughout this website. <a href="javascript:void(0)">Privacy policy</a>
                        </div>
                        <div class="checkbox-wrapper">
                            <input type="checkbox" class="input-checkbox" name="terms" id="terms">
                            <label for="terms">
                                <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M17 1H3C1.89543 1 1 1.89543 1 3V17C1 18.1046 1.89543 19 3 19H17C18.1046 19 19 18.1046 19 17V3C19 1.89543 18.1046 1 17 1Z" stroke="#FFBB44" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
                                    <path d="M7 10L9 12L13 8" stroke="#FFBB44" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
                                </svg>
                                <span>
									I have read and agree to the website <a href="/terms-of-use/" target="_blank">Terms and Conditions</a>
								</span>
                            </label>
                            <input class="input" type="text" name="terms_checker" data-rule-required="true" data-msg-required="Required" style="position:absolute;opacity: 0;visibility: hidden;pointer-events: none">
                        </div>
                        <div class="checkbox-wrapper">
                            <input type="checkbox" class="input-checkbox" id="updates">
                            <label for="updates">
                                <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M17 1H3C1.89543 1 1 1.89543 1 3V17C1 18.1046 1.89543 19 3 19H17C18.1046 19 19 18.1046 19 17V3C19 1.89543 18.1046 1 17 1Z" stroke="#FFBB44" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
                                    <path d="M7 10L9 12L13 8" stroke="#FFBB44" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
                                </svg>
                                <span>
									I would like my email address to be used to receive tracking updates from Shipping Company (optional).
								</span>
                            </label>
                        </div>
                    </div>
                    <div class="checkout-btn">
                        <button type="submit" class="btn js_checkout_submit">Continue to Payment</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    @if(!env('APP_DEBUG'))
        <script src="https://cdn.jsdelivr.net/npm/@ideal-postcodes/address-finder-bundled"></script>
        <script>
            IdealPostcodes.AddressFinder.setup({
                apiKey: "ak_mjoi15mxhkedZ1dz24agyIIpQKIaE",
                outputFields: {
                    line_1: "#address",
                    post_town: "#city",
                    postcode: "#index",
                    country: "#county",
                },
            });
        </script>
    @endif
@endsection
