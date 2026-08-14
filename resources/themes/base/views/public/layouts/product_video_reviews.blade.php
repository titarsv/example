@if(count($product->video_reviews))
    <div class="accordion-item">
        <h2 class="accordion-header">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#productVideoReviews">
                Видеообзоры
            </button>
        </h2>
        <div id="productVideoReviews" class="accordion-collapse collapse" data-bs-parent="#productInfoAccordion">
            <div class="accordion-body">
                <div class="row row-cols-1 row-cols-md-2 g-3">
                    @foreach($product->video_reviews as $video_review)
                        <div class="col">
                            <video controls class="w-100 rounded" poster="{{ isset($video_review->image->data['icon']) ? $video_review->image->data['icon'] : '/images/larchik/video.png' }}">
                                <source type="video/mp4" src="{{ $video_review->image->url() }}">
                            </video>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
@endif