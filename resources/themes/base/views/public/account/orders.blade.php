@extends('public.layouts.main')
@section('meta')
    <title>История заказов | {{ env('APP_NAME') }}</title>
@endsection

@section('content')
    <div class="container py-4">
        <h1 class="h3 mb-4">История заказов</h1>

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
            <div class="mt-3">
                @include('public.layouts.pagination', ['paginator' => $orders])
            </div>
        @else
            <p class="text-muted">У вас пока нет заказов. <a href="{{ base_url('/catalog') }}">Перейти в каталог</a></p>
        @endif
    </div>
@endsection
