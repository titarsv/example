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
  /********Invoice View ********/
  // ---------------------------
  // init date picker
  if ($(".pickadate").length) {
    $(".pickadate").pickadate({
      format: "mm/dd/yyyy"
    });
  }

  /********Invoice List ********/
  // ---------------------------

  // init data table
  if ($(".categories-data-table").length) {
    let displayLength = window.getCookie('categories_display_length');

    let dataListView = $(".categories-data-table").DataTable({
      ajax: {
        url: '/admin/products/categories/list',
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
          name: 'categories.id'
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
            return data.link ? '<a href="'+ data.link +'" target="_blank">'+ data.name +'</a>' : data.name;
          }
        },
        {
          data: 'status',
          name: 'categories.status',
          render: function ( data, type, row ) {
            return '<div class="custom-switch custom-switch-success">\n' +
                '<input type="checkbox" class="custom-control-input js_change_status" data-endpoint="products/categories"\n' +
                '  name="status" value="1" id="js_category_status_'+data.id+'"\n' +
                '  data-id="'+data.id+'" autocomplete="off"'+(data.status ? ' checked' : '')+'>\n' +
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
                  html += '<a class="category-action-view mr-1" href="'+ data[i].link +'">'+ text +'</a>';
                }else if(data[i].type === 'delete'){
                  text = '<i class="bx bx-trash" data-toggle="tooltip" data-placement="bottom" data-original-title="' + __('Delete') + '"></i>';
                  html += '<span class="category-action-edit cursor-pointer js_delete_item" data-endpoint="products/categories" data-id="'+data[i].id+'" data-name="'+data[i].name+'">'+ text +'</span>';
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
          targets: [2, 4, 5],
          orderable: false
        },
        {
          width: "100%",
          targets: 3
        }
      ],
      order: [1, 'asc'],
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

    $('#js_categories_list_wrapper').on('change', '[name="DataTables_Table_0_length"]', function () {
      window.setCookie('categories_display_length', $(this).val(), 90);
    });

    dataListView.on( 'draw.dt', function(){
      $('.categories-data-table [data-toggle="tooltip"]').tooltip();
    });
  }

  // To append actions dropdown inside action-btn div
  var invoiceFilterAction = $(".invoice-filter-action");
  var invoiceOptions = $(".invoice-options");
  $(".action-btns").append(invoiceFilterAction, invoiceOptions);

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

  // ********Invoice Edit***********//
  // --------------------------------
  // form repeater jquery
  if ($(".category-item-repeater").length) {
    $(".category-item-repeater").repeater({
      show: function () {
        $(this).slideDown();
      },
      hide: function (deleteElement) {
        $(this).slideUp(deleteElement);
      }
    });
  }
  // dropdown form's prevent parent action
  $(document).on("click", ".invoice-tax", function (e) {
    e.stopPropagation();
  });
  $(document).on("click", ".invoice-apply-btn", function () {
    var $this = $(this);
    var discount = $this
      .closest(".dropdown-menu")
      .find("#discount")
      .val();
    var tax1 = $this
      .closest(".dropdown-menu")
      .find("#Tax1 option:selected")
      .text();
    var tax2 = $this
      .closest(".dropdown-menu")
      .find("#Tax2 option:selected")
      .text();
    $this
      .parents()
      .eq(4)
      .find(".discount-value")
      .html(discount + "%");
    $this
      .parents()
      .eq(4)
      .find(".tax1")
      .html(tax1);
    $this
      .parents()
      .eq(4)
      .find(".tax2")
      .html(tax2);
  });
  // // on product change also change product description
  $(document).on("change", ".invoice-item-select", function (e) {
    var selectOption = this.options[e.target.selectedIndex].text;
    // switch case for product select change also change product description
    switch (selectOption) {
      case "Frest Admin Template":
        $(e.target)
          .closest(".invoice-item-filed")
          .find(".invoice-item-desc")
          .val("The most developer friendly & highly customisable HTML5 Admin");
        break;
      case "Stack Admin Template":
        $(e.target)
          .closest(".invoice-item-filed")
          .find(".invoice-item-desc")
          .val("Ultimate Bootstrap 4 Admin Template for Next Generation Applications.");
        break;
      case "Robust Admin Template":
        $(e.target)
          .closest(".invoice-item-filed")
          .find(".invoice-item-desc")
          .val(
            "Robust admin is super flexible, powerful, clean & modern responsive bootstrap admin template with unlimited possibilities"
          );
        break;
      case "Apex Admin Template":
        $(e.target)
          .closest(".invoice-item-filed")
          .find(".invoice-item-desc")
          .val("Developer friendly and highly customizable Angular 7+ jQuery Free Bootstrap 4 gradient ui admin template. ");
        break;
      case "Modern Admin Template":
        $(e.target)
          .closest(".invoice-item-filed")
          .find(".invoice-item-desc")
          .val("The most complete & feature packed bootstrap 4 admin template of 2019!");
        break;
    }
  });
  // print button
  if ($(".invoice-print").length > 0) {
    $(".invoice-print").on("click", function () {
      window.print();
    })
  }

  $('#js_add_category').on('click', function () {
    Swal.fire({
      title: __('Enter category name'),
      input: 'text',
      confirmButtonClass: 'btn btn-primary',
      buttonsStyling: false,
      inputAttributes: {
        autocapitalize: 'off'
      },
      showCancelButton: true,
      confirmButtonText: __('Create'),
      cancelButtonText: __('Cancel'),
      showLoaderOnConfirm: true,
      cancelButtonClass: "btn btn-danger ml-1",
      preConfirm: function (name) {
        return fetch("/admin/products/categories/create", {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
          },
          body: JSON.stringify({name: name}),
          credentials: 'same-origin'
        })
            .then(function (response) {
              if (!response.ok) {
                throw new Error(response.statusText)
              }
              return response.json()
            })
            .catch(function (error) {
              Swal.showValidationMessage(
                  error
              )
            })
      },
      allowOutsideClick: function () {
        !Swal.isLoading()
      }
    }).then(function(response) {
      if(typeof response.value !== 'undefined'){
          if(response.value.result === 'success'){
              location = response.value.redirect;
          }else{
              let errors = response.value.errors;
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
          }
      }
    });
  });
});
