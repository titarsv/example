@extends('public.layouts.main')
@section('meta')
    <title>Thank you for your order | {{ env('APP_NAME') }}</title>
@endsection
@section('page_vars')
    @include('public.layouts.microdata.open_graph', [
     'title' => 'Thank you for your order',
     'description' => 'Thank you for your order'
     ])
@endsection

@section('content')
    @if($order->payment == 'btcpay' || ($order->payment == 'mycryptocheckout' && $order->status_id == 1))
        {{--  оплата криптой  --}}
        <div class="section page-thx">
            <div class="page-top">
                <div class="container">
                    {!! Breadcrumbs::render('thanks') !!}
                    <h1 class="page-title">Nice choice! We’ve got your order.</h1>
                    <div class="page-description">Boom! Your order just landed in our system — and we’re already packing it up. You’ve got great taste (obviously).</div>
                </div>
            </div>
            <div class="thx-main">
                <div class="container">
                    <div class="row thx-wrapper">
                        <div class="col-lg-6">
                            <div class="thx-item">
                                <span class="thx-item__title">🔥 Order #{{ $order->id }} confirmed</span>
                                <p>We’ve sent all the details to {{ $order->email }}.</p>
                                <p>Here you can <a href="https://properloud.cc/tracking" style="font-weight: 500; text-decoration: underline">track your order.</a></p>
                            </div>
                            <div class="thx-item">
                                <span class="thx-item__title">🚀 What’s next</span>
                                <p>We’re rolling your order and sending it out within 24 hours.</p>
                            </div>
                            <div class="thx-item">
                                <span class="thx-item__title">❤️ Big love from the {{ env('APP_NAME') }} crew!</span>
                                <p>Stay loud and chill!</p>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="thx-item">
                                <span class="thx-item__title">💚 Here’s what you scored:</span>
                                <div class="ordered-items">
                                    <div class="ordered-items-details">
                                        <table class="cryptocheckout-right__table">
                                            <tbody>
                                            <tr>
                                                <td>
                                                    <div>
                                                        <i>
                                                            <img src="/images/order-01.png" class="img-product" loading="lazy" alt="Products">
                                                        </i>
                                                        <span>Product:</span>
                                                    </div>
                                                </td>
                                                <td>
                                                    @foreach($order->getProducts() as $i => $product)
                                                        {{ $product['product']->name }}{{ $product['quantity'] > 1 ? ' x' . $product['quantity'] : '' }}{{ $i < count($order->getProducts()) - 1 ? ', ' : '' }}
                                                    @endforeach
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <div>
                                                        <i>
                                                            <img src="/images/order-02.png" class="img-subtotal" loading="lazy" alt="Subtotal">
                                                        </i>
                                                        <span>Subtotal:</span>
                                                    </div>
                                                </td>
                                                <td>£{{ $order->total_price }}</td>
                                            </tr>
                                            @if($order->total_sale > 0)
                                            <tr>
                                                <td>
                                                    <div>
                                                        <i>
                                                            <img src="/images/promocode.png" loading="lazy" alt="Discount">
                                                        </i>
                                                        <span>Discount:</span>
                                                    </div>
                                                </td>
                                                <td>-£{{ $order->total_sale }}</td>
                                            </tr>
                                            @endif
                                            <tr>
                                                <td>
                                                    <div>
                                                        <i>
                                                            <img src="/images/order-03.png" class="img-shipping" loading="lazy" alt="Shipping">
                                                        </i>
                                                        <span>Shipping:</span>
                                                    </div>
                                                </td>
                                                <td>{{ !empty($order->delivery_cost) ? '£'.$order->delivery_cost : 'Free shipping' }}</td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <div>
                                                        <i>
                                                            <img src="/images/order-04.png" class="img-payment" loading="lazy" alt="Payment">
                                                        </i>
                                                        <span>Payment:</span>
                                                    </div>
                                                </td>
                                                <td>{{ $order->payment_name }}</td> <!--BTCPay-->
                                            </tr>
                                            <tr>
                                                <td>
                                                    <div>
                                                        <i>
                                                            <img src="/images/order-05.png" class="img-total" loading="lazy" alt="Total">
                                                        </i>
                                                        <span>Total:</span>
                                                    </div>
                                                </td>
                                                <td>£{{ $order->full_price }}</td>
                                            </tr>
                                            </tbody>
                                        </table>
                                        <div class="ordered-items-details__address">
                                            <div>
                                                <div>Billing address</div>
                                                <span>{{ $user_info->name }}</span>
                                                <span>{{ $order->street }}</span>
                                                <span>{{ $order->city }}</span>
                                                <span>{{ $order->index }}</span>
                                                <span>United Kingdom</span>
                                                <span>{{ $order->email }}</span>
                                            </div>
                                            <div>
                                                <div>Shipping address</div>
                                                <span>{{ $user_info->name }}</span>
                                                <span>{{ $order->street }}</span>
                                                <span>{{ $order->city }}</span>
                                                <span>{{ $order->index }}</span>
                                                <span>United Kingdom</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        {{--  оплата криптой  --}}
    @else
        {{--    оплата картой   --}}
        <div class="section page-thx">
            <div class="page-top">
                <div class="container">
                    {!! Breadcrumbs::render('thanks') !!}
                    <h1 class="page-title">Nice choice! We’ve got your order.</h1>
                    <div class="page-description">Boom! Your order just landed in our system — and we’re already packing it up. You’ve got great taste (obviously).</div>
                </div>
            </div>
            <div class="thx-main">
                <div class="container">
                    <div class="row thx-wrapper">
                        <div class="col-lg-6">
                            <div class="thx-item">
                                <span class="thx-item__title">🔥 Order #{{ $order->id }} confirmed</span>
                                <p>We’ve sent all the details to {{ $order->email }}.</p>
                                <p>Here you can <a href="https://properloud.cc/tracking" style="font-weight: 500; text-decoration: underline">track your order.</a></p>
                                <div class="cryptocheckout-left__text" style="margin-top: 20px">
                                    <span class="cryptocheckout-left__title">Complete your order by following these steps:</span>
                                    <ol>
                                        <li>Contact our support team on Telegram: <a href="https://t.me/Proper_Support420" rel="nofollow noopener" target="_blank">@Proper_Support420</a></li>
                                        <li>When messaging support, provide your order number (you can find it in the order confirmation email).</li>
                                        <li>Our team will send you the Skrill payment details required to finalize your purchase.</li>
                                        <li>After the payment is received, we will confirm it and start processing your order.</li>
                                        <li>If you have any questions or need assistance, feel free to reach out to our support team - we’re happy to help.</li>
                                    </ol></div>
                            </div>
                            <div class="thx-item">
                                <span class="thx-item__title">🚀 What’s next</span>
                                <p>We’re rolling your order and sending it out within 24 hours.</p>
                            </div>
                            <div class="thx-item">
                                <span class="thx-item__title">❤️ Big love from the {{ env('APP_NAME') }} crew!</span>
                                <p>Stay loud and chill!</p>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="thx-item">
                                <span class="thx-item__title">💚 Here’s what you scored:</span>
                                <div class="ordered-items">
                                    <div class="ordered-items-details">
                                        <table class="cryptocheckout-right__table">
                                            <tbody>
                                            <tr>
                                                <td>
                                                    <div>
                                                        <i>
                                                            <img src="/images/order-01.png" class="img-product" loading="lazy" alt="Products">
                                                        </i>
                                                        <span>Product:</span>
                                                    </div>
                                                </td>
                                                <td>
                                                    @foreach($order->getProducts() as $i => $product)
                                                        {{ $product['product']->name }}{{ $product['quantity'] > 1 ? ' x' . $product['quantity'] : '' }}{{ $i < count($order->getProducts()) - 1 ? ', ' : '' }}
                                                    @endforeach
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <div>
                                                        <i>
                                                            <img src="/images/order-02.png" class="img-subtotal" loading="lazy" alt="Subtotal">
                                                        </i>
                                                        <span>Subtotal:</span>
                                                    </div>
                                                </td>
                                                <td>£{{ $order->total_price }}</td>
                                            </tr>
                                            @if($order->total_sale > 0)
                                            <tr>
                                                <td>
                                                    <div>
                                                        <i>
                                                            <img src="/images/promocode.png" loading="lazy" alt="Discount">
                                                        </i>
                                                        <span>Discount:</span>
                                                    </div>
                                                </td>
                                                <td>-£{{ $order->total_sale }}</td>
                                            </tr>
                                            @endif
                                            <tr>
                                                <td>
                                                    <div>
                                                        <i>
                                                            <img src="/images/order-03.png" class="img-shipping" loading="lazy" alt="Shipping">
                                                        </i>
                                                        <span>Shipping:</span>
                                                    </div>
                                                </td>
                                                <td>{{ !empty($order->delivery_cost) ? '£'.$order->delivery_cost : 'Free shipping' }}</td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <div>
                                                        <i>
                                                            <img src="/images/order-04.png" class="img-payment" loading="lazy" alt="Payment">
                                                        </i>
                                                        <span>Payment:</span>
                                                    </div>
                                                </td>
                                                <td>{{ $order->payment_name }}</td> <!--Cart Payment-->
                                            </tr>
                                            <tr>
                                                <td>
                                                    <div>
                                                        <i>
                                                            <img src="/images/order-05.png" class="img-total" loading="lazy" alt="Total">
                                                        </i>
                                                        <span>Total:</span>
                                                    </div>
                                                </td>
                                                <td>£{{ $order->full_price }}</td>
                                            </tr>
                                            </tbody>
                                        </table>
                                        <div class="ordered-items-details__address">
                                            <div>
                                                <div>Billing address</div>
                                                <span>{{ $user_info->name }}</span>
                                                <span>{{ $order->street }}</span>
                                                <span>{{ $order->city }}</span>
                                                <span>{{ $order->index }}</span>
                                                <span>United Kingdom</span>
                                                <span>{{ $order->email }}</span>
                                            </div>
                                            <div>
                                                <div>Shipping address</div>
                                                <span>{{ $user_info->name }}</span>
                                                <span>{{ $order->street }}</span>
                                                <span>{{ $order->city }}</span>
                                                <span>{{ $order->index }}</span>
                                                <span>United Kingdom</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        {{--    оплата картой   --}}

        <div class="section section-products">
            <div class="container">
                <div class="section-head">
                    <h2 class="section-title">While you wait 😉</h2>
                </div>
            </div>
            <div class="products-slider slick-slider"
                 data-slick='{"slidesToShow": 5, "slidesToScroll": 1, "infinite": true, "arrows": true, "dots": true, "prevArrow": "#products-slider-prev", "nextArrow": "#products-slider-next", "appendDots": "#products-slider-dots", "responsive":[{"breakpoint":1199,"settings":{"slidesToShow": 4}}, {"breakpoint":991,"settings":{"slidesToShow": 3}}, {"breakpoint":575,"settings":{"slidesToShow": 2}}]}'>
                @foreach($products as $product)
                    <div class="slide">
                        @include('public.layouts.product', ['product' => $product])
                    </div>
                @endforeach
            </div>
            <div class="custom-slider__controls">
                <div class="container">
                    <div class="dots" id="products-slider-dots"></div>
                    <div class="arrows">
                        <div id="products-slider-prev">
                            <svg xmlns="http://www.w3.org/2000/svg" width="56" height="56" viewBox="0 0 56 56" fill="none">
                                <path d="M28.0001 44.3333L11.6667 28L28.0001 11.6666" stroke="#0B0B0B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M44.3334 28H11.6667" stroke="#0B0B0B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <div id="products-slider-next">
                            <svg xmlns="http://www.w3.org/2000/svg" width="56" height="56" viewBox="0 0 56 56" fill="none">
                                <path d="M27.9999 11.6667L44.3333 28L27.9999 44.3334" stroke="#0B0B0B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M11.6666 28L44.3333 28" stroke="#0B0B0B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection
