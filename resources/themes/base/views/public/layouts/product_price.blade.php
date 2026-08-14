<div class="fs-3 fw-bold mb-3">
    @if($product->actual_price > 0)
        <span class="js_current_price" data-price="{{ $product->actual_price }}" data-original_price="{{ $product->original_price }}">₽{{ $product->actual_price }}</span>
    @endif
    @if($product->actual_price < $product->original_price && $product->original_price > 0)
        <span class="text-muted text-decoration-line-through js_old_price fs-5 ms-2">₽{{ $product->original_price }}</span>
    @endif
</div>