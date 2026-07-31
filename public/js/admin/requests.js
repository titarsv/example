/*=========================================================================================
    File Name: app-invoice.js
    Description: app-invoice Javascripts
    ----------------------------------------------------------------------------------------
    Item Name: Frest HTML Admin Template
   Version: 1.0
    Author: PIXINVENT
    Author URL: http://www.themeforest.net/user/pixinvent
==========================================================================================*/
$(document).ready(function () {
    /********Redirects List ********/
    // ---------------------------

    // init data table
    if ($(".requests-data-table").length) {
        let displayLength = window.getCookie('requests_display_length');

        let dataListView = $(".requests-data-table").DataTable({
            ajax: {
                url: '/admin/requests/list',
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
                    data: 'id',
                    name: 'id'
                },
                {
                    data: 'form',
                    name: 'form'
                },
                {
                    data: 'name',
                    name: 'name'
                },
                {
                    data: 'email',
                    name: 'email'
                },
                {
                    data: 'comment',
                    name: 'comment'
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
                                    html += '<span class="review-action-edit cursor-pointer js_delete_item" data-endpoint="requests" data-id="'+data[i].id+'" data-name="'+data[i].name+'">'+ text +'</span>';
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
                    targets: [1, 2],
                    orderable: false
                }
            ],
            order: [0, 'asc'],
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

        $('#js_requests_list_wrapper').on('change', '[name="DataTables_Table_0_length"]', function () {
            window.setCookie('requests_display_length', $(this).val(), 90);
        });

        dataListView.on('draw.dt', function(){
            $('.requests-data-table [data-toggle="tooltip"]').tooltip();
        });
    }
});
