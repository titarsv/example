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
  if ($(".redirects-data-table").length) {
    let displayLength = window.getCookie('redirects_display_length');

    let dataListView = $(".redirects-data-table").DataTable({
      ajax: {
        url: '/admin/promotion/redirects/list',
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
          data: 'from',
          name: 'old_url'
        },
        {
          data: 'to',
          name: 'new_url'
        },
        {
          data: 'status',
          name: 'status'
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
                  html += '<a class="redirect-action-view mr-1" href="'+ data[i].link +'">'+ text +'</a>';
                }else if(data[i].type === 'delete'){
                  text = '<i class="bx bx-trash" data-toggle="tooltip" data-placement="bottom" data-original-title="' + __('Delete') + '"></i>';
                  html += '<span class="redirect-action-edit cursor-pointer js_delete_item" data-endpoint="promotion/redirects" data-id="'+data[i].id+'" data-name="'+data[i].from+'">'+ text +'</span>';
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
          targets: [1, 4, 5],
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

    $('#js_redirects_list_wrapper').on('change', '[name="DataTables_Table_0_length"]', function () {
      window.setCookie('redirects_display_length', $(this).val(), 90);
    });

    dataListView.on('draw.dt', function(){
      $('.redirects-data-table [data-toggle="tooltip"]').tooltip();
    });
  }

  $('#js_add_redirect').on('click', function () {
    let host = $(this).data('host');
    Swal.fire({
      title: __('Add Redirect'),
      html:
        '<div>\n' +
          '<label>' + __('Source') + '</label>\n' +
          '<div class="form-group">\n' +
            '<fieldset>\n' +
            '<div class="input-group input-group-sm">\n' +
              '<div class="input-group-prepend">\n' +
                '<span class="input-group-text">'+host+'</span>\n' +
              '</div>\n' +
              '<input type="text" class="form-control form-control-sm" id="old_url" name="old_url" autocomplete="off" value="">\n' +
            '</div>\n' +
            '</fieldset>\n' +
            '<div class="help-block"></div>\n' +
          '</div>\n' +
        '</div>'+
        '<div>\n' +
          '<div class="form-group">\n' +
            '<label>' + __('Destination') + '</label>\n' +
            '<div class="form-group">\n' +
              '<fieldset>\n' +
                '<div class="input-group input-group-sm">\n' +
                  '<div class="input-group-prepend">\n' +
                    '<span class="input-group-text">'+host+'</span>\n' +
                  '</div>\n' +
                  '<input type="text" class="form-control form-control-sm" id="new_url" name="new_url" autocomplete="off" value="">\n' +
                '</div>\n' +
              '</fieldset>\n' +
            '<div class="help-block"></div>\n' +
          '</div>\n' +
        '</div>',
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
        return fetch("/admin/promotion/redirects/create", {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
          },
          body: JSON.stringify({old_url: $('#old_url').val(), new_url: $('#new_url').val()}),
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
    });
  });
});