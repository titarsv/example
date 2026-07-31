import $ from "jquery";

$(function() {
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    // Выбор вариации
    $(document).on('click', '.js_variation', function(){
        if($(this).hasClass('js_active')){
            return false;
        }
        var $this = $(this);
        var hash;
        var attrs = [];
        var product_card = $this.parents('.js_product_card');
        var original_price;

        product_card.find('.js_active').removeClass('js_active').removeClass('current');
        $this.addClass('js_active').addClass('current');

        product_card.find('.js_active').each(function(){
            var val = $(this).data('id');
            if(val !== ''){
                attrs.push(val);
            }
        });
        hash = attrs.sort(function(a,b){
            return a - b
        }).join('_');

        product_card.find('[name="variation"]').prop('checked', false);
        var input = product_card.find('.js_var_'+hash);
        if(hash !== '' && input.length){
            input.prop('checked', true);
            location.hash = hash;
            var price = parseFloat(input.data('price'));
            original_price = parseFloat(input.data('original_price'));
            product_card.find('.js_current_price').text('£'+price);
        }else{
            if(window.location.hash !== '')
                history.pushState("", document.title, window.location.pathname + window.location.search);
            product_card.find('.js_current_price').text('£'+product_card.find('.js_current_price').data('price'));
            original_price = parseFloat(product_card.find('.js_current_price').data('original_price'));
        }
        product_card.find('.js_old_price').remove();
        if(original_price > price){
            product_card.find('.js_current_price').after('<span class="old-price js_old_price">£'+original_price+'</span>');
        }
    });

    var hash_parts = location.hash.replace('#', '').split('_');
    if(hash_parts.length && hash_parts[0] !== ''){
        for(var i=0; i<hash_parts.length; i++){
            var option = $('.js_variation[data-id="'+hash_parts[i]+'"]');
            option.trigger('click');
        }
    }else if($('.js_variation').length){
        $('.js_variation').eq(0).click();
    }

    // Добавление товаров в корзину
    $(document).on('click', '.js_add_to_cart', function (e) {
        e.preventDefault();
        e.stopPropagation();
        var variation;
        var $this = $(this);

        var product_card = $this.parents('.js_product_card');
        // var qty = product_card.find('.js_qty').val();
        var qty = 1;
        var data = {
            action: 'add',
            product_id: $this.data('id'),
            quantity: qty > 1 ? qty : 1
        };

        variation = product_card.find('[name="variation"]:checked');

        if(variation.length){
            data['variation'] = variation.val();
        }

        $.post("/cart/update", data, function(cart){
            $('.js_cart_counter').each(function(){
                $(this).text(cart.count);
            });

            $('.js_cart_price').text('£'+cart.total);
            $('.js_minicart_wrapper').html(cart.html);
            $('.js_minicart_wrapper').removeClass('empty');

            if(typeof fbq !== 'undefined' && typeof fbqProductsData[data.product_id] !== 'undefined'){
                fbq('track', 'AddToCart', fbqProductsData[data.product_id]);
            }

            if(typeof dataLayer !== 'undefined'){
                dataLayer.push({event: "add_to_cart"});
            }

            $('html').addClass('cart-open');
            $('.cart-overlay, .cart-popup').addClass('active');
            $('.js_minicart_wrapper .slick-slider').slick();
        });
    });

    // Добавление товаров в корзину
    $(document).on('click', '.js_by_now', function (e) {
        e.preventDefault();
        e.stopPropagation();
        var variation;
        var $this = $(this);

        var product_card = $this.parents('.js_product_card');
        // var qty = product_card.find('.js_qty').val();
        var qty = 1;
        var data = {
            action: 'add',
            product_id: $this.data('id'),
            quantity: qty > 1 ? qty : 1
        };

        variation = product_card.find('[name="variation"]:checked');

        if(variation.length){
            data['variation'] = variation.val();
        }

        $.post("/cart/update", data, function(cart){
            location = '/checkout';
        });
    });

    /**
     * Удаление товара из корзины
     */
    $(document).on('click', '.js_remove_product_from_cart', function(e){
        e.preventDefault();
        var $this = $(this);
        var id = $this.data('id');
        update_cart({
            action: 'remove',
            product_id: id
        });
    });

    /**
     * Обновление количества товара в корзине
     */
    $(document).on('input change', '.js_cart_qty', function(){
        var $this = $(this);
        update_cart({
            action: 'update',
            product_id: $this.data('prod-id'),
            quantity: $this.val()
        });
    });

    $(document).on('click', '.header-cart__btn', function () {
        $.post("/cart/get", {}, function(cart){
            let js_minicart_wrapper = $('.js_minicart_wrapper');
            js_minicart_wrapper.html(cart);
            $('html').addClass('cart-open');
            $('.cart-overlay, .cart-popup').addClass('active');
            if(js_minicart_wrapper.find('.cart-empty').length){
                js_minicart_wrapper.addClass('empty');
            }
            $('.js_minicart_wrapper .slick-slider').slick();
            initPromocode();
        });
    });

    $(document).on('click', '.js_apply_promocode', function () {
        var promo_wrapper = $(this).parents('.promo-wrapper'),
            promo_input = promo_wrapper.find('.input'),
            promo_btn = promo_wrapper.find('.btn'),
            promo_error = promo_wrapper.find('.promo-error'),
            promo_subtotal = $('.promo-subtotal');

        var code = promo_input.val().trim();

        $.post('/apply_coupon', {code: code}, function(response){
            if(response.result === 'success'){
                promo_error.fadeOut();
                promo_input.addClass('success');
                $('.js_cart_price').text(response.cart.subtotal);
                $('.js_coupon_sale').text(response.cart.coupon_sale);
                $('.js_total').text(response.cart.total);
                $('.js_total_without_shipping').text(response.cart.total_without_shipping);
                $('#checkout_prices').html(response.cart.checkout_prices);
                setTimeout(function() {
                    promo_btn.prop('disabled', true).html(promo_btn.attr('data-success'));
                    promo_subtotal.addClass('active');
                }, 500);
            }else{
                promo_error.find('span').text(code);
                promo_error.fadeIn();
                promo_input.addClass('error').removeClass('success');
                promo_subtotal.removeClass('active');
                promo_btn.prop('disabled', true).html(promo_btn.attr('data-default'));
            }
        }, 'json');
    });

    $(document).on('click', '.js_remove_coupon', function () {
        var promo_wrapper = $('.promo-wrapper'),
            promo_btn = promo_wrapper.find('.btn'),
            promo_subtotal = $('.promo-subtotal');

        $.post('/apply_coupon', {}, function(response){
            if(response.result === 'success'){
                $('.js_cart_price').text(response.cart.subtotal);
                $('.js_coupon_sale').text(response.cart.coupon_sale);
                $('.js_total').text(response.cart.total);
                $('.js_total_without_shipping').text(response.cart.total_without_shipping);
                $('#checkout_prices').html(response.cart.checkout_prices);
                setTimeout(function() {
                    promo_btn.prop('disabled', true).html(promo_btn.attr('data-default'));
                    promo_subtotal.removeClass('active');
                }, 500);
            }
        }, 'json');
    });
});

/**
 * Обновление корзины
 * @param data
 */
function update_cart(data){
    $.post("/cart/update", data, function(cart){
        $('.js_cart_counter').each(function(){
            $(this).text(cart.count);
        });

        $('.js_cart_price').text('£'+cart.total);
        $('#checkout_prices').html(response.cart.checkout_prices);
        $('.js_minicart_wrapper').html(cart.html);
        if(cart.total == 0){
            $('.js_minicart_wrapper').addClass('empty');
        }else{
            $('.js_minicart_wrapper').removeClass('empty');
        }
        $('html').addClass('cart-open');
        $('.cart-overlay, .cart-popup').addClass('active');
        $('.js_minicart_wrapper .slick-slider').slick();
        initPromocode();
    });
}


function initPromocode(){
    $('.promo-wrapper, .cart-promo__wrapper').each(function () {
        var promo_wrapper = $(this),
            promo_input = promo_wrapper.find('.input'),
            promo_btn = promo_wrapper.find('.js_apply_promocode'),
            promo_error = promo_wrapper.find('.promo-error');

        promo_error.hide();

        function checkInput() {
            const val = promo_input.val().trim();
            promo_btn.prop('disabled', val.length === 0);
        }
        checkInput();

        promo_input.off('input.promocode').on('input.promocode', function() {
            checkInput();
            promo_error.fadeOut();
            $(this).removeClass('error');
            promo_btn.html(promo_btn.attr('data-default'));
        });
    });
}
