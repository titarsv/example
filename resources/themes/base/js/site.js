import $ from 'jquery';

require('slick-carousel/slick/slick.js');

// Собственная презентационная логика темы `base` (не бизнес-логика, см. js/shop/) — вынесена
// в отдельный файл по той же причине: страницы, унаследованные из `base` темой, собранной
// оркестратором импорта динамических страниц (Modules\ThemeImport, см.
// docs/dynamic-page-import-plan.md), не переопределяются этой темой (значит, рендерятся с
// разметкой `base`, ожидающей именно эти классы) — им тоже нужен этот код, а не только
// собственному app.js `base`.

// Фильтр FAQ по категориям.
$(document).on('click', '.js-faq-tab', function () {
    const tab = $(this).data('tab');
    $('.js-faq-tab').removeClass('btn-primary').addClass('btn-outline-secondary');
    $(this).removeClass('btn-outline-secondary').addClass('btn-primary');

    $('.js-faq-item').each(function () {
        $(this).toggle(tab === 'all' || $(this).data('tab') == tab);
    });
});

// Форма отзыва о товаре/о магазине.
$(document).on('submit', '.js-review-form', function (e) {
    e.preventDefault();
    const $form = $(this);
    $.post($form.attr('action'), $form.serialize())
        .done(function (response) {
            if (response && response.error) {
                $form.find('.js-review-error').text('Проверьте, все ли обязательные поля заполнены.').removeClass('d-none');
                return;
            }
            $form.closest('.modal').find('.modal-body').html('<div class="alert alert-success mb-0">Спасибо! Ваш отзыв отправлен на модерацию.</div>');
        })
        .fail(function () {
            $form.find('.js-review-error').text('Ошибка отправки, попробуйте ещё раз.').removeClass('d-none');
        });
});

$(document).on('click', '.js-rating-input .bi', function () {
    const $wrapper = $(this).closest('.js-rating-input');
    const value = $(this).data('value');
    $wrapper.find('input[name="grade"]').val(value);
    $wrapper.find('.bi').each(function () {
        $(this).toggleClass('bi-star-fill', $(this).data('value') <= value).toggleClass('bi-star', $(this).data('value') > value);
    });
});

// Миниатюры галереи товара — клик меняет главное фото ($product->gallery(), см.
// public.layouts.product_gallery). Умышленно простая замена src, без внешних
// JS-библиотек/лайтбокса.
$(document).on('click', '.js-gallery-thumb', function () {
    $('.js-gallery-thumb').removeClass('active');
    $(this).addClass('active');
    $('#jsProductMainImage').attr('src', $(this).data('full'));
});

// Универсальная отправка форм (подписка в футере, контакты, отзывы)
// без перезагрузки страницы — с простым инлайн-уведомлением.
$(document).on('submit', '.ajax_form', function (e) {
    e.preventDefault();
    const $form = $(this);
    const $btn = $form.find('[type="submit"]');
    const originalText = $btn.text();

    $form.find('.js-form-alert').remove();
    $btn.prop('disabled', true);

    $.post($form.attr('action'), $form.serialize())
        .done(function () {
            $form.prepend(
                '<div class="alert alert-success js-form-alert">' +
                ($form.data('success-title') || 'Готово') +
                ($form.data('success-message') ? ' ' + $form.data('success-message') : '') +
                '</div>'
            );
            $form[0].reset();
        })
        .fail(function () {
            $form.prepend(
                '<div class="alert alert-danger js-form-alert">' +
                ($form.data('error-title') || 'Ошибка отправки, попробуйте ещё раз.') +
                '</div>'
            );
        })
        .always(function () {
            $btn.prop('disabled', false).text(originalText);
        });
});

// Слайдеры блоков с товарными рекомендациями (похожие/сопутствующие/
// рекомендуемые/хиты продаж и т.п.) — везде одна и та же карусель.
$('.js-products-slider').slick({
    rows: 0, // иначе slick сам оборачивает каждый слайд доп. div'ом с
             // инлайновым display:inline-block, и height:100% до карточки не доходит
    slidesToShow: 5,
    slidesToScroll: 1,
    infinite: false,
    prevArrow: '<button type="button" class="slick-prev"><i class="bi bi-chevron-left"></i></button>',
    nextArrow: '<button type="button" class="slick-next"><i class="bi bi-chevron-right"></i></button>',
    responsive: [
        { breakpoint: 992, settings: { slidesToShow: 3 } },
        { breakpoint: 576, settings: { slidesToShow: 2 } },
    ],
});