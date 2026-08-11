<footer class="footer mt-5 pt-5 pb-4">
    <div class="container">
        <div class="row gy-4">
            <div class="col-lg-4">
                <div class="fw-bold fs-5 text-white mb-2">{{ !empty($settings->site_name) ? $settings->site_name : env('APP_NAME') }}</div>
                <p class="mb-3">Простой пример интернет-магазина — базовая тема для новых проектов.</p>
                <form class="d-flex ajax_form" action="{{ base_url('/sendmail') }}" method="POST"
                      data-error-title="Ошибка отправки!"
                      data-success-title="Спасибо за подписку."
                      data-success-message="Мы будем присылать вам самые интересные предложения.">
                    @csrf
                    <input type="hidden" name="form" value="Subscribe">
                    <input type="email" name="email" class="form-control me-2" placeholder="Email" required>
                    <button type="submit" class="btn btn-primary text-nowrap">Подписаться</button>
                </form>
            </div>

            @foreach($footer_menu as $group)
                <div class="col-lg-2 col-6">
                    <div class="fw-semibold text-white mb-2">{{ $group->name }}</div>
                    <ul class="list-unstyled">
                        @foreach($group->children as $item)
                            <li class="mb-1"><a {!! $item->link_attributes !!}>{{ $item->name }}</a></li>
                        @endforeach
                    </ul>
                </div>
            @endforeach

            @if(!empty($settings->trustpilot_rating))
                <div class="col-lg-3">
                    <div class="fw-semibold text-white mb-2">{{ $settings->trustpilot_rating_name }}</div>
                    <a href="{{ $settings->trustpilot_trustpilot_link }}" class="text-white text-decoration-none" rel="nofollow" target="_blank">
                        <span class="text-warning">
                            @for($i = 0; $i < 5; $i++)
                                <i class="bi {{ $i < round($settings->trustpilot_rating) ? 'bi-star-fill' : 'bi-star' }}"></i>
                            @endfor
                        </span>
                        <div class="small">{{ $settings->trustpilot_total_reviews }} отзывов на Trustpilot</div>
                    </a>
                </div>
            @endif
        </div>

        <hr class="my-4 border-secondary">

        <div class="d-flex justify-content-between flex-wrap gap-2 small">
            <span>© {{ date('Y') }}, {{ !empty($settings->site_name) ? $settings->site_name : env('APP_NAME') }}</span>
            <span>Базовая тема — пример для форка под новый проект</span>
        </div>
    </div>
</footer>

<!-- Офканвас корзины -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="cartOffcanvas" aria-labelledby="cartOffcanvasLabel">
    <div class="offcanvas-header border-bottom">
        <h5 class="offcanvas-title" id="cartOffcanvasLabel">Корзина</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Закрыть"></button>
    </div>
    <div class="offcanvas-body d-flex flex-column p-0 js-cart-body">
        @include('public.layouts.cart')
    </div>
</div>

<!-- Модалка быстрого просмотра товара -->
<div class="modal fade" id="quickViewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header border-0">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            <div class="modal-body pt-0"></div>
        </div>
    </div>
</div>
