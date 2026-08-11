@extends('public.layouts.main')
@section('meta')
    <title>Заказ №{{ $order->id }} | {{ env('APP_NAME') }}</title>
@endsection

@section('content')
    <div class="container py-4">
        <h1 class="h3 mb-1">Заказ №{{ $order->id }}</h1>
        <p class="text-muted">
            Оформлен {{ $order->created_at->format('d.m.Y') }} &middot;
            статус: <span class="badge text-bg-light border">{{ $order->status ? $order->status->status : trans('locale.Unfinished') }}</span>
        </p>

        <div class="row g-4">
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-body">
                        <table class="table table-sm mb-0">
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
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card mb-3">
                    <div class="card-body">
                        <h2 class="h6">Адрес доставки</h2>
                        <div class="small">
                            <div>{{ $user_info->name }}</div>
                            <div>{{ $order->street }}</div>
                            <div>{{ $order->city }}, {{ $order->index }}</div>
                            <div>{{ $order->email }}</div>
                        </div>
                    </div>
                </div>
                <a href="{{ base_url('/tracking') }}" class="btn btn-outline-primary">Отследить заказ</a>
            </div>
        </div>
    </div>
@endsection
