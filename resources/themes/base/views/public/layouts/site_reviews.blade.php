@if($site_reviews->count())
    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-3">
        @foreach($site_reviews as $review)
            <div class="col">
                <div class="card h-100">
                    <div class="card-body">
                        <p class="card-text">{{ $review->review }}</p>
                        <div class="fw-semibold small">
                            @if(!empty($review->user) && !empty($review->user->first_name))
                                {{ $review->user->first_name }} {{ $review->user->last_name }}
                            @else
                                {{ $review->author }}
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
    <div class="mt-4">
        @include('public.layouts.pagination', ['paginator' => $site_reviews])
    </div>
@endif
