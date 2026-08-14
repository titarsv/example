@if(module_active('cart_checkout') || module_active('wishlist') || module_active('compare'))
    <div class="d-flex flex-wrap gap-2 mb-4">
        @if(module_active('cart_checkout'))
            <button type="button" class="btn btn-primary js-add-to-cart" data-id="{{ $product->id }}">В корзину</button>
            <a href="{{ base_url('/checkout') }}" class="btn btn-outline-primary js-add-to-cart" data-id="{{ $product->id }}">Купить сейчас</a>
        @endif
        @if(module_active('wishlist') && \Cartalyst\Sentinel\Native\Facades\Sentinel::check())
            <button type="button" class="btn btn-outline-secondary js-wishlist-toggle{{ $product->in_wish() ? ' text-danger' : '' }}" data-id="{{ $product->id }}">
                <i class="bi {{ $product->in_wish() ? 'bi-heart-fill' : 'bi-heart' }}"></i>
            </button>
        @endif
        @if(module_active('compare'))
            @php($in_compare = in_array($product->id, session(\Modules\Compare\Services\CompareService::SESSION_KEY, [])))
            <button type="button" class="btn btn-outline-secondary js-compare-toggle{{ $in_compare ? ' text-primary' : '' }}" data-id="{{ $product->id }}">
                <i class="bi bi-arrow-left-right"></i>
                <span class="js-compare-label">{{ $in_compare ? 'В сравнении' : 'Сравнить' }}</span>
            </button>
        @endif
    </div>
@endif