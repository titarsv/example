@extends('public.layouts.main')
@section('meta')
    <title>Спасибо за заказ | {{ env('APP_NAME') }}</title>
@endsection
@section('page_vars')
    @include('public.layouts.microdata.open_graph', [
     'title' => 'Спасибо за заказ',
     'description' => 'Спасибо за заказ'
     ])
@endsection

@section('content')
    <div class="container py-4">
        <div class="mb-3">{!! Breadcrumbs::render('thanks') !!}</div>

        <div class="text-center mb-4">
            <i class="bi bi-check-circle text-success display-3"></i>
            <h1 class="h3 mt-3">Спасибо! Заказ оформлен.</h1>
            <p class="text-muted">Мы получили ваш заказ и уже начали его обработку.</p>
        </div>

        <div class="row justify-content-center g-4">
            <div class="col-lg-5">
                <div class="card h-100">
                    <div class="card-body">
                        <h2 class="h5">Заказ №{{ $order->id }}</h2>
                        <p>Мы отправили все детали на {{ $order->email }}.</p>
                        <p>Здесь можно <a href="{{ base_url('/tracking') }}">отследить статус заказа</a>.</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="card h-100">
                    <div class="card-body">
                        <h2 class="h5">Ваш заказ</h2>
                        <table class="table table-sm">
                            <tbody>
                            <tr>
                                <th class="fw-normal text-muted">Товары</th>
                                <td>
                                    @foreach($order->getProducts() as $i => $product)
                                        {{ $product['product']->name }}{{ $product['quantity'] > 1 ? ' x' . $product['quantity'] : '' }}{{ $i < count($order->getProducts()) - 1 ? ', ' : '' }}
                                    @endforeach
                                </td>
                            </tr>
                            <tr>
                                <th class="fw-normal text-muted">Сумма</th>
                                <td>₽{{ $order->total_price }}</td>
                            </tr>
                            @if($order->total_sale > 0)
                                <tr>
                                    <th class="fw-normal text-muted">Скидка</th>
                                    <td>-₽{{ $order->total_sale }}</td>
                                </tr>
                            @endif
                            <tr>
                                <th class="fw-normal text-muted">Доставка</th>
                                <td>{{ !empty($order->delivery_cost) ? '₽'.$order->delivery_cost : 'Бесплатно' }}</td>
                            </tr>
                            <tr>
                                <th class="fw-normal text-muted">Оплата</th>
                                <td>{{ $order->payment_name }}</td>
                            </tr>
                            <tr class="fw-bold">
                                <th class="fw-normal text-muted">Итого</th>
                                <td>₽{{ $order->full_price }}</td>
                            </tr>
                            </tbody>
                        </table>
                        <div class="small text-muted">
                            <div>{{ $user_info->name }}</div>
                            <div>{{ $order->street }}, {{ $order->city }}, {{ $order->index }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if($products->count())
        <div class="container py-4 border-top">
            <h2 class="h4 mb-3">Пока вы здесь</h2>
            <div class="position-relative px-4">
                <div class="js-products-slider">
                    @foreach($products as $product)
                        <div>
                            @include('public.layouts.product', ['product' => $product])
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif
@endsection
