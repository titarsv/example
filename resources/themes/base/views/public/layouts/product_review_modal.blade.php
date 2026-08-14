<div class="modal fade" id="productReviewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Написать отзыв</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            <div class="modal-body">
                <form class="js-review-form" action="{{ base_url('/review/add') }}" method="POST">
                    @csrf
                    <input type="hidden" name="product_id" value="{{ $product->id }}">
                    <input type="hidden" name="type" value="review">
                    <input type="hidden" name="grade" value="0">
                    <div class="mb-3 js-rating-input">
                        @for($i = 1; $i <= 5; $i++)
                            <i class="bi bi-star fs-4 text-warning" role="button" data-value="{{ $i }}"></i>
                        @endfor
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Имя</label>
                        <input type="text" class="form-control" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Отзыв</label>
                        <textarea class="form-control" name="review" rows="4" required></textarea>
                    </div>
                    <div class="alert alert-danger d-none js-review-error"></div>
                    <button type="submit" class="btn btn-primary w-100">Отправить отзыв</button>
                </form>
            </div>
        </div>
    </div>
</div>