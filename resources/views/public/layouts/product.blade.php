@if(!empty($product))
<div class="product-item">
    <a href="{{ $product->link() }}"></a>
    <div class="product-photo">
{{--        {!! !empty($product->image) ? $product->image->optimized([684, 796], ['alt' => $product->name, 'loading' => 'lazy'], 'static') : '<img src="\images\larchik\no_image.jpg" alt="No image" loading="lazy">' !!}--}}
        @if(!empty($product->image))
            @if(isset($counter) && $counter > 3)
                {!! $product->image->image(
                   [684, 796],
                   ['alt' => $product->name, 'loading' => 'lazy', 'width' => 684, 'height' => 796],
                   'cover',
                   [
                       '<767' => '50vw',
                       '<1199' => '33vw',
                       '25vw'
                   ]
                ) !!}
            @else
                {!! $product->image->image(
                   [684, 796],
                   ['alt' => $product->name, 'fetchpriority' => 'high', 'width' => 684, 'height' => 796],
                   'cover',
                   [
                       '<767' => '50vw',
                       '<1199' => '33vw',
                       '25vw'
                   ]
                ) !!}
            @endif

        @else
            <img src="/images/larchik/no_image.jpg" alt="No image" loading="lazy">
        @endif
        <div class="circle-text">
            <div class="circle-text__main">
                <div class="circle-text__inner" aria-hidden="true"></div>
            </div>
        </div>
        <img class="face" src="/images/face.png" loading="lazy" alt="face">
    </div>
    <div class="product-info">
        <div class="product-info__top">
            <span class="product-info__title">{{ $product->name }}</span>
            <div class="product-info__price">
                @if($product->actual_price < $product->original_price && $product->original_price > 0)
                    <span class="old-price">£{{ $product->original_price }}</span>
                @endif
                @if($product->actual_price > 0)
                    <span class="current-price">£{{ $product->actual_price }}</span>
                @endif
            </div>
        </div>
        <div class="product-options__btn js_product_popup" data-id="{{ $product->id }}">View Options</div>
    </div>
</div>
@endif
