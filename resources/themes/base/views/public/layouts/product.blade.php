@if(!empty($product))
    <div class="card h-100 product-card js-product-card">
        <div class="position-relative">
            <a href="{{ $product->link() }}">
                @if(!empty($product->image))
                    {!! $product->image->image([500, 500], ['alt' => $product->name, 'loading' => 'lazy', 'class' => 'card-img-top product-card__image'], 'cover', ['<767' => '50vw', '<1199' => '33vw', '25vw']) !!}
                @else
                    <img src="/images/larchik/no_image.jpg" alt="Нет фото" class="card-img-top product-card__image" loading="lazy">
                @endif
            </a>
            @if(module_active('wishlist') && \Cartalyst\Sentinel\Native\Facades\Sentinel::check())
                <button type="button" class="btn btn-light btn-sm rounded-circle position-absolute top-0 end-0 m-2 js-wishlist-toggle{{ $product->in_wish() ? ' text-danger' : '' }}" data-id="{{ $product->id }}" aria-label="В избранное">
                    <i class="bi {{ $product->in_wish() ? 'bi-heart-fill' : 'bi-heart' }}"></i>
                </button>
            @endif
            @if(module_active('compare'))
                @php($in_compare = in_array($product->id, session(\Modules\Compare\Services\CompareService::SESSION_KEY, [])))
                <button type="button" class="btn btn-light btn-sm rounded-circle position-absolute top-0 start-0 m-2 js-compare-toggle{{ $in_compare ? ' text-primary' : '' }}" data-id="{{ $product->id }}" aria-label="Сравнить">
                    <i class="bi bi-arrow-left-right"></i>
                </button>
            @endif
        </div>
        <div class="card-body d-flex flex-column">
            <a href="{{ $product->link() }}" class="text-body text-decoration-none">
                <div class="card-title mb-1">{{ $product->name }}</div>
            </a>
            <div class="mb-2">
                @if($product->actual_price > 0)
                    <span class="fw-semibold js_current_price" data-price="{{ $product->actual_price }}" data-original_price="{{ $product->original_price }}">₽{{ $product->actual_price }}</span>
                @endif
                @if($product->actual_price < $product->original_price && $product->original_price > 0)
                    <span class="text-muted text-decoration-line-through js_old_price ms-1">₽{{ $product->original_price }}</span>
                @endif
            </div>
            <button type="button" class="btn btn-outline-primary btn-sm mt-auto js-quick-view" data-id="{{ $product->id }}">Выбрать варианты</button>
        </div>
    </div>
@endif
