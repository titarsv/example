'use strict';
// Depends
// import PerfectScrollbar from 'perfect-scrollbar';
// import LazyLoad from "vanilla-lazyload";
// const Cookie = require('js-cookie');

var $ = require('jquery');

// Are you ready?
$(function(){
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    $(document).on('change', '.js_attribute_checkbox_filter, .js_stock_checkbox_filter', function(){
        filterProducts();
    });

    $(document).on('click', '#js_is_sale', function(){
        filterProducts();
    });

    $(document).on('click', '.js_submit_filters', function(){
        filterProducts();
    });

    $(document).on('change', '.js_mob_attribute_checkbox_filter, .js_mob_stock_checkbox_filter, #js_mob_is_sale', function(){
        mobFilterProducts();
    });

    $(document).on('click', '.js_attribute_link_filter', function(e){
        e.preventDefault();
        $(this).toggleClass('js_active');
        filterProducts();
    });

    $(document).on('change', '.js_price_range_filter', function(){
        var $this = $(this);
        var val = $this.val();
        setTimeout(function(){
            if($this.val() === val){
                filterProducts();
            }
        }, 500);
    });

    $(document).on('click', '#js_more_products', function(e){
        e.preventDefault();
        let page = $(this).data('id');
        if(page > 0){
            filterProducts(page, true);
        }
    });

    $(document).on('click', '.js_remove_filter', function(){
        let id = $(this).data('id');
        let type = $(this).data('type');
        if(type === 'price'){
            let price = $('.js_price_range_filter');
            $('.js_range_from').val(price.data('min')).change();
            $('.js_range_to').val(price.data('max')).change();
            price.val(price.data('min')+';'+price.data('max'));
        }else if(type === 'attribute'){
            $('.js_attribute_checkbox_filter[data-id="'+id+'"]').prop('checked', false);
            $('.js_mob_attribute_checkbox_filter[data-id="'+id+'"]').prop('checked', false);
        }else if(type === 'stock'){
            $('.js_stock_checkbox_filter[data-id="'+id+'"]').prop('checked', false);
        }else if(type === 'is_sale'){
            $('#js_is_sale').removeClass('active');
            $('#js_mob_is_sale').prop('checked', false);
        }
        $(this).remove();
        filterProducts();
    });

    $(document).on('click', '.js_clear_filters', function(){
        $('.js_attribute_checkbox_filter').prop('checked', false);
        $('#js_is_sale').removeClass('active');
        $('#js_mob_is_sale').prop('checked', false);
        let price = $('.js_price_range_filter');
        $('.js_range_from').val(price.data('min'));
        $('.js_range_to').val(price.data('max')).change();
        $('.js_checked_filters').hide();
        filterProducts();
    });

    $(document).on('change', '.js_sort_select', function(){
        // Cookie.set('products_sort', $(this).val());
        filterProducts();
    });

    $(document).on('click', '.js_sort span', function(e){
        e.preventDefault();
        filterProducts();
    });

    $(document).on('click', '.js_mob_sort span', function(e){
        e.preventDefault();
        $('.js_sort_select option[value="'+$(this).data('value')+'"]').prop('selected', true);
        $('body').removeClass('sort-open');
        mobFilterProducts();
    });

    $(document).on('click', '.js_view_link', function(){
        if( $('#js_products_wrapper').data('view') !== $(this).data('view')){
            $('#js_products_wrapper').data('view', $(this).data('view'));
            filterProducts();
        }
    });

    $(document).on('click', '.js_pagination a', function(e){
        e.preventDefault();
        let page = parseInt($(this).data('page'));
        if(page > 0){
            filterProducts(page);
        }
    });

    $(document).on('click', '.js_show_all', function(e){
        e.preventDefault();
        $('#js_show_all').val(1);
        filterProducts(0);
    });
    function filterProducts(page = 1, more = false){
        if($('#js_show_all').val() === '1'){
            page = 0;
        }
        if(window.loading === true){
            if(typeof window.loadingTimeout !== 'undefined')
                clearInterval(window.loadingTimeout);
            window.loadingTimeout = setTimeout(filterProducts(page), 100);
            return;
        }
        window.loading = true;
        var products_wrapper = $('#js_products_wrapper');
        products_wrapper.animate({'opacity': 0}, 100);

        let filters = [];
        $('.js_attribute_checkbox_filter:checked, .js_attribute_link_filter.js_active').each(function () {
            filters.push($(this).data('id'));
        });

        let data = {
            filters: filters,
            category: $('#js_category').val(),
            search_text: $('#js_search_text').val(),
            order: $('.js_sort span.current').data('value'),
            sale: $('#js_sale').length ? $('#js_sale').val() : '',
            is_sale: $('#js_is_sale.active').length ? 1 : 0,
            page: page
        };

        let price_input = $('.js_price_range_filter');
        if(price_input.length){
            let price = price_input.val().split(';');
            let price_min = Math.floor(parseFloat(price[0]));
            let price_max = Math.ceil(parseFloat(price[1]));
            if(price_min > parseFloat(price_input.data('min'))){
                data.price_min = price_min;
            }
            if(price_max < parseFloat(price_input.data('max'))){
                data.price_max = price_max;
            }
        }

        $('body, html').animate({scrollTop: products_wrapper.offset().top - 200}, 300);
        $.ajax({
            type: 'post',
            url: '/products/filter',
            data: data,
            success: function(response){
                window.loading = false;
                if(response.result == 'success'){
                    $('.js_checked_filters').replaceWith(response.checked);
                    $('.js_filters').replaceWith(response.filters);
                    $('.js_mob_filters').replaceWith(response.mob_filters);
                    $('.js_total_products_count').text(response.count);
                    $('h1').text(response.name);
                    if(response.pagination === ''){
                        $('.js_pagination').remove();
                    }else{
                        $('.js_pagination').remove();
                        products_wrapper.after(response.pagination);
                    }
                    $('.js_filter_counter').text(response.counter);

                    products_wrapper.html(response.html);

                    $('.circle-text__inner').each(function(){
                        for(let i = 0; i < 24; i++){
                            $(this).append('<span style="--i: '+i+';">EXAMPLE - EXAMPLE - </span>');
                        }
                    });

                    products_wrapper.animate({'opacity': 1}, 100);
                    history.pushState("", document.title, response.link);
                }
            },
            error: function(){
                window.loading = false;
                products_wrapper.animate({'opacity': 1}, 100);
            },
            async: true,
            dataType: 'json'
        });
    }

    function mobFilterProducts(page = 1){
        if($('#js_show_all').val() === '1'){
            page = 0;
        }
        if(window.loading === true){
            if(typeof window.loadingTimeout !== 'undefined')
                clearInterval(window.loadingTimeout);
            window.loadingTimeout = setTimeout(mobFilterProducts(page), 100);
            return;
        }
        window.loading = true;
        var products_wrapper = $('#js_products_wrapper');
        products_wrapper.animate({'opacity': 0}, 100);
        let filters = [];
        $('.js_mob_attribute_checkbox_filter:checked, .js_mob_attribute_link_filter.js_active').each(function () {
            filters.push($(this).data('id'));
        });

        let data = {
            filters: filters,
            category: $('#js_category').val(),
            search_text: $('#js_search_text').val(),
            order: $('.js_mob_sort span.current').data('value'),
            sale: $('#js_sale').length ? $('#js_sale').val() : '',
            is_sale: $('#js_mob_is_sale').prop('checked') ? 1 : 0,
            page: page,
        };

        let price_input = $('.js_mob_price_range_filter');
        if(price_input.length){
            let price = price_input.val().split(';');
            let price_min = Math.floor(parseFloat(price[0]));
            let price_max = Math.ceil(parseFloat(price[1]));
            if(price_min > parseFloat(price_input.data('min'))){
                data.price_min = price_min;
            }
            if(price_max < parseFloat(price_input.data('max'))){
                data.price_max = price_max;
            }
        }

        $('body, html').animate({scrollTop: products_wrapper.offset().top - 200}, 300);
        $.ajax({
            type: 'post',
            url: '/products/filter',
            data: data,
            success: function(response){
                window.loading = false;
                if(response.result == 'success'){
                    products_wrapper.html(response.html);
                    $('.js_checked_filters').replaceWith(response.checked);
                    $('.js_filters').replaceWith(response.filters);
                    $('.js_mob_filters').replaceWith(response.mob_filters);
                    $('.js_total_products_count').text(response.count);
                    $('h1').text(response.name);
                    if(response.pagination === ''){
                        $('.js_pagination').remove();
                    }else{
                        $('.js_pagination').remove();
                        products_wrapper.after(response.pagination);
                    }
                    $('.circle-text__inner').each(function(){
                        for(let i = 0; i < 24; i++){
                            $(this).append('<span style="--i: '+i+';">EXAMPLE - EXAMPLE - </span>');
                        }
                    });
                    products_wrapper.animate({'opacity': 1}, 100);
                    history.pushState("", document.title, response.link);
                }
            },
            error: function(){
                window.loading = false;
                products_wrapper.animate({'opacity': 1}, 100);
            },
            async: true,
            dataType: 'json'
        });
    }
});
