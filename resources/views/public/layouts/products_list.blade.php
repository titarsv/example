@if($products->count())
    @foreach($products as $i => $product)
        @if(!empty($banners['list']) && isset($banners['list'][$i+1]))
            @include('public.layouts.list_banner', ['banner' => $banners['list'][$i+1]])
        @endif
        @include('public.layouts.product', ['product' => $product, 'counter' => $i])
    @endforeach
    @if(isset($banners['list'][36]))
        @include('public.layouts.list_banner', ['banner' => $banners['list'][36]])
    @endif
@else
    <div class="container"><p class="note-msg"><span>There are no products matching your selection.</span></p></div>
@endif
