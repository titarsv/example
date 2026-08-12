$(document).ready(function(){
    let page_imports_data_table = $(".page-imports-data-table");
    if(page_imports_data_table.length) {
        let displayLength = window.getCookie('page_imports_display_length');

        let dataListView = page_imports_data_table.DataTable({
            ajax: {
                url: '/admin/page_imports/list',
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
                    data: 'status',
                    name: 'status',
                    render: function ( data, type, row ) {
                        return data.status;
                    }
                },
                {
                    data: 'created_at',
                    name: 'created_at',
                    render: function ( data, type, row ) {
                        return data.created_at;
                    }
                },
                {
                    data: 'actions',
                    render: function ( data, type, row ) {
                        let html = '';

                        for(let i in data){
                            let text = '';

                            if(typeof data[i].type !== 'undefined'){
                                if(data[i].type === 'delete'){
                                    text = '<i class="bx bx-trash" data-toggle="tooltip" data-placement="bottom" data-original-title="' + __('Delete') + '"></i>';
                                    html += '<span class="cursor-pointer js_delete_item" data-endpoint="page_imports" data-id="'+data[i].id+'" data-name="'+data[i].name+'">'+ text +'</span>';
                                }else if(data[i].type === 'review'){
                                    text = '<i class="bx bx-list-check" data-toggle="tooltip" data-placement="bottom" data-original-title="' + __('Review') + '"></i>';
                                    html += '<a class="mr-1" href="'+ data[i].link +'">'+ text +'</a>';
                                }
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
                    targets: [0, 4],
                    orderable: false
                },
                { responsivePriority: 2, targets: 4 },
                { responsivePriority: 3, targets: 2 },
                { responsivePriority: 5, targets: 3 },
            ],
            order: [3, 'desc'],
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

        $('#js_page_imports_list_wrapper').on('change', '[name="DataTables_Table_0_length"]', function () {
            window.setCookie('page_imports_display_length', $(this).val(), 90);
        });

        dataListView.on('draw.dt', function(){
            $('.page-imports-data-table [data-toggle="tooltip"]').tooltip();
        });

        $('#js_add_page_import').click(function(){
            swal({
                title: __('Upload page archive'),
                html:
                    '<div class="swal2-content">' +
                    '<div class="form-group">' +
                        '<div class="custom-file mt-1" lang="ru">\n' +
                        '  <input name="archive" type="file" accept=".zip" aria-label="' + __('Archive file (.zip)') + '" class="custom-file-input" id="page_import_archive">\n' +
                        '  <label class="custom-file-label" for="page_import_archive">' + __('Archive file (.zip)') + '</label>\n' +
                        '</div>' +
                    '</div>' +
                    '</div>',
                focusConfirm: false,
                preConfirm: () => {
                    return new Promise((resolve, reject) => {
                        let archive = $('#page_import_archive').get(0);

                        if(!archive.files[0]){
                            reject([__('No file selected')]);
                            return;
                        }

                        let formData = new FormData();
                        formData.append('archive', archive.files[0]);

                        $.ajax({
                            type: "POST",
                            url: "/admin/page_imports/upload",
                            data: formData,
                            processData: false,
                            contentType: false,
                            async: true,
                            success: function(response){
                                if(response.result === 'success'){
                                    resolve(response);
                                }else{
                                    reject(response.errors);
                                }
                            }
                        });
                    });
                }
            }).then(function(result){
                if(typeof result.value !== 'undefined'){
                    dataListView.ajax.reload();
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
    }
});