import $ from "jquery";

$(function() {
    $.fn.validateTooltip = function(options) {
        let $this = $(this);
        let top = parseInt($this.parent().offset().top - 34, 10);
        let left = parseInt($this.parent().offset().left + $this.parent().width() / 2, 10);

        $this.tooltip = $('<div class="validate-error">' +
            '<div class=""><div><i></i>' + options.text + '</div></div>' +
            '</div>');

        $(this).closest('.input-wrapper').addClass('err').append($this.tooltip);

        //$this.tooltip.find('.animated').addClass('shake');

        $this.click(function () {
            $(this).closest('.input-wrapper').removeClass('err');
            $this.tooltip.remove();
        });
    };

    /**
     * Обработка оформления заказа
     */
    $('#order-checkout').on('submit', function (e) {
        e.preventDefault();
        var form = $(this);
        var error_div = form.find('.error-messages');
        var error_phone = form.find('.error-phone');
        var error_name = form.find('.error-name');

        if(!$('#terms').is(':checked')){
            return false;
        }

        if (typeof form.valid === 'function' && !form.valid()) {
            return false;
        }

        // Показываем прелоадер сразу после валидации
        form.find('.js_checkout_submit').prop('disabled', true);
        form.find('.js_checkout_preloader').fadeIn();

        $.ajax({
            url: '/checkout',
            type: 'post',
            data: $(this).serialize(),
            beforeSend: function beforeSend() {
                error_div.removeClass('active');
                error_phone.removeClass('active');
                error_name.removeClass('active');
                $('select, input').removeClass('input-error');
            },
            success: function success(response) {

                if (response.error) {
                    $.each(response.error, function (id, text) {
                        var error = id.split('.');
                        /*if(error.length == 1){
                            $('[name="' + error[0] + '"]').addClass('input-error').validateTooltip({
                                text: text
                            });
                        }else if(error.length == 2){
                            $('[name="' + error[0] + '[' + error[1] + ']"]').addClass('input-error').validateTooltip({
                                text: text
                            });
                        }*/
                    });
                    
                    form.find('.js_checkout_submit').prop('disabled', false);
                    form.find('.js_checkout_preloader').fadeOut();
                    if($('body').width() < 480){
                        $('html, body').scrollTop($('#order-checkout').offset().top);
                    }
                } else if (response.success) {
                    if (typeof dataLayer !== 'undefined') {
                        dataLayer.push({'event':'checkout'});
                    }
                    if (response.success === 'redirect') {
                        if(typeof response.order_id !== 'undefined'){
                            window.location = '/thanks?order_id=' + response.order_id;
                        }else if(typeof response.url !== 'undefined'){
                            window.location = response.url;
                        }
                    }
                }
            },
            error: function() {
                // В случае серверной ошибки(500 и тд) тоже разблокируем кнопку
                form.find('.js_checkout_submit').prop('disabled', false);
                form.find('.js_checkout_preloader').fadeOut();
            }
        });
    });
    $('.cart-form__input-name').keyup(function() {
        $(this).closest('.cart-form__input-wrapper').find('.error-messages').removeClass('active');
    });
    $(document).on('change', '#checkout-step__warehouse', function(){
        $(this).removeClass('input-error');
    });
    $('[name="is_callback_off"]').change(function(){
        if($(this).prop('checked')){
            $.magnificPopup.open({
                items: {
                    src: '#callback_off_popup'
                },
                type: 'inline'
            }, 0);
        }
    });

    $('#confirm_crypto_payment').click(function(){
        if($('#cryptocheckout-confirm').prop('checked')){
            $('[for="cryptocheckout-confirm"]').css('color', '#6c6c6c');
            $.post('/confirm_order_payment', {token: $(this).data('token')}, function(){
                location.reload();
            });
        }else{
            $('[for="cryptocheckout-confirm"]').css('color', '#f00');
        }
    });

    $('.js_payment_method').change(function(){
        $.post('/update_payment_method', {method: $('.js_payment_method:checked').val()}, function(response){
            $('#checkout_prices').html(response.checkout_prices);
        });
    });

    /**
     * Обновление количества товара в чекауте
     */
    $(document).on('input change', '.js_checkout_qty', function(){
        var $this = $(this);
        var data = {
            action: 'update',
            product_id: $this.data('prod-id'),
            quantity: $this.val()
        };

        $.post("/cart/update", data, function(response){
            $('.js_cart_counter').each(function(){
                $(this).text(response.count);
            });
            $('#checkout_prices').html(response.checkout_prices);
            $('.checkout-spend').replaceWith(response.checkout_spend);
        });
    });

    $(document).on('click', '.js_remove_product_from_checkout', function(e){
        e.preventDefault();
        var $this = $(this);
        var id = $this.data('id');
        var data = {
            action: 'remove',
            product_id: id
        };

        $.post("/cart/update", data, function(response){
            $('.js_cart_counter').each(function(){
                $(this).text(response.count);
            });
            $('#checkout_prices').html(response.checkout_prices);
            $('.checkout-spend').replaceWith(response.checkout_spend);
        });
    });
});
