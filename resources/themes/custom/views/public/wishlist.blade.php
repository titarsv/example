@extends('public.layouts.main')
@section('meta')
    <title>Избранное | {{ env('APP_NAME') }}</title>
@endsection

@section('content')
    <div class="container py-4">
        <h1 class="h3 mb-4">Избранное</h1>

        @if($products->count())
            <div class="row row-cols-2 row-cols-md-3 row-cols-xl-4 g-3">
                @foreach($products as $product)
                    <div class="col">
                        @include('public.layouts.product', ['product' => $product])
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-4">
                <i class="bi bi-heart display-4 text-muted mb-3"></i>
                <p class="text-muted">В избранном пока ничего нет. Добавляйте товары нажатием на сердечко в каталоге.</p>
                <a href="{{ base_url('/catalog') }}" class="btn btn-primary">Перейти в каталог</a>
            </div>
        @endif
    </div>
@endsection
