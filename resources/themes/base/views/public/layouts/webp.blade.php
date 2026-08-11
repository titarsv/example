@if(!empty($webp))
<picture>
    <source @if($lazy == 'slider') data-lazy="{{ $webp }}" @elseif($lazy == 'static') data-src="{{ $webp }}" srcset="/images/pixel.webp" @else srcset="{{ $webp }}" @endif type="image/webp">
    <source @if($lazy == 'slider') data-lazy="{{ $original }}" @elseif($lazy == 'static') data-src="{{ $original }}" srcset="/images/pixel.{{ $original_mime }}" @else srcset="{{ $original }}" @endif type="image/{{ $original_mime }}">
    <img @if($lazy == 'slider') data-lazy="{{ $original }}" src="/images/pixel.jpg" @elseif($lazy == 'static') src="/images/pixel.jpg" @else src="{{ $original }}" @endif @foreach($attributes as $key => $attr) {{ $key }}="{{ $attr }}"@endforeach>
</picture>
@else
@if(!empty($original))
<picture>
    <img src="{{ $original }}" @foreach($attributes as $key => $attr){{ $key }}="{{ $attr }}"@endforeach>
</picture>
@else
<picture>
    <img src="/uploads/no_image.jpg" @foreach($attributes as $key => $attr){{ $key }}="{{ $attr }}"@endforeach>
</picture>
@endif
@endif