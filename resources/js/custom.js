'use strict';
// Depends
// import PerfectScrollbar from "perfect-scrollbar";

var $ = require('jquery');
var swal = require('sweetalert2');
// const Cookie = require('js-cookie');
//
// // Are you ready?
$(function(){
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    $(document).on('click', '.js_product_popup', function(e){
        e.preventDefault();
        $.ajax({
            url: '/product_popup',
            data: {
                'id': $(this).data('id')
            },
            method: 'POST',
            success: function(resp) {
                $.magnificPopup.open({
                    items: {
                        src: resp,
                        type: 'inline'
                    },
                    removalDelay: 100,
                    mainClass: 'mfp-fade',
                    showCloseBtn: true,
                    callbacks: {
                        open: function() {
                            document.querySelectorAll('.svg-indicator').forEach(window.buildIndicator);
                            $('body').addClass('popup-opened');
                        },
                        close: function () {
                            $('body').removeClass('popup-opened');
                        }
                    }
                }, 0);
            }
        });
    });

    $('.circle-text__inner').each(function(){
        for(let i = 0; i < 24; i++){
            $(this).append('<span style="--i: '+i+';">EXAMPLE - EXAMPLE - </span>');
        }
    });

    $('form.js_site_review_form').on('submit', function(e){
        e.preventDefault();
        $('.validate-error').remove();
        var form = $(this);
        let data = form.formData({
            validator: {},
            invalid: function(data) {
                for (let name in data.errors) {
                    data.obj[name].obj.validateTooltip({
                        text: data.obj[name].obj.rules[data.errors[name][0]]
                    });
                }
            }
        });

        if(data === false)
            return false;

        $.ajax({
            url: '/shopreview/add',
            data: $(this).serialize()+'&url='+location.href,
            method: 'post',
            dataType: 'json',
            beforeSend: function() {
                form.find('button[type="submit"]').text('Sending...');
            },
            success: function (response) {
                form.trigger('sent', response);
                $.magnificPopup.close();
                if(response.success && (typeof form.data('success-title') !== 'undefined' || typeof  form.data('success-message') !== 'undefined')){
                    new swal(form.data('success-title'), form.data('success-message'), 'success').then(() => {
                        location.reload();
                    });
                }
                form.find('input, textarea').val('');
                form.find('button[type="submit"]').html('Sent');
            }
        });
    });

    $('form.js_product_review_form').on('submit', function(e){
        e.preventDefault();
        var form = $(this);
        let data = form.formData({
            validator: {},
            invalid: function(data) {
                for (let name in data.errors) {
                    data.obj[name].obj.validateTooltip({
                        text: data.obj[name].obj.rules[data.errors[name][0]]
                    });
                }
            }
        });

        if(data === false)
            return false;

        $.ajax({
            url: '/review/add',
            data: $(this).serialize()+'&url='+location.href,
            method: 'post',
            dataType: 'json',
            beforeSend: function() {
                form.find('button[type="submit"]').text('Sending...');
            },
            success: function (response) {
                form.trigger('sent', response);
                $.magnificPopup.close();
                if(response.success && (typeof form.data('success-title') !== 'undefined' || typeof  form.data('success-message') !== 'undefined')){
                    new swal(form.data('success-title'), form.data('success-message'), 'success').then(() => {
                        location.reload();
                    });
                }
                form.find('input, textarea').val('');
                form.find('button[type="submit"]').html('Sent');
            }
        });
    });

    // search
    window.livesearch_updatind = false;
    $('[data-autocomplete="input-search"]').on('keyup focus', function(){
        var form = $(this).parents('form');
        if($(this).val().length > 2){
            livesearch(form);
        }else{
            var search_output = form.find('[data-output="search-results"]');
            search_output.hide();
            window.livesearch_text = '';
        }
    });

    function livesearch(form){
        var search_output = form.find('[data-output="search-results"]');
        var text = form.find('[data-autocomplete="input-search"]').val();
        if(window.livesearch_updatind){
           setTimeout(function(){
               livesearch(form);
           }, 100);
        }else if(text !== window.livesearch_text){
            window.livesearch_updatind = true;
            window.livesearch_text = text;
            var data = {};
            data.search = text;
            $.ajax({
                url: '/livesearch',
                data: data,
                method: 'GET',
                dataType: 'JSON',
                success: function(resp) {
                    var html = '';

                    $.each(resp, function(i, value){
                        html += '<li>';
                        html += '<a href="'+value.url+'" class="search-results-item">';
                        html += '<div class="search-results-item__img">';
                        html += '<img src="'+value.image+'" alt="'+value.name+'">';
                        html += '</div>';
                        html += '<div class="search-results-item__info">';
                        html += '<span>';
                        html += value.name;
                        html += '</span>';
                        if(value.price) {
                            html += '<div>';
                            html += value.price;
                            html += '</div>';
                        }
                        html += '</div>';
                        html += '</a>';
                        html += '</li>';
                    });

                    if(html === ''){
                        html += '<div class="nothing-found">\n' +
                            '  <p>Sorry, nothing found for <span>'+text+'</span>. Check out some of these popular searches:</p>\n' +
                            '  <ul class="search-tags">\n' +
                            '    <li><a href="/search?text=Vape" class="search-tag">Vape</a></li>\n' +
                            '    <li><a href="/search?text=Pod Battery" class="search-tag">Pod Battery</a></li>\n' +
                            '    <li><a href="/search?text=Cartridges" class="search-tag">Cartridges</a></li>\n' +
                            '    <li><a href="/search?text=Edibles" class="search-tag">Edibles</a></li>\n' +
                            // '    <li><a href="/search?text=Accessories" class="search-tag">Accessories</a></li>\n' +
                            '  </ul>\n' +
                            '</div>';
                    }else{
                        html = '<ul>'+html+'</ul>';
                        html += '<div class="view-all">\n' +
                        '  <button type="submit">View all results</button>\n' +
                        '</div>';
                    }

                    search_output.html(html);
                    search_output.show();

                    window.livesearch_updatind = false;
                }
            });
        }
    }

    $('#js_track_order').submit(function(e){
        e.preventDefault();
        let order_id = $(this).get(0).order_id.value;
        let email = $(this).get(0).email.value;

        if(order_id && email){
            $.post('/track_order', {order_id: order_id, email: email}, function(response){
                if(response.success){
                    $('.order-tracking').replaceWith(response.html);
                }else{
                    $('#js_track_order .error').remove();
                    $('#js_track_order .btn').before('<p class="error" style="color:#c40103">'+response.message+'</p>');
                }
            });
        }
    });
});
require('./larchik/filter');
require('./larchik/cart');
require('./larchik/checkout');
