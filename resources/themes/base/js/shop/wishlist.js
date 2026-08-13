import $ from 'jquery';

// Избранное: переключение сердечка на карточке товара/странице товара.
$(document).on('click', '.js-wishlist-toggle', function (e) {
    e.preventDefault();
    const $btn = $(this);
    $.post('/wishlist/toggle', { product_id: $btn.data('id') }, function (response) {
        $btn.find('.bi').toggleClass('bi-heart bi-heart-fill', false);
        $btn.find('.bi').removeClass('bi-heart bi-heart-fill').addClass(response.in_wish ? 'bi-heart-fill' : 'bi-heart');
        $btn.toggleClass('text-danger', response.in_wish);
    });
});