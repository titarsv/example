@extends('public.layouts.main', ['wrapper_class' => 'article-wrapper'])
@section('page_vars')
    {{--<script>--}}
        {{--gtag('event', 'page_view', {--}}
            {{--'send_to': 'AW-623220592',--}}
            {{--'value': 'replace with value',--}}
            {{--'items': [{--}}
                {{--'id': 'replace with value',--}}
                {{--'google_business_vertical': 'retail'--}}
            {{--}]--}}
        {{--});--}}
    {{--</script>--}}
    @include('public.layouts.microdata.open_graph', [
     'title' => $seo->meta_title,
     'description' => $seo->meta_description
     ])
@endsection

@section('content')
    <div class="section-cart">
        <div class="container hidden-sm hidden-md hidden-lg">
            {!! Breadcrumbs::render('cart') !!}
        </div>
        <div class="container">
            <div class="col">
                <div class="cart-wrapper">
                    <span class="cart-title">{{ $seo->name }}</span>
                    <div class="cart-head">
                        <span class="cart-head__main">{{ trans('app.GOODS') }}</span>
                        <span class="cart-head__price">{{ trans('app.PRICE') }}</span>
                        <span class="cart-head__count">{{ trans('app.QUANTITY') }}</span>
                        <span class="cart-head__sum">{{ trans('app.SUM') }}</span>
                    </div>
                    @include('public.layouts.cart_items')
                    <div class="cart-footer">
                        <p class="cart-footer__price">{{ trans('app.total') }}:<span>{{ $cart->total_price }} ₴</span></p>
                        <div class="cart-footer__delivery">{{ trans('app.cart_footer_delivery') }}</div>
                        @if(!empty($cart->total_quantity))
                            {{--<span class="cart-footer__footnote">{{ trans('app.Delivery_is_calculated_after_choosing_the_delivery_method') }}</span>--}}
                            <a class="cart-footer__btn" href="{{ base_url('/checkout') }}">{{ trans('app.ORDER') }}</a>
                            <div class="cart-modal__chck">
                                <input type="checkbox" id="cart-page-agree" checked>
                                <label for="cart-page-agree">{{ __('Подтверждая заказ, я принимаю условия') }} <a href="javascript:void(0)">{{ __('пользовательского соглашения') }}</a></label>
                            </div>
                        @endif
                    </div>
                    @if($recommendations->count())
                        <div class="cart-similar">
                            <div class="section-title">{{ trans('app.RECOMMENDATIONS') }}</div>
                            <div class="row products-wrapper">
                                @foreach($recommendations as $recommendation)
                                    @include('public.layouts.product', ['product' => $recommendation, 'size' => 'sm'])
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
