@php($rating = $rating ?? 0)
<span class="rating-stars text-warning">
    @for($i = 1; $i <= 5; $i++)
        @if($rating >= $i)
            <i class="bi bi-star-fill"></i>
        @elseif($rating > $i - 1)
            <i class="bi bi-star-half"></i>
        @else
            <i class="bi bi-star"></i>
        @endif
    @endfor
</span>
