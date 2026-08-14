@if($items->count())
    <div class="container py-4">
        <h2 class="h4 mb-3">{{ $heading }}</h2>
        <div class="position-relative px-4">
            <div class="js-products-slider">
                @foreach($items as $item)
                    <div>
                        @include('public.layouts.product', ['product' => $item])
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endif