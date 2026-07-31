$(document).ready(function(){
    // init data table
    let imports_data_table = $(".imports-data-table");
    if(imports_data_table.length) {
        let displayLength = window.getCookie('imports_display_length');

        let dataListView = imports_data_table.DataTable({
            ajax: {
                url: '/admin/products/imports/list',
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
                                    html += '<span class="cursor-pointer js_delete_item" data-endpoint="products/imports" data-id="'+data[i].id+'" data-name="'+data[i].name+'">'+ text +'</span>';
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
                    targets: [0, 3, 4, 5],
                    orderable: false
                },
                { responsivePriority: 2, targets: 5 },
                { responsivePriority: 3, targets: 2 },
                { responsivePriority: 5, targets: 3 },
                { responsivePriority: 6, targets: 4 },
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

        $('#js_imports_list_wrapper').on('change', '[name="DataTables_Table_0_length"]', function () {
            window.setCookie('imports_display_length', $(this).val(), 90);
        });

        dataListView.on('draw.dt', function(){
            $('.imports-data-table [data-toggle="tooltip"]').tooltip();
        });

        $('#js_add_import').click(function(){
            swal({
                title: __('New Import'),
                html:
                    '<div class="swal2-content">' +
                    '<div class="form-group">' +
                        '<div class="custom-file mt-1" lang="ru">\n' +
                        '  <input name="import_file" type="file" accept=".xlsx,.xls,.csv" aria-label="' + __('Import File') + '" class="custom-file-input" id="import_file">\n' +
                        '  <label class="custom-file-label" for="import_file">' + __('Data File for Import') + '</label>\n' +
                        '</div>' +
                    '</div>' +
                    '<div class="form-group">' +
                        '<div class="custom-file mt-1" lang="ru">\n' +
                        '  <input name="attachments" type="file" accept=".zip" aria-label="' + __('Attachments') + '" class="custom-file-input" id="attachments">\n' +
                        '  <label class="custom-file-label" for="attachments">' + __('Photo Archive') + '</label>\n' +
                        '</div>' +
                    '</div>' +
                    '</div>',
                focusConfirm: false,
                preConfirm: () => {
                    return new Promise((resolve, reject) => {
                        let formData = new FormData();
                        let import_file = $('#import_file').get(0);
                        formData.append('import_file', import_file.files[0]);
                        let attachments = $('#attachments').get(0);
                        formData.append('attachments', attachments.files[0]);
                        $.ajax({
                            type:"POST",
                            url:"/admin/products/imports/upload",
                            data: formData,
                            processData: false,
                            contentType: false,
                            async:true,
                            success: function(response){
                                if(response.result === 'success'){
                                    resolve(response.redirect);
                                }else{
                                    reject(response.errors);
                                }
                            }
                        });
                    });
                }
            }).then(function(redirect){
                if(typeof redirect.value !== 'undefined'){
                    location = redirect.value;
                }
            }, function(errors){
                if(typeof errors !== 'string'){
                    var message = '';
                    for(err in errors){
                        message += errors[err] + '<br>';
                    }
                    swal(
                        __('Error!'),
                        message,
                        'error'
                    );
                }
            });
        });
    }else{
        $(document).on('change', '.import-field-type', function(){
            let $this = $(this);
            let container = $this.next();
            let id = container.data('id');
            let with_attachments = $this.parents('form').hasClass('with_attachments');
            if($this.val() === 'product.file_id'){
                container.html('<label>' + __('Content Type') + ':</label>' +
                    '<select class="form-control form-control-sm" name="fields['+id+'][format]">' +
                    '<option value="media.name">' + __('File name from site media library') + '</option>' +
                    (with_attachments ? '<option value="archive.name">' + __('File name from archive') + '</option>' : '') +
                    '<option value="link">' + __('File link') + '</option>' +
                    '<option value="media.id">' + __('File ID from site media library') + '</option>' +
                    '</select>' +
                    '<label>' + __('If file not found') + ':</label>' +
                    '<select class="form-control form-control-sm" name="fields['+id+'][not_found]">' +
                    '<option value="stop">' + __('Stop import') + '</option>' +
                    '<option value="skip">' + __('Skip product import') + '</option>' +
                    '<option value="ignore">' + __('Import without image') + '</option>' +
                    '<option value="remain">' + __('Keep old image (when updating)') + '</option>' +
                    '</select>');
            }else if($this.val() === 'galleries.file_id'){
                container.html('<label>' + __('Content Type') + ':</label>' +
                    '<select class="form-control form-control-sm" name="fields['+id+'][format]">' +
                    '<option value="media.name">' + __('File name from site media library') + '</option>' +
                    (with_attachments ? '<option value="archive.name">' + __('File name from archive') + '</option>' : '') +
                    '<option value="link">' + __('File link') + '</option>' +
                    '<option value="media.id">' + __('File ID from site media library') + '</option>' +
                    '</select>' +
                    '<label>' + __('Separator') + ':</label>' +
                    '<input class="form-control form-control-sm" type="text" name="fields['+id+'][separator]">' +
                    '<label>' + __('If file not found') + ':</label>' +
                    '<select class="form-control form-control-sm" name="fields['+id+'][not_found]">' +
                    '<option value="stop">' + __('Stop import') + '</option>' +
                    '<option value="skip">' + __('Skip product import') + '</option>' +
                    '<option value="ignore">' + __('Import without image') + '</option>' +
                    '<option value="remain">' + __('Keep old image (when updating)') + '</option>' +
                    '</select>');
            }else if($this.val() === 'category.id'){
                container.html('<label>' + __('Separator between categories') + ':</label>' +
                    '<input class="form-control form-control-sm" type="text" name="fields['+id+'][separator]">' +
                    '<label>' + __('Category hierarchy separator') + ':</label>' +
                    '<input class="form-control form-control-sm" type="text" name="fields['+id+'][tree_separator]">' +
                    '<label>' + __('If category not found') + ':</label>' +
                    '<select class="form-control form-control-sm" name="fields['+id+'][not_found]">' +
                    '<option value="stop">' + __('Stop import') + '</option>' +
                    '<option value="skip">' + __('Skip product import') + '</option>' +
                    '<option value="ignore">' + __('Import without category') + '</option>' +
                    '<option value="remain">' + __('Keep old categories (when updating)') + '</option>' +
                    '<option value="create">' + __('Create new category') + '</option>' +
                    '</select>');
            }else if($this.val() === 'attribute_values.id'){
                container.html('<label>' + __('Content Type') + ':</label>' +
                    '<select class="form-control form-control-sm attributes-format" name="fields['+id+'][format]">' +
                    '<option value="values">' + __('Options of one attribute') + '</option>' +
                    '<option value="attributes_and_values">' + __('Attribute names and their options') + '</option>' +
                    '</select>' +
                    '<div class="attribute-fields">' +
                    '<label>' + __('Attribute name') + ':</label>' +
                    '<input class="form-control form-control-sm" type="text" name="fields['+id+'][attribute]">' +
                    '</div>' +
                    '<label>' + __('Separator between attribute options') + ':</label>' +
                    '<input class="form-control form-control-sm" type="text" name="fields['+id+'][separator]">' +
                    '<label>' + __('If attribute not found') + ':</label>' +
                    '<select class="form-control form-control-sm" name="fields['+id+'][not_found]">' +
                    '<option value="stop">' + __('Stop import') + '</option>' +
                    '<option value="skip">' + __('Skip product import') + '</option>' +
                    '<option value="ignore">' + __('Import without attribute') + '</option>' +
                    '<option value="remain">' + __('Keep old attributes (when updating)') + '</option>' +
                    '<option value="create">' + __('Create new attribute') + '</option>' +
                    '</select>');
            }else{
                container.html('');
            }
        });
        $(document).on('change', '.attributes-format', function(){
            let $this = $(this);
            let container = $this.next();
            let id = $this.parent().data('id');
            if($this.val() === 'attributes_and_values'){
                container.html('<label>' + __('Separator between attributes') + ':</label>' +
                    '<input class="form-control form-control-sm" type="text" name="fields['+id+'][attributes_separator]">' +
                    '<label>' + __('Separator between attribute name and its values') + ':</label>' +
                    '<input class="form-control form-control-sm" type="text" name="fields['+id+'][attribute_values_separator]">');
            }else if($this.val() === 'values'){
                container.html('<label>' + __('Attribute name') + ':</label>' +
                    '<input class="form-control form-control-sm" type="text" name="fields['+id+'][attribute]">');
            }
        });
        $('#js_start_import').click(function(){
            $('#import_process').removeClass('hidden');
            nextImportStep($(this).data('id'));
        });
        $('#js_refresh_import').click(function(){
            $('#import_process').removeClass('hidden');
            let id = $(this).data('id');
            $.post('/admin/products/imports/refresh_import/'+id, {}, function () {
                nextImportStep(id);
            })
        });
        function nextImportStep(id){
            $.post('/admin/products/imports/next_import_step/'+id, {}, function(response){
                $('.progress-bar').css('width', response.progress+'%').attr('aria-valuenow', response.progress+'%');
                $('.alert-success, .alert-warning, .alert-danger').html('');
                $('.progress').removeClass('progress-bar-primary').removeClass('progress-bar-warning').removeClass('progress-bar-danger');
                if(response.statistic.not_imported == 0){
                    $('.alert-success').html(__('Imported <b>:imported</b> of <b>:total</b> products!', {
                        imported: response.statistic.imported,
                        total: response.total
                    })).removeClass('hidden');
                    $('.progress').addClass('progress-bar-success');
                }else{
                    $('.alert-warning').html(__('Imported <b>:imported</b> of <b>:total</b> products, <b>:not_imported</b> products were not imported due to errors!', {
                        imported: response.statistic.imported,
                        total: response.total,
                        not_imported: response.statistic.not_imported
                    })).removeClass('hidden');
                    $('.alert-success').addClass('hidden');
                }

                if(response.statistic.warnings[0]){
                    let html = '';
                    for(let i in response.statistic.warnings){
                        html += '<p>'+response.statistic.warnings[i]+'</p>';
                    }
                    $('.alert-warning').append(html).removeClass('hidden');
                    $('.progress').addClass('progress-bar-warning');
                }

                if(response.statistic.errors[0]){
                    let html = '';
                    for(var i in response.statistic.errors){
                        html += '<p>'+response.statistic.errors[i]+'</p>';
                    }
                    $('.alert-danger').html(html).removeClass('hidden');
                    $('.progress').addClass('progress-bar-danger');
                }
                if(response.progress < 100){
                    nextImportStep(id);
                }
            });
        }
    }
});