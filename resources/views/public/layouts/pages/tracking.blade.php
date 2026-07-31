@extends('public.layouts.main')
@section('page_vars')
    @include('public.layouts.microdata.open_graph', [
     'title' => $seo->meta_title,
     'description' => $seo->meta_description,
     'image' => '/images/logo.png'
     ])
@endsection

@section('content')
    <div class="page-order-tracking">
        <div class="page-top">
            <div class="container">
                {!! Breadcrumbs::render('page', $page) !!}
                <h1 class="page-title">Track your order</h1>
            </div>
        </div>
        <div class="order-tracking">
            <div class="container">
                <div class="row">
                    <div class="col-lg-6">
                        <div class="order-tracking__info">
                            <p class="order-tracking__text">{{ $seo->description }}</p>
                            <form class="validate-form" id="js_track_order" novalidate>
                                <div class="input-wrapper">
                                    <label for="orderid">Order ID</label>
                                    <input class="input" id="orderid" name="order_id" type="text" data-rule-required="true" data-msg-required="This field is required" placeholder="1679">
                                </div>
                                <div class="input-wrapper">
                                    <label for="email">Billing email</label>
                                    <input class="input" id="email" type="email" name="email" data-rule-required="true" data-msg-required="This field is required" data-msg-email="Enter a valid email address" placeholder="client@mail.com">
                                </div>
                                <button type="submit" class="btn">Track Order</button>
                            </form>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="order-tracking__pic">
                            <img src="/images/order.png" class="lazy" alt="order">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
