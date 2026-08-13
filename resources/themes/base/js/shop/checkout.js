import $ from 'jquery';

// Изменение количества/удаление товара на странице оформления заказа —
// проще всего пересчитать перезагрузкой страницы после ответа сервера.
$(document).on('change', '.js_checkout_qty', function () {
    $.post('/cart/update', { action: 'update', product_id: $(this).data('prod-id'), quantity: $(this).val() }, function () {
        location.reload();
    });
});

$(document).on('click', '.js_remove_product_from_checkout', function () {
    $.post('/cart/update', { action: 'remove', product_id: $(this).data('id') }, function () {
        location.reload();
    });
});

// Оформление заказа.
$(document).on('submit', '#order-checkout', function (e) {
    e.preventDefault();
    const $form = $(this);
    const $btn = $form.find('.js_checkout_submit');
    $form.find('.js-checkout-error').addClass('d-none');
    $btn.prop('disabled', true);

    $.post(location.pathname + location.search, $form.serialize())
        .done(function (response) {
            if (response.success === 'redirect') {
                location.href = response.url || ('/thanks?order_id=' + response.order_id);
                return;
            }
            if (response.error) {
                const messages = typeof response.error === 'string' ? response.error : Object.values(response.error).join(' ');
                $form.find('.js-checkout-error').text(messages).removeClass('d-none');
                $btn.prop('disabled', false);
            }
        })
        .fail(function () {
            $form.find('.js-checkout-error').text('Ошибка оформления заказа, попробуйте ещё раз.').removeClass('d-none');
            $btn.prop('disabled', false);
        });
});

// Отслеживание заказа по номеру и email.
$(document).on('submit', '#js_track_order', function (e) {
    e.preventDefault();
    const $form = $(this);
    $form.find('.js-track-error').addClass('d-none');

    $.post('/track_order', $form.serialize(), function (response) {
        if (response.success) {
            $('.js-track-result').html(response.html);
        } else {
            $form.find('.js-track-error').text(response.message).removeClass('d-none');
        }
    });
});