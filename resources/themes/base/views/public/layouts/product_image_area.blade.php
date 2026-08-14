@if($product->gallery->count())
    @include('public.layouts.product_gallery')
@else
    {!! !empty($product->image) ? $product->image->image(
        [600, 600],
        ['alt' => $product->name, 'loading' => 'lazy', 'class' => 'img-fluid rounded product-card__image w-100'],
        'cover',
        ['<991' => '100vw', 'calc(50vw - 48px)']
    ) : '<img src="/images/larchik/no_image.jpg" alt="Нет фото" class="img-fluid rounded w-100" loading="lazy">' !!}
@endif