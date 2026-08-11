@if($products->count())
    <div class="row row-cols-2 row-cols-md-3 row-cols-xl-4 g-3">
        @foreach($products as $product)
            <div class="col">
                @include('public.layouts.product', ['product' => $product])
            </div>
        @endforeach
    </div>
@else
    <div class="alert alert-light border text-center">По вашему запросу ничего не найдено.</div>
@endif
