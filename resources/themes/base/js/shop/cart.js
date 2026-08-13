import $ from 'jquery';
import * as bootstrap from 'bootstrap';

// Корзина: добавление/удаление/изменение количества через AJAX,
// обновление счётчика в шапке и содержимого офканваса корзины.
function updateCartUI(cart) {
    $('.js-cart-count').text(cart.count).toggleClass('d-none', !cart.count);
    $('.js-cart-body').html(cart.html);
}

$(document).on('click', '.js-add-to-cart', function (e) {
    e.preventDefault();
    const $btn = $(this);
    const data = {
        action: 'add',
        product_id: $btn.data('id'),
        quantity: 1,
    };
    const $variation = $btn.closest('.js-product-card').find('[name="variation"]:checked');
    if ($variation.length) {
        data.variation = $variation.val();
    }

    $.post('/cart/update', data, function (cart) {
        updateCartUI(cart);
        const offcanvas = bootstrap.Offcanvas.getOrCreateInstance(document.getElementById('cartOffcanvas'));
        offcanvas.show();
    });
});

$(document).on('click', '.js-cart-remove', function (e) {
    e.preventDefault();
    $.post('/cart/update', { action: 'remove', product_id: $(this).data('id') }, updateCartUI);
});

$(document).on('change', '.js-cart-qty', function () {
    $.post('/cart/update', {
        action: 'update',
        product_id: $(this).data('id'),
        quantity: $(this).val(),
    }, updateCartUI);
});

// Вариации товара (объём/размер и т.п.) — переключение цены.
$(document).on('click', '.js_variation:not(.disabled)', function () {
    const $this = $(this);
    const $card = $this.closest('.js-product-card');
    const id = $this.data('id');

    $card.find('.js_variation').removeClass('js_active current');
    $this.addClass('js_active current');

    const $input = $card.find('.js_var_' + id);
    $card.find('[name="variation"]').prop('checked', false);

    if ($input.length) {
        $input.prop('checked', true);
        const price = $input.data('price');
        const originalPrice = $input.data('original_price');
        $card.find('.js_current_price').text('₽' + price);
        $card.find('.js_old_price').remove();
        if (originalPrice > price) {
            $card.find('.js_current_price').after('<span class="text-muted text-decoration-line-through js_old_price ms-1">₽' + originalPrice + '</span>');
        }
    }
});

// Купон на странице корзины/оформления заказа.
$(document).on('submit', '.js-coupon-form', function (e) {
    e.preventDefault();
    const $form = $(this);
    $.post('/apply_coupon', $form.serialize(), function (response) {
        if (response.result === 'success') {
            location.reload();
        } else {
            $form.find('.js-coupon-error').text(response.msg).removeClass('d-none');
        }
    });
});