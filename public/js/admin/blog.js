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
  /********Blog List ********/
  // ---------------------------

  // init data table
  if ($(".blog-data-table").length) {
    let displayLength = window.getCookie('blog_display_length');

    let dataListView = $(".blog-data-table").DataTable({
      ajax: {
        url: '/admin/articles/list',
        type: "POST"
      },
      pageLength: displayLength == null ? 25 : displayLength,
      lengthMenu: [[25, 50, -1], [25, 50, __('All')]],
      columns: [
        {
          data: 'id',
          name: 'blog.id'
        },
        {
          data: 'name',
          name: 'localization.value',
          render: function ( data, type, row ) {
            return '<a href="'+ data.link +'">'+ data.name +'</a>';
          }
        },
        {
          data: 'image',
          render: function ( data, type, row ) {
            return data ? '<img class="rounded-circle" src="'+data+'" alt="" width="32" height="32">' : '';
          }
        },
        {
          data: 'status',
          name: 'blog.status',
          render: function ( data, type, row ) {
            return '<div class="custom-switch custom-switch-success">\n' +
              '<input type="checkbox" class="custom-control-input js_change_status" data-endpoint="articles"\n' +
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
                  html += '<span class="category-action-edit cursor-pointer js_delete_item" data-endpoint="articles" data-id="'+data[i].id+'" data-name="'+data[i].name+'">'+ text +'</span>';
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
          targets: [2, 3, 4],
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
      fixedHeader: true
    });

    $('#js_blog_list_wrapper').on('change', '[name="DataTables_Table_0_length"]', function () {
      window.setCookie('blog_display_length', $(this).val(), 90);
    });

    dataListView.on( 'draw.dt', function(){
      $('.blog-data-table [data-toggle="tooltip"]').tooltip();
    });
  }

  $('#js_add_article').on('click', function () {
      Swal.fire({
          title: __('Enter article title'),
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
              return fetch("/admin/articles/create", {
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
