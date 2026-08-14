<img id="jsProductMainImage" src="{{ !empty($product->image) ? $product->image->url([600, 600]) : '/images/larchik/no_image.jpg' }}" alt="{{ $product->name }}" class="img-fluid rounded product-card__image w-100" loading="lazy">
<div class="d-flex gap-2 flex-wrap mt-2">
    @if(!empty($product->image))
        <img src="{{ $product->image->url([100, 100]) }}" data-full="{{ $product->image->url([600, 600]) }}" alt="" class="js-gallery-thumb rounded active" width="80" height="80" loading="lazy" role="button">
    @endif
    @foreach($product->gallery as $gallery_item)
        <img src="{{ $gallery_item->image->url([100, 100]) }}" data-full="{{ $gallery_item->image->url([600, 600]) }}" alt="" class="js-gallery-thumb rounded" width="80" height="80" loading="lazy" role="button">
    @endforeach
</div>