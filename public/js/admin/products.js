jQuery.fn.serializeJSON=function() {
    var json = {};
    jQuery.map(jQuery(this).serializeArray(), function(n, i){
        var _ = n.name.indexOf('[');
        if(_ > -1){
            var o = json;
            _name = n.name.replace(/\]/gi, '').split('[');
            for(var i=0, len=_name.length; i<len; i++){
                if(i == len-1){
                    if(o[_name[i]]) {
                        if(typeof o[_name[i]] == 'string'){
                            o[_name[i]] = [o[_name[i]]];
                        }
                        o[_name[i]].push(n.value);
                    }
                    else o[_name[i]] = n.value || '';
                }
                else o = o[_name[i]] = o[_name[i]] || {};
            }
        }else{
            if(json[n.name] !== undefined){
                if (!json[n.name].push) {
                    json[n.name] = [json[n.name]];
                }
                json[n.name].push(n.value || '');
            }
            else json[n.name] = n.value || '';
        }
    });
    return json;
};

$(document).ready(function () {
    // init data table
    if($(".products-data-table").length){
        let displayLength = window.getCookie('products_display_length');

        let dataListView = $(".products-data-table").DataTable({
            ajax: {
                url: '/admin/products/list',
                type: 'POST',
                'data': function(data){
                    let advanced_filter = $('#js_advanced_filter').val();

                    if(advanced_filter){
                        let advanced_filter_data = JSON.parse(advanced_filter);

                        if(typeof advanced_filter_data.filter !== 'undefined')
                            data.filter = advanced_filter_data.filter;
                    }
                }
            },
            pageLength: displayLength == null ? 25 : displayLength,
            lengthMenu: [[25, 50, -1], [25, 50, __('All')]],
            columns: [
                {
                    data: 'id',
                    targets: 0,
                    render: function (data, type, row, meta) {
                        return '';
                    }
                },
                {
                    data: 'id',
                    name: 'products.id',
                    targets: 1,
                    render: function (data, type, row, meta) {
                        if (type === 'display') {
                            data = '<div class="checkbox"><input type="checkbox" name="selected[]" value="'+data.id+'" class="dt-checkboxes"><label></label></div>'; //body checkbox
                        }
                        return data;
                    },
                    checkboxes: {
                        'selectRow': true,
                        'selectAllRender': '<div class="checkbox"><input type="checkbox" id="js_select_all_checkbox" class="dt-checkboxes"><label></label></div >'  //head checkbox
                    }
                },
                {
                    data: 'image',
                    render: function ( data, type, row ) {
                        return data ? '<img class="rounded-circle" src="'+data+'" width="32" height="32">' : '';
                    }
                },
                {
                    data: 'name',
                    name: 'localization.value',
                    render: function ( data, type, row ) {
                        return data.name;
                    }
                },
                {
                    data: 'sku',
                    name: 'products.sku',
                },
                {
                    data: 'price',
                    name: 'products.price',
                },
                {
                    data: 'categories',
                    render: function ( data, type, row ) {
                        let html = '';
                        for(let id in data){
                            html += '<div class="badge badge-primary mr-1 mb-1 product-category category-'+id+'">'+data[id]+'</div>';
                        }
                        return html;
                    }
                },
                {
                    data: 'stock',
                    name: 'products.stock',
                },
                {
                    data: 'visible',
                    render: function ( data, type, row ) {
                        return '<div class="custom-switch custom-switch-success">\n' +
                            '<input type="checkbox" class="custom-control-input js_change_status" data-endpoint="products"\n' +
                            '  name="status" value="1" id="js_category_status_'+data.id+'"\n' +
                            '  data-id="'+data.id+'" autocomplete="off"'+(data.visible ? ' checked' : '')+'>\n' +
                            '<label class="custom-control-label" for="js_category_status_'+data.id+'">\n' +
                            '  <span class="switch-icon-left"><i class="bx bx-check"></i></span>\n' +
                            '  <span class="switch-icon-right"><i class="bx bx-x"></i></span>\n' +
                            '</label>\n' +
                            '</div>';
                    }
                },
                {
                    data: 'actions',
                    render: function ( data, type, row ) {
                        let html = '';

                        for(let i in data){
                            let text = '';

                            if(typeof data[i].type !== 'undefined'){
                                if(data[i].type === 'edit'){
                                    text = '<i class="bx bx-edit-alt" data-toggle="tooltip" data-placement="bottom" data-original-title="' + __('Edit') + '"></i>';
                                    html += '<a class="mr-1" href="'+ data[i].link +'">'+ text +'</a>';
                                }else if(data[i].type === 'delete'){
                                    text = '<i class="bx bx-trash" data-toggle="tooltip" data-placement="bottom" data-original-title="' + __('Delete') + '"></i>';
                                    html += '<span class="cursor-pointer js_delete_item" data-endpoint="products" data-id="'+data[i].id+'" data-name="'+data[i].name+'">'+ text +'</span>';
                                }
                            }else if(typeof data[i].text !== 'undefined'){
                                text = data[i].text;
                                html += '<a href="'+ data[i].link +'">'+ text +'</a>';
                            }
                        }

                        return html;
                    }
                }
            ],
            processing: true,
            serverSide: true,
            deferRender: true,
            columnDefs: [
                {
                    targets: 0,
                    className: "control"
                },
                {
                    targets: [0, 1, 2, 4, 6, 7, 8, 9],
                    orderable: false
                },
                { responsivePriority: 1, targets: 3 },
                { responsivePriority: 2, targets: 9 },
                { responsivePriority: 3, targets: 4 },
                { responsivePriority: 4, targets: 2 },
                { responsivePriority: 5, targets: 1 },
                { responsivePriority: 6, targets: 8 },
                { responsivePriority: 7, targets: 5},
                { responsivePriority: 8, targets: 7},
                { responsivePriority: 9, targets: 6},
            ],
            order: [3, 'asc'],
            language: window.localization.datatable,
            select: {
                style: "multi",
                selector: "td:nth-child(2)",
                items: "row"
            },
            responsive: {
                details: {
                    type: "column",
                    target: 0
                }
            }
        });

        $('#js_products_list_wrapper').on('change', '[name="DataTables_Table_0_length"]', function () {
            window.setCookie('products_display_length', $(this).val(), 90);
        });

        dataListView.on( 'draw.dt', function(){
            $('.products-data-table [data-toggle="tooltip"]').tooltip();
        });

        // Selection
        window.selected_products = $('#js_products_list');
        if(window.selected_products.length){
            window.selected_total = $('#js_selected_total');
            function selectAllProducts(){
                $('#js_select_group .btn').removeClass('btn-primary');

                let data = {};
                let advanced_filter = $('#js_advanced_filter').val();

                if(advanced_filter){
                    let advanced_filter_data = JSON.parse(advanced_filter);

                    if(typeof advanced_filter_data.filter !== 'undefined')
                        data.filter = advanced_filter_data.filter;
                }

                $.get('/admin/products/get_filtered_ids', data, function(response){
                    window.selected_products.val(response);
                    $('#js_select_all_checkbox').prop('checked', true);
                    $('#js_products_list_wrapper [name="selected[]"]').prop('checked', true);
                    $('#js_select_group .btn').addClass('btn-primary');
                    window.selected_total.text(response.split(',').length);
                });
            }
            $('#js_select_all').click(function(){
                selectAllProducts();
            });
            $('#js_products_list_wrapper').on('change', '#js_select_all_checkbox', function(){
                if($(this).prop('checked')){
                    selectAllProducts();
                }else{
                    window.selected_products.val('');
                    window.selected_total.text(0);
                }
            });
            $('#js_unselect_all').click(function(){
                $('#js_select_all_checkbox').prop('checked', false);
                $('#js_products_list_wrapper [name="selected[]"]').prop('checked', false);
                window.selected_products.val('');
                window.selected_total.text(0);
            });
            $('#js_select_visible').click(function(){
                $('#js_products_list_wrapper [name="selected[]"]').prop('checked', true);
                let ids = [];
                $('#js_products_list_wrapper [name="selected[]"]').each(function(){
                    if($.inArray($(this).val(), ids) == -1)
                        ids.push($(this).val());
                });
                window.selected_products.val(ids.join(','));
                window.selected_total.text(ids.length);
            });
            $('#js_unselect_visible').click(function(){
                $('#js_products_list_wrapper [name="selected[]"]').prop('checked', false);
                let ids = [];
                let val = window.selected_products.val();
                if(val !== ''){
                    ids = window.selected_products.val().split(',');
                }
                $('#js_products_list_wrapper [name="selected[]"]').each(function(){
                    let key = ids.indexOf($(this).val());
                    if(key >= 0)
                        ids.splice(key, 1);
                });
                window.selected_products.val(ids.join(','));
                window.selected_total.text(ids.length);
            });
            $('#js_products_list_wrapper').on('change', '[name="selected[]"]', function(){
                let ids = [];
                let val = window.selected_products.val();
                if(val !== ''){
                    ids = window.selected_products.val().split(',');
                }
                if($(this).prop('checked')){
                    if($.inArray($(this).val(), ids) == -1)
                        ids.push($(this).val());
                }else{
                    let key = ids.indexOf($(this).val());
                    if(key >= 0)
                        ids.splice(key, 1);
                }
                window.selected_products.val(ids.join(','));
                window.selected_total.text(ids.length);
            });
        }

        // Bulk Actions
        $('#js_current_action .dropdown-menu a').click(function(){
            let action = $(this).data('action');
            $('#js_current_action .dropdown-selected-name').text($(this).text());
            $('#js_current_action input').val(action);
            if(action === 'add_category' || action === 'remove_category' || action === 'change_categories') {
                let dropdown = '<div class="btn-group">\n' +
                    '<input type="hidden" name="category" value="" id="mass_category">\n' +
                    '<button type="button" class="btn btn-primary btn-sm glow dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">\n' +
                    '<span class="dropdown-selected-name">' + __('Category') + '</span>\n' +
                    '<span class="caret"></span>\n' +
                    '</button>\n' +
                    '<ul class="dropdown-menu" role="menu">\n' +
                    '<div style="overflow-y: auto;overflow-x: hidden;max-height: calc(100vh - 230px);">';
                for(let cat in window.categories) {
                    if(window.categories[cat].id != '')
                        dropdown += '<a class="dropdown-item" href="javascript:void(0)" data-value="' + window.categories[cat].id + '">' + window.categories[cat].name + '</a>\n';
                }
                dropdown += '</div>\n' +
                    '</ul>';
                $('#js_actions_container').html(dropdown);
            }else if(action === 'add_action' || action === 'remove_action'){
                let dropdown = '<div class="btn-group">\n' +
                    '<input type="hidden" name="action" value="" id="mass_action">\n' +
                    '<button type="button" class="btn btn-primary btn-sm glow dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">\n' +
                    '<span class="dropdown-selected-name">' + __('Promotion') + '</span>\n' +
                    '<span class="caret"></span>\n' +
                    '</button>\n' +
                    '<ul class="dropdown-menu" role="menu">\n' +
                    '<div style="overflow-y: auto;overflow-x: hidden;max-height: calc(100vh - 230px);">';
                for(let act in window.actions) {
                    dropdown += '<a class="dropdown-item" href="javascript:void(0)" data-value="'+window.actions[act].id+'">'+window.actions[act].name+'</a>\n';
                }
                dropdown += '</div>\n' +
                    '</ul>';
                $('#js_actions_container').html(dropdown);
            }else if(action === 'add_label'){
                let dropdown = '<div class="btn-group">\n' +
                    '<input type="hidden" name="label" value="" id="mass_label">\n' +
                    '<button type="button" class="btn btn-primary btn-sm glow dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">\n' +
                    '<span class="dropdown-selected-name">' + __('Label') + '</span>\n' +
                    '<span class="caret"></span>\n' +
                    '</button>\n' +
                    '<ul class="dropdown-menu" role="menu">\n' +
                    '<div style="overflow-y: auto;overflow-x: hidden;max-height: calc(100vh - 230px);">';
                for(let label in window.labels) {
                    dropdown += '<a class="dropdown-item" href="javascript:void(0)" data-value="'+window.labels[label].id+'">'+window.labels[label].name+'</a>\n';
                }
                dropdown += '</div>\n' +
                    '</ul>';
                $('#js_actions_container').html(dropdown);
            }else if(action === 'change_status'){
                let dropdown = '<div class="btn-group">\n' +
                    '<input type="hidden" name="status_id" value="" id="mass_status">\n' +
                    '<button type="button" class="btn btn-primary btn-sm glow dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">\n' +
                    '<span class="dropdown-selected-name">' + __('Availability') + '</span>\n' +
                    '<span class="caret"></span>\n' +
                    '</button>\n' +
                    '<ul class="dropdown-menu" role="menu">\n' +
                    '<a class="dropdown-item" href="javascript:void(0)" data-value="1">' + __('In stock') + '</a>\n' +
                    '<a class="dropdown-item" href="javascript:void(0)" data-value="-2">' + __('Out of stock') + '</a>\n' +
                    '<a class="dropdown-item" href="javascript:void(0)" data-value="0">' + __('Expected') + '</a>\n' +
                    '<a class="dropdown-item" href="javascript:void(0)" data-value="-1">' + __('On order') + '</a>\n' +
                    '</ul>';
                $('#js_actions_container').html(dropdown);
            }else if(action === 'change_attributes'){
                let dropdown = '<div class="btn-group">\n' +
                    '<button type="button" class="btn btn-primary btn-sm glow dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">\n' +
                    '<span class="dropdown-selected-name">' + __('Attribute') + '</span>\n' +
                    '<span class="caret"></span>\n' +
                    '</button>\n' +
                    '<ul class="dropdown-menu" role="menu">\n';

                for(let attr in window.attributes) {
                    dropdown += '<a class="dropdown-item changed_attribute" href="javascript:void(0)" data-value="'+window.attributes[attr].id+'">'+window.attributes[attr].name+'</a>\n';
                }

                dropdown += '</ul></div>';
                $('#js_actions_container').html(dropdown);
            }else{
                $('#js_actions_container').html('');
            }
        });
        $('#js_actions_container').on('click', '.dropdown-menu a.changed_attribute', function(){
            $('#js_actions_container > .btn-group + .btn-group').remove();
            $.ajax({
                url: '/admin/products/getattributevalues',
                type: 'POST',
                data: {
                    'attribute_id': $(this).data('value')
                },
                dataType: 'JSON',
                success: function(resp)
                {
                    let dropdown = '<div class="btn-group">\n' +
                        '<input type="hidden" name="find_attr_val_id" value="" id="mass_find_attr_val">\n' +
                        '<button type="button" class="btn btn-primary btn-sm glow dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">\n' +
                        '<span class="dropdown-selected-name">' + __('Original meaning') + '</span>\n' +
                        '<span class="caret"></span>\n' +
                        '</button>\n' +
                        '<ul class="dropdown-menu" role="menu">\n';

                    if(resp.length){
                        $.each(resp, function (i, value) {
                            dropdown += '<a class="dropdown-item" href="javascript:void(0)" data-value="'+value['attribute_value_id']+'">'+value['attribute_value']+'</a>\n';
                        });
                    }

                    dropdown += '</ul></div>';
                    dropdown += '<div class="btn-group">\n' +
                        '<input type="hidden" name="new_attr_val_id" value="" id="mass_new_attr_val">\n' +
                        '<button type="button" class="btn btn-primary btn-sm glow dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">\n' +
                        '<span class="dropdown-selected-name">' + __('New meaning') + '</span>\n' +
                        '<span class="caret"></span>\n' +
                        '</button>\n' +
                        '<ul class="dropdown-menu" role="menu">\n';

                    if(resp.length){
                        $.each(resp, function (i, value) {
                            dropdown += '<a class="dropdown-item" href="javascript:void(0)" data-value="'+value['attribute_value_id']+'">'+value['attribute_value']+'</a>\n';
                        });
                    }

                    dropdown += '</ul></div>';
                    $('#js_actions_container').append(dropdown);
                }
            });
        });
        $('#js_actions_container').on('click', '.dropdown-menu a', function(){
            $(this).parents('#js_actions_container .btn-group').find('.dropdown-selected-name').text($(this).text());
            $(this).parents('#js_actions_container .btn-group').find('input').val($(this).data('value'));
        });
        $('#submit_mass_action').click(function(){
            let action = $('#js_current_action input').val();
            let products = window.selected_products.val();
            if(products === ''){
                swal(
                    __('Please select products first!'),
                    __('Check the products you want to modify'),
                    'warning'
                );
                return false;
            }

            $(".alert").alert('close');
            addPlaceholder();
            if(action === 'add_category' || action === 'remove_category' || action === 'change_categories'){
                let category_id = $('#mass_category').val();
                $.post('/admin/products/mass_action/' + action, {
                    products: products,
                    category: category_id
                }, function(response) {
                    if (response.result === 'success'){
                        dataListView.draw();
                        Swal.fire({
                            title: __('Сategory updated!'),
                            text: response.message,
                            type: 'success'
                        });
                    }else if(response.result === 'error'){
                        Swal.fire({
                            title: __("Error"),
                            text: response.message,
                            type: "error",
                            confirmButtonClass: 'btn btn-primary',
                            buttonsStyling: false,
                        });
                    }
                    removePlaceholder();
                });
            }else if(action === 'remove_products'){
                Swal.fire({
                    title: __('Are you sure you want to delete selected products?'),
                    text: __('This action cannot be undone!'),
                    type: 'warning',
                    showCancelButton: true,
                    confirmButtonClass: 'btn btn-danger',
                    cancelButtonClass: "btn btn-primary ml-1",
                    confirmButtonText: __('Delete'),
                    cancelButtonText: __('Cancel'),
                    buttonsStyling: false,
                    showLoaderOnConfirm: true,
                    preConfirm: function (num) {
                        return $.ajax('/admin/products/mass_action/' + action, {
                            type: 'post',
                            data: {
                                products: products,
                                num: num
                            },
                            dataType: 'json'
                        }).done(function(response) {
                            return response;
                        }).fail(function(jqXHR) {
                            Swal.showValidationMessage(
                                `Request failed: ${jqXHR.statusText}`
                            );
                        });
                    },
                    allowOutsideClick: () => !Swal.isLoading()
                }).then((result) => {
                    if (result.value && result.value.result === 'success') {
                        Swal.fire({
                            title: __('Deleted!'),
                            text: result.value.message,
                            type: 'success'
                        });
                        if (typeof dataListView !== 'undefined') {
                            dataListView.draw();
                        }
                    }
                }).finally(() => {
                    if(typeof removePlaceholder === 'function'){
                        removePlaceholder();
                    }
                });
            }else if(action === 'change_status') {
                $.post('/admin/products/mass_action/' + action, {
                    products: products,
                    status: $('#mass_status').val()
                }, function(response){
                    if (response.result === 'success') {
                        products = products.split(',');
                        for (let i in products) {
                            $('#product-' + products[i] + ' .status > span').attr('class', $('#mass_status').val() == 0 ? 'off' : 'on');
                        }
                        Swal.fire({
                            title: __('Updated!'),
                            text: response.message,
                            type: 'success'
                        });
                    }else if(response.result === 'error'){
                        Swal.fire({
                            title: __("Error"),
                            text: response.message,
                            type: "error",
                            confirmButtonClass: 'btn btn-primary',
                            buttonsStyling: false,
                        });
                    }
                    removePlaceholder();
                });
            }else if(action === 'add_price' || action === 'add_sale_price' || action === 'multiply_price' || action === 'multiply_sale_price'){
                let title;
                if(action === 'add_price' || action === 'add_sale_price'){
                    title = __('How much to increase the price by?');
                }else if(action === 'multiply_price' || action === 'multiply_sale_price'){
                    title = __('By how many times to multiply the price?');
                }

                Swal.fire({
                    title: title,
                    input: 'text',
                    confirmButtonClass: 'btn btn-primary',
                    buttonsStyling: false,
                    inputAttributes: {
                        autocapitalize: 'off'
                    },
                    showCancelButton: true,
                    cancelButtonClass: "btn btn-danger ml-1",
                    confirmButtonText: __('Apply'),
                    cancelButtonText: __('Cancel'),
                    showLoaderOnConfirm: true,
                    preConfirm: function (num) {
                        return $.ajax('/admin/products/mass_action/' + action, {
                            type: 'post',
                            data: {
                                products: products,
                                num: num
                            },
                            dataType: 'json'
                        });
                    },
                    allowOutsideClick: () => !Swal.isLoading()
                }).then((result) => {
                    if (result.value && result.value.result === 'success') {
                        Swal.fire({
                            title: __('Prices updated!'),
                            text: result.value.message,
                            type: 'success'
                        });
                        if (typeof dataListView !== 'undefined') {
                            dataListView.draw();
                        }
                    }
                }).finally(() => {
                    if (typeof removePlaceholder === 'function') {
                        removePlaceholder();
                    }
                });
            }else if(action === 'change_sort_priority'){
                Swal.fire({
                    title: __('Set sort order'),
                    input: 'text',
                    confirmButtonClass: 'btn btn-primary',
                    buttonsStyling: false,
                    inputAttributes: {
                        autocapitalize: 'off'
                    },
                    showCancelButton: true,
                    cancelButtonClass: "btn btn-danger ml-1",
                    confirmButtonText: __('Apply'),
                    cancelButtonText: __('Cancel'),
                    showLoaderOnConfirm: true,
                    preConfirm: function (num) {
                        return $.ajax('/admin/products/mass_action/' + action, {
                            type: 'post',
                            data: {
                                products: products,
                                num: num
                            },
                            dataType: 'json'
                        }).done(function(response) {
                            return response;
                        }).fail(function(jqXHR) {
                            Swal.showValidationMessage(
                                `Request failed: ${jqXHR.statusText}`
                            );
                        });
                    },
                    allowOutsideClick: () => !Swal.isLoading()
                }).then((result) => {
                    if (result.value && result.value.result === 'success') {
                        Swal.fire({
                            title: __('Sort order updated!'),
                            text: result.value.message,
                            type: 'success'
                        });
                        if (typeof dataListView !== 'undefined') {
                            dataListView.draw();
                        }
                    }
                }).finally(() => {
                    if (typeof removePlaceholder === 'function') {
                        removePlaceholder();
                    }
                });
            }else if(action === 'change_attributes'){
                $.post('/admin/products/mass_action/'+action, {products: products, find_attr_val: $('#mass_find_attr_val').val(), new_attr_val: $('#mass_new_attr_val').val()}, function(response){
                    if(response.result === 'success'){
                        Swal.fire({
                            title: __('Updated!'),
                            text: response.message,
                            type: 'success'
                        });
                    }else if(response.result === 'error'){
                        Swal.fire({
                            title: __("Error"),
                            text: response.message,
                            type: "error",
                            confirmButtonClass: 'btn btn-primary',
                            buttonsStyling: false,
                        });
                    }
                    removePlaceholder();
                });
            }
        });

        dataListView.on('draw', function () {
            let products = window.selected_products.val();
            var selected = products.split(',');
            $('[name="selected[]"]').each(function(){
                if(selected.indexOf(''+$(this).val()) == -1){
                    $(this).prop('checked', false);
                }else{
                    $(this).prop('checked', true);
                }
            });
        });

        function addPlaceholder(){
            $('.app-content').block({
                message: '<div class="bx bx-sync icon-spin font-medium-2 text-primary"></div>',
                overlayCSS: {
                    backgroundColor: '#10163a',
                    cursor: 'wait'
                },
                css: {
                    border: 0,
                    padding: 0,
                    backgroundColor: 'none'
                }
            })
        }

        function removePlaceholder(){
            $('.app-content').unblock();
        }

        // Filter
        let filter_form = $('#js_filter_form');
        if(filter_form.length){
            filter_form.on('click', '#js_add_condition_group', function(){
                let form = filter_form.find('.panel-body');
                let id = form.find('.js_filters_group').length ? parseInt(form.find('.js_filters_group').last().data('group-id')) + 1 : 0;
                form.append('<div class="card form-group text-white bg-danger bg-lighten-1 text-center js_filters_group" data-group-id="'+id+'">\n' +
                    '<div class="card-content">\n' +
                    '<div class="card-body">' +

                    (id > 0 ? '<div class="row">'+
                        '<fieldset class="col form-group mb-1 mt-1" style="min-width: 320px;">\n' +
                        '  <div class="input-group input-group-sm">\n' +
                        '    <div class="input-group-prepend">\n' +
                        '      <label class="input-group-text" for="inputGroupSelect01">' + __('Group combination method:') + '</label>\n' +
                        '    </div>\n' +
                        '    <select name="filter['+id+'][0][relations]" class="form-control form-control-sm relations" style="min-width: 70px;">\n' +
                        '      <option value="AND" selected>' + __('AND') + '</option>\n' +
                        '      <option value="OR">' + __('OR') + '</option>\n' +
                        '    </select>\n' +
                        '  </div>\n' +
                        '</fieldset>' +
                        '<div class="col"></div>' +
                        '<div class="col mb-1 mt-1 d-flex flex-sm-row flex-column justify-content-end">' +
                        '<button type="button" class="btn btn-danger btn-sm text-nowrap px-1 js_remove_group">' +
                        '    <i class="bx bx-x"></i>' + __('Delete group') +
                        '</button>' +
                        '</div>\n' +
                        '</div>' : '') +

                    '<div class="row condition-wrapper">\n' +
                    '  <div class="col">' +
                    '    <div class="row condition" data-id="0">\n' +
                    criterionTemplate(id, 0, 'category') +
                    valueTemplate(id, 0, 'category') +
                    // conditionTemplate(id, 0, 'category') +
                    '    </div>\n' +
                    '  </div>\n' +
                    '</div>\n' +
                    '<div class="row mt-1">\n' +
                    '  <div class="col text-center buttons">\n' +
                    '    <button type="button" class="btn btn-sm btn-primary js_add_sub_condition">' + __('Add condition') + '</button>\n' +
                    '  </div>' +
                    '</div>\n' +

                    '</div>\n' +
                    '</div>\n' +
                    '</div>');
            });

            filter_form.on('click', '.js_add_sub_condition', function(){
                let group = $(this).parents('.js_filters_group');
                let group_id = group.data('group-id');
                let id = group.find('.row.condition').length ? parseInt(group.find('.row.condition').last().data('id')) + 1 : 0;
                group.find('.buttons').parent().before(
                    '<div class="row condition-wrapper">'+
                    '<div class="col">' +
                    '<div class="row">'+
                    '<fieldset class="col form-group mb-1 mt-1" style="min-width: 320px;">\n' +
                    '  <div class="input-group input-group-sm">\n' +
                    '    <div class="input-group-prepend">\n' +
                    '      <label class="input-group-text" for="inputGroupSelect01">' + __('Condition combination method:') + '</label>\n' +
                    '    </div>\n' +
                    '    <select name="filter['+group_id+']['+id+'][relations]" class="form-control form-control-sm relations" style="min-width: 70px;">\n' +
                    '      <option value="AND" selected>' + __('AND') + '</option>\n' +
                    '      <option value="OR">' + __('OR') + '</option>\n' +
                    '    </select>\n' +
                    '  </div>\n' +
                    '</fieldset>' +
                    '<div class="col"></div>' +
                    '<div class="col mb-1 mt-1 d-flex flex-sm-row flex-column justify-content-end">' +
                    '<button type="button" class="btn btn-danger btn-sm text-nowrap px-1 js_remove_condition">' +
                    '    <i class="bx bx-x"></i>' + __('Delete condition') +
                    '</button>' +
                    '</div>\n' +
                    '</div>' +
                    '<div class="row condition" data-id="'+id+'">\n' +
                    criterionTemplate(group_id, id, 'category') +
                    valueTemplate(group_id, id, 'category') +
                    // conditionTemplate(group_id, id, 'category') +
                    '</div>'+
                    '</div>'+
                    '</div>'
                );
            });

            filter_form.on('click', '.js_remove_condition', function(){
                $(this).parents('.condition-wrapper').remove();
            });

            filter_form.on('click', '.js_remove_group', function(){
                $(this).parents('.js_filters_group').remove();
            });

            filter_form.on('change', 'select.criterion', function(){
                let criterion = $(this).val();
                let parent = $(this).parents('.row.condition');
                let group = $(this).parents('.form-group');
                let group_id = group.data('group-id');
                let id = parent.data('id');
                if(criterion === 'category'){
                    parent.replaceWith(
                        '<div class="row condition" data-id="'+id+'">\n' +
                        criterionTemplate(group_id, id, 'category') +
                        valueTemplate(group_id, id, 'category') +
                        // conditionTemplate(group_id, id, 'category') +
                        '</div>\n');
                }else if(criterion === 'attribute'){
                    parent.replaceWith(
                        '<div class="row condition" data-id="'+id+'">\n' +
                        criterionTemplate(group_id, id, 'attribute') +
                        valueTemplate(group_id, id, 'attribute') +
                        '</div>\n');
                }else if(criterion === 'stock'){
                    parent.replaceWith(
                        '<div class="row condition" data-id="'+id+'">\n' +
                        criterionTemplate(group_id, id, 'stock') +
                        valueTemplate(group_id, id, 'stock') +
                        '</div>\n');
                }else if(criterion === 'price'){
                    parent.replaceWith(
                        '<div class="row condition" data-id="'+id+'">\n' +
                        criterionTemplate(group_id, id, 'price') +
                        conditionTemplate(group_id, id, 'price') +
                        valueTemplate(group_id, id, 'price') +
                        '</div>\n');
                }else if(criterion === 'description'){
                    parent.replaceWith(
                        '<div class="row condition" data-id="'+id+'">\n' +
                        criterionTemplate(group_id, id, 'description') +
                        conditionTemplate(group_id, id, 'description') +
                        valueTemplate(group_id, id, 'description') +
                        '</div>\n');
                }
            });

            filter_form.on('change', 'select.attributes', function(){
                let attribute = $(this).val();
                let parent = $(this).parents('.row.condition');
                let group = $(this).parents('.form-group');
                let group_id = group.data('group-id');
                let id = parent.data('id');
                let values = '';

                for(let attr in window.attributes) {
                    if(window.attributes[attr].id == attribute){
                        for(let val in window.attributes[attr].values) {
                            values += '<option value="'+window.attributes[attr].values[val].id+'"'+(val?'':' selected')+'>'+window.attributes[attr].values[val].name+'</option>\n';
                        }
                    }
                }

                parent.children('.form-element:last-child').replaceWith(selectTemplate('filter['+group_id+']['+id+'][value]', __('Value'), values, 'value'));
            });

            filter_form.submit(function(e){
                e.preventDefault();
                let filters = $(this).serializeJSON();
                if($.isEmptyObject(filters) || filter_form.find('.js_filters_group').length < 2){
                    $('#js_quick_filters').removeClass('hidden');
                    if($.isEmptyObject(filters)){
                        let quick_filter = $('#js_quick_filters .js_quick_filter[value=""]');
                        quick_filter.prop('checked', true);
                        $('#js_quick_filters .active').removeClass('active');
                        quick_filter.parent().addClass('active');
                    }
                }else{
                    $('#js_quick_filters').addClass('hidden');
                }
                $('#js_advanced_filter').val(JSON.stringify(filters));
                dataListView.draw();
                updateSelected();
                $('#filter-popup').modal('hide');
            });

            $(document).on('click', '.js_remove_filter', function(){
                let group_id = $(this).data('group');
                let criterion_id = $(this).data('criterion');
                let group = filter_form.find('.js_filters_group[data-group-id="'+group_id+'"]');
                let condition = group.find('.condition[data-id="'+criterion_id+'"]');
                let wrapper = condition.parents('.condition-wrapper');

                if(wrapper.length){
                    wrapper.remove();
                    group.find('.condition-wrapper').first().find('.condition').parent().children('.row:not(.condition)').remove();
                }

                if(group.find('.condition-wrapper').length === 0){
                    group.remove();
                    filter_form.find('.js_filters_group').first().find('.condition-wrapper').parent().children('.row:not(.condition-wrapper)').first().remove();
                }

                $(this).parents('.js_chip').remove();

                filter_form.submit();
            });

            $(document).on('change', '.js_quick_filter', function(){
                let val = $(this).val();

                if(val === ''){
                    filter_form.find('.panel-body').html('');
                    $('#js_selected_filters').html('');
                    $('#js_advanced_filter').val('');
                    filter_form.find('#js_add_condition_group').click();
                    dataListView.draw();
                }else if(val.indexOf('stock') === 0){
                    let stock = val.replace('stock_', '');
                    let group = filter_form.find('.js_filters_group');
                    let applied = false;

                    if(group.length === 1){
                        group.find('.condition').each(function(){
                            if($(this).find('.criterion').val() === 'stock'){
                                $(this).find('.value option[value="'+stock+'"]').prop('selected', true);
                                applied = true;
                            }
                        });

                        if(!applied){
                            if(group.find('.condition').length === 1
                                && group.find('.condition .criterion').val() === 'category'
                                && group.find('.condition .value').val() === ''
                                && $('#js_advanced_filter').val() === ''){
                                group.find('.condition .criterion option[value="stock"]').prop('selected', true).change();
                                group.find('.condition .value option[value="'+stock+'"]').prop('selected', true);
                                applied = true;
                            }else{
                                group.find('.js_add_sub_condition').click();
                                group.find('.condition').last().find('.criterion option[value="stock"]').prop('selected', true).change();
                                group.find('.condition').last().find('.value option[value="'+stock+'"]').prop('selected', true);
                                applied = true;
                            }
                        }
                    }else{
                        filter_form.find('.panel-body').html('<div class="card form-group text-white bg-danger bg-lighten-1 text-center js_filters_group" data-group-id="0">\n' +
                            '  <div class="card-content">\n' +
                            '    <div class="card-body">\n' +
                            '      <div class="row condition-wrapper">\n' +
                            '        <div class="col">\n' +
                            '          <div class="row condition" data-id="0">\n' +
                            criterionTemplate(0, 0, 'stock') +
                            valueTemplate(0, 0, 'stock') +
                            '          </div>\n' +
                            '        </div>\n' +
                            '        <div class="row mt-1">\n' +
                            '          <div class="col text-center buttons">\n' +
                            '            <button type="button" class="btn btn-sm btn-primary js_add_sub_condition">' + __('Add condition') + '</button>\n' +
                            '          </div>\n' +
                            '        </div>\n' +
                            '      </div>\n' +
                            '    </div>\n' +
                            '  </div>\n' +
                            '</div>');

                        filter_form.find('[name="filter[0][0][value]"] option[value="'+stock+'"]').prop('selected', true);
                        applied = true;
                    }

                    if(applied)
                        filter_form.submit();
                }
            });

            $('.js_category_filter').click(function(){
                let id = $(this).data('id');
                let group = filter_form.find('.js_filters_group');
                let applied = false;

                if(group.length === 1){
                    group.find('.condition').each(function(){
                        if($(this).find('.criterion').val() === 'category'){
                            $(this).find('.value option[value="'+id+'"]').prop('selected', true);
                            applied = true;
                        }
                    });

                    if(!applied){
                        group.find('.js_add_sub_condition').click();
                        group.find('.condition').last().find('.value option[value="'+id+'"]').prop('selected', true);
                        applied = true;
                    }
                }else{
                    filter_form.find('.panel-body').html('<div class="card form-group text-white bg-danger bg-lighten-1 text-center js_filters_group" data-group-id="0">\n' +
                        '  <div class="card-content">\n' +
                        '    <div class="card-body">\n' +
                        '      <div class="row condition-wrapper">\n' +
                        '        <div class="col">\n' +
                        '          <div class="row condition" data-id="0">\n' +
                        criterionTemplate(0, 0, 'category') +
                        valueTemplate(0, 0, 'category') +
                        '          </div>\n' +
                        '        </div>\n' +
                        '      </div>\n' +
                        '      <div class="row mt-1">\n' +
                        '        <div class="col text-center buttons">\n' +
                        '          <button type="button" class="btn btn-sm btn-primary js_add_sub_condition">' + __('Add condition') + '</button>\n' +
                        '        </div>\n' +
                        '      </div>\n' +
                        '    </div>\n' +
                        '  </div>\n' +
                        '</div>');

                    filter_form.find('[name="filter[0][0][value]"] option[value="'+id+'"]').prop('selected', true);
                    applied = true;
                }

                if(applied)
                    filter_form.submit();
            });

            function criterionTemplate(group_id, id, selected){
                let params = {
                    category: __('Category'),
                    attribute: __('Attribute'),
                    stock: __('Availability'),
                    price: __('Price'),
                    description: __('Description')
                };

                let options = '';
                for(let param in params){
                    options += '<option value="'+param+'"'+(param==selected?' selected':'')+'>'+params[param]+'</option>\n';
                }

                return selectTemplate('filter['+group_id+']['+id+'][criterion]', __('Filter criterion'), options);
            }

            function conditionTemplate(group_id, id, criterion){
                let params = {
                    // category: {
                    //     with_child: __('Including child categories'),
                    //     without_child: __('Excluding child categories')
                    // },
                    attribute: {
                        '=': __('equals')
                    },
                    stock: {
                        '=': __('equals')
                    },
                    price: {
                        '=': __('equals'),
                        '>': __('greater than'),
                        '<': __('less than')
                    },
                    description: {
                        '=': __('equals'),
                        '%': __('contains')
                    }
                };

                let options = '';
                for(let param in params[criterion]){
                    options += '<option value="'+param+'"'+(param==Object.keys(params[criterion])[0]?' selected':'')+'>'+params[criterion][param]+'</option>\n';
                }

                return selectTemplate('filter['+group_id+']['+id+'][condition]', __('Condition'), options, 'price_condition');
            }

            function valueTemplate(group_id, id, criterion){
                let options = '';
                if(criterion === 'category'){
                    for(let cat in window.categories) {
                        options += '<option value="'+window.categories[cat].id+'"'+(cat?'':' selected')+'>'+(window.categories[cat].id === '' ? __('Without category') : window.categories[cat].name)+'</option>\n';
                    }
                    return selectTemplate('filter['+group_id+']['+id+'][value]', __('Value'), options, 'value');
                }else if(criterion === 'attribute'){
                    let attributes = '';
                    let values = '';
                    for(let attr in window.attributes) {
                        attributes += '<option value="'+window.attributes[attr].id+'"'+(attr?'':' selected')+'>'+window.attributes[attr].name+'</option>\n';
                        if(attr == 0){
                            for(let val in window.attributes[attr].values) {
                                values += '<option value="'+window.attributes[attr].values[val].id+'"'+(val?'':' selected')+'>'+window.attributes[attr].values[val].name+'</option>\n';
                            }
                        }
                    }
                    return selectTemplate('filter['+group_id+']['+id+'][attribute]', __('Attribute'), attributes, 'attributes') +
                        selectTemplate('filter['+group_id+']['+id+'][value]', __('Value'), values, 'value');
                }else if(criterion === 'stock'){
                    let params = {
                        '1': __('In stock'),
                        '-2': __('Out of stock'),
                        '0': __('Expected'),
                        '-1': __('On order')
                    };
                    let options = '';
                    for(let param in params){
                        options += '<option value="'+param+'"'+(param==1?' selected':'')+'>'+params[param]+'</option>\n';
                    }
                    return selectTemplate('filter['+group_id+']['+id+'][value]', __('Value'), options, 'value');
                }else if(criterion === 'price'){
                    setTimeout(function(){
                        $('.touchspin').TouchSpin({
                            buttondown_class: 'btn btn-primary',
                            buttonup_class: 'btn btn-primary'
                        });
                    }, 10);
                    return '<div class="form-element col-sm-4">\n' +
                        '<label class="text-right">Значение</label>\n' +
                        '<div class="input-group input-group-sm bootstrap-touchspin bootstrap-touchspin-injected">' +
                        '<input type="text" name="filter['+group_id+']['+id+'][value]" step="0.01" data-bts-step="0.01" data-bts-decimals="2" class="touchspin form-control form-control-sm value" data-bts-prefix="₴">' +
                        '</div>\n' +
                        '</div>\n';
                }else if(criterion === 'description'){
                    return '<div class="form-element col-sm-4">\n' +
                        '<label class="text-right">Значение</label>\n' +
                        '<input type="text" name="filter['+group_id+']['+id+'][value]" class="form-control form-control-sm value">\n' +
                        '</div>\n';
                }
            }

            function selectTemplate(name, label, options, cls = 'criterion'){
                return '<div class="form-element col">\n' +
                    '<label class="text-right">'+label+':</label>\n' +
                    '<select name="'+name+'" class="form-control form-control-sm ' + cls + '">\n' +
                    options +
                    '</select>\n' +
                    '</div>\n';
            }

            function updateSelected(){
                $('#js_selected_filters').html('');

                filter_form.find('.condition').each(function(){
                    let group_id = $(this).parents('.js_filters_group').data('group-id');
                    let criterion_id = $(this).data('id');
                    let criterion = $(this).find('.criterion');
                    let name = criterion.find('option[value="'+criterion.val()+'"]').text();
                    let value = $(this).find('.value');
                    let val = value.get(0).nodeName === 'SELECT' ? value.find('option[value="'+value.val()+'"]').text() : value.val();

                    if(criterion.val() === 'category'){
                        name += ': '+val;
                    }else if(criterion.val() === 'attribute'){
                        let attribute = $(this).find('.attributes');
                        name = attribute.find('option[value="'+attribute.val()+'"]').text()+': '+val;
                    }else if(criterion.val() === 'stock'){
                        name = val;
                    }else if(criterion.val() === 'price'){
                        let condition = $(this).find('.price_condition').val();
                        if(condition === '='){
                            name += ': '+val+__('GBP');
                        }else if(condition === '>'){
name += ' '+__('from')+': '+val+__('GBP');
                        }else if(condition === '<'){
name += ' '+__('to')+': '+val+__('GBP');
                        }
                    }else if(criterion.val() === 'description'){
                        let condition = $(this).find('.price_condition').val();
                        if(condition === '='){
name += ' '+__('equals')+': '+val;
                        }else if(condition === '%'){
name += ' '+__('contains')+': '+val;
                        }
                    }

                    $('#js_selected_filters').append('<div class="js_chip chip mr-1">\n' +
                        '  <div class="chip-body">\n' +
                        '    <span class="chip-text">'+name+'</span>\n' +
                        '    <div class="chip-closeable js_remove_filter" data-group="'+group_id+'" data-criterion="'+criterion_id+'">\n' +
                        '      <i class="bx bx-minus-circle"></i>\n' +
                        '    </div>\n' +
                        '  </div>\n' +
                        '</div>');
                });
            }
        }
    }

    function initRepeater(){
        let attributes = $('.attributes-repeater');
        if(attributes.length){
            attributes.repeater({
                show: function(){
                    $(this).slideDown();
                    $(this).find('.select2-container').remove();
                    // Large
                    $(this).find('.select2-size-lg').removeAttr('data-select2-id');
                    $(this).find('.select2-size-lg').select2({
                        dropdownAutoWidth: true,
                        width: '100%',
                        containerCssClass: 'select-lg'
                    });

                    // Small
                    $(this).find('.select2-size-sm').removeAttr('data-select2-id');
                    $(this).find('.select2-size-sm').select2({
                        dropdownAutoWidth: true,
                        width: '100%',
                        containerCssClass: 'select-sm'
                    });
                },
                hide: function(deleteElement){
                    toastr.info(__('To complete the deletion, click the "Save Changes" button'), __('Attention'));
                    $(this).slideUp(deleteElement);
                },
                initEmpty: attributes.hasClass('empty')
            });
        }

        let variations = $('.variations-repeater');
        if(variations.length){
            variations.repeater({
                repeaters: [{
                    selector: '.variation-attributes-repeater',
                    show: function(){
                        $(this).slideDown();
                        initPickadate();
                        variations.find('[type="checkbox"]').each(function(){
                            let id = $(this).attr('name').replaceAll('[', '_').replaceAll(']', '_').replaceAll('__', '_');
                            $(this).attr('id', id);
                            $(this).next('label').attr('for', id);
                        });
                    },
                    hide: function(deleteElement){
                        $(this).slideUp(deleteElement);
                    },
                    clone: function(){
                        $(this).find('.js_variation_id').val('');
                    }
                }],
                initEmpty: variations.hasClass('empty'),
                show: function(){
                    $(this).slideDown();
                    initPickadate();
                    variations.find('[type="checkbox"]').each(function(){
                        let id = $(this).attr('name').replaceAll('[', '_').replaceAll(']', '_').replaceAll('__', '_');
                        $(this).attr('id', id);
                        $(this).next('label').attr('for', id);
                    });
                },
                hide: function(deleteElement){
                    toastr.info(__('To complete the deletion, click the "Save Changes" button'), __('Attention'));
                    $(this).slideUp(deleteElement);
                },
                clone: function(){
                    $(this).find('.js_variation_id').val('');
                }
            });
        }
    }
    initRepeater();

    function initPickadate(){
        if($('.pickadate').length){
            $('.pickadate').pickadate({
                monthsFull: [__('January'), __('February'), __('March'), __('April'), __('May'), __('June'), __('July'), __('August'), __('September'), __('October'), __('November'), __('December')],
                monthsShort: [__('Jan'), __('Feb'), __('Mar'), __('Apr'), __('May'), __('Jun'), __('Jul'), __('Aug'), __('Sep'), __('Oct'), __('Nov'), __('Dec')],
                weekdaysFull: [__('Sunday'), __('Monday'), __('Tuesday'), __('Wednesday'), __('Thursday'), __('Friday'), __('Saturday')],
                weekdaysShort: [__('Sun'), __('Mon'), __('Tue'), __('Wed'), __('Thu'), __('Fri'), __('Sat')],
                showMonthsShort: undefined,
                showWeekdaysFull: undefined,
                today: __('Today'),
                clear: __('Clear'),
                close: __('Close'),
                labelMonthNext: __('Next month'),
                labelMonthPrev: __('Previous month'),
                labelMonthSelect: __('Select a month'),
                labelYearSelect: __('Select a year'),
                formatSubmit: 'dd.mm.yyyy',
                hiddenName: true,
                selectYears: true,
                selectMonths: true
            });
        }
    }
    initPickadate();

    $(document).on('change', '.js_product_attribute_select', function(){
        var $this = $(this);
        if($this.val()){
            $this.parents('[data-repeater-item]').find('.js_product_attribute_id').val($this.val());
            $.post('/admin/products/attributes/api/values/'+$this.val(), {}, function(response){
                if(response.result === 'success'){
                    let destination = $this.parents('[data-repeater-item]').eq(0).find('.js_product_values_select');
                    destination.html('');
                    for(let i in response.values){
                        destination.append('<option value="'+response.values[i].id+'">'+response.values[i].name+$this.find('[value="'+$this.val()+'"]').data('unit')+'</option>');
                        let parent = destination.parent();
                        parent.find('.select2-container').remove();
                        // Large
                        parent.find('.select2-size-lg').removeAttr('data-select2-id');
                        parent.find('.select2-size-lg').select2({
                            dropdownAutoWidth: true,
                            width: '100%',
                            containerCssClass: 'select-lg'
                        });

                        // Small
                        parent.find('.select2-size-sm').removeAttr('data-select2-id');
                        parent.find('.select2-size-sm').select2({
                            dropdownAutoWidth: true,
                            width: '100%',
                            containerCssClass: 'select-sm'
                        });
                    }
                }
            }, 'json');
        }else{
            let destination = $this.parents('[data-repeater-item]').eq(0).find('.js_product_values_select');
            destination.html('');
        }
    });

    $('#js_product_form').on('saved', function(e, data){
        if(typeof data.redirect !== 'undefined'){
            location = data.redirect;
        }
    });
});
