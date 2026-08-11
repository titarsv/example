@extends('public.layouts.main')
@section('meta')
    <title>Личный кабинет | {{ env('APP_NAME') }}</title>
@endsection

@section('content')
    <div class="container py-4">
        <h1 class="h3 mb-4">Личный кабинет</h1>
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-body">
                        <div class="fw-semibold">{{ $user->first_name }} {{ $user->last_name }}</div>
                        <div class="text-muted small mb-3">{{ $user->email }}</div>
                        <div class="d-grid gap-2">
                            <a href="{{ base_url('/user/orders') }}" class="btn btn-outline-primary">История заказов</a>
                            @if(module_active('wishlist'))
                                <a href="{{ base_url('/wishlist') }}" class="btn btn-outline-primary">Избранное</a>
                            @endif
                            <a href="{{ base_url('/logout') }}" class="btn btn-outline-secondary">Выйти</a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-body">
                        <h2 class="h5 mb-3">Последние заказы</h2>
                        @if($orders->count())
                            <div class="table-responsive">
                                <table class="table align-middle">
                                    <tbody>
                                    @foreach($orders as $order)
                                        <tr>
                                            <td><a href="{{ base_url('/user/orders/'.$order->id) }}">Заказ №{{ $order->id }}</a></td>
                                            <td>{{ $order->created_at->format('d.m.Y') }}</td>
                                            <td><span class="badge text-bg-light border">{{ $order->status ? $order->status->status : trans('locale.Unfinished') }}</span></td>
                                            <td class="text-end">₽{{ $order->full_price }}</td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="text-muted mb-0">У вас пока нет заказов. <a href="{{ base_url('/catalog') }}">Перейти в каталог</a></p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
