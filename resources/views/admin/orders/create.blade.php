@include('admin.layouts.header')
@extends('admin.layouts.main')
@section('title','Создание заказа')
@section('content')

    <div class="content-title">
        <div class="row">
            <div class="col-sm-10">
                <h1>Создание заказа</h1>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-12">
                @if(session('message-success'))
                    <div class="alert alert-success">
                        {{ session('message-success') }}
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                @elseif(session('message-error'))
                    <div class="alert alert-danger">
                        {{ session('message-error') }}
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                @endif
            </div>
        </div>
    </div>

    @include('admin.orders.create_form')

    <script>
        jQuery(document).ready(function(){
            // Добавить товар в заказ
            $(document).on('click', '#add_to_order', function(){
                addPlaceholder();
                if(window.xhr && window.xhr.readyState != 4){
                    window.xhr.abort();
                }
                window.xhr = $.post('/admin/categories/children/1', [], function(response){
                    var categories = '';
                    for(var i in response.categories){
                        var category = response.categories[i];
                        categories += '<li>';
                        categories += '<span class="category" data-id="'+category.id+'">'+category.name+'</span>';
                        categories += '<div class="children">';
                        categories += '</div>';
                        categories += '</li>';
                    }
                    removePlaceholder();
                    swal({
                        title: 'Добавление товара',
                        html:
                        '<form id="search_product">\n' +
                        '<div class="search">\n' +
                        '<svg class="search-ico" viewBox="0 0 64 64"><path d="M54.375 49.625L43.25 38.375c1.875-2.875 2.875-6 2.875-9.625 0-9.5-7.75-17.25-17.375-17.25-9.5 0-17.5 7.375-17.5 17s7.625 17.25 17.25 17.25c3.875 0 7-1.25 9.875-3.25l11.25 11.25zM28.75 39.375c-6 0-10.875-4.875-10.875-10.875 0-6.125 4.875-10.875 10.875-10.875 6.125 0 10.875 4.75 10.875 10.875 0 6-4.75 10.875-10.875 10.875z"></path></svg>' +
                        '<input placeholder="Введите название или код товара" data-autocomplete="input-search" autocomplete="off" name="text" type="search">\n' +
                        '<div data-output="search-results" class="search-results" style="display: none"></div>\n' +
                        '</div>\n' +
                        '<ul id="categories_tree">\n' +
                        categories +
                        '</ul>\n' +
                        '</form>',
                        showCancelButton: true,
                        showLoaderOnConfirm: true,
                        confirmButtonText: 'Добавить выбранный товар',
                        cancelButtonText: 'Отменить',
                        preConfirm: () => {
                            return new Promise((resolve, reject) => {
                                var data = {};
                                var i = 0;
                                $('#search_product [name="products[]"]:checked').each(function(){
                                    data['products['+i+']'] = $(this).val();
                                    let id = $('#products_table tr').length;
                                    $('#products_table').append('<tr>\n' +
                                        '\t<td align="center">\n' +
                                        '\t\t<input type="hidden" name="products['+id+'][code]" value="'+$(this).val()+'">\n' +
                                        $(this).data('sku') +
                                        '\t</td>\n' +
                                        '\t<td>\n' +
                                        '\t\t<img src="'+$(this).data('image')+'" class="img-thumbnail">\n' +
                                        '\t\t<div>'+$(this).data('price')+' грн</div>\n' +
                                        '\t</td>\n' +
                                        '\t<td>\n' +
                                        $(this).data('name') +
                                        '\t</td>\n' +
                                        '\t<td>\n' +
                                        ($(this).data('stock') == -2 ? 'Нет в наличии' : ($(this).data('stock') == -1 ? 'Под заказ' : ($(this).data('stock') == 0 ? 'Ожидается' : 'В наличии'))) +
                                        '\t</td>\n' +
                                        '\t<td>\n' +
                                        '\t\t<div class="input-group" style="max-width: 120px;">\n' +
                                        '\t\t\t<input type="number" class="form-control count_field" step="1" min="1" value="1" size="5" name="products['+id+'][qty]" data-id="'+$(this).val()+'">\n' +
                                        '\t\t\t<span class="input-group-addon">шт</span>\n' +
                                        '\t\t</div>\n' +
                                        '\t</td>\n' +
                                        '\t<td align="center">'+$(this).data('price')+' грн</td>\n' +
                                        '\t<td align="center">\n' +
                                        '\t\t<a class="btn btn-primary" target="_blank" href="/admin/products/edit/'+$(this).val()+'">\n' +
                                        '\t\t\t<i class="glyphicon glyphicon-edit"></i>\n' +
                                        '\t\t</a>\n' +
                                        '\t\t<button type="button" class="btn btn-danger remove-product-from-order" data-key="'+$(this).val()+'">\n' +
                                        '\t\t\t<i class="glyphicon glyphicon-trash"></i>\n' +
                                        '\t\t</button>\n' +
                                        '\t</td>\n' +
                                        '</tr>');
                                    i++;
                                });
                                resolve('');
                            });
                        }
                    }).then(function(html){

                    }, function(errors) {
                        if(typeof errors !== 'string'){
                            var message = '';
                            for(err in errors){
                                message += errors[err] + '<br>';
                            }
                            swal(
                                'Ошибка!',
                                message,
                                'error'
                            );
                        }
                    });

                    $('.swal2-confirm').prop('disabled', true);
                });
            });

            $(document).on('keyup', '[data-autocomplete="input-search"]', function(){
                var search_output = $('[data-output="search-results"]');
                var search = $(this).val();
                var target = $(this).attr('data-target');
                search_output.html('').hide();

                if (search.length > 2) {
                    var data = {limit: 1000};
                    data.search = search;
                    if(window.xhr && window.xhr.readyState != 4){
                        window.xhr.abort();
                    }
                    window.xhr = $.ajax({
                        url: '/livesearch',
                        data: data,
                        method: 'GET',
                        dataType: 'JSON',
                        success: function(resp) {
                            var html = '<ul class="products">';
                            $.each(resp, function(i, value){
                                if (value.empty) {
                                    html += '<li>';
                                    html += value.empty;
                                    html += '</li>';
                                } else {
                                    html += '<li>';
                                    html += '<div><input type="checkbox" name="products[]" value="'+value.product_id+'"' +
                                        ' data-sku="'+value.sku+'"' +
                                        ' data-image="'+value.image+'"' +
                                        ' data-stock="'+value.stock+'"' +
                                        ' data-price="'+value.price+'"' +
                                        ' data-name="'+value.name+'"></div>';
                                    html += '<div><img src="'+value.image+'"></div>';
                                    html += '<div><span>'+value.name+'</span><span>'+value.sku+'(<b>'+(value.stock == -2 ? 'Нет в наличии' : (value.stock == -1 ? 'Под заказ' : (value.stock == 0 ? 'Ожидается' : 'В наличии')))+'</b>)</span><span>'+value.price+' грн.</span></div>';
                                    html += '</li>';
                                }
                            });
                            html += '</ul>';

                            $.each(search_output, function(i, value){
                                if ($(value).attr('data-target') == target) {
                                    $(value).html(html).show();
                                }
                            });

                            $('#categories_tree').hide();
                        }
                    });
                } else {
                    search_output.hide();
                    $('#categories_tree').show();
                }
            });

            $(document).on('click', '#categories_tree .category', function(){
                var $this = $(this);
                var id = $this.data('id');
                if($this.parent().hasClass('active')){
                    $this.parent().removeClass('active')
                }else{
                    var children = $this.next();
                    if(children.hasClass('loaded')){
                        $this.parent().addClass('active');
                    }else{
                        $.post('/admin/categories/children/'+id, [], function(response){
                            var categories = '<ul class="categories">';
                            for(var i in response.categories){
                                var category = response.categories[i];
                                categories += '<li>';
                                categories += '<span class="category" data-id="'+category.id+'">'+category.name+'</span>';
                                categories += '<div class="children">';
                                categories += '</div>';
                                categories += '</li>';
                            }
                            categories += '<ul>';
                            children.append(categories);
                            if(response.products.length){
                                var products = '<ul class="products">';
                                for(var i in response.products){
                                    var product = response.products[i];
                                    products += '<li>';
                                    products += '<div><input type="checkbox" name="products[]" value="'+product.id+'"' +
                                        ' data-sku="'+product.sku+'"' +
                                        ' data-image="'+product.image+'"' +
                                        ' data-stock="'+product.stock+'"' +
                                        ' data-price="'+product.price+'"' +
                                        ' data-name="'+product.name+'"></div>';
                                    products += '<div><img src="'+product.image+'"></div>';
                                    products += '<div><span>'
                                        +product.name
                                        +' (<b>'+(product.stock == -2 ? 'Нет в наличии' : (product.stock == -1 ? 'Под заказ' : (product.stock == 0 ? 'Ожидается' : 'В наличии')))+'</b>)</span><span>'
                                        +product.sku
                                        +'</span><span>'
                                        +product.price
                                        +' грн.</span></div>';
                                    products += '</li>';
                                }
                                products += '</ul>';
                                children.append(products);
                            }

                            children.addClass('loaded');
                            $this.parent().addClass('active');
                        });
                    }
                }
            });

            $(document).on('change', '#search_product .products input', function(){
                var products = $('#search_product [name="products[]"]:checked').length;
                if(products == 0){
                    $('.swal2-confirm').prop('disabled', true);
                    $('.swal2-confirm').text('Добавить выбранный товар');
                }else if(products == 1){
                    $('.swal2-confirm').prop('disabled', false);
                    $('.swal2-confirm').text('Добавить выбранный товар');
                }else if(products > 1){
                    $('.swal2-confirm').prop('disabled', false);
                    $('.swal2-confirm').text('Добавить выбранные товары ('+products+'шт.)');
                }
            });

            // Удалить товар из заказа
            $(document).on('click', '.remove-product-from-order', function(){
                swal({
                    title: 'Вы уверены?',
                    text: "Этот товар будет удалён из заказа!",
                    type: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Да, удалить его!',
                    cancelButtonText: 'Нет, я ошибся.'
                }).then((result) => {
                    $(this).parents('tr').remove();
                },
                (cancel) => {});
            });

            window.newpostUpdate = function(id, value) {
                if (id === 'city') {
                    var data = {
                        city_id: value
                    };
                    var path = '/checkout/warehouses';
                    var selector = $('#warehouse');
                } else if (id === 'region') {
                    var data = {
                        region_id: value
                    };
                    var path = '/checkout/cities';
                    var selector = $('#city');
                    $('#warehouse').html('<option value="">Выберите населённый пункт</option>');
                }
                selector.find('option').text('Обновляются данные, ожидайте...');
                selector.attr('disabled', 'disabled');

                jQuery.ajax({
                    url: path,
                    data: data,
                    type: 'post',
                    dataType: 'json',
                    beforeSend: function() {

                    },
                    success: function(response){
                        if (response.error) {

                        } else if (response.success) {
                            var html = '<option value="0">Сделайте выбор</option>';
                            jQuery.each(response.success, function(i, resp){
                                if (id === 'city') {
                                    var info = resp.address_ru;
                                } else if (id === 'region') {
                                    var info = resp.name_ru;
                                }
                                html += '<option value="' + resp.id + '">' + info + '</option>';
                            });
                            selector.html(html);
                            selector.prop('disabled', false);
                        }
                    }
                })
            };

            window.justinUpdate = function(id, value) {
                if (id === 'city') {
                    var data = {
                        city_id: value
                    };
                    var path = '/checkout/justin_warehouses';
                    var selector = $('#warehouse');
                } else if (id === 'region') {
                    var data = {
                        region_id: value
                    };
                    var path = '/checkout/justin_cities';
                    var selector = $('#city');
                    $('#warehouse').html('<option value="">Выберите населённый пункт</option>');
                }
                selector.find('option').text('Обновляются данные, ожидайте...');
                selector.attr('disabled', 'disabled');

                jQuery.ajax({
                    url: path,
                    data: data,
                    type: 'post',
                    dataType: 'json',
                    beforeSend: function(){

                    },
                    success: function(response){
                        if (response.error) {

                        } else if (response.success) {
                            var html = '<option value="0">Сделайте выбор</option>';
                            jQuery.each(response.success, function(i, resp){
                                html += '<option value="' + i + '">' + resp.name + '</option>';
                            });
                            selector.html(html);
                            selector.prop('disabled', false);
                        }
                    }
                })
            };

            $('#js_delivery_method').change(function(){
                var $this = $(this);
                var method = $this.val();
                if(method === ''){
                    $this.parents('table').find('.delivery').remove();
                }else{
                    var data = {
                        delivery: method,
                        id: $(this).data('order-id')
                    };

                    $.post('/admin/orders/delivery', data, function(response){
                        $this.parents('table').find('.delivery').remove();
                        $this.parents('tr').after(response.delivery);
                    }, 'json');
                }

            });
        });
    </script>
@endsection
