$(document).ready(function () {
    // init data table
    let exports_data_table = $(".exports-data-table");
    if(exports_data_table.length) {
        let displayLength = window.getCookie('exports_display_length');

        let dataListView = exports_data_table.DataTable({
            ajax: {
                url: '/admin/products/exports/list',
                type: "POST"
            },
            pageLength: displayLength == null ? 25 : displayLength,
            lengthMenu: [[25, 50, -1], [25, 50, __('All')]],
            columns: [
                {
                    data: '',
                    render: function( data, type, row ) {
                        return '';
                    }
                },
                {
                    data: 'name',
                    name: 'name',
                    render: function ( data, type, row ) {
                        return data.name;
                    }
                },
                {
                    data: 'type',
                    name: 'type',
                    render: function ( data, type, row ) {
                        return data.type;
                    }
                },
                {
                    data: 'url',
                    render: function ( data, type, row ) {
                        if(data.file){
                            return '<a href="'+data.file+'" target="_blank">'+data.url+'</a>';
                        }else{
                            return data.url;
                        }
                    }
                },
                {
                    data: 'updated_at',
                    render: function ( data, type, row ) {
                        return data.updated_at;
                    }
                },
                {
                    data: 'nextRun',
                    render: function ( data, type, row ) {
                        return data.nextRun;
                    }
                },
                {
                    data: 'status',
                    render: function ( data, type, row ) {
                        return data.status;
                    }
                },
                {
                    data: 'actions',
                    className: 'dt-right',
                    render: function ( data, type, row ) {
                        let html = '';

                        for(let i in data){
                            let text = '';

                            if(typeof data[i].type !== 'undefined'){
                                if(data[i].type === 'refresh'){
                                    text = '<i class="bx bx-sync" data-toggle="tooltip" data-placement="bottom" data-original-title="' + __('Refresh') + '"></i>';
                                    html += '<a class="js_fast_export mr-1" href="javascript:void(0);" data-id="'+data[i].id+'">'+ text +'</a>';
                                }else if(data[i].type === 'download'){
                                    text = '<i class="bx bx-export" data-toggle="tooltip" data-placement="bottom" data-original-title="' + __('Download') + '"></i>';
                                    html += '<a class="mr-1" href="'+ data[i].link +'">'+ text +'</a>';
                                }else if(data[i].type === 'edit'){
                                    text = '<i class="bx bx-edit-alt" data-toggle="tooltip" data-placement="bottom" data-original-title="' + __('Edit') + '"></i>';
                                    html += '<a class="mr-1" href="'+ data[i].link +'">'+ text +'</a>';
                                }else if(data[i].type === 'delete'){
                                    text = '<i class="bx bx-trash" data-toggle="tooltip" data-placement="bottom" data-original-title="' + __('Delete') + '"></i>';
                                    html += '<span class="cursor-pointer js_delete_item" data-endpoint="products/exports" data-id="'+data[i].id+'" data-name="'+data[i].name+'">'+ text +'</span>';
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
                    targets: [0, 3, 4, 5, 6, 7],
                    orderable: false
                },
                { responsivePriority: 1, targets: 2 },
                { responsivePriority: 2, targets: 7 },
                { responsivePriority: 3, targets: 4 },
                { responsivePriority: 4, targets: 3 },
                { responsivePriority: 5, targets: 5 },
                { responsivePriority: 6, targets: 6 },
            ],
            order: [1, 'asc'],
            //dom: '<"top d-flex flex-wrap"<"action-filters flex-grow-1"f><"actions action-btns d-flex align-items-center">><"clear">rt<"bottom"p>',
            language: window.localization.datatable,
            select: {
                style: "multi",
                selector: "td:first-child",
                items: "row"
            },
            responsive: {
                details: {
                    type: "column",
                    target: 0
                }
            }
        });

        $('#js_exports_list_wrapper').on('change', '[name="DataTables_Table_0_length"]', function () {
            window.setCookie('exports_display_length', $(this).val(), 90);
        });

        dataListView.on( 'draw.dt', function(){
            $('.exports-data-table [data-toggle="tooltip"]').tooltip();
        });

        exports_data_table.on('click', '.js_fast_export', function(e){
            e.preventDefault();
            let id = $(this).data('id');
            Swal.fire({
                title: __('File generation in progress'),
                html: '<p>' + __('Please wait for the process to complete') + '</p><div id="export_progress"></div>',
                confirmButtonText: '',
                onBeforeOpen: () => {
                    Swal.showLoading()
                }
            });
            next_export_step(id, 1);
        });
        function next_export_step(id, start = 0){
            $.post('/admin/products/exports/'+id+'/refresh', {start: start}, function(response){
                if(response.saved != response.total) {
                    let percent = Math.round(response.saved / response.total * 100);
                    $('#export_progress').html('<p>' + __('Processed :saved of :total products', {saved: response.saved, total: response.total}) + '</p>' +
                        '<div class="progress progress-striped active">\n' +
                        '<div class="progress-bar"  role="progressbar" aria-valuenow="' + percent + '" aria-valuemin="0" aria-valuemax="100" style="width: ' + percent + '%">\n' +
                        percent + '%\n' +
                        '</div>\n' +
                        '</div>');
                    next_export_step(id);
                }else{
                    $('#export_progress').html('<p>' + __('Export completed!') + '</p>' +
                        '<div class="progress progress-striped active">\n' +
                        '<div class="progress-bar"  role="progressbar" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100" style="width: 100%">\n' +
                        '100%\n' +
                        '</div>\n' +
                        '</div>');
                    Swal({
                        type: 'success',
                        title: __('Export completed!'),
                        showConfirmButton: false,
                        timer: 1500
                    })
                }
            });
        }
    }

    $('#type').change(function(){
        $('#js_extension').text('.'+$(this).val());
    });

    function initRepeater(){
        let fields = $('.fields-repeater');
        if(fields.length){
            fields.repeater({
                repeaters: [{
                    selector: '.fields-modifications-repeater',
                    show: function(){
                        $(this).slideDown();
                    },
                    hide: function(deleteElement){
                        $(this).slideUp(deleteElement);
                    }
                }],
                show: function(){
                    $(this).slideDown();
                },
                hide: function(deleteElement){
                    toastr.info(__('To complete the deletion, click the \"Save Changes\" button'), __('Attention'));
                    $(this).slideUp(deleteElement);
                }
            });
        }
    }
    initRepeater();

    if($('.touchspin').length){
        $('.touchspin').TouchSpin({
            buttondown_class: 'btn btn-primary',
            buttonup_class: 'btn btn-primary',
            max: null,
            step: 100,
            decimals: 2,
            forcestepdivisibility: 'none'
        });
    }

    $('#js_export_fields_form').on('change', '.modification select', function(){
        let field_id = $(this).parents('.js_export_field').data('id');
        let modification = $(this).parents('.modification');
        let modification_id = modification.data('id');
        let val = $(this).val();
        modification.find('input').remove();
        if(val === 'replace_all' || val === 'replace_part'){
            modification.append('<input type="text" class="form-control form-control-sm mb-1" name="fields['+field_id+'][modifications]['+modification_id+'][from]" placeholder="' + __('What to replace') + '" value="">' +
                '<input type="text" class="form-control form-control-sm mb-1" name="fields['+field_id+'][modifications]['+modification_id+'][to]" placeholder="' + __('Replace with') + '" value="">');
        }else if(val === 'add_prefix' || val === 'add_suffix' || val === 'add_num' || val === 'multiple'){
            modification.append('<input type="text" class="form-control form-control-sm mb-1" name="fields['+field_id+'][modifications]['+modification_id+'][value]" placeholder="' + __('Enter value') + '" value="">');
        }
    });

    $('#js_export_settings_form').on('saved', function(e, response){
        if(response.result === 'success'){
            $(this).attr('action', '/admin/products/exports/'+response.id+'/update_settings');
            $('#js_export_fields_form').attr('action', '/admin/products/exports/'+response.id+'/update_fields');
            $('#js_export_filter_form').attr('action', '/admin/products/exports/'+response.id+'/update_filter');
            $('.nav-tabs .disabled').removeClass('disabled');
        }
    });

    // Фильтр
    let filter_form = $('#js_export_filter_form');
    if(filter_form.length){
        filter_form.on('click', '#js_add_condition_group', function(){
            let form = filter_form.find('.panel-body');
            let id = form.find('.js_filters_group').length ? parseInt(form.find('.js_filters_group').last().data('group-id')) + 1 : 0;
            form.append('<div class="card form-group text-white bg-danger bg-lighten-1 text-center js_filters_group" data-group-id="'+id+'">\n' +
                '<div class="card-content">\n' +
                '<div class="card-body">' +

                '<div class="row">'+
                    (id > 0 ? '<fieldset class="col form-group mb-1 mt-1" style="min-width: 320px;">\n' +
                    '  <div class="input-group input-group-sm">\n' +
                    '    <div class="input-group-prepend">\n' +
                    '      <label class="input-group-text" for="inputGroupSelect01">' + __('Group combination method') + ':</label>\n' +
                    '    </div>\n' +
                    '    <select name="filter['+id+'][0][relations]" class="form-control form-control-sm relations" style="min-width: 70px;">\n' +
                    '      <option value="AND" selected>' + __('AND') + '</option>\n' +
                    '      <option value="OR">' + __('OR') + '</option>\n' +
                    '    </select>\n' +
                    '  </div>\n' +
                    '</fieldset>' : '') +
                    '<div class="col"></div>' +
                    '<div class="col mb-1 mt-1 d-flex flex-sm-row flex-column justify-content-end">' +
                    '<button type="button" class="btn btn-danger btn-sm text-nowrap px-1 js_remove_group">' +
                    '    <i class="bx bx-x"></i>' + __('Delete group') +
                    '</button>' +
                    '</div>\n' +
                '</div>' +

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
                '      <label class="input-group-text" for="inputGroupSelect01">' + __('Condition combination method') + ':</label>\n' +
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
            filter_form.find('.js_filters_group').first().find('.row:not(.condition-wrapper) fieldset').remove();
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
                $('.touchspin').TouchSpin({
                    buttondown_class: 'btn btn-primary',
                    buttonup_class: 'btn btn-primary',
                    max: null,
                    step: 100,
                    decimals: 2,
                    forcestepdivisibility: 'none'
                });
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
                    '=': __('Equals')
                },
                stock: {
                    '=': __('Equals')
                },
                price: {
                    '=': __('Equals'),
                    '>': __('Greater than'),
                    '<': __('Less than')
                },
                description: {
                    '=': __('Equals'),
                    '%': __('Contains')
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
                    options += '<option value="'+window.categories[cat].id+'"'+(cat?'':' selected')+'>'+(window.categories[cat].id === '' ? __('No categories') : window.categories[cat].name)+'</option>\n';
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
                    $(".touchspin").TouchSpin({
                        buttondown_class: "btn btn-primary",
                        buttonup_class: "btn btn-primary"
                    });
                }, 10);
                return '<div class="form-element col">\n' +
                    '<label class="text-right">' + __('Value') + '</label>\n' +
                    '<div class="input-group input-group-sm bootstrap-touchspin bootstrap-touchspin-injected">' +
                    '<input type="text" name="filter['+group_id+']['+id+'][value]" class="touchspin form-control form-control-sm value" data-bts-prefix="₴">' +
                    '</div>\n' +
                    '</div>\n';
            }else if(criterion === 'description'){
                return '<div class="form-element col">\n' +
                    '<label class="text-right">' + __('Value') + '</label>\n' +
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
    }
});