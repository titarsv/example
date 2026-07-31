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
  /********Order View ********/
  // ---------------------------
  // init date picker
  if ($(".pickadate").length) {
    $(".pickadate").pickadate({
      format: "mm/dd/yyyy"
    });
  }

  /********Orders List ********/
  // ---------------------------

  // init data table
  if ($(".orders-data-table").length) {
    let displayLength = window.getCookie('orders_display_length');

    let dataListView = $(".orders-data-table").DataTable({
      ajax: {
        url: '/admin/orders/list',
        type: "POST"
      },
      pageLength: displayLength == null ? 25 : displayLength,
      lengthMenu: [[25, 50, -1], [25, 50, __("All")]],
      columns: [
        {
          data: 'id',
          name: 'orders.id'
        },
        {
          data: 'products',
          render: function ( data, type, row ) {
            let html = '<ul class="list-unstyled users-list m-0  d-flex align-items-center">';
            for(let i in data){
              html += '<li data-toggle="tooltip" data-popup="tooltip-custom" data-placement="bottom" title="" class="avatar pull-up" data-original-title="'+data[i].name+'">\n' +
                  (data[i].link ? '<a href="'+data[i].link+'" target="_blank">\n' : '') +
                  '<img src="'+data[i].image+'" alt="'+data[i].name+'" width="30" height="30">\n' +
                  (data[i].link ? '</a>\n' : '') +
                '</li>\n';
            }
            html += '</ul>';

            return html;
          }
        },
        {
          data: 'status',
          name: 'orders.status',
          render: function ( data, type, row ) {
            return '<div class="badge badge-pill badge-glow badge-'+data.class+'">'+data.status+'</div>';
          }
        },
        {
            data: 'name',
            name: 'orders.name'
        },
        {
            data: 'tracking'
        },
        {
            data: 'payment_status',
            name: 'orders.payment_status',
            render: function ( data, type, row ) {
                return '<div class="badge badge-pill badge-glow badge-'+data.class+'">'+data.status+'</div>';
            }
        },
        {
            data: 'price',
            name: 'orders.total_price'
        },
        {
            data: 'date',
            name: 'orders.created_at'
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
                  html += '<a class="category-action-view mr-1" href="'+ data[i].link +'">'+ text +'</a>';
                }else if(data[i].type === 'delete'){
                  text = '<i class="bx bx-trash" data-toggle="tooltip" data-placement="bottom" data-original-title="' + __('Delete') + '"></i>';
                  html += '<span class="category-action-edit cursor-pointer js_delete_item" data-endpoint="orders" data-id="'+data[i].id+'" data-name="'+data[i].name+'">'+ text +'</span>';
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
          targets: [1, 2, 3, 4, 7],
          orderable: false
        }
      ],
      order: [0, 'desc'],
      language: window.localization.datatable,
      select: {
        style: "multi",
        selector: "td:first-child",
        items: "row"
      },
        orderCellsTop: true,
        fixedHeader: true,
        initComplete: function () {
            var api = this.api();

            // For each column
            api
                .columns()
                .eq(0)
                .each(function (colIdx) {
                    // Set the header cell to contain the input element
                    var cell = $('.filters th').eq(
                        $(api.column(colIdx).header()).index()
                    );

                    var title = $(cell).text();
                    // $(cell).html('<input type="text" class="form-control" placeholder="' + title + '" />');

                    // On every keypress in this input
                    $(
                        'input',
                        $('.filters th').eq($(api.column(colIdx).header()).index())
                    )
                        .off('keyup change')
                        .on('keyup change', function (e) {
                            e.stopPropagation();

                            // Get the search value
                            $(this).attr('title', $(this).val());
                            var regexr = '{search}';

                            var cursorPosition = this.selectionStart;
                            // Search the column for that value
                            api
                                .column(colIdx)
                                .search(
                                    this.value != ''
                                        ? regexr.replace('{search}', '' + this.value + '')
                                        : '',
                                    this.value != '',
                                    this.value == ''
                                )
                                .draw();

                            $(this)
                                .focus()[0]
                                .setSelectionRange(cursorPosition, cursorPosition);
                        });
                });
        },
    });

    $('#js_orders_list_wrapper').on('change', '[name="DataTables_Table_0_length"]', function () {
      window.setCookie('orders_display_length', $(this).val(), 90);
    });

    dataListView.on( 'draw.dt', function(){
      $('.orders-data-table [data-toggle="tooltip"]').tooltip();
    });

    dataListView.on('responsive-resize', function(e, r, c){
        if(c[1]){
            $(".orders-data-table .filters").show();
        }else{
            $(".orders-data-table .filters").hide();
        }
        for(let i in c){
            let col = $(".orders-data-table .filters th").eq(i);
            if(c[i]){
                col.show();
            }else{
                col.hide();
            }
        }
    });
  }

  // add class in row if checkbox checked
  $(".dt-checkboxes-cell")
    .find("input")
    .on("change", function () {
      var $this = $(this);
      if ($this.is(":checked")) {
        $this.closest("tr").addClass("selected-row-bg");
      } else {
        $this.closest("tr").removeClass("selected-row-bg");
      }
    });
  // Select all checkbox
  $(document).on("change", ".dt-checkboxes-select-all input", function () {
    if ($(this).is(":checked")) {
      $(".dt-checkboxes-cell")
        .find("input")
        .prop("checked", this.checked)
        .closest("tr")
        .addClass("selected-row-bg");
    } else {
      $(".dt-checkboxes-cell")
        .find("input")
        .prop("checked", "")
        .closest("tr")
        .removeClass("selected-row-bg");
    }
  });

  // ********Order Edit***********//
  // --------------------------------
  // form repeater jquery
  if ($(".order-item-repeater").length) {
    $(".order-item-repeater").repeater({
      show: function () {
        $(this).slideDown();
      },
      hide: function (deleteElement) {
        $(this).slideUp(deleteElement);
      }
    });
  }

  // $('#js_add_order').on('click', function () {
  //   Swal.fire({
  //     title: 'Введите название категории',
  //     input: 'text',
  //     confirmButtonClass: 'btn btn-primary',
  //     buttonsStyling: false,
  //     inputAttributes: {
  //       autocapitalize: 'off'
  //     },
  //     showCancelButton: true,
  //     confirmButtonText: 'Создать',
  //     cancelButtonText: 'Отмена',
  //     showLoaderOnConfirm: true,
  //     cancelButtonClass: "btn btn-danger ml-1",
  //     preConfirm: function (name) {
  //       return fetch("/admin/products/categories/create", {
  //         method: 'POST',
  //         headers: {
  //           'Content-Type': 'application/json',
  //           'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
  //         },
  //         body: JSON.stringify({name: name}),
  //         credentials: 'same-origin'
  //       })
  //           .then(function (response) {
  //             if (!response.ok) {
  //               throw new Error(response.statusText)
  //             }
  //             return response.json()
  //           })
  //           .catch(function (error) {
  //             Swal.showValidationMessage(
  //                 error
  //             )
  //           })
  //     },
  //     allowOutsideClick: function () {
  //       !Swal.isLoading()
  //     }
  //   }).then(function(response) {
  //     if(response.value.result === 'success'){
  //       location = response.value.redirect;
  //     }else{
  //       let errors = response.value.errors;
  //       if(typeof errors !== 'string'){
  //         var message = '';
  //         for(err in errors){
  //           message += errors[err] + '<br>';
  //         }
  //         swal(
  //             'Ошибка!',
  //             message,
  //             'error'
  //         );
  //       }
  //     }
  //   });
  // });
});
