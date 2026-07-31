@if($site_reviews)
    <div class="reviews-main">
        <div class="reviews-main__wrapper">
            @foreach($site_reviews as $review)
                <div class="review-item">
                    <div>{{ $review->review }}</div>
                    <div>
                        @if(!empty($review->user))
                            @if(!empty($review->user->first_name))
                                {{ $review->user->first_name }} {{ $review->user->last_name }}
                            @else
                                {{ $review->author }}
                            @endif
                        @else
                            {{ $review->author }}
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
        @include('public.layouts.pagination', ['paginator' => $site_reviews, 'without_js' => true])
    </div>
@endif
